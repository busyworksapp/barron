// Database Import Script for Railway MySQL
// This script creates all tables and seeds initial data

import mysql from 'mysql2/promise';
import fs from 'fs';
import path from 'path';

// Database configuration
const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway',
  multipleStatements: true
};

console.log('\n╔══════════════════════════════════════════════════════════════╗');
console.log('║     BARRON DATABASE IMPORT - Railway MySQL                ║');
console.log('╚══════════════════════════════════════════════════════════════╝\n');

async function importDatabase() {
  let connection;
  
  try {
    console.log('📋 Configuration:');
    console.log(`   Host: ${config.host}`);
    console.log(`   Port: ${config.port}`);
    console.log(`   Database: ${config.database}\n`);
    
    // Read SQL file
    console.log('📖 Reading SQL file...');
    const sqlFile = path.join(process.cwd(), 'database', 'complete_schema.sql');
    
    if (!fs.existsSync(sqlFile)) {
      throw new Error(`SQL file not found: ${sqlFile}`);
    }
    
    const sqlContent = fs.readFileSync(sqlFile, 'utf8');
    console.log(`✅ SQL file loaded (${sqlContent.split('\n').length} lines)\n`);
    
    // Connect to database
    console.log('🔌 Connecting to Railway MySQL...');
    connection = await mysql.createConnection(config);
    console.log('✅ Connected successfully!\n');
    
    // Execute SQL
    console.log('⚡ Executing SQL statements...');
    console.log('   This may take 30-60 seconds...\n');
    
    await connection.query(sqlContent);
    
    console.log('✅ SQL executed successfully!\n');
    
    // Verify tables were created
    console.log('🔍 Verifying database...');
    const [tables] = await connection.query(
      "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'railway'"
    );
    console.log(`✅ Found ${tables[0].count} tables\n`);
    
    // Check admin user
    const [users] = await connection.query(
      "SELECT id, username, email, first_name, last_name FROM users WHERE email = 'admin@barron'"
    );
    
    if (users.length > 0) {
      console.log('✅ Admin user verified:');
      console.log(`   Email: ${users[0].email}`);
      console.log(`   Username: ${users[0].username}`);
      console.log(`   Name: ${users[0].first_name} ${users[0].last_name}\n`);
    }
    
    // Check roles
    const [roles] = await connection.query("SELECT COUNT(*) as count FROM roles");
    console.log(`✅ ${roles[0].count} roles created\n`);
    
    // Check permissions
    const [permissions] = await connection.query("SELECT COUNT(*) as count FROM permissions");
    console.log(`✅ ${permissions[0].count} permissions created\n`);
    
    console.log('╔══════════════════════════════════════════════════════════════╗');
    console.log('║          ✅ DATABASE IMPORT SUCCESSFUL!                     ║');
    console.log('╚══════════════════════════════════════════════════════════════╝\n');
    
    console.log('📊 What was created:');
    console.log(`   • ${tables[0].count} database tables`);
    console.log('   • Admin user: admin@barron / admin123');
    console.log(`   • ${roles[0].count} roles configured`);
    console.log(`   • ${permissions[0].count} permissions set up\n`);
    
    console.log('🚀 Next steps:');
    console.log('   1. Access your Railway app URL');
    console.log('   2. Login with: admin@barron / admin123');
    console.log('   3. Start using the system!\n');
    
  } catch (error) {
    console.error('❌ ERROR:', error.message);
    console.error('\nFull error:', error);
    process.exit(1);
  } finally {
    if (connection) {
      await connection.end();
      console.log('🔌 Database connection closed\n');
    }
  }
}

// Run the import
importDatabase();
