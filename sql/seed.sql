-- ============================================
-- Credit Risk Simulator - Seed Data
-- ============================================
-- Run this script after schema.sql to populate demo data

USE credit_risk_db;

-- ============================================
-- Default Users
-- ============================================
-- Passwords are hashed using PHP password_hash() with PASSWORD_DEFAULT
-- admin123 = $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- agent123 = $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO users (email, password, nom, prenom, role, statut) VALUES
('admin@bank.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'System', 'admin', 'actif'),
('agent@bank.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dupont', 'Marie', 'agent', 'actif'),
('agent2@bank.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Martin', 'Pierre', 'agent', 'actif');

-- ============================================
-- Sample Clients
-- ============================================
INSERT INTO clients (cin, nom, prenom, date_naissance, email, telephone, adresse, ville, situation_professionnelle, employeur, anciennete_emploi, revenu_mensuel, charges_mensuelles, historique_credit, agent_id) VALUES
('AB123456', 'Benali', 'Ahmed', '1985-03-15', 'ahmed.benali@email.com', '0612345678', '123 Rue Mohammed V', 'Casablanca', 'CDI', 'Banque Populaire', 60, 8500.00, 1500.00, 'Aucun incident', 2),
('CD789012', 'Alami', 'Fatima', '1990-07-22', 'fatima.alami@email.com', '0623456789', '45 Avenue Hassan II', 'Rabat', 'Fonctionnaire', 'Ministère Education', 36, 6000.00, 1200.00, 'Aucun incident', 2),
('EF345678', 'Tazi', 'Youssef', '1978-11-08', 'youssef.tazi@email.com', '0634567890', '78 Boulevard Anfa', 'Casablanca', 'Independant', 'Auto-entrepreneur', 24, 4500.00, 2000.00, 'Un incident', 2),
('GH901234', 'Idrissi', 'Sara', '1995-01-30', 'sara.idrissi@email.com', '0645678901', '12 Rue Liberté', 'Marrakech', 'CDD', 'Société Générale', 8, 3200.00, 800.00, 'Aucun incident', 3),
('IJ567890', 'Chraibi', 'Mohammed', '1970-06-12', 'mohammed.chraibi@email.com', '0656789012', '56 Avenue FAR', 'Fès', 'Retraite', 'Ex-OCP', 0, 5500.00, 1000.00, 'Aucun incident', 3);

-- ============================================
-- Scoring Criteria Configuration
-- ============================================
INSERT INTO scoring_criteria (code, libelle, description, poids, points_max, regles, ordre) VALUES
('revenu_mensuel', 'Revenu Mensuel', 'Évaluation basée sur le revenu mensuel net du client', 25, 25, 
'{
    "type": "range",
    "ranges": [
        {"min": 5000, "max": null, "points": 25, "label": "Excellent (>5000€)"},
        {"min": 3000, "max": 5000, "points": 20, "label": "Très bon (3000-5000€)"},
        {"min": 2000, "max": 3000, "points": 15, "label": "Bon (2000-3000€)"},
        {"min": 1000, "max": 2000, "points": 10, "label": "Moyen (1000-2000€)"},
        {"min": 0, "max": 1000, "points": 5, "label": "Faible (<1000€)"}
    ]
}', 1),

('taux_endettement', 'Taux d''Endettement', 'Ratio charges/revenus mensuels', 25, 25,
'{
    "type": "range",
    "ranges": [
        {"min": 0, "max": 30, "points": 25, "label": "Excellent (<30%)"},
        {"min": 30, "max": 40, "points": 15, "label": "Acceptable (30-40%)"},
        {"min": 40, "max": 50, "points": 8, "label": "Élevé (40-50%)"},
        {"min": 50, "max": null, "points": 0, "label": "Critique (>50%)"}
    ]
}', 2),

('situation_professionnelle', 'Situation Professionnelle', 'Stabilité de l''emploi du client', 15, 15,
'{
    "type": "enum",
    "values": {
        "CDI": 15,
        "Fonctionnaire": 14,
        "Independant": 10,
        "Retraite": 8,
        "CDD": 6,
        "Sans emploi": 0
    }
}', 3),

('anciennete_emploi', 'Ancienneté Emploi', 'Durée dans l''emploi actuel', 10, 10,
'{
    "type": "range",
    "unit": "months",
    "ranges": [
        {"min": 60, "max": null, "points": 10, "label": "Excellente (>5 ans)"},
        {"min": 24, "max": 60, "points": 7, "label": "Bonne (2-5 ans)"},
        {"min": 12, "max": 24, "points": 4, "label": "Moyenne (1-2 ans)"},
        {"min": 0, "max": 12, "points": 2, "label": "Faible (<1 an)"}
    ]
}', 4),

('historique_credit', 'Historique Crédit', 'Comportement de remboursement passé', 15, 15,
'{
    "type": "enum",
    "values": {
        "Aucun incident": 15,
        "Un incident": 8,
        "Plusieurs incidents": 0
    }
}', 5),

('age', 'Âge', 'Tranche d''âge du client', 10, 10,
'{
    "type": "range",
    "unit": "years",
    "ranges": [
        {"min": 25, "max": 45, "points": 10, "label": "Optimal (25-45 ans)"},
        {"min": 45, "max": 60, "points": 8, "label": "Bon (45-60 ans)"},
        {"min": 60, "max": null, "points": 5, "label": "Senior (>60 ans)"},
        {"min": 18, "max": 25, "points": 4, "label": "Jeune (<25 ans)"}
    ]
}', 6);

-- ============================================
-- Application Settings
-- ============================================
INSERT INTO settings (cle, valeur, description, type) VALUES
('seuil_approbation', '70', 'Score minimum pour approbation automatique', 'int'),
('seuil_revision', '50', 'Score minimum pour révision manuelle', 'int'),
('seuil_refus', '50', 'Score en dessous duquel c''est un refus automatique', 'int'),
('taux_interet_defaut', '5.00', 'Taux d''intérêt annuel par défaut (%)', 'float'),
('duree_max_mois', '360', 'Durée maximale de crédit en mois', 'int'),
('montant_max', '1000000', 'Montant maximum de crédit', 'float'),
('nom_banque', 'Crédit Banque Maroc', 'Nom de l''établissement bancaire', 'string'),
('devise', 'MAD', 'Devise utilisée', 'string');

-- ============================================
-- Sample Credit Requests with Scores
-- ============================================
-- Request 1: Ahmed Benali - Approved (High score)
INSERT INTO credit_requests (reference, client_id, agent_id, montant, duree_mois, taux_annuel, objet_credit, mensualite, cout_total, statut, date_decision) VALUES
('CR-2024-001', 1, 2, 150000.00, 60, 5.00, 'Achat véhicule', 2830.79, 169847.40, 'approuve', NOW());

INSERT INTO scores (credit_request_id, score_revenu, score_endettement, score_situation_pro, score_anciennete, score_historique, score_age, score_total, taux_endettement, capacite_remboursement, facteurs_favorables, facteurs_defavorables) VALUES
(1, 25, 25, 15, 10, 15, 10, 100, 17.65, 7000.00, 
'["Revenu mensuel excellent (8500€)", "Taux d''endettement très faible (17.65%)", "Emploi stable en CDI depuis 5 ans", "Aucun incident de paiement"]',
'[]');

INSERT INTO decisions (credit_request_id, agent_id, decision, justification) VALUES
(1, 2, 'approuve', 'Excellent profil client. Score parfait de 100/100. Capacité de remboursement largement suffisante.');

-- Request 2: Fatima Alami - Approved (Good score)
INSERT INTO credit_requests (reference, client_id, agent_id, montant, duree_mois, taux_annuel, objet_credit, mensualite, cout_total, statut, date_decision) VALUES
('CR-2024-002', 2, 2, 80000.00, 48, 5.00, 'Travaux maison', 1841.65, 88399.20, 'approuve', NOW());

INSERT INTO scores (credit_request_id, score_revenu, score_endettement, score_situation_pro, score_anciennete, score_historique, score_age, score_total, taux_endettement, capacite_remboursement, facteurs_favorables, facteurs_defavorables) VALUES
(2, 20, 25, 14, 7, 15, 10, 91, 20.00, 4800.00,
'["Revenu mensuel très bon (6000€)", "Fonctionnaire avec emploi stable", "Aucun incident de paiement", "Âge optimal"]',
'["Ancienneté emploi moyenne (3 ans)"]');

INSERT INTO decisions (credit_request_id, agent_id, decision, justification) VALUES
(2, 2, 'approuve', 'Très bon profil. Fonctionnaire stable avec excellent historique de crédit.');

-- Request 3: Youssef Tazi - Manual Review (Medium score)
INSERT INTO credit_requests (reference, client_id, agent_id, montant, duree_mois, taux_annuel, objet_credit, mensualite, cout_total, statut) VALUES
('CR-2024-003', 3, 2, 200000.00, 84, 5.50, 'Expansion activité', 2876.45, 241621.80, 'en_revision');

INSERT INTO scores (credit_request_id, score_revenu, score_endettement, score_situation_pro, score_anciennete, score_historique, score_age, score_total, taux_endettement, capacite_remboursement, facteurs_favorables, facteurs_defavorables) VALUES
(3, 15, 8, 10, 4, 8, 8, 53, 44.44, 2500.00,
'["Revenu correct (4500€)", "Expérience entrepreneuriale"]',
'["Taux d''endettement élevé (44.44%)", "Un incident de paiement dans l''historique", "Statut indépendant moins stable"]');

-- Request 4: Sara Idrissi - Refused (Low score)
INSERT INTO credit_requests (reference, client_id, agent_id, montant, duree_mois, taux_annuel, objet_credit, mensualite, cout_total, statut, date_decision) VALUES
('CR-2024-004', 4, 3, 50000.00, 36, 5.00, 'Achat équipement', 1498.88, 53959.68, 'refuse', NOW());

INSERT INTO scores (credit_request_id, score_revenu, score_endettement, score_situation_pro, score_anciennete, score_historique, score_age, score_total, taux_endettement, capacite_remboursement, facteurs_favorables, facteurs_defavorables) VALUES
(4, 15, 25, 6, 2, 15, 4, 67, 25.00, 2400.00,
'["Taux d''endettement acceptable", "Aucun incident de paiement"]',
'["Contrat CDD peu stable", "Ancienneté emploi faible (8 mois)", "Jeune profil (<25 ans)"]');

INSERT INTO decisions (credit_request_id, agent_id, decision, justification) VALUES
(4, 3, 'refuse', 'Profil trop risqué. Emploi instable en CDD avec faible ancienneté. Capacité de remboursement insuffisante pour le montant demandé.');
