<?php
/**
 * Copy this file to config.php and adjust.
 * - use_database false: api/data.php reads data.json (no MySQL).
 * - use_database true: run sql/schema.sql, create DB, then php api/seed.php
 */
return [
    'use_database' => true,
    'database' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'foodieph',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
