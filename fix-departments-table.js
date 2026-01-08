// Fix departments table - rename name to department_name
import mysql from 'mysql2/promise';

const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway'
};

console.log('\n🔧 Fixing departments table structure...\n');

async function fixDepartmentsTable() {
  let connection;
  
  try {
    connection = await mysql.createConnection(config);
    console.log('✅ Connected to database\n');
    
    // Rename name to department_name
    console.log('Renaming name to department_name...');
    await connection.query(`
      ALTER TABLE departments 
      CHANGE COLUMN name department_name VARCHAR(100) NOT NULL
    `);
    console.log('✅ Column renamed to department_name\n');
    
    // Verify changes
    console.log('Verifying table structure...');
    const [columns] = await connection.query('DESCRIBE departments');
    console.log('✅ Departments table structure:');
    columns.forEach(col => {
      console.log(`   - ${col.Field} (${col.Type})`);
    });
    
    console.log('\n🎉 Departments table fixed successfully!\n');
    
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

fixDepartmentsTable();
