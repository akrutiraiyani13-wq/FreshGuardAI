CREATE DATABASE IF NOT EXISTS freshguardai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE freshguardai;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    plan ENUM('Free', 'Premium', 'Yearly') DEFAULT 'Free',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS food_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    quantity VARCHAR(50),
    purchase_date DATE,
    expiry_date DATE NOT NULL,
    storage_location VARCHAR(50),
    notes TEXT,
    image VARCHAR(255),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT,
    message TEXT NOT NULL,
    type VARCHAR(50),
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS replacement_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_name VARCHAR(100) NOT NULL,
    quantity VARCHAR(50) DEFAULT '1',
    category VARCHAR(50) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    ingredients TEXT,
    instructions TEXT,
    image VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS recipe_food_mapping (
    recipe_id INT NOT NULL,
    food_id INT,
    ingredient_information VARCHAR(100),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS barcode_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(100),
    category VARCHAR(50),
    weight VARCHAR(50),
    shelf_life_days INT DEFAULT 30,
    mfg_days_ago INT DEFAULT 5,
    image VARCHAR(255),
    storage_location VARCHAR(100),
    price DECIMAL(10,2) DEFAULT 0.00,
    calories VARCHAR(50),
    sugar VARCHAR(50),
    protein VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS scan_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    barcode VARCHAR(50) NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    category VARCHAR(50),
    image VARCHAR(255),
    mfg_date DATE,
    exp_date DATE,
    price DECIMAL(10,2) DEFAULT 0.00,
    freshness_status VARCHAR(30),
    freshness_score INT DEFAULT 0,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Demo Users (Password for all demo accounts is 'password')
INSERT INTO users (name, email, password, plan) VALUES
('Demo User', 'demo@freshguardai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Premium'),
('Aarav Patel', 'aarav@freshguardai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Free'),
('Priya Sharma', 'priya@freshguardai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Yearly'),
('Rohan Mehta', 'rohan@freshguardai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Premium'),
('Neha Verma', 'neha@freshguardai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Free'),
('Karan Joshi', 'karan@freshguardai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Free'),
('Meera Nair', 'meera@freshguardai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Premium'),
('Vikram Singh', 'vikram@freshguardai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Yearly');

-- 10 Food Items for Demo User (with online photos)
INSERT INTO food_items (user_id, name, category, quantity, purchase_date, expiry_date, storage_location, notes, status, image) VALUES
(1, 'Farm Fresh Milk', 'Dairy', '1 Gallon', CURDATE() - INTERVAL 2 DAY, CURDATE() + INTERVAL 2 DAY, 'Fridge', 'Use soon for cereal', 'Use Soon', 'https://images.unsplash.com/photo-1563636619-e9143da7973b?auto=format&fit=crop&w=500&q=80'),
(1, 'Organic Eggs (Dozen)', 'Dairy', '1 Dozen', CURDATE() - INTERVAL 5 DAY, CURDATE() + INTERVAL 10 DAY, 'Fridge', 'Great for breakfast', 'Fresh', 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=500&q=80'),
(1, 'Cherry Tomatoes', 'Vegetables', '1 Bag', CURDATE() - INTERVAL 7 DAY, CURDATE() + INTERVAL 2 DAY, 'Fridge', 'Good for curry & omelette', 'Use Soon', 'https://images.unsplash.com/photo-1546094096-0df4bcaaa337?auto=format&fit=crop&w=500&q=80'),
(1, 'Raw Potatoes', 'Vegetables', '1.5 kg', CURDATE() - INTERVAL 3 DAY, CURDATE() + INTERVAL 14 DAY, 'Pantry', 'Keep in cool dry basket', 'Fresh', 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=500&q=80'),
(1, 'Chicken Breast', 'Meat', '2 lbs', CURDATE() - INTERVAL 1 DAY, CURDATE() + INTERVAL 4 DAY, 'Fridge', 'Marinate before cooking', 'Fresh', 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=500&q=80'),
(1, 'Fresh Artisan Bread', 'Bakery', '1 Loaf', CURDATE() - INTERVAL 10 DAY, CURDATE() - INTERVAL 2 DAY, 'Counter', 'Expired - discard safely', 'Expired', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=500&q=80'),
(1, 'Greek Yogurt Plain', 'Dairy', '400 g', CURDATE() - INTERVAL 6 DAY, CURDATE() + INTERVAL 1 DAY, 'Fridge', 'Smoothie or breakfast bowl', 'Use Soon', 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=500&q=80'),
(1, 'Baby Spinach', 'Vegetables', '1 Pack', CURDATE() - INTERVAL 8 DAY, CURDATE() - INTERVAL 1 DAY, 'Fridge', 'Expired - compost', 'Expired', 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?auto=format&fit=crop&w=500&q=80'),
(1, 'Washington Apples', 'Fruits', '6 pcs', CURDATE() - INTERVAL 2 DAY, CURDATE() + INTERVAL 9 DAY, 'Fridge', 'Crisper drawer', 'Fresh', 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=500&q=80'),
(1, 'Cheddar Cheese Block', 'Dairy', '250 g', CURDATE() - INTERVAL 4 DAY, CURDATE() + INTERVAL 3 DAY, 'Fridge', 'Great for toasties', 'Use Soon', 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?auto=format&fit=crop&w=500&q=80');

-- 8 Notifications
INSERT INTO notifications (user_id, food_id, message, type, is_read) VALUES
(1, 1, 'Farm Fresh Milk expires in 2 days. Check Recipes for ideas before it goes to waste!', 'use_soon', 0),
(1, 3, 'Cherry Tomatoes expires in 2 days. Great for Vegetable Curry or Breakfast Omelette!', 'use_soon', 0),
(1, 6, 'Fresh Artisan Bread has expired! Please dispose of it safely and add it to your replacement list.', 'expired', 0),
(1, 7, 'Greek Yogurt Plain expires tomorrow. Use it soon in a smoothie or breakfast bowl!', 'use_soon', 0),
(1, 8, 'Baby Spinach has expired! Safely discard or compost it.', 'expired', 0),
(1, 10, 'Cheddar Cheese Block expires in 3 days. Try Cheese & Tomato Toastie!', 'use_soon', 0),
(1, 2, 'Organic Eggs (Dozen) added to Fridge inventory and tracking started.', 'info', 1),
(1, 5, 'Chicken Breast is fresh for 4 more days in your Fridge.', 'info', 1);

-- 10 Replacement List Items
INSERT INTO replacement_list (user_id, food_name, quantity, category, notes, status) VALUES
(1, 'Fresh Artisan Bread', '1 Loaf', 'Bakery', 'Replace expired loaf', 'Pending'),
(1, 'Orange Juice', '1 Carton', 'Beverages', 'Weekly breakfast essential', 'Pending'),
(1, 'Baby Spinach', '1 Bag', 'Vegetables', 'Fresh greens for omelette & salad', 'Pending'),
(1, 'Whole Wheat Pasta', '500 g', 'Pantry', 'Pair with homemade tomato sauce', 'Pending'),
(1, 'Fresh Strawberries', '1 Punnet', 'Fruits', 'For milkshake and yogurt bowl', 'Pending'),
(1, 'Extra Virgin Olive Oil', '500 ml', 'Pantry', 'Running low in pantry', 'Pending'),
(1, 'Salted Butter', '200 g', 'Dairy', 'Needed for French Toast', 'Pending'),
(1, 'Garlic Bulbs', '2 pcs', 'Vegetables', 'For garlic chicken & tomato sauce', 'Pending'),
(1, 'Red Onions', '1 kg', 'Vegetables', 'Base for Vegetable Curry', 'Pending'),
(1, 'Oreo Chocolate Cookies', '2 Packs', 'Snacks', 'Weekend snack restock', 'Pending');

-- 10 Recipes with online photos
INSERT INTO recipes (name, description, ingredients, instructions, image) VALUES
('Vegetable Curry', 'A hearty tomato-based vegetable curry.', 'Tomatoes, Potato, Onion, Capsicum', 'Chop everything, saute spices and onions, add tomatoes and potatoes, and simmer for 20 mins.', 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?auto=format&fit=crop&w=600&q=80'),
('Breakfast Omelette', 'Quick and healthy protein-packed breakfast.', 'Eggs, Tomatoes, Milk', 'Whisk eggs with a splash of milk, pour into heated pan, add chopped tomatoes and cook until set.', 'https://images.unsplash.com/photo-1510693206972-df098062cb71?auto=format&fit=crop&w=600&q=80'),
('Tomato Sauce', 'Homemade fresh pasta or pizza sauce.', 'Tomatoes, Garlic, Olive Oil', 'Saute garlic in olive oil, add crushed tomatoes, and simmer for 45 minutes until thickened.', 'https://images.unsplash.com/photo-1472476443507-c7a5948772fc?auto=format&fit=crop&w=600&q=80'),
('French Toast', 'Classic golden breakfast using milk-battered bread.', 'Milk, Eggs, Bread, Cinnamon', 'Whisk eggs and milk, dip bread slices, and pan-fry in butter until golden brown on both sides.', 'https://images.unsplash.com/photo-1484723091739-30a097e8f929?auto=format&fit=crop&w=600&q=80'),
('Crispy Bread Crumbs', 'Zero-waste toasted bread crumbs for frying or baking.', 'Bread, Garlic, Herbs', 'Toast bread until dry, pulse in a blender with herbs, and store in an airtight jar.', 'https://images.unsplash.com/photo-1586444248902-2f64eddc13df?auto=format&fit=crop&w=600&q=80'),
('Classic Milkshake', 'Creamy chilled shake ready in minutes.', 'Milk, Sugar, Ice Cream', 'Blend chilled milk with ice cream and flavorings until smooth and frothy.', 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=600&q=80'),
('Garlic Chicken Skillet', 'Juicy pan-seared chicken with tomatoes and herbs.', 'Chicken, Tomatoes, Garlic', 'Season chicken breast, sear until golden, add halved cherry tomatoes and garlic, cook through.', 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?auto=format&fit=crop&w=600&q=80'),
('Roasted Herb Potatoes', 'Crispy oven-roasted potatoes for any meal.', 'Potato, Olive Oil, Salt, Pepper', 'Cube raw potatoes, toss in oil and seasonings, and roast at 220C for 30 minutes.', 'https://images.unsplash.com/photo-1568569350062-ebfa3cb195df?auto=format&fit=crop&w=600&q=80'),
('Spinach & Egg Scramble', 'Nutrient-rich 10-minute skillet meal.', 'Eggs, Spinach, Milk', 'Wilt spinach in a pan, pour in whisked eggs and milk, and gently fold until soft.', 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=600&q=80'),
('Cheese & Tomato Toastie', 'Warm melted sandwich ideal for quick lunches.', 'Bread, Cheese, Tomatoes', 'Layer cheese slices and sliced tomatoes between bread and grill until golden and melted.', 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=600&q=80');

INSERT INTO recipe_food_mapping (recipe_id, food_id, ingredient_information) VALUES
(1, 3, 'Tomatoes'),
(1, 4, 'Potato'),
(2, 2, 'Eggs'),
(2, 3, 'Tomatoes'),
(2, 1, 'Milk'),
(3, 3, 'Tomatoes'),
(4, 1, 'Milk'),
(4, 2, 'Eggs'),
(4, 6, 'Bread'),
(5, 6, 'Bread'),
(6, 1, 'Milk'),
(7, 5, 'Chicken'),
(7, 3, 'Tomatoes'),
(8, 4, 'Potato'),
(9, 2, 'Eggs'),
(9, 8, 'Spinach'),
(9, 1, 'Milk'),
(10, 6, 'Bread'),
(10, 10, 'Cheese'),
(10, 3, 'Tomatoes');

-- 13 Barcode Products with online photos
INSERT INTO barcode_products (barcode, name, brand, category, weight, shelf_life_days, mfg_days_ago, image, storage_location, price, calories, sugar, protein) VALUES
('8901234567890', 'Lay''s Classic Salted Chips', 'Lay''s', 'Snacks', '52 g', 180, 30, 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?auto=format&fit=crop&w=500&q=80', 'Pantry', 1.50, '280 kcal', '0.5g', '3g'),
('8901234567891', 'Amul Fresh Milk', 'Amul', 'Dairy', '500 ml', 5, 3, 'https://images.unsplash.com/photo-1563636619-e9143da7973b?auto=format&fit=crop&w=500&q=80', 'Fridge', 2.00, '240 kcal', '12g', '16g'),
('8901234567892', 'Harvest Gold Bread', 'Harvest Gold', 'Bakery', '400 g', 7, 9, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=500&q=80', 'Counter', 1.80, '80 kcal', '1.5g', '3g'),
('8901234567893', 'Tropicana Orange Juice', 'Tropicana', 'Beverages', '1 L', 60, 15, 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=500&q=80', 'Fridge', 3.50, '110 kcal', '22g', '1g'),
('8901234567894', 'Britannia Cheese Slices', 'Britannia', 'Dairy', '200 g', 90, 88, 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?auto=format&fit=crop&w=500&q=80', 'Fridge', 2.50, '60 kcal', '1g', '4g'),
('8901234567895', 'Maggi 2-Minute Noodles', 'Nestle', 'Snacks', '70 g', 270, 45, 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=500&q=80', 'Pantry', 0.50, '310 kcal', '1g', '6g'),
('8901234567896', 'Oreo Chocolate Cookies', 'Cadbury', 'Snacks', '120 g', 180, 40, 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=500&q=80', 'Pantry', 1.20, '140 kcal', '13g', '1g'),
('8901234567897', 'Real Mixed Fruit Juice', 'Dabur', 'Beverages', '1 L', 120, 118, 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=500&q=80', 'Fridge', 3.20, '120 kcal', '25g', '0g'),
('8901234567898', 'Cherry Tomatoes Pack', 'Fresh Farms', 'Vegetables', '500 g', 10, 8, 'https://images.unsplash.com/photo-1546094096-0df4bcaaa337?auto=format&fit=crop&w=500&q=80', 'Fridge', 2.40, '35 kcal', '4g', '1.5g'),
('8901234567899', 'Chicken Breast Fillet', 'Happy Pastures', 'Meat', '500 g', 6, 2, 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=500&q=80', 'Fridge', 6.99, '165 kcal', '0g', '31g'),
('8901234567900', 'Greek Yogurt Plain', 'Chobani', 'Dairy', '150 g', 14, 12, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=500&q=80', 'Fridge', 1.50, '100 kcal', '4g', '17g'),
('8901234567901', 'Free Range Eggs (Dozen)', 'Sunny Side', 'Dairy', '600 g', 21, 5, 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=500&q=80', 'Fridge', 4.20, '70 kcal', '0g', '6g'),
('8901234567902', 'Raw Potatoes Bag', 'Farm Harvest', 'Vegetables', '1 kg', 30, 7, 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=500&q=80', 'Pantry', 2.10, '110 kcal', '1g', '3g');

-- 10 Scan History Records with online photos
INSERT INTO scan_history (user_id, barcode, product_name, category, image, mfg_date, exp_date, price, freshness_status, freshness_score) VALUES
(1, '8901234567891', 'Amul Fresh Milk', 'Dairy', 'https://images.unsplash.com/photo-1563636619-e9143da7973b?auto=format&fit=crop&w=500&q=80', CURDATE() - INTERVAL 3 DAY, CURDATE() + INTERVAL 2 DAY, 2.00, 'soon', 40),
(1, '8901234567892', 'Harvest Gold Bread', 'Bakery', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=500&q=80', CURDATE() - INTERVAL 9 DAY, CURDATE() - INTERVAL 2 DAY, 1.80, 'spoiled', 0),
(1, '8901234567890', 'Lay''s Classic Salted Chips', 'Snacks', 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?auto=format&fit=crop&w=500&q=80', CURDATE() - INTERVAL 30 DAY, CURDATE() + INTERVAL 150 DAY, 1.50, 'fresh', 83),
(1, '8901234567893', 'Tropicana Orange Juice', 'Beverages', 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=500&q=80', CURDATE() - INTERVAL 15 DAY, CURDATE() + INTERVAL 45 DAY, 3.50, 'fresh', 75),
(1, '8901234567894', 'Britannia Cheese Slices', 'Dairy', 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?auto=format&fit=crop&w=500&q=80', CURDATE() - INTERVAL 88 DAY, CURDATE() + INTERVAL 2 DAY, 2.50, 'soon', 2),
(1, '8901234567895', 'Maggi 2-Minute Noodles', 'Snacks', 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=500&q=80', CURDATE() - INTERVAL 45 DAY, CURDATE() + INTERVAL 225 DAY, 0.50, 'fresh', 83),
(1, '8901234567896', 'Oreo Chocolate Cookies', 'Snacks', 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=500&q=80', CURDATE() - INTERVAL 40 DAY, CURDATE() + INTERVAL 140 DAY, 1.20, 'fresh', 78),
(1, '8901234567898', 'Cherry Tomatoes Pack', 'Vegetables', 'https://images.unsplash.com/photo-1546094096-0df4bcaaa337?auto=format&fit=crop&w=500&q=80', CURDATE() - INTERVAL 8 DAY, CURDATE() + INTERVAL 2 DAY, 2.40, 'soon', 20),
(1, '8901234567899', 'Chicken Breast Fillet', 'Meat', 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=500&q=80', CURDATE() - INTERVAL 2 DAY, CURDATE() + INTERVAL 4 DAY, 6.99, 'fresh', 67),
(1, '8901234567901', 'Free Range Eggs (Dozen)', 'Dairy', 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=500&q=80', CURDATE() - INTERVAL 5 DAY, CURDATE() + INTERVAL 16 DAY, 4.20, 'fresh', 76);

-- 8 Contact Messages
INSERT INTO contact_messages (name, email, message) VALUES
('Aarav Patel', 'aarav@freshguardai.com', 'Love how easy it is to see Use Soon items and matching recipes!'),
('Priya Sharma', 'priya@freshguardai.com', 'The replacement shopping list saved me from buying duplicate milk.'),
('Rohan Mehta', 'rohan@freshguardai.com', 'Can you add more Indian breakfast recipes?'),
('Neha Verma', 'neha@freshguardai.com', 'Barcode scanning makes adding dairy products super fast.'),
('Karan Joshi', 'karan@freshguardai.com', 'Great UI and very helpful expiry alerts for my hostel kitchen.'),
('Meera Nair', 'meera@freshguardai.com', 'Yearly plan is great value for our family of five.'),
('Vikram Singh', 'vikram@freshguardai.com', 'Would love WhatsApp reminder support in future updates.'),
('Ananya Rao', 'ananya@freshguardai.com', 'Awesome college project presentation — clean and practical!');
