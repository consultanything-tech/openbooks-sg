# OpenBooks SG API Reference (v1)

Base URL: `https://your-domain.com/api/v1`

## Authentication

All API endpoints require a Bearer token in the `Authorization` header.

```
Authorization: Bearer YOUR_API_TOKEN
```

Tokens can be generated from the Settings page by an ADMIN user. Requests without a valid token receive a `401 Unauthorized` response.

## Rate Limiting

The API enforces rate limiting to protect server resources. When exceeded, you will receive a `429 Too Many Requests` response with a `Retry-After` header.

| Scope          | Limit            |
|----------------|------------------|
| Standard calls | 60 per minute    |
| Write calls    | 30 per minute    |

## Error Response Format

All error responses follow a consistent JSON structure:

```json
{
    "success": false,
    "message": "Description of what went wrong",
    "errors": {
        "field_name": ["Validation error message"]
    }
}
```

| HTTP Status | Meaning                          |
|-------------|----------------------------------|
| `400`       | Bad request / validation failure |
| `401`       | Unauthenticated                  |
| `403`       | Forbidden (insufficient role)    |
| `404`       | Resource not found               |
| `422`       | Unprocessable entity             |
| `429`       | Rate limit exceeded              |
| `500`       | Internal server error            |

---

## Invoices

### List Invoices

```
GET /invoices
```

**Query Parameters**

| Parameter | Type   | Description                  |
|-----------|--------|------------------------------|
| `status`  | string | Filter by status (draft, sent, paid, overdue, partial) |
| `page`    | int    | Page number (default: 1)     |

**Response** `200 OK`

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "invoice_number": "INV-2026-001",
            "customer": {
                "id": 5,
                "name": "Acme Pte Ltd"
            },
            "issue_date": "2026-09-01",
            "due_date": "2026-09-30",
            "subtotal": 1500.00,
            "tax_total": 135.00,
            "total": 1635.00,
            "amount_paid": 0.00,
            "balance_due": 1635.00,
            "status": "sent",
            "currency": "SGD"
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 42,
        "per_page": 15
    }
}
```

### Get Invoice

```
GET /invoices/{id}
```

**Response** `200 OK`

```json
{
    "success": true,
    "data": {
        "id": 1,
        "invoice_number": "INV-2026-001",
        "customer": {
            "id": 5,
            "name": "Acme Pte Ltd",
            "email": "billing@acme.sg"
        },
        "issue_date": "2026-09-01",
        "due_date": "2026-09-30",
        "line_items": [
            {
                "description": "Consulting Services - September",
                "quantity": 10,
                "unit_price": 150.00,
                "tax_rate": 9,
                "amount": 1500.00
            }
        ],
        "subtotal": 1500.00,
        "tax_total": 135.00,
        "total": 1635.00,
        "amount_paid": 0.00,
        "balance_due": 1635.00,
        "status": "sent",
        "currency": "SGD",
        "notes": "Payment via PayNow or bank transfer.",
        "payments": []
    }
}
```

### Create Invoice

```
POST /invoices
```

**Request Body**

```json
{
    "customer_id": 5,
    "issue_date": "2026-09-01",
    "due_date": "2026-09-30",
    "currency": "SGD",
    "notes": "Payment via PayNow or bank transfer.",
    "line_items": [
        {
            "description": "Consulting Services - September",
            "quantity": 10,
            "unit_price": 150.00,
            "tax_rate": 9
        }
    ]
}
```

**Response** `201 Created`

```json
{
    "success": true,
    "message": "Invoice created successfully",
    "data": {
        "id": 43,
        "invoice_number": "INV-2026-043"
    }
}
```

### Update Invoice

```
PUT /invoices/{id}
```

Accepts the same body as Create Invoice. Only `draft` and `sent` invoices can be updated.

**Response** `200 OK`

```json
{
    "success": true,
    "message": "Invoice updated successfully",
    "data": {
        "id": 43,
        "invoice_number": "INV-2026-043"
    }
}
```

### Delete Invoice

```
DELETE /invoices/{id}
```

Only `draft` invoices can be deleted.

**Response** `200 OK`

```json
{
    "success": true,
    "message": "Invoice deleted successfully"
}
```

## Customers

### List Customers

```
GET /customers
```

**Query Parameters**

| Parameter | Type   | Description              |
|-----------|--------|--------------------------|
| `search`  | string | Search by name or email  |
| `page`    | int    | Page number (default: 1) |

**Response** `200 OK`

```json
{
    "success": true,
    "data": [
        {
            "id": 5,
            "name": "Acme Pte Ltd",
            "email": "billing@acme.sg",
            "phone": "+65 6123 4567",
            "uen": "202012345A",
            "address": "1 Raffles Place, Singapore 048616",
            "currency": "SGD",
            "balance": 3270.00
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 18,
        "per_page": 15
    }
}
```

### Get Customer

```
GET /customers/{id}
```

**Response** `200 OK`

```json
{
    "success": true,
    "data": {
        "id": 5,
        "name": "Acme Pte Ltd",
        "email": "billing@acme.sg",
        "phone": "+65 6123 4567",
        "uen": "202012345A",
        "address": "1 Raffles Place, Singapore 048616",
        "currency": "SGD",
        "balance": 3270.00,
        "notes": null,
        "created_at": "2026-01-15T08:30:00Z"
    }
}
```

### Create Customer

```
POST /customers
```

**Request Body**

```json
{
    "name": "Tan Holdings Pte Ltd",
    "email": "accounts@tanh.sg",
    "phone": "+65 9876 5432",
    "uen": "202198765B",
    "address": "10 Anson Road, Singapore 079903",
    "currency": "SGD"
}
```

**Response** `201 Created`

```json
{
    "success": true,
    "message": "Customer created successfully",
    "data": {
        "id": 19,
        "name": "Tan Holdings Pte Ltd"
    }
}
```

### Update Customer

```
PUT /customers/{id}
```

Accepts the same body as Create Customer.

**Response** `200 OK`

```json
{
    "success": true,
    "message": "Customer updated successfully",
    "data": {
        "id": 19,
        "name": "Tan Holdings Pte Ltd"
    }
}
```

## Quotes

### List Quotes

```
GET /quotes
```

**Query Parameters**

| Parameter | Type   | Description                            |
|-----------|--------|----------------------------------------|
| `status`  | string | Filter by status (draft, sent, accepted, declined) |
| `page`    | int    | Page number (default: 1)               |

**Response** `200 OK`

```json
{
    "success": true,
    "data": [
        {
            "id": 3,
            "quote_number": "QUO-2026-003",
            "customer": {
                "id": 5,
                "name": "Acme Pte Ltd"
            },
            "quote_date": "2026-08-15",
            "expiry_date": "2026-09-15",
            "total": 4350.00,
            "status": "sent",
            "currency": "SGD"
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 8,
        "per_page": 15
    }
}
```

### Get Quote

```
GET /quotes/{id}
```

**Response** `200 OK`

```json
{
    "success": true,
    "data": {
        "id": 3,
        "quote_number": "QUO-2026-003",
        "customer": {
            "id": 5,
            "name": "Acme Pte Ltd"
        },
        "quote_date": "2026-08-15",
        "expiry_date": "2026-09-15",
        "line_items": [
            {
                "description": "Website Redesign",
                "quantity": 1,
                "unit_price": 4000.00,
                "tax_rate": 9,
                "amount": 4000.00
            }
        ],
        "subtotal": 4000.00,
        "tax_total": 350.00,
        "total": 4350.00,
        "status": "sent",
        "currency": "SGD",
        "notes": "Valid for 30 days."
    }
}
```

### Create Quote

```
POST /quotes
```

**Request Body**

```json
{
    "customer_id": 5,
    "quote_date": "2026-09-20",
    "expiry_date": "2026-10-20",
    "currency": "SGD",
    "notes": "Valid for 30 days.",
    "line_items": [
        {
            "description": "Website Redesign",
            "quantity": 1,
            "unit_price": 4000.00,
            "tax_rate": 9
        }
    ]
}
```

**Response** `201 Created`

```json
{
    "success": true,
    "message": "Quote created successfully",
    "data": {
        "id": 9,
        "quote_number": "QUO-2026-009"
    }
}
```

## Payments

### List Payments

```
GET /payments
```

**Query Parameters**

| Parameter    | Type   | Description                  |
|--------------|--------|------------------------------|
| `invoice_id` | int    | Filter by invoice            |
| `page`       | int    | Page number (default: 1)     |

**Response** `200 OK`

```json
{
    "success": true,
    "data": [
        {
            "id": 12,
            "invoice_id": 1,
            "invoice_number": "INV-2026-001",
            "amount": 1635.00,
            "payment_date": "2026-09-15",
            "method": "paynow",
            "reference": "PAY-20260915-0042",
            "notes": null
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 35,
        "per_page": 15
    }
}
```

### Record Payment

```
POST /payments
```

**Request Body**

```json
{
    "invoice_id": 1,
    "amount": 1635.00,
    "payment_date": "2026-09-15",
    "method": "paynow",
    "reference": "PAY-20260915-0042",
    "notes": "Full payment received"
}
```

**Response** `201 Created`

```json
{
    "success": true,
    "message": "Payment recorded successfully",
    "data": {
        "id": 36,
        "invoice_id": 1,
        "amount": 1635.00
    }
}
```

## Bills

### List Bills

```
GET /bills
```

**Query Parameters**

| Parameter | Type   | Description                                |
|-----------|--------|--------------------------------------------|
| `status`  | string | Filter by status (draft, open, paid, partial) |
| `page`    | int    | Page number (default: 1)                   |

**Response** `200 OK`

```json
{
    "success": true,
    "data": [
        {
            "id": 7,
            "bill_number": "BILL-2026-007",
            "vendor": {
                "id": 3,
                "name": "CloudHost Pte Ltd"
            },
            "bill_date": "2026-09-01",
            "due_date": "2026-09-30",
            "total": 545.00,
            "amount_paid": 545.00,
            "balance_due": 0.00,
            "status": "paid",
            "currency": "SGD"
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 22,
        "per_page": 15
    }
}
```

### Get Bill

```
GET /bills/{id}
```

**Response** `200 OK`

```json
{
    "success": true,
    "data": {
        "id": 7,
        "bill_number": "BILL-2026-007",
        "vendor": {
            "id": 3,
            "name": "CloudHost Pte Ltd"
        },
        "bill_date": "2026-09-01",
        "due_date": "2026-09-30",
        "line_items": [
            {
                "description": "VPS Hosting - September",
                "quantity": 1,
                "unit_price": 500.00,
                "tax_rate": 9,
                "amount": 500.00
            }
        ],
        "subtotal": 500.00,
        "tax_total": 45.00,
        "total": 545.00,
        "amount_paid": 545.00,
        "balance_due": 0.00,
        "status": "paid",
        "currency": "SGD",
        "notes": null,
        "payments": [
            {
                "id": 8,
                "amount": 545.00,
                "payment_date": "2026-09-10",
                "method": "bank_transfer"
            }
        ]
    }
}
```

## Items

### List Items

```
GET /items
```

**Query Parameters**

| Parameter | Type   | Description              |
|-----------|--------|--------------------------|
| `search`  | string | Search by name or SKU    |
| `type`    | string | Filter by type (product, service) |
| `page`    | int    | Page number (default: 1) |

**Response** `200 OK`

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Consulting Hour",
            "sku": "SVC-001",
            "type": "service",
            "unit_price": 150.00,
            "tax_rate": 9,
            "unit": "hour",
            "description": "Professional consulting services"
        },
        {
            "id": 2,
            "name": "Widget A",
            "sku": "PRD-001",
            "type": "product",
            "unit_price": 25.00,
            "tax_rate": 9,
            "unit": "piece",
            "description": "Standard widget",
            "stock_quantity": 150
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 24,
        "per_page": 15
    }
}
```

## Reports

### Profit & Loss

```
GET /reports/profit-loss
```

**Query Parameters**

| Parameter   | Type   | Description                          |
|-------------|--------|--------------------------------------|
| `from_date` | string | Start date in `YYYY-MM-DD` format    |
| `to_date`   | string | End date in `YYYY-MM-DD` format      |

**Response** `200 OK`

```json
{
    "success": true,
    "data": {
        "period": {
            "from": "2026-01-01",
            "to": "2026-09-30"
        },
        "income": [
            { "account": "Sales Revenue", "amount": 125000.00 },
            { "account": "Service Revenue", "amount": 48000.00 }
        ],
        "total_income": 173000.00,
        "expenses": [
            { "account": "Rent", "amount": 24000.00 },
            { "account": "Salaries", "amount": 72000.00 },
            { "account": "Utilities", "amount": 3600.00 }
        ],
        "total_expenses": 99600.00,
        "net_income": 73400.00,
        "currency": "SGD"
    }
}
```

### Balance Sheet

```
GET /reports/balance-sheet
```

**Query Parameters**

| Parameter | Type   | Description                       |
|-----------|--------|-----------------------------------|
| `as_of`   | string | Date in `YYYY-MM-DD` format       |

**Response** `200 OK`

```json
{
    "success": true,
    "data": {
        "as_of": "2026-09-30",
        "assets": [
            { "account": "Cash - DBS", "amount": 85000.00 },
            { "account": "Accounts Receivable", "amount": 32000.00 },
            { "account": "Inventory", "amount": 12500.00 }
        ],
        "total_assets": 129500.00,
        "liabilities": [
            { "account": "Accounts Payable", "amount": 8500.00 },
            { "account": "GST Payable", "amount": 4200.00 }
        ],
        "total_liabilities": 12700.00,
        "equity": [
            { "account": "Retained Earnings", "amount": 43400.00 },
            { "account": "Owner's Equity", "amount": 73400.00 }
        ],
        "total_equity": 116800.00,
        "currency": "SGD"
    }
}
```

## Company

### Get Company Profile

```
GET /company
```

**Response** `200 OK`

```json
{
    "success": true,
    "data": {
        "name": "My Business Pte Ltd",
        "uen": "202012345A",
        "email": "admin@mybusiness.sg",
        "phone": "+65 6123 4567",
        "address": "1 Raffles Place, #20-01, Singapore 048616",
        "base_currency": "SGD",
        "gst_registered": true,
        "gst_number": "M12345678A",
        "fiscal_year_start": "01-01",
        "logo_url": null
    }
}
```
