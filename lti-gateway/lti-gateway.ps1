# LTI Gateway PowerShell Script (Windows Makefile Alternative)
# Usage: .\lti-gateway.ps1 [command]

param(
    [Parameter(Position=0)]
    [string]$Command = "help"
)

$DockerCompose = "docker-compose -f ../docker-compose-lti.yml"

function Show-Help {
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "    LTI Gateway Windows Management Script" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Usage: .\lti-gateway.ps1 [command]" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Available Commands:" -ForegroundColor Green
    Write-Host "  install          Install project" -ForegroundColor White
    Write-Host "  up               Start all services" -ForegroundColor White
    Write-Host "  down             Stop all services" -ForegroundColor White
    Write-Host "  restart          Restart services" -ForegroundColor White
    Write-Host "  logs             View logs" -ForegroundColor White
    Write-Host "  test             Run tests" -ForegroundColor White
    Write-Host "  shell            Enter app container" -ForegroundColor White
    Write-Host "  migrate          Run database migrations" -ForegroundColor White
    Write-Host "  seed             Seed test data" -ForegroundColor White
    Write-Host "  fresh            Reset database" -ForegroundColor White
    Write-Host "  cache-clear      Clear cache" -ForegroundColor White
    Write-Host "  health           Run health check" -ForegroundColor White
    Write-Host "  cleanup          Cleanup old logs" -ForegroundColor White
    Write-Host "  stats            Show statistics" -ForegroundColor White
    Write-Host "  tools            List tool configs" -ForegroundColor White
    Write-Host "  test-moodle      Test Moodle connection" -ForegroundColor White
    Write-Host "  queue-restart    Restart queue" -ForegroundColor White
    Write-Host "  backup           Backup database" -ForegroundColor White
    Write-Host "  reset            Reset all data (DANGER!)" -ForegroundColor Red
    Write-Host "  update           Update to latest version" -ForegroundColor White
    Write-Host "  key-generate     Generate RSA key pair" -ForegroundColor White
    Write-Host "  analyze          Analyze logs" -ForegroundColor White
    Write-Host "  export-mappings  Export user mappings" -ForegroundColor White
    Write-Host "  import-mappings  Import user mappings" -ForegroundColor White
    Write-Host "  metrics          View system metrics" -ForegroundColor White
    Write-Host "  report           Generate system report" -ForegroundColor White
    Write-Host "  sync-users       Sync user data" -ForegroundColor White
    Write-Host "  notify           Send admin notifications" -ForegroundColor White
    Write-Host "  status           Show system status" -ForegroundColor White
    Write-Host "  help             Show this help" -ForegroundColor White
    Write-Host ""
}

function Install-Project {
    Write-Host "Installing LTI Gateway..." -ForegroundColor Green
    if (Test-Path "scripts\install.sh") {
        bash scripts/install.sh
    } else {
        Write-Host "Error: install.sh not found" -ForegroundColor Red
    }
}

function Start-Services {
    Write-Host "Starting all services..." -ForegroundColor Green
    Invoke-Expression "$DockerCompose up -d"
}

function Stop-Services {
    Write-Host "Stopping all services..." -ForegroundColor Yellow
    Invoke-Expression "$DockerCompose down"
}

function Restart-Services {
    Write-Host "Restarting services..." -ForegroundColor Yellow
    Invoke-Expression "$DockerCompose restart"
}

function Show-Logs {
    Write-Host "Viewing logs (Press Ctrl+C to exit)..." -ForegroundColor Cyan
    Invoke-Expression "$DockerCompose logs -f"
}

function Run-Tests {
    Write-Host "Running tests..." -ForegroundColor Green
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan test"
}

function Enter-Shell {
    Write-Host "Entering app container..." -ForegroundColor Cyan
    Invoke-Expression "$DockerCompose exec lti_gateway_app bash"
}

function Run-Migrate {
    Write-Host "Running database migrations..." -ForegroundColor Green
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan migrate --force"
}

function Run-Seed {
    Write-Host "Seeding test data..." -ForegroundColor Green
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan db:seed"
}

function Run-Fresh {
    Write-Host "Resetting database..." -ForegroundColor Yellow
    $confirm = Read-Host "Are you sure? All data will be lost! (yes/no)"
    if ($confirm -eq "yes") {
        Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan migrate:fresh --seed"
    } else {
        Write-Host "Operation cancelled" -ForegroundColor Yellow
    }
}

function Clear-Cache {
    Write-Host "Clearing cache..." -ForegroundColor Yellow
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan cache:clear"
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan config:clear"
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan route:clear"
    Write-Host "Cache cleared" -ForegroundColor Green
}

function Check-Health {
    Write-Host "Running health check..." -ForegroundColor Green
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan lti:health-check"
}

function Cleanup-Logs {
    Write-Host "Cleaning up old logs..." -ForegroundColor Yellow
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan lti:cleanup"
}

function Show-Stats {
    Write-Host "Showing statistics..." -ForegroundColor Cyan
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan lti:stats"
}

function List-Tools {
    Write-Host "Listing tool configs..." -ForegroundColor Cyan
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan lti:tools"
}

function Test-Moodle {
    Write-Host "Testing Moodle connection..." -ForegroundColor Green
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan lti:test-moodle"
}

function Restart-Queue {
    Write-Host "Restarting queue..." -ForegroundColor Yellow
    Invoke-Expression "$DockerCompose restart lti_gateway_queue"
}

function Backup-Database {
    Write-Host "Backing up database..." -ForegroundColor Green
    if (Test-Path "scripts\backup.sh") {
        bash scripts/backup.sh
    } else {
        Write-Host "Error: backup.sh not found" -ForegroundColor Red
    }
}

function Reset-All {
    Write-Host "WARNING: This will reset all data!" -ForegroundColor Red
    $confirm = Read-Host "Type 'RESET' to confirm"
    if ($confirm -eq "RESET") {
        if (Test-Path "scripts\reset.sh") {
            bash scripts/reset.sh
        } else {
            Write-Host "Error: reset.sh not found" -ForegroundColor Red
        }
    } else {
        Write-Host "Operation cancelled" -ForegroundColor Yellow
    }
}

function Update-Project {
    Write-Host "Updating to latest version..." -ForegroundColor Green
    if (Test-Path "scripts\update.sh") {
        bash scripts/update.sh
    } else {
        Write-Host "Error: update.sh not found" -ForegroundColor Red
    }
}

function Generate-Keys {
    Write-Host "Generating RSA key pair..." -ForegroundColor Green
    php scripts/generate-keys.php
}

function Analyze-Logs {
    Write-Host "Analyzing logs..." -ForegroundColor Cyan
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan lti:analyze"
}

function Export-Mappings {
    Write-Host "Exporting user mappings..." -ForegroundColor Green
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan lti:export-mappings"
}

function Import-Mappings {
    Write-Host "Importing user mappings..." -ForegroundColor Green
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan lti:import-mappings"
}

function Show-Metrics {
    Write-Host "Viewing system metrics..." -ForegroundColor Cyan
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:8081/metrics/system" -UseBasicParsing
        $response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 10
    } catch {
        Write-Host "Failed to get metrics, ensure service is running" -ForegroundColor Red
    }
}

function Generate-Report {
    Write-Host "Generating system report..." -ForegroundColor Green
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan lti:report"
}

function Sync-Users {
    Write-Host "Syncing user data..." -ForegroundColor Green
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan lti:sync-users"
}

function Notify-Admins {
    Write-Host "Sending admin notifications..." -ForegroundColor Green
    Invoke-Expression "$DockerCompose exec lti_gateway_app php artisan lti:notify"
}

function Show-Status {
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "     LTI Gateway System Status" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Docker Container Status:" -ForegroundColor Yellow
    Invoke-Expression "$DockerCompose ps"
    Write-Host ""
    Write-Host "Last 24 Hours Statistics:" -ForegroundColor Yellow
    Invoke-Expression "$DockerCompose exec -T lti_gateway_app php artisan lti:stats --days=1"
    Write-Host ""
    Write-Host "Health Check:" -ForegroundColor Yellow
    Invoke-Expression "$DockerCompose exec -T lti_gateway_app php artisan lti:health-check"
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
}

# Main Logic
switch ($Command.ToLower()) {
    "help" { Show-Help }
    "install" { Install-Project }
    "up" { Start-Services }
    "down" { Stop-Services }
    "restart" { Restart-Services }
    "logs" { Show-Logs }
    "test" { Run-Tests }
    "shell" { Enter-Shell }
    "migrate" { Run-Migrate }
    "seed" { Run-Seed }
    "fresh" { Run-Fresh }
    "cache-clear" { Clear-Cache }
    "health" { Check-Health }
    "cleanup" { Cleanup-Logs }
    "stats" { Show-Stats }
    "tools" { List-Tools }
    "test-moodle" { Test-Moodle }
    "queue-restart" { Restart-Queue }
    "backup" { Backup-Database }
    "reset" { Reset-All }
    "update" { Update-Project }
    "key-generate" { Generate-Keys }
    "analyze" { Analyze-Logs }
    "export-mappings" { Export-Mappings }
    "import-mappings" { Import-Mappings }
    "metrics" { Show-Metrics }
    "report" { Generate-Report }
    "sync-users" { Sync-Users }
    "notify" { Notify-Admins }
    "status" { Show-Status }
    default {
        Write-Host "Unknown command: $Command" -ForegroundColor Red
        Write-Host "Use '.\lti-gateway.ps1 help' to see available commands" -ForegroundColor Yellow
        exit 1
    }
}
