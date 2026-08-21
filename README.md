# Hardware ERP (PHP + MySQL)

SATYANARAYAN HARDWARE STORES billing app.

## Command Prompt se setup (Windows)

XAMPP Control Panel mein **Apache** aur **MySQL** Start karo, phir CMD kholo:

```bat
cd /d C:\xampp\htdocs\hardware-erp
setup.bat
```

Har step pe Enter dabao (XAMPP default: user `root`, password khali).

Bina poochhe automatic:

```bat
cd /d C:\xampp\htdocs\hardware-erp
setup.bat /yes
```

Agar `php.exe` nahi milti:

```bat
cd /d C:\xampp\htdocs\hardware-erp
C:\xampp\php\php.exe setup.php
```

Sirf MySQL import (PHP ke bina):

```bat
cd /d C:\xampp\htdocs\hardware-erp
setup-mysql.bat
```

Uske baad browser: http://localhost/hardware-erp/index.php

## Browser wizard (optional)

http://localhost/hardware-erp/install.php

## Linux / PHP CLI

```bash
php setup.php --yes --host=127.0.0.1 --user=root --pass= --name=hardware_erp
php -S localhost:8000
```

PHP 8.1+ with `pdo_mysql` chahiye. Voice/photo assistant ke liye `curl` recommended.

## Shop Assistant (OpenRouter)

1. Browser: `assistant.php`
2. [OpenRouter key](https://openrouter.ai/keys) paste karke Save.
3. 🎤 Bolke sale/purchase bolo, ya supplier bill ki photo/PDF (scan, screenshot, WhatsApp image) attach karo — supplier + bill software mein save ho jata hai.

`.env` mein:

```
OPENROUTER_API_KEY=sk-or-v1-...
OPENROUTER_MODEL=google/gemini-2.5-flash
```

## Tables

| Table | Kaam |
| --- | --- |
| `customers` | Customer master |
| `products` | Product + stock |
| `sales` / `sale_items` | Invoices |
| `purchases` / `purchase_items` | Purchase bills |
