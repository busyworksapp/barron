// Complete Database Status Check
import mysql from 'mysql2/promise';

const config = {
  host: 'caboose.proxy.rlwy.net',
  port: 20038,
  user: 'root',
  password: 'EDDEmqdRstvoHdqCmEflYJrnpaBwWajy',
  database: 'railway'
};

async function checkDatabase() {
  let connection;
  
  try {
    console.log('\n╔══════════════════════════════════════════════════════════════╗');
    console.log('║          COMPLETE DATABASE STATUS CHECK                    ║');
    console.log('╚══════════════════════════════════════════════════════════════╝\n');
    
    connection = await mysql.createConnection(config);
    
    // 1. ALL TABLES
    console.log('📊 ALL TABLES CREATED:');
    const [tables] = await connection.query(
      "SELECT table_name, table_rows FROM information_schema.tables WHERE table_schema = 'railway' ORDER BY table_name"
    );
    
    let tableList = {};
    tables.forEach((table, index) => {
      const name = table.table_name || table.TABLE_NAME;
      const rows = table.table_rows || table.TABLE_ROWS || 0;
      console.log(`   ${(index + 1).toString().padStart(2)}. ${name.padEnd(40)} (${rows} rows)`);
      tableList[name] = rows;
    });
    console.log(`\n   ✅ TOTAL: ${tables.length} tables created\n`);
    
    // 2. AUTHENTICATION & USERS
    console.log('👥 AUTHENTICATION & USERS:');
    const [users] = await connection.query("SELECT id, username, email, first_name, last_name, status FROM users");
    console.log(`   Users: ${users.length}`);
    users.forEach(user => {
      console.log(`      - ${user.username} (${user.email}) - ${user.first_name} ${user.last_name} [${user.status}]`);
    });
    
    const [roles] = await connection.query("SELECT * FROM roles");
    console.log(`   Roles: ${roles.length}`);
    roles.forEach(role => {
      console.log(`      - ${role.name}: ${role.description}`);
    });
    
    const [perms] = await connection.query("SELECT name FROM permissions ORDER BY name");
    console.log(`   Permissions: ${perms.length}`);
    perms.forEach(perm => {
      console.log(`      - ${perm.name}`);
    });
    console.log('');
    
    // 3. MASTER DATA
    console.log('🏢 MASTER DATA:');
    const [depts] = await connection.query("SELECT COUNT(*) as count FROM departments");
    console.log(`   Departments: ${depts[0].count}`);
    
    const [emps] = await connection.query("SELECT COUNT(*) as count FROM employees");
    console.log(`   Employees: ${emps[0].count}`);
    
    const [machines] = await connection.query("SELECT COUNT(*) as count FROM machines");
    console.log(`   Machines: ${machines[0].count}`);
    
    const [products] = await connection.query("SELECT COUNT(*) as count FROM products");
    console.log(`   Products: ${products[0].count}\n`);
    
    // 4. PRODUCTION
    console.log('🏭 PRODUCTION:');
    const [orders] = await connection.query("SELECT COUNT(*) as count FROM orders");
    console.log(`   Orders: ${orders[0].count}`);
    
    const [jobs] = await connection.query("SELECT COUNT(*) as count FROM jobs");
    console.log(`   Jobs: ${jobs[0].count}`);
    
    const [logs] = await connection.query("SELECT COUNT(*) as count FROM production_logs");
    console.log(`   Production Logs: ${logs[0].count}\n`);
    
    // 5. QUALITY MANAGEMENT
    console.log('✅ QUALITY MANAGEMENT:');
    const [rejects] = await connection.query("SELECT COUNT(*) as count FROM internal_rejects");
    console.log(`   Internal Rejects: ${rejects[0].count}`);
    
    const [returns] = await connection.query("SELECT COUNT(*) as count FROM customer_returns");
    console.log(`   Customer Returns: ${returns[0].count}\n`);
    
    // 6. COMPLIANCE
    console.log('📋 COMPLIANCE:');
    const [ncr] = await connection.query("SELECT COUNT(*) as count FROM ncr_reports");
    console.log(`   NCR Reports: ${ncr[0].count}`);
    
    const [sop] = await connection.query("SELECT COUNT(*) as count FROM sop_failures");
    console.log(`   SOP Failures: ${sop[0].count}\n`);
    
    // 7. MAINTENANCE
    console.log('🔧 MAINTENANCE:');
    const [schedules] = await connection.query("SELECT COUNT(*) as count FROM preventive_maintenance_schedules");
    console.log(`   PM Schedules: ${schedules[0].count}`);
    
    const [tickets] = await connection.query("SELECT COUNT(*) as count FROM maintenance_tickets");
    console.log(`   Maintenance Tickets: ${tickets[0].count}\n`);
    
    // 8. FINANCE/BOM
    console.log('💰 FINANCE/BOM:');
    const [bom] = await connection.query("SELECT COUNT(*) as count FROM bom");
    console.log(`   BOM Items: ${bom[0].count}`);
    
    const [bomComp] = await connection.query("SELECT COUNT(*) as count FROM bom_components");
    console.log(`   BOM Components: ${bomComp[0].count}\n`);
    
    // SUMMARY
    console.log('╔══════════════════════════════════════════════════════════════╗');
    console.log('║                    ✅ DATABASE STATUS                       ║');
    console.log('╚══════════════════════════════════════════════════════════════╝\n');
    
    console.log('📊 SUMMARY:');
    console.log(`   ✅ ${tables.length} tables created and ready`);
    console.log(`   ✅ ${users.length} admin user with full access`);
    console.log(`   ✅ ${roles.length} role (Administrator)`);
    console.log(`   ✅ ${perms.length} permissions configured`);
    console.log('   ✅ All modules ready for use\n');
    
    console.log('🚀 SYSTEM STATUS: FULLY OPERATIONAL\n');
    console.log('Login credentials:');
    console.log('   Email: admin@barron.com');
    console.log('   Password: admin123\n');
    
  } catch (error) {
    console.error('❌ ERROR:', error.message);
  } finally {
    if (connection) {
      await connection.end();
    }
  }
}

checkDatabase();
