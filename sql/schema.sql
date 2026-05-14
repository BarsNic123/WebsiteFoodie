-- Run in phpMyAdmin or: mysql -u root < sql/schema.sql
CREATE DATABASE IF NOT EXISTS foodieph CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE foodieph;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  icon VARCHAR(32) NOT NULL,
  filter_key VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS restaurants (
  id INT UNSIGNED NOT NULL PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  image VARCHAR(600) NOT NULL,
  rating DECIMAL(2,1) NOT NULL,
  delivery_time VARCHAR(20) NOT NULL,
  delivery_fee INT NOT NULL,
  cuisines_json JSON NOT NULL,
  tag VARCHAR(100) NOT NULL,
  tag_style VARCHAR(20) NOT NULL DEFAULT '',
  category VARCHAR(50) NOT NULL,
  is_open TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menu_items (
  id INT UNSIGNED NOT NULL PRIMARY KEY,
  restaurant_id INT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  description VARCHAR(600) NOT NULL,
  price INT NOT NULL,
  CONSTRAINT fk_menu_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS restaurant_applications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  restaurant_name VARCHAR(200) NOT NULL,
  cuisine_type VARCHAR(100) NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  owner_name VARCHAR(200) NOT NULL,
  owner_email VARCHAR(190) NOT NULL,
  owner_phone VARCHAR(30) NOT NULL,
  business_address TEXT NOT NULL,
  city VARCHAR(100) NOT NULL,
  delivery_zones VARCHAR(200) NOT NULL DEFAULT '',
  operating_hours VARCHAR(200) NOT NULL DEFAULT '',
  avg_delivery_time VARCHAR(50) NOT NULL DEFAULT '',
  delivery_fee INT NOT NULL DEFAULT 0,
  min_order INT NOT NULL DEFAULT 0,
  payment_methods VARCHAR(200) NOT NULL DEFAULT '',
  bir_tin VARCHAR(50) NOT NULL DEFAULT '',
  business_permit VARCHAR(100) NOT NULL DEFAULT '',
  social_media VARCHAR(300) NOT NULL DEFAULT '',
  how_heard VARCHAR(200) NOT NULL DEFAULT '',
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  restaurant_id INT UNSIGNED NOT NULL,
  items_json JSON NOT NULL,
  subtotal INT NOT NULL,
  delivery_fee INT NOT NULL,
  total_amount INT NOT NULL,
  delivery_address TEXT NOT NULL,
  contact_number VARCHAR(20) NOT NULL,
  status ENUM('pending','preparing','delivering','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_order_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON DELETE CASCADE
) ENGINE=InnoDB;
