-- Database Creation
CREATE DATABASE IF NOT EXISTS Tejindra_Rai_25126478;
USE Tejindra_Rai_25126478;

-- Users Table with Admin features
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_pic VARCHAR(255) DEFAULT 'default.jpg',
    bio TEXT,
    location VARCHAR(100),
    rating DECIMAL(3,2) DEFAULT 0.00,
    is_admin TINYINT(1) DEFAULT 0,
    is_banned TINYINT(1) DEFAULT 0,
    ban_reason TEXT DEFAULT NULL,
    banned_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories Table with Parent-Child
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    parent_id INT DEFAULT NULL,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category (name, parent_id),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Listings Table
CREATE TABLE listings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category_id INT NOT NULL,
    size VARCHAR(20),
    `condition` VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_category (category_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Listing Images Table
CREATE TABLE listing_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    listing_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    INDEX idx_listing (listing_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wishlists Table
CREATE TABLE wishlists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    listing_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, listing_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages Table
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    listing_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (listing_id, sender_id, receiver_id),
    INDEX idx_unread (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews Table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    listing_id INT NOT NULL,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (listing_id, from_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contact Messages Table
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    subject VARCHAR(255) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create trigger to update user rating
DELIMITER $$
CREATE TRIGGER update_user_rating AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    UPDATE users 
    SET rating = (
        SELECT AVG(rating) 
        FROM reviews 
        WHERE to_user_id = NEW.to_user_id
    )
    WHERE id = NEW.to_user_id;
END$$
DELIMITER ;

-- HIERARCHICAL CATEGORIES
-- Main categories (parent_id = NULL)
INSERT INTO categories (name, parent_id) VALUES
('Men', NULL),
('Women', NULL),
('Unisex', NULL);

-- Men's subcategories (parent_id = 1)
INSERT INTO categories (name, parent_id) VALUES
('Tops', 1),
('Bottoms', 1),
('Outerwear', 1),
('Shoes', 1),
('Accessories', 1);

-- Women's subcategories (parent_id = 2)
INSERT INTO categories (name, parent_id) VALUES
('Tops', 2),
('Bottoms', 2),
('Outerwear', 2),
('Shoes', 2),
('Accessories', 2),
('Vintage', 2);

-- Unisex subcategories (parent_id = 14)
INSERT INTO categories (name, parent_id) VALUES
('T-Shirts', 14),
('Hoodies & Sweatshirts', 14),
('Jeans', 14),
('Jackets', 14),
('Sneakers', 14),
('Accessories', 14),
('Bags', 14),
('Hats & Caps', 14),
('Sunglasses', 14),
('Watches', 14);

-- SAMPLE DATA: Create admin user

-- Password is: admin123
INSERT INTO users (username, email, password, profile_pic, bio, location, rating, is_admin) VALUES
('admin', 'admin@jml.com', '$2y$10$.z5vcws40VsXk9HYZAoRqOvgQglVElWnp8cJyZwxA84y7fnQ29zzS', 'profiles/profile_1769179551_6973899fbfaad.jpg', '', 'Kathmandu, Nepal', 5.00, 1),
('apple', 'apple@gmail.com', '$2y$10$UUNYNMGIp9B9cHyVjtQh3OO4RYGiECD.dPeSC5Yz1zD0r37mSjai.', 'profiles/profile_6973130a43a23.jpg', '', 'nepal', 0.00, 0),
('Ball', 'ball@gmail.com', '$2y$10$7Q9Ipi39iJQ4qHVjOx6/PubCqJXoZL2cIgrvPG0IGydkCyFiny3s2', 'default.jpg', NULL, 'Kathmandu,Nepal', 0.00, 0);

-- MEN'S ITEMS (Main category: Men = 1)

-- Men's Tops (category_id = 3)
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(1, 3, 'Vintage Graphic T-Shirt', 'Cool vintage graphic tee in excellent condition', 800.00, 'M', 'Excellent', 'active'),
(1, 3, 'Classic White T-Shirt', 'Simple white cotton t-shirt, perfect for everyday wear', 500.00, 'L', 'Like New', 'active'),
(1, 3, 'Black Band T-Shirt', 'Retro band t-shirt from the 90s', 1200.00, 'M', 'Good', 'active'),
(1, 3, 'Striped Polo Shirt', 'Navy blue striped polo', 900.00, 'L', 'Excellent', 'active'),
(1, 3, 'Grey Crewneck Sweatshirt', 'Comfortable grey crewneck, minimal wear', 1500.00, 'L', 'Like New', 'active'),
(1, 3, 'Blue Pullover Sweatshirt', 'Cozy blue pullover sweater', 1300.00, 'M', 'Excellent', 'active'),
(1, 3, 'College Logo Sweatshirt', 'University branded sweatshirt', 1100.00, 'XL', 'Good', 'active'),
(1, 3, 'Red Athletic Sweatshirt', 'Sports style sweatshirt in red', 1000.00, 'L', 'Excellent', 'active');

-- Men's Bottoms (category_id = 4)
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(1, 4, 'Black Chino Pants', 'Slim fit black chinos', 1200.00, '32', 'Excellent', 'active'),
(1, 4, 'Khaki Cargo Pants', 'Comfortable cargo pants with pockets', 1000.00, '34', 'Good', 'active'),
(1, 4, 'Grey Dress Pants', 'Formal grey trousers', 1500.00, '32', 'Like New', 'active'),
(1, 4, 'Navy Work Pants', 'Durable work pants', 900.00, '34', 'Excellent', 'active'),
(1, 4, 'Denim Shorts', 'Classic blue denim shorts', 800.00, 'M', 'Good', 'active'),
(1, 4, 'Black Athletic Shorts', 'Sports shorts with pockets', 600.00, 'L', 'Excellent', 'active'),
(1, 4, 'Khaki Cargo Shorts', 'Casual cargo shorts', 750.00, 'M', 'Like New', 'active'),
(1, 4, 'Grey Sweat Shorts', 'Comfortable sweat shorts', 500.00, 'L', 'Good', 'active');

-- Men's Outerwear (category_id = 5)
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(1, 5, 'Black Zip-Up Hoodie', 'Classic black hoodie with zipper', 2000.00, 'M', 'Like New', 'active'),
(1, 5, 'Denim Jacket', 'Vintage denim jacket, lightly worn', 2500.00, 'L', 'Excellent', 'active'),
(1, 5, 'Grey Pullover Hoodie', 'Comfortable grey pullover hoodie', 1800.00, 'L', 'Good', 'active'),
(1, 5, 'Green Bomber Jacket', 'Military style bomber jacket', 3000.00, 'M', 'Excellent', 'active'),
(1, 5, 'Navy Blue Hoodie', 'Navy hoodie with front pocket', 1600.00, 'XL', 'Like New', 'active'),
(1, 5, 'Brown Leather Jacket', 'Genuine leather jacket', 4500.00, 'L', 'Good', 'active'),
(1, 5, 'Red Track Jacket', 'Sporty track jacket with stripes', 1400.00, 'M', 'Excellent', 'active'),
(1, 5, 'Black Blazer', 'Formal black blazer', 3500.00, 'L', 'Like New', 'active');

-- Men's Shoes (category_id = 6)
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(1, 6, 'White Sneakers', 'Classic white canvas sneakers', 1500.00, '42', 'Good', 'active'),
(1, 6, 'Black Leather Boots', 'Genuine leather boots', 3500.00, '43', 'Excellent', 'active'),
(1, 6, 'Running Shoes', 'Lightweight athletic running shoes', 2000.00, '42', 'Like New', 'active'),
(1, 6, 'Brown Dress Shoes', 'Formal brown oxford shoes', 2500.00, '44', 'Good', 'active');

-- Men's Accessories (category_id = 7)
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(1, 7, 'Leather Belt', 'Classic brown leather belt', 600.00, 'One Size', 'Excellent', 'active'),
(1, 7, 'Baseball Cap', 'Vintage baseball cap', 400.00, 'One Size', 'Good', 'active'),
(1, 7, 'Canvas Backpack', 'Durable canvas backpack', 1800.00, 'One Size', 'Like New', 'active'),
(1, 7, 'Sunglasses', 'Retro style sunglasses', 900.00, 'One Size', 'Excellent', 'active');

-- WOMEN'S ITEMS (Main category: Women = 2)

-- Women's Tops (category_id = 8)
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(1, 8, 'White Cotton Tank Top', 'Simple white cotton tank', 400.00, 'S', 'Like New', 'active'),
(1, 8, 'Black Lace Tank Top', 'Elegant lace detail tank top', 700.00, 'M', 'Excellent', 'active'),
(1, 8, 'Pink Ribbed Tank Top', 'Cute pink ribbed tank', 450.00, 'S', 'Good', 'active'),
(1, 8, 'Grey Athletic Tank Top', 'Sports tank for workouts', 600.00, 'M', 'Like New', 'active'),
(1, 8, 'Floral Print Blouse', 'Beautiful floral blouse', 1100.00, 'M', 'Excellent', 'active'),
(1, 8, 'White Button-Up Shirt', 'Classic white shirt', 900.00, 'S', 'Like New', 'active'),
(1, 8, 'Silk Blouse', 'Elegant silk blouse in cream', 1500.00, 'M', 'Excellent', 'active'),
(1, 8, 'Striped Casual Shirt', 'Navy striped casual shirt', 800.00, 'L', 'Good', 'active');

-- Women's Bottoms (category_id = 9)
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(1, 9, 'Black Skinny Pants', 'Stretchy black skinny pants', 1000.00, 'S', 'Excellent', 'active'),
(1, 9, 'Wide Leg Trousers', 'Elegant wide leg pants', 1300.00, 'M', 'Like New', 'active'),
(1, 9, 'Beige Chino Pants', 'Casual beige chinos', 900.00, 'S', 'Good', 'active'),
(1, 9, 'High-Waist Black Pants', 'Trendy high-waist pants', 1200.00, 'M', 'Excellent', 'active'),
(1, 9, 'Denim Cut-Off Shorts', 'Vintage denim shorts', 650.00, 'S', 'Good', 'active'),
(1, 9, 'Black High-Waist Shorts', 'Stylish high-waist shorts', 800.00, 'M', 'Excellent', 'active'),
(1, 9, 'White Linen Shorts', 'Comfortable linen shorts', 700.00, 'S', 'Like New', 'active'),
(1, 9, 'Pink Athletic Shorts', 'Sporty pink shorts', 550.00, 'M', 'Good', 'active');

-- Women's Outerwear (category_id = 10)
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(1, 10, 'Pink Zip Hoodie', 'Cute pink zip-up hoodie', 1600.00, 'S', 'Like New', 'active'),
(1, 10, 'Denim Jacket', 'Classic light wash denim jacket', 2200.00, 'M', 'Excellent', 'active'),
(1, 10, 'Black Leather Jacket', 'Faux leather moto jacket', 2800.00, 'S', 'Like New', 'active'),
(1, 10, 'Beige Cardigan', 'Cozy knit cardigan', 1400.00, 'M', 'Good', 'active'),
(1, 10, 'Grey Oversized Hoodie', 'Trendy oversized hoodie', 1700.00, 'One Size', 'Excellent', 'active'),
(1, 10, 'White Puffer Jacket', 'Warm white puffer jacket', 3000.00, 'M', 'Like New', 'active'),
(1, 10, 'Navy Blazer', 'Professional navy blazer', 2500.00, 'S', 'Excellent', 'active'),
(1, 10, 'Green Bomber Jacket', 'Olive green bomber', 2000.00, 'M', 'Good', 'active');

-- Women's Shoes (category_id = 11)
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(1, 11, 'Black Heels', 'Classic black stiletto heels', 2000.00, '38', 'Excellent', 'active'),
(1, 11, 'White Sneakers', 'Casual white sneakers', 1500.00, '37', 'Like New', 'active'),
(1, 11, 'Brown Ankle Boots', 'Stylish ankle boots', 2500.00, '39', 'Good', 'active'),
(1, 11, 'Flats', 'Comfortable ballet flats', 1000.00, '38', 'Excellent', 'active');

-- Women's Accessories (category_id = 12)
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(1, 12, 'Leather Handbag', 'Classic leather handbag', 3500.00, 'One Size', 'Excellent', 'active'),
(1, 12, 'Silk Scarf', 'Beautiful silk scarf', 800.00, 'One Size', 'Like New', 'active'),
(1, 12, 'Gold Necklace', 'Elegant gold necklace', 1500.00, 'One Size', 'Good', 'active'),
(1, 12, 'Sunglasses', 'Oversized sunglasses', 900.00, 'One Size', 'Excellent', 'active');

-- Women's Vintage (category_id = 13)
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(1, 13, 'Vintage Floral Dress', '1970s floral maxi dress', 2500.00, 'M', 'Vintage', 'active'),
(1, 13, 'Retro Denim Jacket', '1980s vintage denim jacket', 3000.00, 'S', 'Vintage', 'active'),
(1, 13, 'Vintage Leather Bag', 'Classic vintage leather bag', 4000.00, 'One Size', 'Vintage', 'active'),
(1, 13, 'Retro Sunglasses', '1990s style sunglasses', 1200.00, 'One Size', 'Vintage', 'active');

-- Unisex item by user 2
INSERT INTO listings (user_id, category_id, title, description, price, size, `condition`, status) VALUES
(2, 23, 'sunglasses', 'sunglasses for summer or sunny day', 400.00, 'One Size', 'Like New', 'active');

-- SAMPLE IMAGES FOR LISTINGS
INSERT INTO listing_images (listing_id, image_path, is_primary) VALUES
(1, 'listings/1.jpeg', 1),
(2, 'listings/2.jpeg', 1),
(3, 'listings/3.jpeg', 1),
(4, 'listings/4.jpeg', 1),
(5, 'listings/5.jpeg', 1),
(6, 'listings/6.jpeg', 1),
(7, 'listings/7.jpeg', 1),
(8, 'listings/8.jpeg', 1),
(9, 'listings/9.jpeg', 1),
(10, 'listings/10.jpeg', 1),
(11, 'listings/11.jpeg', 1),
(12, 'listings/12.jpeg', 1),
(13, 'listings/13.jpeg', 1),
(14, 'listings/14.jpeg', 1),
(15, 'listings/15.jpeg', 1),
(16, 'listings/16.jpeg', 1),
(17, 'listings/17.jpeg', 1),
(18, 'listings/18.jpeg', 1),
(19, 'listings/19.jpeg', 1),
(20, 'listings/20.jpeg', 1),
(21, 'listings/21.jpeg', 1),
(22, 'listings/22.jpeg', 1),
(23, 'listings/23.jpeg', 1),
(24, 'listings/24.jpeg', 1),
(25, 'listings/25.jpeg', 1),
(26, 'listings/26.jpeg', 1),
(27, 'listings/27.jpeg', 1),
(28, 'listings/28.jpeg', 1),
(29, 'listings/29.jpeg', 1),
(30, 'listings/30.jpeg', 1),
(31, 'listings/31.jpeg', 1),
(32, 'listings/32.jpeg', 1),
(33, '1.jpeg', 1),
(34, '2.jpeg', 1),
(35, '3.jpeg', 1),
(36, '4.jpeg', 1),
(37, '5.jpeg', 1),
(38, '6.jpeg', 1),
(39, '7.jpeg', 1),
(40, '8.jpeg', 1),
(41, '9.jpeg', 1),
(42, '10.jpeg', 1),
(43, '11.jpeg', 1),
(44, '12.jpeg', 1),
(45, '13.jpeg', 1),
(46, '14.jpeg', 1),
(47, '15.jpeg', 1),
(48, '16.jpeg', 1),
(49, '17.jpeg', 1),
(50, '18.jpeg', 1),
(51, '19.jpeg', 1),
(52, '20.jpeg', 1),
(53, '21.jpeg', 1),
(54, '22.jpeg', 1),
(55, '23.jpeg', 1),
(56, '24.jpeg', 1),
(57, '25.jpeg', 1),
(58, '26.jpeg', 1),
(59, '27.jpeg', 1),
(60, '28.jpeg', 1),
(61, '29.jpeg', 1),
(62, '30.jpeg', 1),
(63, '31.jpeg', 1),
(64, '32.jpeg', 1),
(65, 'listings/33.jpeg', 1),
(66, 'listings/34.jpeg', 1),
(67, 'listings/35.jpeg', 1),
(68, 'listings/36.jpeg', 1),
(69, 'listings/listing_1769167136_69735920e6a54.jpg', 1);

-- SAMPLE CONTACT MESSAGE
INSERT INTO contact_messages (name, email, phone, subject, message, status) VALUES
('ball', 'ball@gmail.com', '9700000000', 'refund', 'give me refund', 'new');

-- SAMPLE WISHLIST
INSERT INTO wishlists (user_id, listing_id) VALUES
(2, 1);