-- Briefly — XAMPP / MySQL setup
-- Run from phpMyAdmin (SQL tab) or:
-- /Applications/XAMPP/xamppfiles/bin/mysql -u root < database/sql/xampp-setup.sql

CREATE DATABASE IF NOT EXISTS briefly
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE briefly;
