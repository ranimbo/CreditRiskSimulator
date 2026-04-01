-- ============================================
-- Credit Risk Simulator - Database Schema
-- ============================================
-- Run this script first to create the database structure
-- Then run seed.sql to populate with demo data

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS credit_risk_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE credit_risk_db;

-- ============================================
-- Table: users (Bank agents and administrators)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    role ENUM('admin', 'agent') NOT NULL DEFAULT 'agent',
    statut ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: clients (Bank clients/borrowers)
-- ============================================
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cin VARCHAR(20) NOT NULL UNIQUE COMMENT 'Carte d''identité nationale',
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    email VARCHAR(255) NULL,
    telephone VARCHAR(20) NULL,
    adresse TEXT NULL,
    ville VARCHAR(100) NULL,
    
    -- Professional information
    situation_professionnelle ENUM('CDI', 'CDD', 'Fonctionnaire', 'Independant', 'Sans emploi', 'Retraite') NOT NULL,
    employeur VARCHAR(255) NULL,
    anciennete_emploi INT DEFAULT 0 COMMENT 'Ancienneté en mois',
    
    -- Financial information
    revenu_mensuel DECIMAL(12,2) NOT NULL DEFAULT 0,
    charges_mensuelles DECIMAL(12,2) NOT NULL DEFAULT 0,
    
    -- Credit history
    historique_credit ENUM('Aucun incident', 'Un incident', 'Plusieurs incidents') NOT NULL DEFAULT 'Aucun incident',
    
    -- Metadata
    agent_id INT NOT NULL COMMENT 'Agent who created the client',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_cin (cin),
    INDEX idx_nom_prenom (nom, prenom),
    INDEX idx_agent (agent_id),
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: credit_requests (Credit/loan requests)
-- ============================================
CREATE TABLE IF NOT EXISTS credit_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique reference number',
    client_id INT NOT NULL,
    agent_id INT NOT NULL,
    
    -- Credit details
    montant DECIMAL(12,2) NOT NULL COMMENT 'Montant demandé',
    duree_mois INT NOT NULL COMMENT 'Durée en mois',
    taux_annuel DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Taux d''intérêt annuel',
    objet_credit VARCHAR(255) NULL COMMENT 'Objet du crédit',
    
    -- Calculated values
    mensualite DECIMAL(12,2) NULL COMMENT 'Mensualité calculée',
    cout_total DECIMAL(12,2) NULL COMMENT 'Coût total du crédit',
    
    -- Status
    statut ENUM('en_attente', 'approuve', 'refuse', 'en_revision') NOT NULL DEFAULT 'en_attente',
    
    -- Metadata
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_decision DATETIME NULL,
    commentaire TEXT NULL,
    
    INDEX idx_reference (reference),
    INDEX idx_client (client_id),
    INDEX idx_agent (agent_id),
    INDEX idx_statut (statut),
    INDEX idx_date (date_demande),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: scores (Scoring results for each request)
-- ============================================
CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    credit_request_id INT NOT NULL UNIQUE,
    
    -- Individual criterion scores
    score_revenu INT NOT NULL DEFAULT 0 COMMENT 'Score revenu mensuel (max 25)',
    score_endettement INT NOT NULL DEFAULT 0 COMMENT 'Score taux d''endettement (max 25)',
    score_situation_pro INT NOT NULL DEFAULT 0 COMMENT 'Score situation professionnelle (max 15)',
    score_anciennete INT NOT NULL DEFAULT 0 COMMENT 'Score ancienneté emploi (max 10)',
    score_historique INT NOT NULL DEFAULT 0 COMMENT 'Score historique crédit (max 15)',
    score_age INT NOT NULL DEFAULT 0 COMMENT 'Score âge (max 10)',
    
    -- Totals
    score_total INT NOT NULL DEFAULT 0 COMMENT 'Score total (max 100)',
    taux_endettement DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT 'Taux d''endettement calculé',
    capacite_remboursement DECIMAL(12,2) NOT NULL DEFAULT 0,
    
    -- Analysis
    facteurs_favorables TEXT NULL COMMENT 'JSON array of positive factors',
    facteurs_defavorables TEXT NULL COMMENT 'JSON array of negative factors',
    
    date_calcul DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_credit_request (credit_request_id),
    INDEX idx_score_total (score_total),
    FOREIGN KEY (credit_request_id) REFERENCES credit_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: decisions (Final decisions with justification)
-- ============================================
CREATE TABLE IF NOT EXISTS decisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    credit_request_id INT NOT NULL UNIQUE,
    agent_id INT NOT NULL,
    
    decision ENUM('approuve', 'refuse', 'en_revision') NOT NULL,
    justification TEXT NULL,
    conditions TEXT NULL COMMENT 'Conditions for approval',
    
    date_decision DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_credit_request (credit_request_id),
    INDEX idx_decision (decision),
    FOREIGN KEY (credit_request_id) REFERENCES credit_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: scoring_criteria (Configurable scoring parameters)
-- ============================================
CREATE TABLE IF NOT EXISTS scoring_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    libelle VARCHAR(255) NOT NULL,
    description TEXT NULL,
    poids INT NOT NULL DEFAULT 10 COMMENT 'Weight percentage',
    points_max INT NOT NULL DEFAULT 10 COMMENT 'Maximum points',
    regles TEXT NOT NULL COMMENT 'JSON configuration of scoring rules',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    ordre INT NOT NULL DEFAULT 0 COMMENT 'Display order',
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: settings (Application settings)
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cle VARCHAR(100) NOT NULL UNIQUE,
    valeur TEXT NOT NULL,
    description VARCHAR(255) NULL,
    type ENUM('int', 'float', 'string', 'bool', 'json') NOT NULL DEFAULT 'string',
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_cle (cle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
