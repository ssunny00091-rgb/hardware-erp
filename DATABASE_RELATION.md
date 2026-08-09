# 🔗 DATABASE RELATION
## Paint & Hardware ERP

---

# Company

Company
│
├── Products
├── Customers
├── Suppliers
├── Painters
└── Plumbers

---

# Products

Products
│
├── Purchase Items
└── Sale Items

---

# Customers

Customers
│
├── Sales
│      └── Sale Items
│
└── Customer Payments

---

# Suppliers

Suppliers
│
├── Purchases
│      └── Purchase Items
│
└── Supplier Payments

---

# Painters

Painters
│
└── Sales

---

# Plumbers

Plumbers
│
└── Sales

---

# Sales

Sales
│
├── Customer
├── Painter (Optional)
├── Sale Items
└── Customer Payments

---

# Purchases

Purchases
│
├── Supplier
└── Purchase Items

---

# Customer Ledger

Customer
│
├── Sales
└── Payments

↓

Outstanding

↓

Statement

---

# Purchase Ledger

Supplier
│
├── Purchases
└── Payments

↓

Outstanding

↓

Statement

---

# Painter Ledger

Painter
│
└── Sales

↓

Business Report

---

# Plumber Ledger

Plumber
│
└── Sales

↓

Business Report

---

# Dashboard

Dashboard

↓

Sales

↓

Purchase

↓

Stock

↓

Outstanding

↓

Reports