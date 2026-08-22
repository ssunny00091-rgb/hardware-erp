CREATE DATABASE IF NOT EXISTS hardware_erp
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE hardware_erp;

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  mobile VARCHAR(20) DEFAULT NULL,
  address TEXT,
  gst VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_customers_mobile (mobile)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_name VARCHAR(255) NOT NULL,
  brand VARCHAR(100) DEFAULT '',
  category VARCHAR(100) DEFAULT '',
  unit VARCHAR(50) DEFAULT 'Piece',
  purchase_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  selling_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  stock DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  gst_percent DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  hsn_code VARCHAR(50) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_products_name (product_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(50) NOT NULL,
  customer_id INT UNSIGNED DEFAULT NULL,
  customer_name VARCHAR(255) DEFAULT '',
  mobile VARCHAR(20) DEFAULT '',
  address TEXT,
  gst VARCHAR(50) DEFAULT '',
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  received DECIMAL(12,2) DEFAULT NULL,
  ref_type VARCHAR(30) DEFAULT NULL,
  ref_party_id INT UNSIGNED DEFAULT NULL,
  ref_name VARCHAR(255) DEFAULT NULL,
  customer_party_id INT UNSIGNED DEFAULT NULL,
  sale_date DATE DEFAULT NULL,
  due_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_sales_invoice (invoice_no),
  KEY idx_sales_created (created_at),
  KEY idx_sales_date (sale_date),
  CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sale_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED DEFAULT NULL,
  product_name VARCHAR(255) NOT NULL,
  color_code VARCHAR(120) DEFAULT NULL,
  color_hex VARCHAR(7) DEFAULT NULL,
  hsn_code VARCHAR(50) DEFAULT NULL,
  qty DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  unit VARCHAR(50) DEFAULT 'Piece',
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchases (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  supplier_name VARCHAR(255) DEFAULT '',
  invoice_no VARCHAR(100) DEFAULT '',
  purchase_date DATE NOT NULL,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  paid DECIMAL(12,2) DEFAULT NULL,
  supplier_party_id INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_purchases_date (purchase_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED DEFAULT NULL,
  product_name VARCHAR(255) NOT NULL,
  qty DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  unit VARCHAR(50) DEFAULT 'Piece',
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_purchase_items_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
  CONSTRAINT fk_purchase_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  paid_on DATE NOT NULL,
  notes VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pp_purchase (purchase_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS parties (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  mobile VARCHAR(20) DEFAULT '',
  address TEXT,
  type VARCHAR(30) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_parties_type_name (type, name),
  KEY idx_parties_mobile (mobile)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ledger_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  party_id INT UNSIGNED NOT NULL,
  entry_date DATE NOT NULL,
  particulars VARCHAR(255) NOT NULL,
  ref_no VARCHAR(50) DEFAULT '',
  debit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  credit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  sale_id INT UNSIGNED NULL,
  purchase_id INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ledger_party (party_id),
  KEY idx_ledger_sale (sale_id),
  KEY idx_ledger_purchase (purchase_id)
) ENGINE=InnoDB;
