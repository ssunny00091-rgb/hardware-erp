# 🗄 DATABASE SCHEMA
## Paint & Hardware ERP

---

# MASTER TABLES

## 1. Company

company

- id
- company_name
- owner_name
- mobile
- email
- gst_no
- address
- logo

---

## 2. Products

products

- id
- product_name
- category
- brand
- unit
- purchase_price
- selling_price
- gst
- stock
- minimum_stock
- created_at

---

## 3. Customers

customers

- id
- customer_code
- customer_name
- mobile
- email
- gst_no
- address
- city
- state
- pincode
- credit_limit
- payment_term
- opening_balance
- status
- created_at
- updated_at

---

## 4. Suppliers

suppliers

- id
- supplier_name
- mobile
- address
- gst_no
- opening_balance
- created_at

---

## 5. Painters

painters

- id
- painter_name
- mobile
- address
- created_at

---

## 6. Plumbers

plumbers

- id
- plumber_name
- mobile
- address
- created_at

---

# TRANSACTION TABLES

## Sales

sales

- id
- invoice_no
- customer_id
- painter_id
- payment_mode
- total
- discount
- gst
- grand_total
- bill_date
- created_at

---

## Sale Items

sale_items

- id
- sale_id
- product_id
- qty
- unit
- rate
- total

---

## Purchases

purchases

- id
- supplier_id
- invoice_no
- total
- purchase_date
- created_at

---

## Purchase Items

purchase_items

- id
- purchase_id
- product_id
- qty
- unit
- rate
- total

---

# LEDGER TABLES

customer_payments

supplier_payments

---

# FUTURE

users

roles

notifications

activity_logs

ai_history