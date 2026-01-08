// Add is_active columns to roles and permissions tables
import mysql from 'mysql2/promise';

const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway'
};

console.log('\n🔧 Adding is_active columns...\n');

async function addIsActiveColumns() {
  let connection;
  
  try {
    connection = await mysql.createConnection(config);
    console.log('✅ Connected to database\n');
    
    // Add to permissions
    console.log('Adding is_active to permissions...');
    await connection.query('ALTER TABLE permissions ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER permission_code');
    console.log('✅ Added to permissions\n');
    
    // Add to roles
    console.log('Adding is_active to roles...');
    await connection.query('ALTER TABLE roles ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER role_name');
    console.log('✅ Added to roles\n');
    
    console.log('🎉 Columns added successfully!\n');
    
  } catch (error) {
    if (error.errno === 1060) {
      console.log('✅ Columns already exist\n');
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

addIsActiveColumns();
