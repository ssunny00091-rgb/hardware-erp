USE hardware_erp;

INSERT INTO customers (name, mobile, address, gst) VALUES
  ('Rahul Kumar', '9431875263', 'Jayanagar', '10ABCDE1234F1Z5'),
  ('Amit Singh', '9876543210', 'Madhubani', ''),
  ('Vijay Sharma', '9123456789', 'Darbhanga', '')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO products (product_name, brand, category, unit, purchase_price, selling_price, stock, gst_percent, hsn_code) VALUES
  ('Asian Paint 20L', 'Asian Paints', 'Paint', 'Ltr', 1200.00, 1500.00, 40.00, 18.00, '3208'),
  ('Asian Paint 10L', 'Asian Paints', 'Paint', 'Ltr', 700.00, 900.00, 25.00, 18.00, '3208'),
  ('JK White Cement', 'JK', 'Cement', 'Bag', 350.00, 450.00, 80.00, 28.00, '2523'),
  ('Berger Putty', 'Berger', 'Putty', 'Bag', 500.00, 650.00, 30.00, 28.00, '3214'),
  ('Fevicol SH', 'Pidilite', 'Adhesive', 'Piece', 140.00, 180.00, 50.00, 18.00, '3506'),
  ('Fevikwik', 'Pidilite', 'Adhesive', 'Piece', 3.00, 5.00, 200.00, 18.00, '3506');
