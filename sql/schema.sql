-- ============================================================
--  Foodie.PH — Full Database Schema
--  Run in phpMyAdmin or: mysql -u root < sql/schema.sql
--  All monetary values stored in CENTAVOS (INT) unless noted.
-- ============================================================

CREATE DATABASE IF NOT EXISTS foodieph
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE foodieph;

-- ============================================================
-- 1. CATEGORIES
--    Drives the "Browse by Category" chips on the homepage.
--    filter_key must match the category field in restaurants.
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED  NOT NULL,
  name        VARCHAR(100)  NOT NULL COMMENT 'Display name, e.g. Burger',
  icon        VARCHAR(32)   NOT NULL COMMENT 'Emoji icon, e.g. 🍔',
  filter_key  VARCHAR(50)   NOT NULL COMMENT 'Slug used for JS filtering, e.g. fast-food',
  PRIMARY KEY (id),
  UNIQUE KEY uq_filter_key (filter_key)
) ENGINE=InnoDB
  COMMENT='Food category chips shown on the homepage';

-- ============================================================
-- 2. RESTAURANTS
--    Core restaurant listing. Managed via admin.php.
--    cuisines_json stores a JSON array, e.g. ["Filipino","Grill"]
-- ============================================================
CREATE TABLE IF NOT EXISTS restaurants (
  id            INT UNSIGNED    NOT NULL,
  name          VARCHAR(200)    NOT NULL,
  image         VARCHAR(600)    NOT NULL  COMMENT 'Full URL to cover photo',
  rating        DECIMAL(2,1)    NOT NULL  DEFAULT 0.0,
  delivery_time VARCHAR(20)     NOT NULL  COMMENT 'Display string, e.g. 20-30',
  delivery_fee  INT             NOT NULL  DEFAULT 0 COMMENT 'In centavos',
  cuisines_json JSON            NOT NULL  COMMENT 'JSON array of cuisine strings',
  tag           VARCHAR(100)    NOT NULL  DEFAULT '' COMMENT 'Badge text, e.g. Popular',
  tag_style     VARCHAR(20)     NOT NULL  DEFAULT '' COMMENT 'CSS modifier: green | gold | blue | (empty=red)',
  category      VARCHAR(50)     NOT NULL  COMMENT 'Must match a categories.filter_key',
  is_open       TINYINT(1)      NOT NULL  DEFAULT 1 COMMENT '1=open, 0=closed',
  created_at    TIMESTAMP       NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP       NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_category (category),
  KEY idx_is_open  (is_open)
) ENGINE=InnoDB
  COMMENT='Partner restaurants listed on the platform';

-- ============================================================
-- 3. MENU ITEMS
--    Each item belongs to one restaurant.
--    price stored in centavos (e.g. ₱189 → 18900).
-- ============================================================
CREATE TABLE IF NOT EXISTS menu_items (
  id            INT UNSIGNED  NOT NULL,
  restaurant_id INT UNSIGNED  NOT NULL,
  name          VARCHAR(200)  NOT NULL,
  description   VARCHAR(600)  NOT NULL  DEFAULT '',
  price         INT           NOT NULL  DEFAULT 0 COMMENT 'In centavos',
  is_available  TINYINT(1)    NOT NULL  DEFAULT 1 COMMENT '1=available, 0=sold out',
  sort_order    INT           NOT NULL  DEFAULT 0 COMMENT 'Display order within restaurant',
  created_at    TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_restaurant (restaurant_id),
  CONSTRAINT fk_menu_restaurant
    FOREIGN KEY (restaurant_id) REFERENCES restaurants (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  COMMENT='Menu items for each restaurant';

-- ============================================================
-- 4. USERS
--    Customer and admin accounts.
--    phone and email_notifications added from register.php.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  first_name           VARCHAR(80)   NOT NULL  DEFAULT '',
  last_name            VARCHAR(80)   NOT NULL  DEFAULT '',
  name                 VARCHAR(160)  NOT NULL  COMMENT 'Full name (first + last), kept for backward compat',
  email                VARCHAR(190)  NOT NULL,
  phone                VARCHAR(20)   NOT NULL  DEFAULT '' COMMENT 'PH format, e.g. 09171234567',
  password_hash        VARCHAR(255)  NOT NULL,
  role                 ENUM('admin','user') NOT NULL DEFAULT 'user',
  email_notifications  TINYINT(1)   NOT NULL  DEFAULT 1 COMMENT '1=opted in from register checkbox',
  is_active            TINYINT(1)   NOT NULL  DEFAULT 1,
  last_login_at        TIMESTAMP    NULL       DEFAULT NULL,
  created_at           TIMESTAMP    NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_email (email),
  KEY idx_role (role)
) ENGINE=InnoDB
  COMMENT='Customer and admin user accounts';

-- ============================================================
-- 5. ORDERS
--    Placed via checkout.php.
--    user_id is nullable to support guest checkout.
--    full_name, delivery_notes, payment_method, order_notes
--    added to capture all checkout.html fields.
--    All money values in centavos.
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  user_id          INT UNSIGNED  NULL      DEFAULT NULL COMMENT 'NULL = guest order',
  restaurant_id    INT UNSIGNED  NOT NULL,
  full_name        VARCHAR(160)  NOT NULL  COMMENT 'From checkout form',
  contact_number   VARCHAR(20)   NOT NULL,
  delivery_address TEXT          NOT NULL,
  delivery_notes   TEXT          NOT NULL  DEFAULT '' COMMENT 'Landmarks / special instructions',
  payment_method   VARCHAR(50)   NOT NULL  DEFAULT '' COMMENT 'cash | gcash | maya | card',
  order_notes      TEXT          NOT NULL  DEFAULT '' COMMENT 'Allergies / special requests',
  items_json       JSON          NOT NULL  COMMENT 'Snapshot of cart at time of order',
  subtotal         INT           NOT NULL  DEFAULT 0 COMMENT 'In centavos',
  delivery_fee     INT           NOT NULL  DEFAULT 0 COMMENT 'In centavos',
  total_amount     INT           NOT NULL  DEFAULT 0 COMMENT 'In centavos',
  status           ENUM('pending','preparing','out_for_delivery','delivered','cancelled')
                                 NOT NULL  DEFAULT 'pending',
  estimated_delivery VARCHAR(30) NOT NULL  DEFAULT '60-90 minutes',
  created_at       TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
  COMMENT='Customer orders placed through checkout';

-- ============================================================
-- 6. RESTAURANT APPLICATIONS
--    Submitted via partner.php (Partner With Us form).
--    Captures every field from the registration form.
-- ============================================================
CREATE TABLE IF NOT EXISTS restaurant_applications (
  id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,

  -- Business Info
  restaurant_name   VARCHAR(200)  NOT NULL,
  cuisine_type      VARCHAR(100)  NOT NULL COMMENT 'Category slug, e.g. fast-food',
  description       TEXT          NOT NULL DEFAULT '',

  -- Owner / Contact
  owner_first_name  VARCHAR(80)   NOT NULL DEFAULT '',
  owner_last_name   VARCHAR(80)   NOT NULL DEFAULT '',
  owner_name        VARCHAR(200)  NOT NULL COMMENT 'Full name (first + last)',
  owner_email       VARCHAR(190)  NOT NULL,
  owner_phone       VARCHAR(30)   NOT NULL,

  -- Location & Operations
  business_address  TEXT          NOT NULL,
  city              VARCHAR(100)  NOT NULL,
  delivery_zones    VARCHAR(200)  NOT NULL DEFAULT '',
  operating_hours   VARCHAR(200)  NOT NULL DEFAULT '' COMMENT 'e.g. Mon-Sun 8AM-10PM',
  avg_delivery_time VARCHAR(50)   NOT NULL DEFAULT '' COMMENT 'e.g. 60-90 mins',
  delivery_fee      INT           NOT NULL DEFAULT 0 COMMENT 'In centavos',
  min_order         INT           NOT NULL DEFAULT 0 COMMENT 'In centavos',

  -- Payment & Preferences
  payment_methods   VARCHAR(200)  NOT NULL DEFAULT '' COMMENT 'Comma-separated list',
  wants_updates     TINYINT(1)    NOT NULL DEFAULT 1 COMMENT 'Opted in to email/SMS promos',
  same_phone        TINYINT(1)    NOT NULL DEFAULT 1 COMMENT 'Business phone = mobile number',

  -- Legal & Compliance
  bir_tin           VARCHAR(50)   NOT NULL DEFAULT '' COMMENT 'BIR Tax Identification Number',
  business_permit   VARCHAR(100)  NOT NULL DEFAULT '' COMMENT 'LGU business permit number',
  has_bir_form      TINYINT(1)    NULL     DEFAULT NULL COMMENT '1=yes, 0=no BIR 2303 form',
  has_android       TINYINT(1)    NULL     DEFAULT NULL COMMENT '1=yes, 0=no Android device',

  -- Misc
  social_media      VARCHAR(300)  NOT NULL DEFAULT '' COMMENT 'Facebook / Instagram URL',
  how_heard         VARCHAR(200)  NOT NULL DEFAULT '' COMMENT 'How they found Foodie.PH',

  -- Admin workflow
  status            ENUM('pending','under_review','approved','rejected')
                                  NOT NULL DEFAULT 'pending',
  admin_notes       TEXT          NOT NULL DEFAULT '' COMMENT 'Internal notes from admin',
  reviewed_by       INT UNSIGNED  NULL     DEFAULT NULL COMMENT 'users.id of admin who reviewed',
  reviewed_at       TIMESTAMP     NULL     DEFAULT NULL,
  created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_status      (status),
  KEY idx_city        (city),
  KEY idx_created     (created_at),
  CONSTRAINT fk_app_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  COMMENT='Restaurant partner applications submitted via partner.php';

-- ============================================================
-- 7. ORDER STATUS HISTORY  (audit trail)
--    Every time an order status changes, a row is inserted.
-- ============================================================
CREATE TABLE IF NOT EXISTS order_status_history (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  order_id    INT UNSIGNED  NOT NULL,
  old_status  VARCHAR(30)   NOT NULL DEFAULT '',
  new_status  VARCHAR(30)   NOT NULL,
  changed_by  INT UNSIGNED  NULL     DEFAULT NULL COMMENT 'users.id of admin/system',
  note        VARCHAR(300)  NOT NULL DEFAULT '',
  changed_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_order (order_id),
  CONSTRAINT fk_history_order
    FOREIGN KEY (order_id) REFERENCES orders (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  COMMENT='Audit trail of every order status change';

-- ============================================================
-- 8. DELIVERY ZONES  (reference table)
--    Used by the zone selector banner on the homepage.
-- ============================================================
CREATE TABLE IF NOT EXISTS delivery_zones (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100)  NOT NULL COMMENT 'e.g. Metro Manila',
  slug        VARCHAR(50)   NOT NULL COMMENT 'e.g. metro-manila',
  icon        VARCHAR(32)   NOT NULL DEFAULT '' COMMENT 'Emoji or FA class',
  is_active   TINYINT(1)    NOT NULL DEFAULT 1,
  sort_order  INT           NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB
  COMMENT='Delivery zones shown in the zone selector banner';

-- ============================================================
-- DEFAULT DATA — Delivery Zones
-- ============================================================
INSERT IGNORE INTO delivery_zones (name, slug, icon, is_active, sort_order) VALUES
  ('Metro Manila',        'metro-manila', '🏙️',  1, 1),
  ('Cebu',                'cebu',         '🏖️',  1, 2),
  ('Nationwide Delivery', 'nationwide',   '🚚',  1, 3);

-- ============================================================
-- DEFAULT DATA — Categories  (matches data.json)
-- ============================================================
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

-- ============================================================
-- DEFAULT DATA — Admin user
--    Password: admin123  (bcrypt hash — change after first login)
-- ============================================================
INSERT IGNORE INTO users
  (first_name, last_name, name, email, phone, password_hash, role)
VALUES
  ('Foodie', 'Admin', 'Foodie Admin',
   'admin@foodieph.com', '',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'admin');
