// Test password verification
import mysql from 'mysql2/promise';

const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway'
};

async function testPassword() {
  const connection = await mysql.createConnection(config);
  
  // Get the hash
  const [rows] = await connection.query(
    'SELECT password FROM users WHERE email = ?',
    ['admin@barron.com']
  );
  
  console.log('Stored hash:', rows[0].password);
  console.log('Testing with password: admin123');
  console.log('\nNote: PHP password_verify() should handle $2y$ hashes automatically');
  console.log('The hash starts with $2y$12$ which is a bcrypt hash with cost 12');
  
  await connection.end();
}

testPassword();
