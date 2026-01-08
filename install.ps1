# Barron Production Management System
# Database Installation Script
# Run this script to set up the database

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Barron Production Management System" -ForegroundColor Cyan
Write-Host "Database Installation" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Database credentials
$MYSQL_HOST = "yamanote.proxy.rlwy.net"
$MYSQL_PORT = "39713"
$MYSQL_USER = "root"
$MYSQL_PASSWORD = "hwemqHyJCOMkVycHiOcRqWBXnUryhFjw"
$MYSQL_DATABASE = "railway"

Write-Host "Connecting to MySQL database..." -ForegroundColor Yellow
Write-Host "Host: $MYSQL_HOST" -ForegroundColor Gray
Write-Host "Port: $MYSQL_PORT" -ForegroundColor Gray
Write-Host "Database: $MYSQL_DATABASE" -ForegroundColor Gray
Write-Host ""

# Check if mysql command is available
$mysqlPath = Get-Command mysql -ErrorAction SilentlyContinue
if (-not $mysqlPath) {
    Write-Host "ERROR: MySQL client not found!" -ForegroundColor Red
    Write-Host "Please install MySQL client or add it to your PATH" -ForegroundColor Red
    Write-Host ""
    Write-Host "Download MySQL: https://dev.mysql.com/downloads/mysql/" -ForegroundColor Yellow
    exit 1
}

# Install schema
Write-Host "Installing database schema..." -ForegroundColor Yellow
$schemaFile = Join-Path $PSScriptRoot "database\schema.sql"

if (Test-Path $schemaFile) {
    $mysqlCommand = "mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD --port $MYSQL_PORT --protocol=TCP $MYSQL_DATABASE"
    Get-Content $schemaFile | & mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD --port $MYSQL_PORT --protocol=TCP $MYSQL_DATABASE
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Schema installed successfully" -ForegroundColor Green
    } else {
        Write-Host "✗ Schema installation failed" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✗ Schema file not found: $schemaFile" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Install seed data
Write-Host "Installing seed data..." -ForegroundColor Yellow
$seedFile = Join-Path $PSScriptRoot "database\seed_data.sql"

if (Test-Path $seedFile) {
    Get-Content $seedFile | & mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD --port $MYSQL_PORT --protocol=TCP $MYSQL_DATABASE
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Seed data installed successfully" -ForegroundColor Green
    } else {
        Write-Host "✗ Seed data installation failed" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✗ Seed data file not found: $seedFile" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Database installation completed!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Default Admin Credentials:" -ForegroundColor Cyan
Write-Host "  Username: admin@barron" -ForegroundColor White
Write-Host "  Password: admin123" -ForegroundColor White
Write-Host ""
Write-Host "⚠️  IMPORTANT: Change the default password after first login!" -ForegroundColor Yellow
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Configure your web server to serve the application" -ForegroundColor White
Write-Host "2. Ensure PHP 8.0+ is installed and configured" -ForegroundColor White
Write-Host "3. Create 'logs' and 'uploads' directories with write permissions" -ForegroundColor White
Write-Host "4. Access the application via your web browser" -ForegroundColor White
Write-Host ""
