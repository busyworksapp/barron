// Fix roles table - add missing role_code column
import mysql from 'mysql2/promise';

const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway'
};

console.log('\n🔧 Fixing roles table structure...\n');

async function fixRolesTable() {
  let connection;
  
  try {
    connection = await mysql.createConnection(config);
    console.log('✅ Connected to database\n');
    
    // Add role_code column
    console.log('Adding role_code column...');
    await connection.query(`
      ALTER TABLE roles 
      ADD COLUMN role_code VARCHAR(50) AFTER id
    `);
    console.log('✅ role_code column added\n');
    
    // Rename name to role_name for consistency
    console.log('Renaming name to role_name...');
    await connection.query(`
      ALTER TABLE roles 
      CHANGE COLUMN name role_name VARCHAR(100) NOT NULL
    `);
    console.log('✅ Column renamed to role_name\n');
    
    // Update existing role with code
    console.log('Updating Administrator role with code...');
    await connection.query(`
      UPDATE roles 
      SET role_code = 'admin' 
      WHERE role_name = 'Administrator'
    `);
    console.log('✅ Administrator role updated\n');
    
    // Verify changes
    console.log('Verifying table structure...');
    const [columns] = await connection.query('DESCRIBE roles');
    console.log('✅ Roles table structure:');
    columns.forEach(col => {
      console.log(`   - ${col.Field} (${col.Type})`);
    });
    
    console.log('\n🎉 Roles table fixed successfully!\n');
    
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

fixRolesTable();
