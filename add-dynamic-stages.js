// Add Missing Production Stages Table
import mysql from 'mysql2/promise';

const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway'
};

const createTableSQL = `
CREATE TABLE IF NOT EXISTS production_stages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NOT NULL,
    stage_code VARCHAR(50) NOT NULL,
    stage_name VARCHAR(100) NOT NULL,
    stage_order INT NOT NULL,
    estimated_duration_hours DECIMAL(10,2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dept_stage_code (department_id, stage_code),
    INDEX idx_department (department_id),
    INDEX idx_stage_order (stage_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
`;

async function addProductionStagesTable() {
  let connection;
  
  try {
    console.log('\n╔══════════════════════════════════════════════════════════════╗');
    console.log('║     ADDING PRODUCTION STAGES TABLE (DYNAMIC WORKFLOW)      ║');
    console.log('╚══════════════════════════════════════════════════════════════╝\n');
    
    console.log('🔌 Connecting to Railway MySQL...');
    connection = await mysql.createConnection(config);
    console.log('✅ Connected!\n');
    
    console.log('📋 Creating production_stages table...');
    await connection.query(createTableSQL);
    console.log('✅ Table created successfully!\n');
    
    // Verify table exists
    const [tables] = await connection.query(
      "SHOW TABLES LIKE 'production_stages'"
    );
    
    if (tables.length > 0) {
      console.log('✅ Verification: production_stages table exists\n');
      
      // Show table structure
      const [structure] = await connection.query('DESCRIBE production_stages');
      console.log('📊 Table Structure:');
      structure.forEach(field => {
        console.log(`   ${field.Field.padEnd(30)} ${field.Type.padEnd(20)} ${field.Null === 'YES' ? 'NULL' : 'NOT NULL'}`);
      });
      console.log('');
    }
    
    console.log('╔══════════════════════════════════════════════════════════════╗');
    console.log('║          ✅ DYNAMIC STAGES TABLE ADDED!                    ║');
    console.log('╚══════════════════════════════════════════════════════════════╝\n');
    
    console.log('🎯 WHAT THIS ENABLES:');
    console.log('   ✅ Dynamic production workflow stages per department');
    console.log('   ✅ Configurable stage order');
    console.log('   ✅ Estimated duration tracking');
    console.log('   ✅ Stage activation/deactivation');
    console.log('   ✅ Automatic workflow generation\n');
    
    console.log('💡 HOW TO USE:');
    console.log('   1. Go to Master Data → Departments');
    console.log('   2. Click "View Stages" button');
    console.log('   3. Add dynamic stages (e.g., Cutting, Welding, Assembly)');
    console.log('   4. Set stage order and duration');
    console.log('   5. Stages automatically appear in job scheduling!\n');
    
  } catch (error) {
    console.error('❌ ERROR:', error.message);
    process.exit(1);
  } finally {
    if (connection) {
      await connection.end();
      console.log('🔌 Connection closed\n');
    }
  }
}

addProductionStagesTable();
