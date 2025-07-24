-- Script SQL pour créer la base de données et les tables du système de colloques

-- 1. Création de la base de données
CREATE DATABASE IF NOT EXISTS colloque_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE colloque_db;

-- 2. Table des colloques
CREATE TABLE IF NOT EXISTS colloques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    lieu VARCHAR(255),
    date_colloque DATE,
    heure_colloque TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Table des participants
CREATE TABLE IF NOT EXISTS participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    horodateur DATETIME,
    username VARCHAR(100),
    nom_complet VARCHAR(255),
    institution VARCHAR(255),
    fonction VARCHAR(255),
    email VARCHAR(255),
    colloque_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (colloque_id) REFERENCES colloques(id) ON DELETE CASCADE
);

ALTER TABLE participants ADD COLUMN presence ENUM('oui','non') DEFAULT 'non';
ALTER TABLE colloques
ADD COLUMN statut ENUM('actif', 'termine') DEFAULT 'actif';

-- Script SQL pour créer la base de données et les tables du système de colloques

-- 1. Création de la base de données
CREATE DATABASE IF NOT EXISTS nouya2095517_1ajr2u CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nouya2095517_1ajr2u;

-- 2. Table des colloques
CREATE TABLE IF NOT EXISTS colloques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    lieu VARCHAR(255),
    date_colloque DATE,
    heure_colloque TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Table des participants
CREATE TABLE IF NOT EXISTS participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    horodateur DATETIME,
    username VARCHAR(100),
    nom_complet VARCHAR(255),
    institution VARCHAR(255),
    fonction VARCHAR(255),
    email VARCHAR(255),
    colloque_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (colloque_id) REFERENCES colloques(id) ON DELETE CASCADE
);

ALTER TABLE participants ADD COLUMN presence ENUM('oui','non') DEFAULT 'non';
ALTER TABLE colloques
ADD COLUMN statut ENUM('actif', 'termine') DEFAULT 'actif';
