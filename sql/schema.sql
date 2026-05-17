-- ============================================================
--  Foodie.PH — Database Schema (v2)
-- ============================================================
--
--  Setup (XAMPP) — FIRST TIME ONLY:
--    1. Start Apache + MySQL
--    2. Import this file (creates tables; does NOT delete existing data)
--    3. Copy config.sample.php → config.php (use_database => true)
--    4. Load restaurants from data.json:
--         php api\seed.php
--
--  WARNING: Do NOT use sql/reset-database.sql unless you want to
--  wipe ALL orders, restaurants, and users. After a reset, run seed.php.
--
--  Money: all INT amounts are Philippine pesos (whole numbers),
--         e.g. 189 = ₱189.00 — same as data.json and the UI.
--
--  Orders: saved by api/place-order.php on every Place Order.
--  Admin:  admin@foodieph.com / admin123 (change after first login)
--
-- ============================================================

CREATE DATABASE IF NOT EXISTS foodieph
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE foodieph;

SET NAMES utf8mb4;

-- ============================================================
-- 1. CATEGORIES
--    Homepage category chips. filter_key = restaurants.category
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED  NOT NULL,
  name        VARCHAR(100)  NOT NULL COMMENT 'Display name, e.g. Burger',
  icon        VARCHAR(32)   NOT NULL COMMENT 'Emoji icon, e.g. 🍔',
  filter_key  VARCHAR(50)   NOT NULL COMMENT 'Slug for JS filtering, e.g. fast-food (many chips can share one)',
  PRIMARY KEY (id),
  KEY idx_filter_key (filter_key)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Food category chips on the homepage';

-- ============================================================
-- 2. RESTAURANTS
--    Managed in admin.php. cuisines_json = ["Filipino","Grill"]
-- ============================================================
CREATE TABLE IF NOT EXISTS restaurants (
  id            INT UNSIGNED    NOT NULL,
  name          VARCHAR(200)    NOT NULL,
  image         VARCHAR(600)    NOT NULL  COMMENT 'Cover photo URL',
  rating        DECIMAL(2,1)    NOT NULL  DEFAULT 0.0,
  delivery_time VARCHAR(20)     NOT NULL  COMMENT 'Display string, e.g. 45-60',
  delivery_fee  INT UNSIGNED    NOT NULL  DEFAULT 0 COMMENT 'PHP pesos, e.g. 49 = ₱49',
  cuisines_json JSON            NOT NULL  COMMENT 'JSON array of cuisine strings',
  tag           VARCHAR(100)    NOT NULL  DEFAULT '' COMMENT 'Badge: Popular, New, etc.',
  tag_style     VARCHAR(20)     NOT NULL  DEFAULT '' COMMENT 'CSS: green | gold | blue | empty=red',
  category      VARCHAR(50)     NOT NULL  COMMENT 'Must match categories.filter_key',
  is_open       TINYINT(1)      NOT NULL  DEFAULT 1 COMMENT '1=open, 0=closed',
  created_at    TIMESTAMP       NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP       NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_category (category),
  KEY idx_is_open  (is_open)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Partner restaurants on the platform';

-- ============================================================
-- 3. MENU ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS menu_items (
  id            INT UNSIGNED  NOT NULL,
  restaurant_id INT UNSIGNED  NOT NULL,
  name          VARCHAR(200)  NOT NULL,
  description   VARCHAR(600)  NOT NULL  DEFAULT '',
  price         INT UNSIGNED  NOT NULL  DEFAULT 0 COMMENT 'PHP pesos, e.g. 189 = ₱189',
  is_available  TINYINT(1)    NOT NULL  DEFAULT 1 COMMENT '1=available, 0=sold out',
  sort_order    INT           NOT NULL  DEFAULT 0 COMMENT 'Display order within restaurant',
  created_at    TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_restaurant (restaurant_id),
  CONSTRAINT fk_menu_restaurant
    FOREIGN KEY (restaurant_id) REFERENCES restaurants (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Menu items per restaurant';

-- ============================================================
-- 4. USERS
--    Customers + admins (login.php, register.php)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  first_name           VARCHAR(80)   NOT NULL  DEFAULT '',
  last_name            VARCHAR(80)   NOT NULL  DEFAULT '',
  name                 VARCHAR(160)  NOT NULL  COMMENT 'Full display name',
  email                VARCHAR(190)  NOT NULL,
  phone                VARCHAR(20)   NOT NULL  DEFAULT '',
  street_address       VARCHAR(300)  NOT NULL  DEFAULT '',
  unit                 VARCHAR(100)  NOT NULL  DEFAULT '',
  city                 VARCHAR(100)  NOT NULL  DEFAULT '',
  state                VARCHAR(100)  NOT NULL  DEFAULT '',
  postal_code          VARCHAR(20)   NOT NULL  DEFAULT '',
  country              VARCHAR(100)  NOT NULL  DEFAULT 'Philippines',
  password_hash        VARCHAR(255)  NOT NULL,
  role                 ENUM('admin','user') NOT NULL DEFAULT 'user',
  email_notifications  TINYINT(1)    NOT NULL  DEFAULT 1,
  job_title            VARCHAR(100)  NOT NULL  DEFAULT '' COMMENT 'Position / role title (staff & admin)',
  department           VARCHAR(100)  NOT NULL  DEFAULT '' COMMENT 'Team or department',
  staff_id             VARCHAR(50)   NOT NULL  DEFAULT '' COMMENT 'Internal staff or employee ID',
  emergency_contact    VARCHAR(30)   NOT NULL  DEFAULT '' COMMENT 'Emergency contact number',
  is_active            TINYINT(1)    NOT NULL  DEFAULT 1,
  last_login_at        TIMESTAMP     NULL      DEFAULT NULL,
  created_at           TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_email (email),
  KEY idx_role (role)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Customer and admin accounts';

-- ============================================================
-- 5. ORDERS
--    One row per Place Order (api/place-order.php).
--    user_id NULL = guest checkout.
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
  id                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  user_id            INT UNSIGNED  NULL      DEFAULT NULL COMMENT 'NULL = guest',
  restaurant_id      INT UNSIGNED  NOT NULL,
  full_name          VARCHAR(160)  NOT NULL,
  contact_number     VARCHAR(20)   NOT NULL,
  delivery_address   TEXT          NOT NULL,
  delivery_notes     TEXT          NOT NULL,
  payment_method     VARCHAR(50)   NOT NULL  DEFAULT '' COMMENT 'cash | gcash | maya | card',
  order_notes        TEXT          NOT NULL,
  items_json         JSON          NOT NULL  COMMENT 'Cart snapshot at checkout',
  subtotal           INT UNSIGNED  NOT NULL  DEFAULT 0 COMMENT 'PHP pesos',
  delivery_fee       INT UNSIGNED  NOT NULL  DEFAULT 0 COMMENT 'PHP pesos',
  total_amount       INT UNSIGNED  NOT NULL  DEFAULT 0 COMMENT 'PHP pesos (subtotal + delivery_fee)',
  status             ENUM(
                         'pending',
                         'preparing',
                         'out_for_delivery',
                         'delivered',
                         'cancelled'
                       ) NOT NULL DEFAULT 'pending',
  estimated_delivery VARCHAR(30)   NOT NULL  DEFAULT '60-90 minutes',
  created_at         TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user       (user_id),
  KEY idx_restaurant (restaurant_id),
  KEY idx_status     (status),
  KEY idx_created    (created_at),
  CONSTRAINT fk_order_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_order_restaurant
    FOREIGN KEY (restaurant_id) REFERENCES restaurants (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Customer orders from checkout';

-- ============================================================
-- 6. RESTAURANT APPLICATIONS
--    Partner sign-ups (partner.php)
-- ============================================================
CREATE TABLE IF NOT EXISTS restaurant_applications (
  id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  restaurant_name   VARCHAR(200)  NOT NULL,
  cuisine_type      VARCHAR(100)  NOT NULL,
  description       TEXT          NOT NULL,
  owner_first_name  VARCHAR(80)   NOT NULL  DEFAULT '',
  owner_last_name   VARCHAR(80)   NOT NULL  DEFAULT '',
  owner_name        VARCHAR(200)  NOT NULL,
  owner_email       VARCHAR(190)  NOT NULL,
  owner_phone       VARCHAR(30)   NOT NULL,
  business_address  TEXT          NOT NULL,
  city              VARCHAR(100)  NOT NULL,
  delivery_zones    VARCHAR(200)  NOT NULL  DEFAULT '',
  operating_hours   VARCHAR(200)  NOT NULL  DEFAULT '',
  avg_delivery_time VARCHAR(50)   NOT NULL  DEFAULT '',
  delivery_fee      INT UNSIGNED  NOT NULL  DEFAULT 0 COMMENT 'PHP pesos',
  min_order         INT UNSIGNED  NOT NULL  DEFAULT 0 COMMENT 'PHP pesos',
  payment_methods   VARCHAR(200)  NOT NULL  DEFAULT '',
  wants_updates     TINYINT(1)    NOT NULL  DEFAULT 1,
  same_phone        TINYINT(1)    NOT NULL  DEFAULT 1,
  bir_tin           VARCHAR(50)   NOT NULL  DEFAULT '',
  business_permit   VARCHAR(100)  NOT NULL  DEFAULT '',
  has_bir_form      TINYINT(1)    NULL      DEFAULT NULL,
  has_android       TINYINT(1)    NULL      DEFAULT NULL,
  social_media      VARCHAR(300)  NOT NULL  DEFAULT '',
  how_heard         VARCHAR(200)  NOT NULL  DEFAULT '',
  status            ENUM('pending','under_review','approved','rejected')
                                  NOT NULL  DEFAULT 'pending',
  admin_notes       TEXT          NOT NULL,
  reviewed_by       INT UNSIGNED  NULL      DEFAULT NULL,
  reviewed_at       TIMESTAMP     NULL      DEFAULT NULL,
  created_at        TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_status  (status),
  KEY idx_city    (city),
  KEY idx_created (created_at),
  CONSTRAINT fk_app_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Restaurant partner applications';

-- ============================================================
-- 7. ORDER STATUS HISTORY
--    Written on place-order and when admin updates status.
-- ============================================================
CREATE TABLE IF NOT EXISTS order_status_history (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  order_id    INT UNSIGNED  NOT NULL,
  old_status  VARCHAR(30)   NOT NULL  DEFAULT '',
  new_status  VARCHAR(30)   NOT NULL,
  changed_by  INT UNSIGNED  NULL      DEFAULT NULL COMMENT 'users.id or NULL for guest',
  note        VARCHAR(300)  NOT NULL  DEFAULT '',
  changed_at  TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_order (order_id),
  CONSTRAINT fk_history_order
    FOREIGN KEY (order_id) REFERENCES orders (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail for order status changes';

-- ============================================================
-- 8. DELIVERY ZONES
-- ============================================================
CREATE TABLE IF NOT EXISTS delivery_zones (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100)  NOT NULL,
  slug        VARCHAR(50)   NOT NULL,
  icon        VARCHAR(32)   NOT NULL  DEFAULT '',
  is_active   TINYINT(1)    NOT NULL  DEFAULT 1,
  sort_order  INT           NOT NULL  DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Delivery zones for the homepage banner';

-- ============================================================
-- SEED DATA — reference tables
-- (Restaurants + menu: run php api/seed.php from data.json)
-- ============================================================

INSERT IGNORE INTO delivery_zones (name, slug, icon, is_active, sort_order) VALUES
  ('Metro Manila',        'metro-manila', '🏙️',  1, 1),
  ('Cebu',                'cebu',         '🏖️',  1, 2),
  ('Nationwide Delivery', 'nationwide',   '🚚',  1, 3);

INSERT IGNORE INTO categories (id, name, icon, filter_key) VALUES
  (1,  'Burger',    '🍔', 'fast-food'),
  (2,  'Pizza',     '🍕', 'fast-food'),
  (3,  'Chicken',   '🍗', 'fast-food'),
  (4,  'Asian',     '🍜', 'asian'),
  (5,  'Sushi',     '🍣', 'asian'),
  (6,  'Desserts',  '🍰', 'desserts'),
  (7,  'Coffee',    '☕', 'desserts'),
  (8,  'Healthy',   '🥗', 'healthy'),
  (9,  'Steak',     '🥩', 'fast-food'),
  (10, 'Pasta',     '🍝', 'fast-food'),
  (11, 'Breakfast', '🍳', 'healthy'),
  (12, 'Milk Tea',  '🧋', 'desserts');

-- Password: admin123
INSERT IGNORE INTO users (
  first_name, last_name, name, email, phone, password_hash, role
) VALUES (
  'Foodie', 'Admin', 'Foodie Admin',
  'admin@foodieph.com', '',
  '$2y$10$7Pd6cd1SKj45QwGsFxm8N.uYq.kvLklbiOoCCi5YEQlKdG/UxpRAm',
  'admin'
);

-- ============================================================
-- Done. Next step:
--   cd C:\xampp\htdocs\WebsiteFoodie
--   C:\xampp\php\php.exe api\seed.php
-- ============================================================
