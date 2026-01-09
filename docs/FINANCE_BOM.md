# Finance & BOM Module - Integration Guide

## Overview
The Finance & BOM module provides comprehensive cost tracking, bill of materials management, and accounting system integration for the production management system.

## Key Features

### 1. Bill of Materials (BOM) Management
- **Product-based BOMs**: Create multiple BOM versions per product
- **Component tracking**: Add/edit/delete materials with quantities and costs
- **Version control**: Draft → Active → Archived workflow
- **Cost roll-up**: Automatic total cost calculation

### 2. Job Costing
- **Cost breakdown**: Materials + Labor + Overhead
- **Real-time calculation**: Based on active BOMs and maintenance logs
- **Export ready**: JSON format for accounting system import

### 3. Material Requirements Planning (MRP)
- **Production planning**: Calculate material needs for any quantity
- **Cost estimation**: Total material costs before production
- **Inventory planning**: Know what to order

### 4. Financial Reporting
- **Date range summaries**: Total jobs, revenue, costs, profit
- **Cost breakdown charts**: Visual pie chart of cost types
- **Trend analysis**: Period-over-period comparison ready

## API Endpoints

### BOM Management API: `/api/finance/boms.php`

**GET - List BOMs for Product**
```http
GET /api/finance/boms.php?action=list&product_id=5
```
Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product_id": 5,
      "version": "1.0",
      "status": "active",
      "created_at": "2026-01-09 10:00:00"
    }
  ]
}
```

**GET - BOM Details with Items**
```http
GET /api/finance/boms.php?action=detail&id=1
```
Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "product_id": 5,
    "product_name": "Widget Assembly",
    "sku": "WDG-001",
    "version": "1.0",
    "status": "active",
    "total_cost": 45.75,
    "items": [
      {
        "id": 1,
        "material_id": 10,
        "material_name": "Steel Plate",
        "material_sku": "STL-PLT-001",
        "quantity": 2.5,
        "unit": "kg",
        "unit_cost": 5.00,
        "sequence": 1
      }
    ]
  }
}
```

**POST - Create BOM**
```http
POST /api/finance/boms.php?action=create
Content-Type: application/json

{
  "product_id": 5,
  "version": "1.0"
}
```

**POST - Add BOM Item**
```http
POST /api/finance/boms.php?action=add_item
Content-Type: application/json

{
  "bom_id": 1,
  "material_id": 10,
  "quantity": 2.5,
  "unit_cost": 5.00
}
```

**POST - Update BOM Item**
```http
POST /api/finance/boms.php?action=update_item
Content-Type: application/json

{
  "item_id": 1,
  "quantity": 3.0,
  "unit_cost": 4.80
}
```

**POST - Delete BOM Item**
```http
POST /api/finance/boms.php?action=delete_item
Content-Type: application/json

{
  "item_id": 1
}
```

**POST - Update BOM Status**
```http
POST /api/finance/boms.php?action=update_status
Content-Type: application/json

{
  "bom_id": 1,
  "status": "active"
}
```

**GET - Material Requirements**
```http
GET /api/finance/boms.php?action=requirements&product_id=5&quantity=100
```
Response:
```json
{
  "success": true,
  "data": [
    {
      "material_id": 10,
      "material_name": "Steel Plate",
      "sku": "STL-PLT-001",
      "unit": "kg",
      "unit_quantity": 2.5,
      "total_quantity": 250.0,
      "unit_cost": 5.00,
      "total_cost": 1250.00
    }
  ]
}
```

### Costing API: `/api/finance/costing.php`

**GET - Job Costing**
```http
GET /api/finance/costing.php?action=job&job_id=123
```
Response:
```json
{
  "success": true,
  "data": {
    "materials": 1250.00,
    "labor": 320.00,
    "overhead": 471.00,
    "total": 2041.00
  }
}
```

**GET - Export Job Costing** (Admin/Manager only)
```http
GET /api/finance/costing.php?action=export&job_id=123
```
Response:
```json
{
  "success": true,
  "data": {
    "job_id": 123,
    "job_number": "JOB-20260109-0001",
    "order_number": "ORD-12345",
    "product": "Widget Assembly",
    "sku": "WDG-001",
    "quantity": 100,
    "costs": {
      "materials": 1250.00,
      "labor": 320.00,
      "overhead": 471.00,
      "total": 2041.00
    },
    "export_date": "2026-01-09 14:30:00",
    "gl_codes": {
      "materials": "5000",
      "labor": "5100",
      "overhead": "5200",
      "wip": "1300"
    }
  }
}
```

**GET - Financial Summary** (Admin/Manager only)
```http
GET /api/finance/costing.php?action=summary&start_date=2026-01-01&end_date=2026-01-31
```
Response:
```json
{
  "success": true,
  "data": {
    "total_jobs": 45,
    "total_revenue": 125000.00,
    "total_costs": 96153.85,
    "total_profit": 28846.15,
    "cost_breakdown": {
      "materials": 55000.00,
      "labor": 28000.00,
      "overhead": 13153.85
    }
  }
}
```

## Usage Examples

### Example 1: Create BOM for New Product
```javascript
// Step 1: Create BOM
const bomResponse = await fetch('/api/finance/boms.php?action=create', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    product_id: 5,
    version: '1.0'
  })
});
const bomResult = await bomResponse.json();
const bomId = bomResult.bom_id;

// Step 2: Add materials
await fetch('/api/finance/boms.php?action=add_item', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    bom_id: bomId,
    material_id: 10,
    quantity: 2.5,
    unit_cost: 5.00
  })
});

// Step 3: Activate BOM
await fetch('/api/finance/boms.php?action=update_status', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    bom_id: bomId,
    status: 'active'
  })
});
```

### Example 2: Calculate Job Cost
```javascript
const response = await fetch('/api/finance/costing.php?action=job&job_id=123');
const result = await response.json();

console.log('Materials:', result.data.materials);
console.log('Labor:', result.data.labor);
console.log('Overhead:', result.data.overhead);
console.log('Total Cost:', result.data.total);
```

### Example 3: Material Requirements Planning
```javascript
const response = await fetch('/api/finance/boms.php?action=requirements&product_id=5&quantity=100');
const result = await response.json();

result.data.forEach(material => {
  console.log(`Need ${material.total_quantity} ${material.unit} of ${material.material_name}`);
  console.log(`Total cost: $${material.total_cost}`);
});
```

## Accounting System Integration

### D365 Finance Integration
The export format is designed to be compatible with Dynamics 365 Finance:

```javascript
// Export job costing data
const response = await fetch('/api/finance/costing.php?action=export&job_id=123');
const exportData = await response.json().data;

// Map to D365 journal entries
const journalEntries = [
  {
    account: exportData.gl_codes.materials,
    debit: exportData.costs.materials,
    description: `Materials - ${exportData.job_number}`
  },
  {
    account: exportData.gl_codes.labor,
    debit: exportData.costs.labor,
    description: `Labor - ${exportData.job_number}`
  },
  {
    account: exportData.gl_codes.overhead,
    debit: exportData.costs.overhead,
    description: `Overhead - ${exportData.job_number}`
  },
  {
    account: exportData.gl_codes.wip,
    credit: exportData.costs.total,
    description: `WIP - ${exportData.job_number}`
  }
];

// Post to D365 via API
await postToD365(journalEntries);
```

### QuickBooks Integration
```javascript
// Export and map to QuickBooks format
const exportData = await fetchJobCostExport(jobId);

const qbJournalEntry = {
  TxnDate: exportData.export_date,
  Line: [
    {
      DetailType: "JournalEntryLineDetail",
      Amount: exportData.costs.materials,
      JournalEntryLineDetail: {
        PostingType: "Debit",
        AccountRef: { value: "Materials Account ID" }
      }
    },
    // ... more lines
  ]
};
```

## Cost Calculation Logic

### Job Cost Formula
```
Total Job Cost = Materials + Labor + Overhead

Materials = (BOM unit cost) × (job quantity)
Labor = (maintenance log hours) × (labor rate per hour)
Overhead = (Materials + Labor) × 30%
```

### Labor Rate Configuration
Default rate: $25.00/hour (configurable in BOMManager class)

```php
// In classes/BOMManager.php, line ~140
$laborRate = 25.00; // Change this value as needed
```

## Database Schema

### Tables Created
- `boms`: BOM headers
- `bom_items`: BOM line items (materials/components)
- `job_costs`: Job cost tracking
- `accounting_exports`: Export audit trail

### Key Relationships
- BOMs → Products (one-to-many)
- BOM Items → BOMs (one-to-many)
- BOM Items → Products (as materials)
- Job Costs → Jobs
- Maintenance Tasks → Jobs (for labor tracking)

## Permissions

### Admin & Planner
- Create/edit BOMs
- Add/update/delete BOM items
- Change BOM status
- Full access to all reports

### Manager
- View BOMs (read-only)
- Access financial reports
- Export job costing data

### Operator
- No access to finance module

## UI Pages

### `/pages/finance/bom-editor.php`
- Product selection dropdown
- BOM list view with versions
- BOM detail view with items table
- Inline editing of quantities and costs
- Add/delete items
- Activate BOM workflow

### `/pages/finance/reports.php`
- Financial summary cards (jobs, revenue, costs, profit)
- Cost breakdown pie chart
- Date range filtering
- Individual job costing lookup
- Export job data as JSON
- Material requirements calculator

## Best Practices

1. **BOM Versioning**: Always create new version when making significant changes
2. **Active BOMs**: Only one active BOM per product at a time
3. **Cost Updates**: Update unit costs regularly for accurate costing
4. **Labor Tracking**: Link maintenance tasks to jobs for accurate labor costs
5. **Regular Exports**: Export job costs periodically for accounting reconciliation
6. **Archive Old BOMs**: Move outdated BOMs to archived status

## Troubleshooting

**Q: BOM total cost is $0?**
A: Ensure unit_cost is set for all BOM items. Unit cost can be NULL if not tracked.

**Q: Job cost calculation returns all zeros?**
A: Check that product has an active BOM and maintenance logs are linked to job.

**Q: Can't activate BOM?**
A: Ensure BOM has at least one item and user has admin/planner permissions.

**Q: Material requirements returns empty?**
A: Ensure product has an active BOM (status = 'active').

## Future Enhancements

- Real-time inventory integration
- Purchase order generation from MRP
- Multi-currency support
- Actual vs. standard cost variance analysis
- Profit margin analysis by product/customer
- Integration with ERP systems (SAP, Oracle)
