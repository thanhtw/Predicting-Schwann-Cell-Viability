#!/bin/bash
# PSC Viability Prediction - Docker Deployment Script
# Bash Script for Linux/Mac

set -e

# Ubuntu's current Docker packages provide Compose v2 as `docker compose`.
# Fall back to the legacy standalone command when necessary.
if docker compose version > /dev/null 2>&1; then
    COMPOSE=(docker compose)
elif command -v docker-compose > /dev/null 2>&1; then
    COMPOSE=(docker-compose)
else
    echo "Error: Docker Compose is not installed."
    exit 1
fi

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}PSC Viability Prediction - Docker Deploy${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""

# Parse arguments
BUILD=false
FRESH=false
STOP=false
LOGS=false
RESTART=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --build)
            BUILD=true
            shift
            ;;
        --fresh)
            FRESH=true
            shift
            ;;
        --stop)
            STOP=true
            shift
            ;;
        --logs)
            LOGS=true
            shift
            ;;
        --restart)
            RESTART=true
            shift
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            exit 1
            ;;
    esac
done

# Check if Docker is running
if ! docker ps > /dev/null 2>&1; then
    echo -e "${RED}Error: Docker is not running. Please start Docker.${NC}"
    exit 1
fi

# Stop containers
if [ "$STOP" = true ]; then
    echo -e "${YELLOW}Stopping all containers...${NC}"
    "${COMPOSE[@]}" down
    echo -e "${GREEN}Containers stopped successfully!${NC}"
    exit 0
fi

# View logs
if [ "$LOGS" = true ]; then
    echo -e "${YELLOW}Showing logs (Ctrl+C to exit)...${NC}"
    "${COMPOSE[@]}" logs -f
    exit 0
fi

# Restart containers
if [ "$RESTART" = true ]; then
    echo -e "${YELLOW}Restarting containers...${NC}"
    "${COMPOSE[@]}" restart
    echo -e "${GREEN}Containers restarted successfully!${NC}"
    exit 0
fi

# Create .env.docker from example if not exists
if [ ! -f ".env.docker" ]; then
    echo -e "${YELLOW}Creating .env.docker from .env.docker.example...${NC}"
    if [ -f ".env.docker.example" ]; then
        cp .env.docker.example .env.docker
        echo -e "${GREEN}.env.docker created successfully!${NC}"
    else
        echo -e "${RED}Error: .env.docker.example not found!${NC}"
        exit 1
    fi
fi

# Copy environment file to WebApp if not exists
if [ ! -f "WebApp/.env" ]; then
    echo -e "${YELLOW}Copying .env.docker to WebApp/.env...${NC}"
    cp .env.docker WebApp/.env
fi

# A fresh deployment must clean before building. The explicit container names
# may belong to an older checkout with a different Compose project name, in
# which case `compose down` alone cannot find them.
if [ "$FRESH" = true ]; then
    echo -e "${YELLOW}Removing old project containers, images, and volumes...${NC}"

    stale_containers=(laravel-webapp predict-service WebApp-db)
    stale_projects=()
    stale_project_containers=()
    stale_images=()
    stale_volumes=()

    for container in "${stale_containers[@]}"; do
        if docker container inspect "$container" > /dev/null 2>&1; then
            project="$(docker container inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' "$container")"
            [ -n "$project" ] && [ "$project" != "<no value>" ] && stale_projects+=("$project")
        fi
    done

    # Include unnamed containers such as nginx from every discovered old
    # Compose project, not just the three services with fixed names.
    for project in "${stale_projects[@]}"; do
        while IFS= read -r container_id; do
            [ -z "$container_id" ] && continue
            stale_project_containers+=("$container_id")

            service="$(docker container inspect --format '{{index .Config.Labels "com.docker.compose.service"}}' "$container_id")"
            if [ "$service" = "laravel-webapp" ] || [ "$service" = "predict-service" ]; then
                stale_images+=("$(docker container inspect --format '{{.Image}}' "$container_id")")
            fi

            while IFS= read -r volume; do
                [ -n "$volume" ] && stale_volumes+=("$volume")
            done < <(docker container inspect --format '{{range .Mounts}}{{if eq .Type "volume"}}{{println .Name}}{{end}}{{end}}' "$container_id")
        done < <(docker container ls --all --quiet --filter "label=com.docker.compose.project=$project")
    done

    # Clean resources registered to the current checkout first.
    "${COMPOSE[@]}" down --volumes --remove-orphans --rmi local

    for container_id in "${stale_project_containers[@]}"; do
        docker container rm --force "$container_id" > /dev/null 2>&1 || true
    done

    # Then clean fixed-name containers left by older checkout/project names.
    for container in "${stale_containers[@]}"; do
        if docker container inspect "$container" > /dev/null 2>&1; then
            echo -e "${YELLOW}Removing stale container: ${container}${NC}"
            docker container rm --force "$container"
        fi
    done

    for volume in "${stale_volumes[@]}"; do
        docker volume rm "$volume" > /dev/null 2>&1 || true
    done

    for image in "${stale_images[@]}"; do
        docker image rm "$image" > /dev/null 2>&1 || true
    done

    echo -e "${GREEN}Old project resources removed successfully!${NC}"
fi

# Build containers
if [ "$BUILD" = true ] || [ "$FRESH" = true ]; then
    echo -e "${YELLOW}Building Docker images...${NC}"
    # Build sequentially. In a parallel build, one service failure is often
    # shown only as "context canceled" on an unrelated service.
    "${COMPOSE[@]}" build --no-cache laravel-webapp
    "${COMPOSE[@]}" build --no-cache predict-service
fi

# Start containers
echo -e "${YELLOW}Starting Docker containers...${NC}"
"${COMPOSE[@]}" up -d

# Wait for MySQL to be ready
echo -e "${YELLOW}Waiting for MySQL to be ready...${NC}"
max_retries=30
retries=0
while [ $retries -lt $max_retries ]; do
    # Use the password configured in the container and avoid exposing it in
    # the process arguments or printing mysqladmin's password warning.
    if "${COMPOSE[@]}" exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqladmin ping --host=localhost --user=root --silent' > /dev/null 2>&1; then
        break
    fi
    retries=$((retries + 1))
    echo -n "."
    sleep 2
done
echo ""

if [ $retries -eq $max_retries ]; then
    echo -e "${RED}MySQL failed to start in time!${NC}"
    exit 1
fi

echo -e "${GREEN}MySQL is ready!${NC}"

# Run migrations and seeders
if [ "$FRESH" = true ]; then
    echo -e "${YELLOW}Running database migrations...${NC}"
    "${COMPOSE[@]}" exec laravel-webapp php artisan migrate:fresh --force
    
    echo -e "${YELLOW}Seeding database...${NC}"
    "${COMPOSE[@]}" exec laravel-webapp php artisan db:seed --force
    
    echo -e "${YELLOW}Creating storage link...${NC}"
    "${COMPOSE[@]}" exec laravel-webapp php artisan storage:link
    
    echo -e "${YELLOW}Clearing caches...${NC}"
    "${COMPOSE[@]}" exec laravel-webapp php artisan config:clear
    "${COMPOSE[@]}" exec laravel-webapp php artisan cache:clear
    "${COMPOSE[@]}" exec laravel-webapp php artisan view:clear
else
    echo -e "${YELLOW}Running database migrations...${NC}"
    "${COMPOSE[@]}" exec laravel-webapp php artisan migrate --force
fi

echo ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}Deployment Complete!${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo -e "${CYAN}Access the application at: http://localhost:52025${NC}"
echo ""
echo -e "${YELLOW}Default admin credentials:${NC}"
echo "  Email: admin@example.com"
echo "  Password: password"
echo ""
echo -e "${YELLOW}Useful commands:${NC}"
echo "  View logs:         ./deploy.sh --logs"
echo "  Stop containers:   ./deploy.sh --stop"
echo "  Restart:           ./deploy.sh --restart"
echo "  Fresh install:     ./deploy.sh --fresh"
echo "  Rebuild images:    ./deploy.sh --build"
echo ""
