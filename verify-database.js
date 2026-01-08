// Verify Database Import
import mysql from 'mysql2/promise';

const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway'
};

async function verifyDatabase() {
  let connection;
  
  try {
    console.log('\n🔍 Verifying Database Import...\n');
    
    connection = await mysql.createConnection(config);
    
    // List all tables
    const [tables] = await connection.query(
      "SELECT table_name FROM information_schema.tables WHERE table_schema = 'railway' ORDER BY table_name"
    );
    
    console.log(`📊 Tables created (${tables.length}):`);
    tables.forEach((table, index) => {
      console.log(`   ${index + 1}. ${table.table_name || table.TABLE_NAME}`);
    });
    console.log('');
    
    // Check users
    const [users] = await connection.query("SELECT * FROM users LIMIT 5");
    console.log(`👥 Users in database: ${users.length}`);
    if (users.length > 0) {
      users.forEach(user => {
        console.log(`   - ${user.email} (${user.username})`);
      });
    }
    console.log('');
    
    // Check roles
    const [roles] = await connection.query("SELECT * FROM roles");
    console.log(`🎭 Roles: ${roles.length}`);
    roles.forEach(role => {
      console.log(`   - ${role.name}: ${role.description}`);
    });
    console.log('');
    
    // Check permissions
    const [permissions] = await connection.query("SELECT COUNT(*) as count FROM permissions");
    console.log(`🔐 Permissions: ${permissions[0].count}\n`);
    
    // Check departments
    const [depts] = await connection.query("SELECT COUNT(*) as count FROM departments");
    console.log(`🏢 Departments: ${depts[0].count}`);
    
    // Check products
    const [products] = await connection.query("SELECT COUNT(*) as count FROM products");
    console.log(`📦 Products: ${products[0].count}`);
    
    // Check machines
    const [machines] = await connection.query("SELECT COUNT(*) as count FROM machines");
    console.log(`🔧 Machines: ${machines[0].count}\n`);
    
    console.log('✅ Database verification complete!\n');
    
  } catch (error) {
    console.error('❌ ERROR:', error.message);
  } finally {
    if (connection) {
      await connection.end();
    }
  }
}

verifyDatabase();
