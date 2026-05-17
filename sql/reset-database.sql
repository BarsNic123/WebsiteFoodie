-- ============================================================
--  FULL DATABASE RESET — deletes ALL data
--  Only use when you want a clean slate.
--
--  After running this file:
--    1. Import sql/schema.sql
--    2. php api/seed.php
-- ============================================================

USE foodieph;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS restaurants;
DROP TABLE IF EXISTS restaurant_applications;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS delivery_zones;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;
