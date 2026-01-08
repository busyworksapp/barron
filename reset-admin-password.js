// Update admin password to ensure it's correctly hashed
import mysql from 'mysql2/promise';
import bcrypt from 'bcrypt';

const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway'
};

console.log('\n🔧 Updating admin password...\n');

async function updateAdminPassword() {
  let connection;
  
  try {
    connection = await mysql.createConnection(config);
    console.log('✅ Connected to database\n');
    
    // Generate new hash for 'admin123'
    const password = 'admin123';
    const hash = await bcrypt.hash(password, 12);
    
    console.log('Password:', password);
    console.log('New hash:', hash);
    console.log('');
    
    // Update the admin user's password
    console.log('Updating admin user password...');
    await connection.query(
      'UPDATE users SET password = ? WHERE email = ?',
      [hash, 'admin@barron.com']
    );
    console.log('✅ Password updated\n');
    
    // Verify the update
    const [rows] = await connection.query(
      'SELECT username, email, password FROM users WHERE email = ?',
      ['admin@barron.com']
    );
    
    console.log('✅ Verification:');
    console.log('   Username:', rows[0].username);
    console.log('   Email:', rows[0].email);
    console.log('   Password hash:', rows[0].password.substring(0, 30) + '...');
    
    // Test the hash
    const match = await bcrypt.compare(password, rows[0].password);
    console.log('   Hash test:', match ? '✅ PASS' : '❌ FAIL');
    
    console.log('\n🎉 Admin password reset successfully!\n');
    console.log('Login credentials:');
    console.log('   Email: admin@barron.com');
    console.log('   Password: admin123\n');
    
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

updateAdminPassword();
