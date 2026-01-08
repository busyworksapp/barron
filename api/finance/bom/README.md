# Finance/BOM API Documentation

## Overview
The Finance/BOM module provides comprehensive Bill of Materials management with automatic cost calculation and component tracking.

---

## API Endpoints

### 1. Get BOM Statistics
**Endpoint:** `GET /api/finance/bom/stats.php`

**Description:** Returns dashboard statistics for BOM overview

**Authentication:** Required (`finance.view_bom`)

**Response:**
```json
{
  "success": true,
  "data": {
    "active_count": 15,
    "approved_count": 15,
    "draft_count": 3,
    "avg_cost": "1250.50"
  }
}
```

---

### 2. List BOMs
**Endpoint:** `GET /api/finance/bom/list.php`

**Description:** Returns filtered list of BOMs with component counts

**Authentication:** Required (`finance.view_bom`)

**Query Parameters:**
- `search` (optional) - Search BOM number, product name, version, or description
- `status` (optional) - Filter by status (draft/active/obsolete)
- `product_id` (optional) - Filter by product ID

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "bom_number": "BOM202601001",
      "product_id": 5,
      "product_name": "Widget Assembly",
      "version": "1.0",
      "status": "active",
      "description": "Standard production BOM",
      "overhead_percentage": "10.00",
      "total_cost": "250.00",
      "component_count": 8,
      "created_at": "2026-01-08 10:30:00",
      "updated_at": "2026-01-08 10:30:00"
    }
  ]
}
```

---

### 3. Get Single BOM
**Endpoint:** `GET /api/finance/bom/get.php?id={id}`

**Description:** Returns detailed BOM information including all components

**Authentication:** Required (`finance.view_bom`)

**Query Parameters:**
- `id` (required) - BOM ID

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "bom_number": "BOM202601001",
    "product_id": 5,
    "product_name": "Widget Assembly",
    "product_code": "WDG-001",
    "version": "1.0",
    "status": "active",
    "description": "Standard production BOM",
    "overhead_percentage": "10.00",
    "total_cost": "250.00",
    "notes": "Approved by production team",
    "created_by": 1,
    "created_at": "2026-01-08 10:30:00",
    "updated_at": "2026-01-08 10:30:00",
    "components": [
      {
        "id": 1,
        "bom_id": 1,
        "component_name": "Steel Sheet 2mm",
        "quantity": "5.000",
        "unit": "kg",
        "unit_cost": "25.00",
        "total_cost": "125.00",
        "created_at": "2026-01-08 10:30:00"
      },
      {
        "id": 2,
        "bom_id": 1,
        "component_name": "Fasteners M6",
        "quantity": "20.000",
        "unit": "pcs",
        "unit_cost": "2.50",
        "total_cost": "50.00",
        "created_at": "2026-01-08 10:30:00"
      }
    ]
  }
}
```

---

### 4. Create BOM
**Endpoint:** `POST /api/finance/bom/create.php`

**Description:** Creates a new Bill of Materials with components

**Authentication:** Required (`finance.edit_bom`)

**Request Body (Form Data):**
- `bom_number` (required) - Unique BOM identifier
- `product_id` (required) - Product ID
- `version` (required) - Version number (e.g., "1.0", "2.1")
- `status` (required) - Status (draft/active/obsolete)
- `description` (optional) - BOM description
- `overhead_percentage` (optional) - Overhead % (default: 0)
- `total_cost` (required) - Total calculated cost
- `notes` (optional) - Additional notes
- `components` (required) - JSON array of components

**Components JSON Format:**
```json
[
  {
    "component_name": "Steel Sheet 2mm",
    "quantity": "5.00",
    "unit": "kg",
    "unit_cost": "25.00",
    "total_cost": "125.00"
  },
  {
    "component_name": "Fasteners M6",
    "quantity": "20.00",
    "unit": "pcs",
    "unit_cost": "2.50",
    "total_cost": "50.00"
  }
]
```

**Response:**
```json
{
  "success": true,
  "message": "BOM created successfully",
  "bom_id": 1
}
```

**Error Responses:**
- 400 - Missing required fields
- 400 - Invalid status
- 400 - Duplicate BOM number
- 400 - At least one component required
- 403 - Unauthorized access
- 500 - Server error

---

### 5. Update BOM
**Endpoint:** `POST /api/finance/bom/update.php`

**Description:** Updates existing BOM and replaces all components

**Authentication:** Required (`finance.edit_bom`)

**Request Body (Form Data):**
- `bom_id` (required) - BOM ID to update
- `bom_number` (required) - Unique BOM identifier
- `product_id` (required) - Product ID
- `version` (required) - Version number
- `status` (required) - Status (draft/active/obsolete)
- `description` (optional) - BOM description
- `overhead_percentage` (optional) - Overhead %
- `total_cost` (required) - Total calculated cost
- `notes` (optional) - Additional notes
- `components` (required) - JSON array of components

**Response:**
```json
{
  "success": true,
  "message": "BOM updated successfully"
}
```

**Error Responses:**
- 400 - Missing required fields
- 400 - Invalid status
- 400 - BOM not found
- 400 - Duplicate BOM number
- 400 - At least one component required
- 403 - Unauthorized access
- 500 - Server error

---

## Data Models

### BOM Table Structure
```sql
CREATE TABLE bom (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bom_number VARCHAR(50) UNIQUE NOT NULL,
    product_id INT NOT NULL,
    version VARCHAR(20) NOT NULL,
    status ENUM('draft', 'active', 'obsolete') DEFAULT 'draft',
    description TEXT,
    overhead_percentage DECIMAL(5,2) DEFAULT 0,
    total_cost DECIMAL(12,2) NOT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### BOM Components Table Structure
```sql
CREATE TABLE bom_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bom_id INT NOT NULL,
    component_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bom_id) REFERENCES bom(id) ON DELETE CASCADE
);
```

---

## Business Logic

### Cost Calculation
Total BOM Cost = Material Cost + Labor Cost + Overhead

**Material Cost:** Sum of all component costs
```
Material Cost = Σ(quantity × unit_cost) for all components
```

**Overhead Cost:** Percentage of material + labor
```
Overhead Cost = (Material Cost + Labor Cost) × (overhead_percentage / 100)
```

**Total Cost:**
```
Total Cost = Material Cost + Labor Cost + Overhead Cost
```

### Component Units
Supported units:
- `pcs` - Pieces
- `kg` - Kilograms
- `m` - Meters
- `l` - Liters
- `box` - Boxes
- `set` - Sets

### Status States
- **Draft** - BOM under development, not approved
- **Active** - Current approved BOM for production
- **Obsolete** - Deprecated BOM, kept for historical reference

### Version Control
- Version format: Major.Minor (e.g., "1.0", "2.1", "3.5")
- Multiple versions can exist per product
- Only one version should be "active" at a time (recommended)

---

## Usage Examples

### Example 1: Create a New BOM

**JavaScript:**
```javascript
const formData = new FormData();
formData.append('bom_number', 'BOM202601001');
formData.append('product_id', '5');
formData.append('version', '1.0');
formData.append('status', 'draft');
formData.append('description', 'Standard production BOM');
formData.append('overhead_percentage', '10');
formData.append('total_cost', '250.00');

const components = [
  {
    component_name: 'Steel Sheet 2mm',
    quantity: '5.00',
    unit: 'kg',
    unit_cost: '25.00',
    total_cost: '125.00'
  },
  {
    component_name: 'Fasteners M6',
    quantity: '20.00',
    unit: 'pcs',
    unit_cost: '2.50',
    total_cost: '50.00'
  }
];

formData.append('components', JSON.stringify(components));

fetch('/api/finance/bom/create.php', {
  method: 'POST',
  body: formData
})
.then(response => response.json())
.then(data => console.log(data));
```

### Example 2: Get BOMs for a Specific Product

**cURL:**
```bash
curl -X GET "http://yourdomain.com/api/finance/bom/list.php?product_id=5" \
  -H "Cookie: PHPSESSID=your_session_id"
```

### Example 3: Update BOM Status

**JavaScript:**
```javascript
const formData = new FormData();
formData.append('bom_id', '1');
formData.append('bom_number', 'BOM202601001');
formData.append('product_id', '5');
formData.append('version', '1.0');
formData.append('status', 'active'); // Changed from draft to active
formData.append('overhead_percentage', '10');
formData.append('total_cost', '250.00');
formData.append('components', JSON.stringify(components));

fetch('/api/finance/bom/update.php', {
  method: 'POST',
  body: formData
})
.then(response => response.json())
.then(data => console.log(data));
```

---

## Error Handling

All API endpoints return standardized error responses:

```json
{
  "success": false,
  "message": "Error description here"
}
```

Common HTTP Status Codes:
- `200` - Success
- `400` - Bad Request (validation error)
- `403` - Forbidden (permission denied)
- `404` - Not Found
- `500` - Internal Server Error

---

## Security

### Authentication
- All endpoints require active session
- Session validated via `Auth` class
- 30-minute timeout

### Authorization
- `finance.view_bom` - Required to view BOMs
- `finance.edit_bom` - Required to create/update BOMs

### Data Validation
- BOM number uniqueness enforced
- Status validated against allowed values
- Product existence verified
- Component array must contain at least one item
- All numeric fields validated

### Transaction Support
- Create and update operations use database transactions
- Rollback on error ensures data consistency
- Activity logging for audit trail

---

## Activity Logging

All BOM operations are logged:

**Create BOM:**
```
Action: create_bom
Details: Created BOM {bom_number} for product with {count} components
```

**Update BOM:**
```
Action: update_bom
Details: Updated BOM {bom_number} with {count} components
```

---

## Performance Considerations

- Component deletion/insertion done in single transaction
- Indexes on `bom_number`, `product_id`, `status`
- Component count calculated via subquery (cached in list view)
- JOIN queries optimized for product details

---

## Future Enhancements

- [ ] BOM comparison between versions
- [ ] Cost history tracking
- [ ] Material availability checking
- [ ] Multi-currency support
- [ ] Export to PDF/Excel
- [ ] BOM cloning functionality
- [ ] Approval workflow
- [ ] Change request tracking

---

**Last Updated:** January 8, 2026  
**API Version:** 1.0  
**Module Version:** 1.1
