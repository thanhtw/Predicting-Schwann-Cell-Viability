# PSC Viability Prediction - Docker Deployment Script
# PowerShell Script for Windows

param(
    [switch]$Build,
    [switch]$Fresh,
    [switch]$Stop,
    [switch]$Logs,
    [switch]$Restart
)

$ErrorActionPreference = "Stop"

# Always run relative to this script, even when invoked from another directory.
Set-Location -LiteralPath $PSScriptRoot

function Invoke-Compose {
    & docker compose @args
    if ($LASTEXITCODE -ne 0) {
        throw "Docker Compose command failed: docker compose $($args -join ' ')"
    }
}

function Invoke-Artisan {
    Invoke-Compose exec -T laravel-webapp php artisan @args
}

function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

Write-ColorOutput Green "========================================="
Write-ColorOutput Green "PSC Viability Prediction - Docker Deploy"
Write-ColorOutput Green "========================================="
Write-Host ""

# Check Docker and the Compose plugin before changing anything.
$savedErrorPreference = $ErrorActionPreference
$ErrorActionPreference = "SilentlyContinue"
docker info 2>$null | Out-Null
$dockerInfoExitCode = $LASTEXITCODE
docker compose version 2>$null | Out-Null
$composeVersionExitCode = $LASTEXITCODE
$ErrorActionPreference = $savedErrorPreference
if ($dockerInfoExitCode -ne 0 -or $composeVersionExitCode -ne 0) {
    Write-ColorOutput Red "Error: Docker Desktop is not running or Docker Compose v2 is unavailable."
    exit 1
}

# Stop containers
if ($Stop) {
    Write-ColorOutput Yellow "Stopping all containers..."
    Invoke-Compose down
    Write-ColorOutput Green "Containers stopped successfully!"
    exit 0
}

# View logs
if ($Logs) {
    Write-ColorOutput Yellow "Showing logs (Ctrl+C to exit)..."
    Invoke-Compose logs -f
    exit 0
}

# Restart containers
if ($Restart) {
    Write-ColorOutput Yellow "Restarting containers..."
    Invoke-Compose restart
    Write-ColorOutput Green "Containers restarted successfully!"
    exit 0
}

# Create .env.docker from example if not exists
if (-not (Test-Path ".env.docker")) {
    Write-ColorOutput Yellow "Creating .env.docker from .env.docker.example..."
    if (Test-Path ".env.docker.example") {
        Copy-Item ".env.docker.example" ".env.docker"
        Write-ColorOutput Green ".env.docker created successfully!"
    } else {
        Write-ColorOutput Red "Error: .env.docker.example not found!"
        exit 1
    }
}

# Copy environment file to WebApp if not exists
if (-not (Test-Path "WebApp\.env")) {
    Write-ColorOutput Yellow "Copying .env.docker to WebApp\.env..."
    Copy-Item ".env.docker" "WebApp\.env"
}

# Build containers
if ($Build -or $Fresh) {
    Write-ColorOutput Yellow "Building Docker images..."
    Invoke-Compose build --no-cache
}

# Fresh start - remove volumes
if ($Fresh) {
    Write-ColorOutput Yellow "Removing old volumes and containers..."
    Invoke-Compose down -v --remove-orphans
}

# Start containers
Write-ColorOutput Yellow "Starting Docker containers..."
Invoke-Compose up -d

# Wait for MySQL to be ready
Write-ColorOutput Yellow "Waiting for MySQL to be ready..."
$maxRetries = 30
$retries = 0
while ($retries -lt $maxRetries) {
    # MYSQL_PWD avoids mysqladmin's harmless "password on the command line"
    # warning, which PowerShell turns into an error when ErrorActionPreference
    # is Stop. Read the configured password from the container so customized
    # .env.docker/Compose credentials continue to work.
    & docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqladmin ping --host=localhost --user=root --silent' 2>$null | Out-Null
    if ($LASTEXITCODE -eq 0) {
        break
    }
    $retries++
    Write-Host "." -NoNewline
    Start-Sleep -Seconds 2
}
Write-Host ""

if ($retries -eq $maxRetries) {
    Write-ColorOutput Red "MySQL failed to start in time!"
    exit 1
}

Write-ColorOutput Green "MySQL is ready!"

# Wait for PHP-FPM and ensure a valid Laravel key exists before Artisan runs.
Write-ColorOutput Yellow "Waiting for the Laravel application..."
$retries = 0
while ($retries -lt $maxRetries) {
    & docker compose exec -T laravel-webapp php -r "exit(file_exists('vendor/autoload.php') ? 0 : 1);" 2>$null
    if ($LASTEXITCODE -eq 0) { break }
    $retries++
    Start-Sleep -Seconds 2
}
if ($retries -eq $maxRetries) {
    Write-ColorOutput Red "Laravel failed to start. Run '.\deploy.ps1 -Logs' for details."
    exit 1
}

$appKey = & docker compose exec -T laravel-webapp php artisan tinker --execute="echo config('app.key');" 2>$null
if ($LASTEXITCODE -ne 0 -or -not ($appKey -match '^base64:[A-Za-z0-9+/]{43}=$')) {
    Write-ColorOutput Yellow "Generating Laravel application key..."
    Invoke-Artisan key:generate --force
}

# Run migrations and seeders
if ($Fresh) {
    Write-ColorOutput Yellow "Running database migrations..."
    Invoke-Artisan migrate:fresh --seed --force
    
    Write-ColorOutput Yellow "Creating storage link..."
    Invoke-Artisan storage:link --force
    
    Write-ColorOutput Yellow "Clearing caches..."
    Invoke-Artisan optimize:clear
} else {
    Write-ColorOutput Yellow "Running database migrations..."
    Invoke-Artisan migrate --force
}

# Fail deployment if the web endpoint or shared JWT authentication is broken.
Write-ColorOutput Yellow "Running deployment checks..."
$httpCode = & curl.exe -sS -o NUL -w "%{http_code}" http://localhost:52025/login
if ($LASTEXITCODE -ne 0 -or $httpCode -ne "200") {
    throw "Web application check failed (HTTP $httpCode)."
}

$laravelJwtHash = (& docker compose exec -T laravel-webapp php -r "echo hash('sha256', getenv('JWT_SECRET'));" 2>$null).Trim()
$predictionJwtHash = (& docker compose exec -T predict-service python -c "import hashlib, os; print(hashlib.sha256(os.environ['JWT_SECRET'].encode()).hexdigest())" 2>$null).Trim()
if ($LASTEXITCODE -ne 0 -or $laravelJwtHash.Length -ne 64 -or $laravelJwtHash -ne $predictionJwtHash) {
    throw "Laravel and the prediction service do not share the same JWT secret."
}

Write-Host ""
Write-ColorOutput Green "========================================="
Write-ColorOutput Green "Deployment Complete!"
Write-ColorOutput Green "========================================="
Write-Host ""
Write-ColorOutput Cyan "Access the application at: http://localhost:52025"
Write-Host ""
Write-ColorOutput Yellow "Default admin credentials:"
Write-Host "  Username: admin"
Write-Host "  Password: password"
Write-Host ""
Write-ColorOutput Yellow "Useful commands:"
Write-Host "  View logs:         .\deploy.ps1 -Logs"
Write-Host "  Stop containers:   .\deploy.ps1 -Stop"
Write-Host "  Restart:           .\deploy.ps1 -Restart"
Write-Host "  Fresh install:     .\deploy.ps1 -Fresh"
Write-Host "  Rebuild images:    .\deploy.ps1 -Build"
Write-Host ""
