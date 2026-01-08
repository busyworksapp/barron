# Database Import Script for Railway MySQL
# This script imports the complete schema to your Railway database

Write-Host "`n╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║     BARRON DATABASE IMPORT - Railway MySQL              ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Database credentials
$host_db = "caboose.proxy.rlwy.net"
$port = "20038"
$database = "railway"
$username = "root"
$password = "EDDEmqdRstvoHdqCmEflYJrnpaBwWajy"
$sqlFile = "database\complete_schema.sql"

Write-Host "📋 Configuration:" -ForegroundColor Yellow
Write-Host "   Host: $host_db" -ForegroundColor White
Write-Host "   Port: $port" -ForegroundColor White
Write-Host "   Database: $database" -ForegroundColor White
Write-Host "   SQL File: $sqlFile" -ForegroundColor White
Write-Host ""

# Check if SQL file exists
if (-not (Test-Path $sqlFile)) {
    Write-Host "❌ ERROR: SQL file not found: $sqlFile" -ForegroundColor Red
    exit 1
}

Write-Host "✅ SQL file found ($(((Get-Content $sqlFile).Length)) lines)" -ForegroundColor Green
Write-Host ""

# Read SQL content
Write-Host "📖 Reading SQL file..." -ForegroundColor Cyan
$sqlContent = Get-Content $sqlFile -Raw

Write-Host "📊 SQL file size: $($sqlContent.Length) characters" -ForegroundColor Green
Write-Host ""

# Try to import using mysql command if available
Write-Host "🔍 Checking for MySQL client..." -ForegroundColor Cyan
$mysqlExists = Get-Command mysql -ErrorAction SilentlyContinue

if ($mysqlExists) {
    Write-Host "✅ MySQL client found!" -ForegroundColor Green
    Write-Host ""
    Write-Host "⚡ Importing database..." -ForegroundColor Cyan
    
    # Use mysql command
    $sqlContent | mysql -h $host_db -P $port -u $username -p$password $database
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Green
        Write-Host "║          ✅ DATABASE IMPORT SUCCESSFUL!                   ║" -ForegroundColor Green
        Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Green
        Write-Host ""
        Write-Host "📊 What was imported:" -ForegroundColor Cyan
        Write-Host "   • 22+ database tables created" -ForegroundColor White
        Write-Host "   • Admin user created: admin@barron / admin123" -ForegroundColor White
        Write-Host "   • 5 default roles configured" -ForegroundColor White
        Write-Host "   • 17+ permissions set up" -ForegroundColor White
        Write-Host ""
        Write-Host "🚀 Next steps:" -ForegroundColor Yellow
        Write-Host "   1. Access your Railway app URL" -ForegroundColor White
        Write-Host "   2. Login with: admin@barron / admin123" -ForegroundColor White
        Write-Host "   3. Start using the system!" -ForegroundColor White
        Write-Host ""
    } else {
        Write-Host "❌ Import failed with error code: $LASTEXITCODE" -ForegroundColor Red
    }
} else {
    Write-Host "❌ MySQL client not found" -ForegroundColor Red
    Write-Host ""
    Write-Host "📝 MANUAL IMPORT OPTIONS:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "OPTION 1: Install MySQL Client" -ForegroundColor Cyan
    Write-Host "   choco install mysql-cli" -ForegroundColor White
    Write-Host "   Then run this script again" -ForegroundColor White
    Write-Host ""
    Write-Host "OPTION 2: Use Railway Web Dashboard" -ForegroundColor Cyan
    Write-Host "   1. Go to https://railway.app/" -ForegroundColor White
    Write-Host "   2. Open your MySQL service" -ForegroundColor White
    Write-Host "   3. Click 'Query' tab" -ForegroundColor White
    Write-Host "   4. Copy/paste contents of: $sqlFile" -ForegroundColor White
    Write-Host "   5. Click 'Run'" -ForegroundColor White
    Write-Host ""
    Write-Host "OPTION 3: Use MySQL Workbench (GUI)" -ForegroundColor Cyan
    Write-Host "   Download: https://dev.mysql.com/downloads/workbench/" -ForegroundColor White
    Write-Host "   See DATABASE_IMPORT_GUIDE.md for details" -ForegroundColor White
    Write-Host ""
    Write-Host "Full instructions in: DATABASE_IMPORT_GUIDE.md" -ForegroundColor Green
}

Write-Host ""
