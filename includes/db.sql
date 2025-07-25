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
ALTER TABLE colloques ADD COLUMN duree INT;


CREATE TABLE presences (
  id INT AUTO_INCREMENT PRIMARY KEY,
  participant_id INT NOT NULL,
  date_presence DATE NOT NULL,
  status ENUM('present', 'absent') DEFAULT 'absent',
  FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
  UNIQUE KEY unique_participant_day (participant_id, date_presence)
);
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom_complet VARCHAR(255) NOT NULL,
  institution VARCHAR(255),
  fonction VARCHAR(255),
  email VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE participants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  colloque_id INT NOT NULL,
  user_id INT NOT NULL,
  FOREIGN KEY (colloque_id) REFERENCES colloques(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
