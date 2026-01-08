# Database Import Script for Railway MySQL
Write-Host "`n=== BARRON DATABASE IMPORT ===" -ForegroundColor Cyan
Write-Host ""

$sqlFile = "database\complete_schema.sql"

# Check if SQL file exists
if (-not (Test-Path $sqlFile)) {
    Write-Host "ERROR: SQL file not found!" -ForegroundColor Red
    exit 1
}

Write-Host "SQL file found: $sqlFile" -ForegroundColor Green
Write-Host "File has $((Get-Content $sqlFile).Count) lines" -ForegroundColor Green
Write-Host ""

# Check for MySQL client
$mysqlExists = Get-Command mysql -ErrorAction SilentlyContinue

if ($mysqlExists) {
    Write-Host "MySQL client found! Importing..." -ForegroundColor Green
    Write-Host ""
    
    # Import database
    Get-Content $sqlFile | mysql -h caboose.proxy.rlwy.net -P 20038 -u root -pEDDEmqdRstvoHdqCmEflYJrnpaBwWajy railway
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "=== DATABASE IMPORT SUCCESSFUL ===" -ForegroundColor Green
        Write-Host ""
        Write-Host "Created:" -ForegroundColor Cyan
        Write-Host "  22+ database tables" -ForegroundColor White
        Write-Host "  Admin user: admin@barron / admin123" -ForegroundColor White
        Write-Host "  5 roles + 17 permissions" -ForegroundColor White
        Write-Host ""
        Write-Host "You can now login to your Railway app!" -ForegroundColor Yellow
    } else {
        Write-Host "Import failed!" -ForegroundColor Red
    }
} else {
    Write-Host "MySQL client NOT found" -ForegroundColor Red
    Write-Host ""
    Write-Host "SOLUTION OPTIONS:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "1. Install MySQL client:" -ForegroundColor Cyan
    Write-Host "   choco install mysql-cli" -ForegroundColor White
    Write-Host ""
    Write-Host "2. Use Railway web dashboard:" -ForegroundColor Cyan
    Write-Host "   - Go to https://railway.app/" -ForegroundColor White
    Write-Host "   - Open MySQL service -> Query tab" -ForegroundColor White
    Write-Host "   - Copy/paste database\complete_schema.sql" -ForegroundColor White
    Write-Host ""
    Write-Host "See DATABASE_IMPORT_GUIDE.md for full instructions" -ForegroundColor Green
}
