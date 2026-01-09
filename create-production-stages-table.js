// Create production_stages table in Railway database
import mysql from 'mysql2/promise';

const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway'
};

console.log('\n🔧 Creating production_stages table...\n');

async function createProductionStagesTable() {
  let connection;
  
  try {
    connection = await mysql.createConnection(config);
    console.log('✅ Connected to database\n');
    
    // Create production_stages table
    await connection.query(`
      CREATE TABLE IF NOT EXISTS production_stages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        department_id INT NOT NULL,
        stage_name VARCHAR(100) NOT NULL,
        stage_code VARCHAR(50) NOT NULL,
        stage_order INT NOT NULL DEFAULT 1,
        description TEXT,
        estimated_hours DECIMAL(5,2) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY unique_dept_stage_code (department_id, stage_code),
        INDEX idx_department (department_id),
        INDEX idx_stage_order (stage_order),
        INDEX idx_active (is_active)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);
    
    console.log('✅ production_stages table created!\n');
    
    // Show table count
    const [tables] = await connection.query('SHOW TABLES');
    console.log(`📊 Total tables in database: ${tables.length}\n`);
    
    // Show structure
    const [cols] = await connection.query('DESCRIBE production_stages');
    console.log('📋 production_stages structure:');
    cols.forEach(col => {
      console.log(`   ${col.Field.padEnd(20)} ${col.Type.padEnd(20)} ${col.Null.padEnd(5)} ${col.Key}`);
    });
    
    console.log('\n🎉 production_stages table ready!\n');
    
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

createProductionStagesTable();
