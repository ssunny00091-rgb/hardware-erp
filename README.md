# Hardware ERP (PHP + MySQL)

SATYANARAYAN HARDWARE STORES billing app.

## One-by-one setup (XAMPP — sabse aasan)

### 1) XAMPP install + start
1. https://www.apachefriends.org se XAMPP install karo.
2. XAMPP Control Panel kholo.
3. **Apache** Start.
4. **MySQL** Start.

### 2) Project copy
1. Is folder ko yahan paste karo:
   `C:\xampp\htdocs\hardware-erp`
2. Browser mein kholo:
   http://localhost/hardware-erp/install.php

### 3) Wizard ke 6 steps
1. **PHP check** — green ticks dekho, Next.
2. **MySQL details** — XAMPP default:
   - Host: `127.0.0.1`
   - Port: `3306`
   - Database: `hardware_erp`
   - User: `root`
   - Password: khali
   phir **Test connection + save**.
3. **Create database**.
4. **Create tables**.
5. Sample products/customers tick karke **Finish**.
6. **Dashboard kholo**.

### 4) Daily use
- Dashboard: http://localhost/hardware-erp/index.php
- Products: http://localhost/hardware-erp/products.php
- Purchase: http://localhost/hardware-erp/purchase.php

Setup dobara: `install.php?restart=1`

## Linux / PHP built-in server

```bash
cp .env.example .env
php -S localhost:8000
```

Browser: http://localhost:8000/install.php

PHP 8.1+ with `pdo_mysql` chahiye.

## Tables

| Table | Kaam |
| --- | --- |
| `customers` | Customer master |
| `products` | Product + stock |
| `sales` / `sale_items` | Invoices |
| `purchases` / `purchase_items` | Purchase bills |
