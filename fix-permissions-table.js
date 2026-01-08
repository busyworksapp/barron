// Fix permissions table - rename name to permission_code
import mysql from 'mysql2/promise';

const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway'
};

console.log('\n🔧 Fixing permissions table structure...\n');

async function fixPermissionsTable() {
  let connection;
  
  try {
    connection = await mysql.createConnection(config);
    console.log('✅ Connected to database\n');
    
    // Rename name to permission_code
    console.log('Renaming name to permission_code...');
    await connection.query(`
      ALTER TABLE permissions 
      CHANGE COLUMN name permission_code VARCHAR(100) NOT NULL UNIQUE
    `);
    console.log('✅ Column renamed to permission_code\n');
    
    // Verify changes
    console.log('Verifying table structure...');
    const [columns] = await connection.query('DESCRIBE permissions');
    console.log('✅ Permissions table structure:');
    columns.forEach(col => {
      console.log(`   - ${col.Field} (${col.Type})`);
    });
    
    // Show sample permissions
    console.log('\n✅ Sample permissions:');
    const [rows] = await connection.query('SELECT permission_code FROM permissions LIMIT 5');
    rows.forEach(row => {
      console.log(`   - ${row.permission_code}`);
    });
    
    console.log('\n🎉 Permissions table fixed successfully!\n');
    
  } catch (error) {
    console.error('❌ Error:', error.message);
    process.exit(1);
  } finally {
    if (connection) {
      await connection.end();
      console.log('🔌 Connection closed\n');
    }
  }
}

fixPermissionsTable();
