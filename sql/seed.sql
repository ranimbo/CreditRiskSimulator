USE credit_risk_db;

-- 1. Insert Base Users
-- admin123 => $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- agent123 => $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO utilisateur (nom, email, mot_de_passe) VALUES 
('System Admin', 'admin@bank.com', 'admin123'),
('Marie Dupont', 'agent@bank.com', 'agent123');

-- Get User IDs
SET @admin_id = (SELECT id FROM utilisateur WHERE email = 'admin@bank.com');
SET @agent_id = (SELECT id FROM utilisateur WHERE email = 'agent@bank.com');

-- 2. Insert Admin and Agent Bancaire profiles
INSERT INTO admin (id, matricule, role, derniere_connexion) VALUES 
(@admin_id, 'ADM-001', 'SUPERADMIN', NOW());

INSERT INTO agent_bancaire (id, matricule, agence, date_creation) VALUES 
(@agent_id, 'AGT-001', 'Agence Centrale Casablanca', NOW());

-- 3. Insert Clients
INSERT INTO client (nom, date_naissance, cin, situation_pro, revenu_mensuel_net, charges_mensuelles, anciennete_emploi, historique_credit) VALUES
('Benali Ahmed', '1985-03-15', 'AB123456', 'CDI', 8500.00, 1500.00, 60, 1),
('Alami Fatima', '1990-07-22', 'CD789012', 'FONCTIONNAIRE', 6000.00, 1200.00, 36, 1),
('Tazi Youssef', '1978-11-08', 'EF345678', 'INDEPENDANT', 4500.00, 2000.00, 24, 0),
('Idrissi Sara', '1995-01-30', 'GH901234', 'SANS_EMPLOI', 1200.00, 800.00, 0, 1);

-- 4. Insert Scoring Criteria
INSERT INTO critere_scoring (libelle, poids, points_max, actif, ordre) VALUES
('Revenu Mensuel Net', 25, 25, 1, 1),
('Taux d''Endettement', 25, 25, 1, 2),
('Situation Professionnelle', 15, 15, 1, 3),
('Ancienneté Emploi', 10, 10, 1, 4),
('Historique Crédit', 15, 15, 1, 5),
('Âge', 10, 10, 1, 6);

-- 5. Insert Sample Demande Credit
INSERT INTO demande_credit (date_creation, montant_demande, duree, type_credit, taux_endettement_calc, statut, agent_id, client_id) VALUES
(NOW(), 150000.00, 60, 'CONSOMMATION', 17.65, 'ACCORDE', @agent_id, 1),
(NOW(), 200000.00, 84, 'PROFESSIONNEL', 44.44, 'A_ANALYSER', @agent_id, 3);

-- 6. Insert Sample Score
INSERT INTO score (valeur_totale, date_calcul, detail_par_critere, version_moteur, temps_calcul, demande_id) VALUES
(100, NOW(), '{"revenu": 25, "endettement": 25, "situation_pro": 15, "anciennete": 10, "historique": 15, "age": 10}', '1.0', 0.05, 1),
(53, NOW(), '{"revenu": 15, "endettement": 8, "situation_pro": 10, "anciennete": 4, "historique": 8, "age": 8}', '1.0', 0.05, 2);

-- 7. Insert Sample Decision
INSERT INTO decision (resultat, justification, facteurs_favorables, facteurs_defavorables, date_decision, score_id) VALUES
('ACCORDE', 'Excellent profil client. Score parfait de 100/100.', '["Revenu excellent", "Taux endettement très faible", "Emploi CDI"]', '[]', NOW(), 1),
('A_ANALYSER', 'Profil intermédiaire, nécessite une étude supplémentaire (indépendant).', '["Revenu correct"]', '["Endettement élevé", "Plus risqué chez indépendant"]', NOW(), 2);
