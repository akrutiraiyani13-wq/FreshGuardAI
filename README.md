# FreshGuardAI

Smart food freshness tracking, expiry alerts, recipe matching, barcode scanning, and replacement shopping list web application built with PHP & MySQL.

## Setup Instructions
1. Install Laragon or XAMPP for Windows.
2. Place this project folder in `c:/laragon/www/FreshGuardAI`.
3. Open phpMyAdmin or MySQL terminal and import `database.sql` (or let `includes/db.inc.php` auto-create `freshguardai`).
4. The application expects the MySQL database `freshguardai` with `root` user and empty password (configurable in `includes/db.inc.php`).
5. Start Apache and MySQL in Laragon.
6. Open your browser and navigate to `http://localhost/FreshGuardAI/`.

## Demo Credentials
- **Email:** `demo@freshguardai.com`
- **Password:** `password`

## Database Tables (`freshguardai`)
- `users` — User accounts and subscription plans (`Free`, `Premium`, `Yearly`)
- `food_items` — Pantry/fridge food inventory with expiry dates and storage locations
- `notifications` — Automated `Use Soon` and `Expired` alerts
- `recipes` — Recipe catalog
- `recipe_food_mapping` — Links recipes to food ingredients
- `replacement_list` — Shopping / restock list
- `barcode_products` — Barcode product catalog with nutrition & shelf-life data
- `scan_history` — Per-user barcode scan logs
- `contact_messages` — Contact form submissions
