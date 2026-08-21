# Hardware ERP (PHP + MySQL)

SATYANARAYAN HARDWARE STORES billing app — Next.js/Supabase se PHP + MySQL pe convert.

## Features

- Dashboard with live sales/purchase totals from MySQL
- New sale, invoice preview, print/PDF (browser print)
- Sales history (view/delete; delete restores stock)
- Product master CRUD
- Purchase entry (increases stock)
- Customer lookup by mobile number

## Setup

1. PHP 8.1+ with `pdo_mysql`
2. MySQL 5.7+ / 8 / MariaDB
3. Copy `.env.example` to `.env` and set credentials:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=hardware_erp
DB_USER=root
DB_PASS=your_password
```

4. Import schema (or open `install.php` in the browser):

```bash
mysql -u root -p < sql/schema.sql
mysql -u root -p < sql/seed.sql
```

5. Serve the project root:

```bash
php -S localhost:8000
```

Open http://localhost:8000 then http://localhost:8000/install.php if tables are not created yet.

Apache/XAMPP: copy this folder into `htdocs` and visit `/hardware-erp/`. If the app is not at the web root, keep using the `.php` URLs (`index.php`, `products.php`, `purchase.php`).

## Data tables

| Table | Purpose |
| --- | --- |
| `customers` | Customer master |
| `products` | Product master + stock |
| `sales` / `sale_items` | Invoices |
| `purchases` / `purchase_items` | Purchase bills |
