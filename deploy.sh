#!/bin/bash
# PSC Viability Prediction - Docker Deployment Script
# Bash Script for Linux/Mac

set -e

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
    docker-compose down
    echo -e "${GREEN}Containers stopped successfully!${NC}"
    exit 0
fi

# View logs
if [ "$LOGS" = true ]; then
    echo -e "${YELLOW}Showing logs (Ctrl+C to exit)...${NC}"
    docker-compose logs -f
    exit 0
fi

# Restart containers
if [ "$RESTART" = true ]; then
    echo -e "${YELLOW}Restarting containers...${NC}"
    docker-compose restart
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

# Build containers
if [ "$BUILD" = true ] || [ "$FRESH" = true ]; then
    echo -e "${YELLOW}Building Docker images...${NC}"
    docker-compose build --no-cache
fi

# Fresh start - remove volumes
if [ "$FRESH" = true ]; then
    echo -e "${YELLOW}Removing old volumes and containers...${NC}"
    docker-compose down -v
fi

# Start containers
echo -e "${YELLOW}Starting Docker containers...${NC}"
docker-compose up -d

# Wait for MySQL to be ready
echo -e "${YELLOW}Waiting for MySQL to be ready...${NC}"
max_retries=30
retries=0
while [ $retries -lt $max_retries ]; do
    if docker-compose exec -T mysql mysqladmin ping -h localhost -u root -pMySecureRootPass2025! 2>&1 | grep -q "mysqld is alive"; then
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
    docker-compose exec laravel-webapp php artisan migrate:fresh --force
    
    echo -e "${YELLOW}Seeding database...${NC}"
    docker-compose exec laravel-webapp php artisan db:seed --force
    
    echo -e "${YELLOW}Creating storage link...${NC}"
    docker-compose exec laravel-webapp php artisan storage:link
    
    echo -e "${YELLOW}Clearing caches...${NC}"
    docker-compose exec laravel-webapp php artisan config:clear
    docker-compose exec laravel-webapp php artisan cache:clear
    docker-compose exec laravel-webapp php artisan view:clear
else
    echo -e "${YELLOW}Running database migrations...${NC}"
    docker-compose exec laravel-webapp php artisan migrate --force
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
