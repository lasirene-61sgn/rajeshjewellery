# API Search & Filter Documentation (Work Orders & Customer Orders)

This document provides complete reference documentation for searching, filtering, and pagination across the **Work Orders** and **Customer Orders** APIs.

---

## 1. Endpoints Overview

All endpoints automatically detect whether an **Admin** or a **Craftsman** is making the request using their Sanctum bearer token.

| Feature | Primary Endpoint | Aliases | Allowed Roles |
|---|---|---|---|
| **Work Orders Listing** | `GET /api/work-orders` | `GET /api/common/work-orders`<br>`GET /api/admin/work-orders`<br>`GET /api/craftsman/work-orders` | Admin, Craftsman |
| **Work Orders Filter Options (All)** | `GET /api/work-orders/filters` | `GET /api/common/work-orders/filters`<br>`GET /api/work-orders?filter={type}` | Admin, Craftsman |
| **Design Codes Only** | `GET /api/work-orders/design-codes` | `GET /api/work-orders/filters?type=design_code` | Admin, Craftsman |
| **Nicknames Only** | `GET /api/work-orders/nicknames` | `GET /api/work-orders/filters?type=nickname` | Admin, Craftsman |
| **Order Types Only** | `GET /api/work-orders/order-types` | `GET /api/work-orders/filters?type=order_type` | Admin, Craftsman |
| **Product Names Only** | `GET /api/work-orders/product-names` | `GET /api/work-orders/filters?type=product_name` | Admin, Craftsman |
| **Craftsmen List Only** | `GET /api/work-orders/craftsmen` | `GET /api/work-orders/filters?type=craftsman` | **Admin Only** |
| **Categories Only** | `GET /api/work-orders/categories` | `GET /api/work-orders/filters?type=category` | Admin, Craftsman |
| **Subcategories Only** | `GET /api/work-orders/subcategories` | `GET /api/work-orders/filters?type=subcategory` | Admin, Craftsman |
| **Work Orders Tabs & Live Counts** | `GET /api/work-orders/tabs` | `GET /api/common/work-orders/tabs`<br>`GET /api/admin/work-orders/tabs`<br>`GET /api/craftsman/work-orders/tabs` | Admin, Craftsman |
| **Work Order Print Slip** | `GET /api/work-orders/{id}/print` | `GET /api/common/work-orders/{id}/print`<br>`GET /api/admin/work-orders/{id}/print`<br>`GET /api/craftsman/work-orders/{id}/print` | Admin, Craftsman |
| **Bulk Work Orders Print Slips** | `POST /api/work-orders/bulk-print` | `POST /api/common/work-orders/bulk-print`<br>`POST /api/admin/work-orders/bulk-print`<br>`POST /api/craftsman/work-orders/bulk-print` | Admin, Craftsman |
| **Customer Orders Listing** | `GET /api/customer-orders` | `GET /api/orders`<br>`GET /api/common/customer-orders`<br>`GET /api/common/orders` | Admin, Craftsman |
| **Customer Orders Filters** | `GET /api/customer-orders/filters` | `GET /api/orders/filters`<br>`GET /api/customer-orders?filter={type}` | Admin, Craftsman |
| **Customer Order Types Only** | `GET /api/customer-orders/order-types` | `GET /api/orders/order-types` | Admin, Craftsman |

### Authentication Header
```http
Authorization: Bearer <your_sanctum_token>
Accept: application/json
```

---

## 2. Work Orders Filters & Search (`/api/work-orders`)

The API supports identical filters and multi-token reference searches as available on the Web Admin panel.

### Supported Query Parameters

| Parameter | Type | Example | Description |
|---|---|---|---|
| `search` | `string` | `WO-2026-0001` or `REF001, REF002, REF003` | Searches across `work_order_no`, `reference_no`, `product_name`, `design_code`, `design_nickname`, `seal`, and assigned Craftsman name/mobile.<br>**Supports multiple tokens** separated by commas, semicolons, newlines, or spaces. |
| `ref_no`<br>*(or `reference_no`)* | `string` or `array` | `REF-101, REF-102`<br>or `ref_no[]=REF-101&ref_no[]=REF-102` | Dedicated multi-reference filter. Ideal for Excel/CSV column pastes, comma-separated lists, or array inputs. Exact-matches reference numbers and work order numbers. |
| `tab`<br>*(or `status`)* | `string` | `allocated` | Filters by status tab:<br>• `all` (All orders)<br>• `pending` (Unassigned)<br>• `allocated` (Assigned to craftsman)<br>• `in_process` (In fabrication)<br>• `for_approval` (Submitted for QC)<br>• `partially_completed` (Partial quantity approved)<br>• `completed` (Finished)<br>• `rejected` (Rejected by craftsman/admin)<br>• `returned` (Returned by QC)<br>• `overdue` (Due date past today and not completed) |
| `category` | `string` | `Ring`, `Bangle`, `Chain` | Filter by jewelry category. |
| `subcategory` | `string` | `Casting`, `Handmade` | Filter by jewelry subcategory. |
| `craftsman_id` | `integer` | `11` | Filter by assigned Craftsman ID. *(On Admin side, also matches craftsmen linked to the work order's design code)*. |
| `design_code` | `string` | `DC-001` | Filter by exact design code. |
| `product_name` | `string` | `Peacock Ring` | Partial/like search on product name. |
| `nickname` | `string` | `Peacock` | Filter by design nickname (matches `design_nickname` or linked `design_codes.nickname`). |
| `order_type` | `string` or `array` | `Regular` or `Regular,Custom` | Filter by order type. Supports single string, comma-separated list, or array (`order_type[]=Regular&order_type[]=Custom`). |
| `from_date`<br>*(or `date_from`)* | `date` (`Y-m-d`) | `2026-11-01` | Filter orders created on or after this date. |
| `to_date`<br>*(or `date_to`)* | `date` (`Y-m-d`) | `2026-11-30` | Filter orders created on or before this date. |
| `due_date_from` | `date` (`Y-m-d`) | `2026-11-15` | Filter orders with delivery due date on or after this date. |
| `due_date_to` | `date` (`Y-m-d`) | `2026-11-20` | Filter orders with delivery due date on or before this date. |
| `return_due_date_from` | `date` (`Y-m-d`) | `2026-11-10` | Filter by QC return due date starting from this date. |
| `return_due_date_to` | `date` (`Y-m-d`) | `2026-11-15` | Filter by QC return due date up to this date. |
| `per_page`<br>*(or `limit`)* | `integer` | `10`, `15`, `25`, `50`, `100` | Items per page (default: `15`, min: `1`, max: `100`). |
| `page` | `integer` | `2` | Page number for pagination. |

---

## 3. Dedicated Filter Options Endpoints (Dropdown / Select Pickers)

To populate filter dropdowns or select chips in your mobile or web app without fetching heavy work order lists, use `GET /api/work-orders/filters`.

You can request a single filter list (e.g. design code only) or all filter options at once.

### Direct Main API Query Flags (`/api/work-orders`)

You can query filter options directly on the main endpoint (`/api/work-orders`) without navigating to separate route paths:

| Desired Filter | Single Main Endpoint Query | Direct Route Alternative | Role Access |
|---|---|---|---|
| **Design Nicknames Only** | `GET /api/work-orders?design_nickname` | `GET /api/work-orders/nicknames` | Admin & Craftsman |
| **Product Names Only** | `GET /api/work-orders?product_name` | `GET /api/work-orders/product-names` | Admin & Craftsman |
| **Design Codes Only** | `GET /api/work-orders?design_code` | `GET /api/work-orders/design-codes` | Admin & Craftsman |
| **Craftsman Names Only** | `GET /api/work-orders?craftsman_name` | `GET /api/work-orders/craftsmen` | **Admin Only** *(Craftsman panel is restricted / returns 403)* |
| **Order Types Only** | `GET /api/work-orders?order_type` | `GET /api/work-orders/order-types` | Admin & Craftsman |
| **Categories Only** | `GET /api/work-orders?category` | `GET /api/work-orders/categories` | Admin & Craftsman |
| **Subcategories Only** | `GET /api/work-orders?subcategory` | `GET /api/work-orders/subcategories` | Admin & Craftsman |

> [!TIP]
> **Single Filter Response Only**: When queried with any of these filter flags, the API returns **ONLY** the separate list for that filter (no heavy order records, no tab counts, no pagination).
>
> **Typeahead / Autocomplete Search**: You can add `&q=...` to filter items (e.g. `GET /api/work-orders?design_nickname&q=Pea`).
>
> **Order Table Filter vs Filter Options**: When passing `tab=...` or `page=...` alongside a filter value (e.g. `GET /api/work-orders?tab=allocated&design_code=DC-001`), the API returns the **filtered work orders list**. When queried as a bare flag (e.g. `GET /api/work-orders?design_code`), it returns the **filter options list**.

### Example Responses for Single Filters

#### 1. Design Code (`GET /api/work-orders/design-codes` or `?type=design_code`)
```json
{
    "success": true,
    "role": "admin",
    "filter": "design_code",
    "data": ["DC-001", "DC-002", "DC-003"],
    "codes": ["DC-001", "DC-002", "DC-003"],
    "items": [
        { "id": 1, "code": "DC-001", "nickname": "Peacock" },
        { "id": 2, "code": "DC-002", "nickname": "Lotus" }
    ]
}
```

#### 2. Nickname Only (`GET /api/work-orders/nicknames` or `?type=nickname`)
```json
{
    "success": true,
    "role": "admin",
    "filter": "design_nickname",
    "data": ["Lotus", "Peacock", "Temple"],
    "nicknames": ["Lotus", "Peacock", "Temple"],
    "items": ["Lotus", "Peacock", "Temple"]
}
```

#### 3. Order Type Only (`GET /api/work-orders/order-types` or `?type=order_type`)
```json
{
    "success": true,
    "role": "admin",
    "filter": "order_type",
    "data": ["Regular", "Custom", "Urgent", "Repair"],
    "order_types": ["Regular", "Custom", "Urgent", "Repair"],
    "items": ["Regular", "Custom", "Urgent", "Repair"]
}
```

#### 4. Craftsman Only (`GET /api/work-orders/craftsmen` or `?type=craftsman`) — *Admin Only*
```json
{
    "success": true,
    "role": "admin",
    "filter": "craftsman",
    "data": [
        { "id": 11, "name": "Ramesh", "mobile": "9876543210", "email": "ramesh@example.com" },
        { "id": 12, "name": "Suresh", "mobile": "9876543211", "email": "suresh@example.com" }
    ],
    "names": ["Ramesh", "Suresh"]
}
```
*(If requested by a Craftsman, returns `{"success": false, "message": "Craftsman filter is available for Admin only."}`)*

#### 5. All Filter Options Combined (`GET /api/work-orders/filters`)
If no `type` is specified, all filter lists are returned together:
```json
{
    "success": true,
    "role": "admin",
    "data": {
        "design_codes": ["DC-001", "DC-002"],
        "designs": [ { "id": 1, "code": "DC-001", "nickname": "Peacock" } ],
        "product_names": ["Gold Ring 22K", "Diamond Bangle"],
        "order_types": ["Regular", "Custom", "Urgent", "Repair"],
        "craftsmen": [ { "id": 11, "name": "Ramesh", "mobile": "9876543210" } ],
        "design_nicknames": ["Peacock", "Lotus"],
        "categories": ["Ring", "Bangle"],
        "subcategories": ["Casting", "Handmade"]
    }
}
```
*(For Craftsman panel, the `craftsmen` key is completely omitted)*.

---

## 4. Customer Orders Filters & Search (`/api/customer-orders` or `/api/orders`)

### Supported Query Parameters

| Parameter | Type | Example | Description |
|---|---|---|---|
| `search` | `string` | `REF-9021` or `REF1, REF2, REF3` | Searches across `reference_number`, `product_name`, `notes`, `order_type`, `size`, and Craftsman name/mobile.<br>**Supports multiple tokens** separated by commas, newlines, or spaces. |
| `ref_no`<br>*(or `reference_number`, `reference_no`)* | `string` or `array` | `REF-9001, REF-9002` | Dedicated multi-reference filter. Supports comma/space/newline separated strings or array. |
| `status`<br>*(or `tab`)* | `string` | `in_process` | Filter by order status:<br>• `all` (All orders)<br>• `pending` (Unallocated)<br>• `allocated` (Assigned)<br>• `in_process` *(or `in_progress`)*<br>• `returned`<br>• `completed` |
| `craftsman_id` | `integer` | `11` | Filter by assigned Craftsman ID (Admin only). |
| `product_name` | `string` | `Gold Necklace` | Partial/like search on product name. |
| `order_type` | `string` or `array` | `Urgent,Custom` | Filter by order type. |
| `from_date`<br>*(or `date_from`)* | `date` (`Y-m-d`) | `2026-11-01` | Filter orders created on or after this date. |
| `to_date`<br>*(or `date_to`)* | `date` (`Y-m-d`) | `2026-11-30` | Filter orders created on or before this date. |
| `due_date_from` | `date` (`Y-m-d`) | `2026-11-15` | Filter orders due on or after this date. |
| `due_date_to` | `date` (`Y-m-d`) | `2026-11-20` | Filter orders due on or before this date. |
| `per_page`<br>*(or `limit`)* | `integer` | `15` | Items per page (default: `15`, max: `100`). |
| `page` | `integer` | `1` | Page number. |

---

## 5. Multi-Reference & Excel Column Paste Support

Both `search` and `ref_no` handle bulk multi-item lookups automatically:

1. **Comma / Semicolon separated**:  
   `ref_no=REF001,REF002,REF003`
2. **Newline / Excel column paste** (URI encoded `\n` or `\r\n`):  
   `ref_no=REF001%0AREF002%0AREF003`
3. **Space-separated tokens**:  
   `search=WO-2026-0001 WO-2026-0002 WO-2026-0003`
4. **Array parameters**:  
   `ref_no[]=REF001&ref_no[]=REF002`

The backend runs indexed exact `whereIn` checks alongside multi-token `LIKE` clauses for unmatched tokens.

---

## 6. API Response Schema & Pagination Structure

The API returns a clean, non-duplicated response structure. All pagination keys are embedded directly at the `data` level alongside the active tab, tab counts, and items:

```json
{
    "success": true,
    "role": "admin",
    "data": {
        "current_tab": "allocated",
        "counts": {
            "all": 35,
            "pending": 5,
            "allocated": 12,
            "in_process": 8,
            "for_approval": 4,
            "partially_completed": 2,
            "completed": 10,
            "rejected": 1,
            "returned": 1,
            "overdue": 3
        },
        "tabs": [
            {
                "key": "all",
                "label": "All Orders",
                "count": 35,
                "is_active": false
            },
            {
                "key": "allocated",
                "label": "Allocated",
                "count": 12,
                "is_active": true
            }
        ],
        "search_tokens": [
            "REF-8821",
            "REF-8822"
        ],
        "search_token_count": 2,
        "filter_options": {
            "design_codes": ["DC-100", "DC-101"],
            "product_names": ["Gold Ring 22K"],
            "order_types": ["Regular", "Custom", "Urgent", "Repair"],
            "craftsmen": [
                { "id": 11, "name": "Ramesh", "mobile": "9876543210" }
            ],
            "design_nicknames": ["Peacock", "Lotus"],
            "categories": ["Ring"],
            "subcategories": ["Casting"]
        },
        "items": [
            {
                "id": 101,
                "craftsman_serial_no": 1,
                "s_no": 1,
                "sno": 1,
                "work_order_no": "WO-2026-0012",
                "reference_no": "REF-8821",
                "product_name": "Gold Ring 22K",
                "design_code": "DC-100",
                "design_nickname": "Peacock",
                "category": "Ring",
                "subcategory": "Casting",
                "size": "14",
                "quantity": 1,
                "completed_quantity": 0,
                "balance_quantity": 1,
                "unit_type": "PCS",
                "hallmark_purity": "916",
                "target_weight": 4.5,
                "seal": "916",
                "has_seal": true,
                "status": "allocated",
                "due_date": "2026-11-18",
                "due_date_formatted": "18 Nov 2026"
            }
        ],
        "data": [ /* alias to items for standard paginator compatibility */ ],
        "current_page": 1,
        "first_page_url": "https://arihanthjewellers.in/api/common/work-orders?tab=allocated&page=1",
        "from": 1,
        "last_page": 2,
        "last_page_url": "https://arihanthjewellers.in/api/common/work-orders?tab=allocated&page=2",
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "page": null,
                "active": false
            },
            {
                "url": "https://arihanthjewellers.in/api/common/work-orders?tab=allocated&page=1",
                "label": "1",
                "page": 1,
                "active": true
            },
            {
                "url": "https://arihanthjewellers.in/api/common/work-orders?tab=allocated&page=2",
                "label": "2",
                "page": 2,
                "active": false
            },
            {
                "url": "https://arihanthjewellers.in/api/common/work-orders?tab=allocated&page=2",
                "label": "Next &raquo;",
                "page": 2,
                "active": false
            }
        ],
        "next_page_url": "https://arihanthjewellers.in/api/common/work-orders?tab=allocated&page=2",
        "path": "https://arihanthjewellers.in/api/common/work-orders",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 24,
        "has_more_pages": true
    }
}
```

---

## 7. Practical Examples

### Example 1: Multi-Reference Number Search
```bash
curl -G "https://arihanthjewellers.in/api/common/work-orders" \
  --data-urlencode "ref_no=REF001,REF002,REF003" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Example 2: Filtering Work Orders by Craftsman, Category, and Due Date
```bash
curl -G "https://arihanthjewellers.in/api/common/work-orders" \
  --data-urlencode "tab=allocated" \
  --data-urlencode "craftsman_id=11" \
  --data-urlencode "category=Bangle" \
  --data-urlencode "due_date_from=2026-11-15" \
  --data-urlencode "due_date_to=2026-11-30" \
  --data-urlencode "per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Example 3: Searching Customer Orders by Keyword and Status
```bash
curl -G "https://arihanthjewellers.in/api/common/orders" \
  --data-urlencode "search=Necklace" \
  --data-urlencode "status=in_process" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Example 4: JavaScript / Flutter / React Fetch
```javascript
const params = new URLSearchParams({
  tab: 'allocated',
  search: 'Peacock Ring',
  category: 'Ring',
  from_date: '2026-11-01',
  to_date: '2026-11-10',
  per_page: 15,
  page: 1,
});

const response = await fetch(`https://arihanthjewellers.in/api/common/work-orders?${params}`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  },
});

const json = await response.json();

// Direct access to items and pagination
const items = json.data.items;
const total = json.data.total;
const nextPage = json.data.next_page_url;
const paginationLinks = json.data.links;
const tabCounts = json.data.counts;
```

### Example 5: Fetching Filter Options (Dropdown Pickers)
```javascript
// 1. Fetch Design Codes Only
const designRes = await fetch('https://arihanthjewellers.in/api/common/work-orders/filters?type=design_code', {
  headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
});
const designJson = await designRes.json();
console.log(designJson.data); // ["DC-001", "DC-002", "DC-003", ...]

// 2. Fetch Craftsmen Only (Admin Only)
const craftsmanRes = await fetch('https://arihanthjewellers.in/api/common/work-orders/filters?type=craftsman', {
  headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
});
const craftsmanJson = await craftsmanRes.json();
console.log(craftsmanJson.data); // [{ id: 11, name: "Ramesh", mobile: "9876543210" }, ...]

// 3. Fetch Product Names Only
const productRes = await fetch('https://arihanthjewellers.in/api/common/work-orders/filters?type=product_name', {
  headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
});
const productJson = await productRes.json();
console.log(productJson.data); // ["Gold Ring 22K", "Peacock Bangle", ...]
```

---

## 7. Work Order Print Slips & Partially Completed Specifications

### 7.1 Single & Bulk Print Endpoints
- `GET /api/work-orders/{id}/print`: Returns rendered HTML print slip or JSON payload if `?format=json` or `Accept: application/json` is passed.
- `POST /api/work-orders/bulk-print`: Accepts `{ "order_ids": [1, 2, 3] }` and prints multiple work order slips formatted for A4 printing.

### 7.2 ESO & 916 Hallmark Seal Requirement
- Whenever an order has `ESO` in its reference number (e.g., `1307ESO2784249-001`):
  - `has_seal`: `true`
  - `seal`: `"916"`
  - Print Slips display the **`NEED 916 SEAL ONLY`** bold red banner and state `Seal: 916 SEAL (NEED 916 SEAL ONLY)` in the specifications table.
  - Image modals in Admin and Craftsman web panels display the **`916 SEAL`** badge over the jewelry image.

### 7.3 Partially Completed Orders
- When a work order has status `partially_completed` or `completed_quantity > 0`:
  - `is_partially_completed`: `true`
  - `quantity`: Total ordered quantity (e.g., `10`)
  - `completed_quantity`: Done quantity (e.g., `3`)
  - `balance_quantity`: Remaining quantity to manufacture (e.g., `7`)
- **Print Slips (Admin & Craftsman)**:
  - Displays top banner: `PARTIALLY COMPLETED — COMPLETED: X / Y | BALANCE TO DO: Z PCS`.
  - Details table replaces static `Qty` with separate breakdown rows:
    - `Total Qty: Y PCS`
    - `Completed: X PCS`
    - `Balance: Z PCS` (prominently styled for the craftsman).


