// Add is_active column to role_permissions table
import mysql from 'mysql2/promise';

const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway'
};

console.log('\n🔧 Fixing role_permissions table...\n');

async function fixRolePermissionsTable() {
  let connection;
  
  try {
    connection = await mysql.createConnection(config);
    console.log('✅ Connected to database\n');
    
    // Add is_active column
    console.log('Adding is_active column...');
    await connection.query('ALTER TABLE role_permissions ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER permission_id');
    console.log('✅ Added is_active column\n');
    
    // Show final structure
    const [columns] = await connection.query('DESCRIBE role_permissions');
    console.log('📋 Final structure:');
    columns.forEach(col => {
      console.log(`   ${col.Field.padEnd(20)} ${col.Type.padEnd(20)} ${col.Null.padEnd(5)} ${col.Key}`);
    });
    
    console.log('\n🎉 role_permissions table fixed!\n');
    
  } catch (error) {
    if (error.errno === 1060) {
      console.log('✅ Column already exists\n');
    } else {
      console.error('❌ Error:', error.message);
      process.exit(1);
    }
  } finally {
    if (connection) {
      await connection.end();
      console.log('🔌 Connection closed\n');
    }
  }
}

fixRolePermissionsTable();
