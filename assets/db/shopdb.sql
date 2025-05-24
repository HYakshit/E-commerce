-- PostgreSQL database schema for ShopNow e-commerce application
-- To be used on Replit

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    firebase_uid VARCHAR(100) UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(255),
    is_admin BOOLEAN NOT NULL DEFAULT FALSE,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    parent_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sku VARCHAR(50) UNIQUE,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INTEGER NOT NULL DEFAULT 0,
    in_stock BOOLEAN NOT NULL DEFAULT TRUE,
    featured BOOLEAN NOT NULL DEFAULT FALSE,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    image VARCHAR(255),
    specifications TEXT,
    brand VARCHAR(50),
    rating DECIMAL(3, 1),
    rating_count INTEGER DEFAULT 0,
    tags VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    total DECIMAL(10, 2) NOT NULL,
    shipping_address TEXT NOT NULL,
    billing_address TEXT,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE SET NULL,
    product_name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INTEGER NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    rating INTEGER NOT NULL,
    review TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Coupons table
CREATE TABLE IF NOT EXISTS coupons (
    id SERIAL PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    type VARCHAR(10) NOT NULL CHECK (type IN ('percentage', 'fixed')),
    value DECIMAL(10, 2) NOT NULL,
    min_order_value DECIMAL(10, 2) DEFAULT 0,
    max_usage INTEGER,
    usage_count INTEGER DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    expiry_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data

-- Sample categories
INSERT INTO categories (name, slug, description, image, sort_order) VALUES
('Electronics', 'electronics', 'Electronic devices and gadgets', 'https://images.unsplash.com/photo-1526738549149-8e07eca6c147', 1),
('Clothing', 'clothing', 'Fashion clothing and accessories', 'https://images.unsplash.com/photo-1534452203293-494d7ddbf7e0', 2),
('Home & Kitchen', 'home-kitchen', 'Home appliances and kitchen equipment', 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7', 3),
('Books', 'books', 'Books, magazines and literature', 'https://images.unsplash.com/photo-1516979187457-637abb4f9353', 4),
('Sports & Outdoors', 'sports-outdoors', 'Sporting goods and outdoor equipment', 'https://images.unsplash.com/photo-1517649763962-0c623066013b', 5),
('Beauty & Personal Care', 'beauty-personal-care', 'Beauty products and personal care items', 'https://images.unsplash.com/photo-1526047932273-341f2a7631f9', 6);

-- Sample products
INSERT INTO products (name, sku, description, price, stock_quantity, in_stock, featured, category_id, image, specifications, brand, rating, rating_count, tags) VALUES
('Premium Smart Watch', 'WATCH001', 'Stay connected with our premium smart watch that tracks your fitness goals and keeps you updated with notifications.', 249.99, 25, TRUE, TRUE, 1, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30', 'Display: 1.3" AMOLED\r\nBattery Life: Up to 7 days\r\nWater Resistant: 5 ATM\r\nConnectivity: Bluetooth 5.0\r\nCompatibility: iOS 12.0+ / Android 7.0+', 'TechGear', 4.5, 120, 'smart watch, fitness tracker, wearable tech'),
('Wireless Bluetooth Earbuds', 'EARBUDS002', 'Premium wireless earbuds with active noise cancellation and superior sound quality. Perfect for music lovers on the go.', 129.99, 40, TRUE, TRUE, 1, 'https://images.unsplash.com/photo-1524678606370-a47ad25cb82a', 'Battery Life: 8 hours (24 with case)\r\nActive Noise Cancellation: Yes\r\nWater Resistant: IPX4\r\nBluetooth: 5.1\r\nCharging: USB-C, Wireless', 'AudioPro', 4.7, 85, 'earbuds, wireless, bluetooth, audio'),
('Premium Leather Wallet', 'WALLET003', 'Handcrafted genuine leather wallet with multiple card slots and RFID protection. Elegant and functional design.', 59.99, 50, TRUE, FALSE, 2, 'https://images.unsplash.com/photo-1509695507497-903c140c43b0', 'Material: Genuine Leather\r\nDimensions: 4.5" x 3.5" x 0.5"\r\nCard Slots: 8\r\nRFID Protection: Yes\r\nColor: Brown', 'LeatherCraft', 4.3, 65, 'wallet, leather, accessories, men'),
('Stylish Sunglasses', 'SUN004', 'Trendy sunglasses with UV protection and polarized lenses. Perfect for beach days and outdoor activities.', 79.99, 35, TRUE, TRUE, 2, 'https://images.unsplash.com/photo-1525904097878-94fb15835963', 'Frame Material: Acetate\r\nLens: Polarized\r\nUV Protection: 100%\r\nStyle: Wayfarer\r\nIncludes: Protective Case', 'VisionStyle', 4.2, 42, 'sunglasses, eyewear, accessories, summer'),
('Portable Bluetooth Speaker', 'SPEAKER005', 'Compact and powerful bluetooth speaker with 360° sound and 20 hours of battery life. Take your music anywhere.', 89.99, 20, TRUE, TRUE, 1, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e', 'Power Output: 20W\r\nBattery Life: 20 hours\r\nWaterproof: IPX7\r\nBluetooth Range: 30m\r\nSize: 7" x 3" x 3"', 'SoundWave', 4.6, 110, 'speaker, bluetooth, audio, portable'),
('Ceramic Coffee Mug Set', 'MUG006', 'Set of 4 premium ceramic coffee mugs in assorted colors. Microwave and dishwasher safe.', 34.99, 45, TRUE, FALSE, 3, 'https://images.unsplash.com/photo-1479064555552-3ef4979f8908', 'Material: Ceramic\r\nCapacity: 12oz\r\nDishwasher Safe: Yes\r\nMicrowave Safe: Yes\r\nColors: Assorted', 'HomeEssentials', 4.4, 78, 'mugs, coffee, kitchenware, ceramic');

-- Sample users (password is 'admin123' for admin user)
INSERT INTO users (firebase_uid, email, name, password, is_admin) VALUES
('admin123', 'admin@shopnow.com', 'Admin User', '$2y$10$1qAz2wSx3eDc4rFv5tDOsu/4uQEgdZO3.PW.VzF8.CariVV6/2.re', TRUE),
('user123', 'customer@example.com', 'John Customer', '$2y$10$yCmMb41Vl.e1v9z0HQ.aje77ZK1RG7ECNelQwQL9YfdfoZXkc/MZ2', FALSE);

-- Sample coupons
INSERT INTO coupons (code, type, value, min_order_value, max_usage, active, expiry_date) VALUES
('WELCOME10', 'percentage', 10.00, 50.00, 100, TRUE, '2025-12-31'),
('SUMMER25', 'percentage', 25.00, 100.00, 50, TRUE, '2025-08-31'),
('SALE15', 'percentage', 15.00, 75.00, 75, TRUE, '2025-12-31');