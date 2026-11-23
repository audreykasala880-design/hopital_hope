-- =====================================================================
-- PROJET : APPLICATION WEB DE GESTION DES RENDEZ-VOUS MÉDICAUX
-- TP PROGRAMMATION II - 2e LICENCE SCIENCES INFORMATIQUES
-- UNIVERSITÉ CATHOLIQUE DU CONGO (UCC)
-- =====================================================================

DROP DATABASE IF EXISTS gestion_rendez_vous_db;
CREATE DATABASE IF NOT EXISTS gestion_rendez_vous_db 
CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE gestion_rendez_vous_db;

-- ---------------------------------------------------------------------
-- TABLE 1 : UTILISATEURS (Gestion des accès et authentification)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'receptionniste', 'medecin') NOT NULL DEFAULT 'receptionniste',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE 2 : PATIENTS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    sexe ENUM('M', 'F') NOT NULL,
    telephone VARCHAR(30) NOT NULL,
    email VARCHAR(150) NOT NULL,
    mot_de_passe VARCHAR(255) NULL,
    compte_actif TINYINT(1) NOT NULL DEFAULT 1,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    adresse TEXT NULL,
    groupe_sanguin ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
    antecedents TEXT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE 3 : MEDECINS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medecins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    specialite VARCHAR(100) NOT NULL,
    telephone VARCHAR(30) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    bureau VARCHAR(50) NULL,
    photo VARCHAR(255) DEFAULT 'assets/images/doctor-default.png',
    actif TINYINT(1) DEFAULT 1,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE 4 : SALLES DE CONSULTATION
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS salles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    etage VARCHAR(50) NOT NULL,
    capacite INT NOT NULL DEFAULT 1,
    equipements TEXT NOT NULL COMMENT 'Équipements séparés par virgules ex: Stéthoscope, ECG, Échographe',
    statut ENUM('disponible', 'maintenance', 'fermee') NOT NULL DEFAULT 'disponible',
    description TEXT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE 5 : DISPONIBILITES_MEDECINS (Horaires hebdomadaires)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS disponibilites_medecins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_medecin INT NOT NULL,
    jour_semaine ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche') NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    CONSTRAINT fk_disp_medecin FOREIGN KEY (id_medecin) 
        REFERENCES medecins(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_disp_med_heures CHECK (heure_fin > heure_debut)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE 6 : INDISPONIBILITES_MEDECINS (Absences, Congés, Réunions)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS indisponibilites_medecins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_medecin INT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    type ENUM('absence', 'conge', 'reunion', 'formation') NOT NULL DEFAULT 'absence',
    motif VARCHAR(255) NULL,
    CONSTRAINT fk_indisp_medecin FOREIGN KEY (id_medecin) 
        REFERENCES medecins(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_indisp_med_dates CHECK (date_fin > date_debut)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE 7 : DISPONIBILITES_SALLES (Heures d'ouverture des salles)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS disponibilites_salles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_salle INT NOT NULL,
    jour_semaine ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche') NOT NULL,
    heure_ouverture TIME NOT NULL,
    heure_fermeture TIME NOT NULL,
    CONSTRAINT fk_disp_salle FOREIGN KEY (id_salle) 
        REFERENCES salles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_disp_salle_heures CHECK (heure_fermeture > heure_ouverture)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE 8 : INDISPONIBILITES_SALLES (Maintenance, Fermeture, Désinfection)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS indisponibilites_salles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_salle INT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    type ENUM('maintenance', 'reparation', 'desinfection', 'fermeture') NOT NULL DEFAULT 'maintenance',
    motif VARCHAR(255) NULL,
    CONSTRAINT fk_indisp_salle FOREIGN KEY (id_salle) 
        REFERENCES salles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_indisp_salle_dates CHECK (date_fin > date_debut)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TABLE 9 : RENDEZ_VOUS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rendez_vous (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT NOT NULL,
    id_medecin INT NOT NULL,
    id_salle INT NOT NULL,
    date_heure DATETIME NOT NULL,
    duree_minutes INT NOT NULL DEFAULT 30,
    date_heure_fin DATETIME NOT NULL,
    equipement_requis VARCHAR(255) NULL,
    motif VARCHAR(255) NOT NULL,
    statut ENUM('en_attente', 'confirme', 'termine', 'annule', 'reporte') NOT NULL DEFAULT 'confirme',
    remarques TEXT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rdv_patient FOREIGN KEY (id_patient) 
        REFERENCES patients(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_rdv_medecin FOREIGN KEY (id_medecin) 
        REFERENCES medecins(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_rdv_salle FOREIGN KEY (id_salle) 
        REFERENCES salles(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_medecin_periode (id_medecin, date_heure, date_heure_fin),
    INDEX idx_salle_periode (id_salle, date_heure, date_heure_fin),
    INDEX idx_rdv_date (date_heure)
) ENGINE=InnoDB;

-- =====================================================================
-- DONNÉES DE DÉMONSTRATION RÉALISTES
-- =====================================================================

-- Mot de passe haché par défaut pour tous les comptes : "admin123"
-- Généré via password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES
('Prof. Jean Kasongo (UCC)', 'admin@hopecmedical.cd', '$2y$10$PMz9Xy769CbyWerFIoq2H.79ypw/QmY7AihEldvWKXcbA70H9KEBe', 'admin'),
('Marie Mwamba (Secrétaire)', 'reception@hopecmedical.cd', '$2y$10$PMz9Xy769CbyWerFIoq2H.79ypw/QmY7AihEldvWKXcbA70H9KEBe', 'receptionniste');

-- Patients
INSERT INTO patients (nom, prenom, date_naissance, sexe, telephone, email, adresse, groupe_sanguin, antecedents) VALUES
('Mukendi', 'Christian', '1998-04-12', 'M', '+243815000001', 'christian.mukendi@ucc.cd', 'Av. Université No 14, Kinshasa Limete', 'O+', 'Allergie à la pénicilline'),
('Kalala', 'Priscille', '2001-09-23', 'F', '+243825000002', 'priscille.kalala@gmail.com', 'Q. Righini, Lemba, Kinshasa', 'A+', 'Asthme modéré'),
('Tshilumba', 'David', '1995-11-05', 'M', '+243895000003', 'david.tshilumba@yahoo.fr', 'Bvd du 30 Juin, Gombe', 'B+', 'Aucun antécédent notable'),
('Mbombo', 'Esther', '2003-02-17', 'F', '+243995000004', 'esther.mbombo@outlook.com', 'Av. Kabasele, Kintambo', 'AB+', 'Hypertension artérielle légère');

-- Médecins
INSERT INTO medecins (nom, prenom, specialite, telephone, email, bureau, photo, actif) VALUES
('Dr. Ilunga', 'Alain', 'Cardiologie', '+243810111222', 'dr.ilunga@hopecmedical.cd', 'Bureau C-101', 'assets/images/doctor1.png', 1),
('Dr. Kabange', 'Nathalie', 'Pédiatrie', '+243820222333', 'dr.kabange@hopecmedical.cd', 'Bureau P-202', 'assets/images/doctor2.png', 1),
('Dr. Mbayo', 'Patrick', 'Médecine Générale', '+243890333444', 'dr.mbayo@hopecmedical.cd', 'Bureau G-005', 'assets/images/doctor3.png', 1),
('Dr. Ngalula', 'Dorcas', 'Gynécologie-Obstétrique', '+243990444555', 'dr.ngalula@hopecmedical.cd', 'Bureau GO-104', 'assets/images/doctor4.png', 1);

-- Salles
INSERT INTO salles (nom, etage, capacite, equipements, statut, description) VALUES
('Salle 101 - Consultation Générale', 'Rez-de-chaussée', 2, 'Stéthoscope, Tensiomètre, Balance médicale, Table d examen', 'disponible', 'Salle principale pour consultations de routine'),
('Salle 102 - Cardiologie & ECG', '1er Étage', 1, 'Électrocardiographe (ECG), Tensiomètre, Défibrillateur, Table inclinable', 'disponible', 'Salle dédiée aux bilans cardiovasculaires'),
('Salle 103 - Échographie & Imagerie', '1er Étage', 2, 'Échographe Doppler couleur, Table d examen gynécologique, Écran mural', 'disponible', 'Examens d imagerie et suivi obstétrique'),
('Salle 104 - Pédiatrie', 'Rez-de-chaussée', 3, 'Toise pédiatrique, Pèse-bébé, Otoscope, Table d examen matelassée', 'disponible', 'Environnement ludique et adapté aux enfants'),
('Salle 201 - Petite Chirurgie', '2e Étage', 1, 'Scialytique, Bistouri électrique, Table d opération mineure, Stérilisateur', 'maintenance', 'Salle temporairement en maintenance technique');

-- Disponibilités hebdomadaires des médecins (Lundi à Vendredi de 08:00 à 16:00 par exemple)
INSERT INTO disponibilites_medecins (id_medecin, jour_semaine, heure_debut, heure_fin) VALUES
-- Dr. Ilunga (Cardio)
(1, 'Lundi', '08:00:00', '16:00:00'),
(1, 'Mardi', '08:00:00', '16:00:00'),
(1, 'Mercredi', '08:00:00', '14:00:00'),
(1, 'Jeudi', '08:00:00', '16:00:00'),
(1, 'Vendredi', '08:00:00', '15:00:00'),
-- Dr. Kabange (Pédiatrie)
(2, 'Lundi', '09:00:00', '17:00:00'),
(2, 'Mercredi', '09:00:00', '17:00:00'),
(2, 'Jeudi', '09:00:00', '17:00:00'),
(2, 'Vendredi', '09:00:00', '16:00:00'),
-- Dr. Mbayo (Médecine Générale)
(3, 'Lundi', '08:00:00', '18:00:00'),
(3, 'Mardi', '08:00:00', '18:00:00'),
(3, 'Mercredi', '08:00:00', '18:00:00'),
(3, 'Jeudi', '08:00:00', '18:00:00'),
(3, 'Vendredi', '08:00:00', '18:00:00'),
(3, 'Samedi', '09:00:00', '13:00:00'),
-- Dr. Ngalula (Gynécologie)
(4, 'Mardi', '08:30:00', '15:30:00'),
(4, 'Mercredi', '08:30:00', '15:30:00'),
(4, 'Jeudi', '08:30:00', '15:30:00'),
(4, 'Vendredi', '08:30:00', '15:30:00');

-- Disponibilités d'ouverture des salles (Lundi au Samedi)
INSERT INTO disponibilites_salles (id_salle, jour_semaine, heure_ouverture, heure_fermeture) VALUES
(1, 'Lundi', '07:30:00', '18:30:00'),
(1, 'Mardi', '07:30:00', '18:30:00'),
(1, 'Mercredi', '07:30:00', '18:30:00'),
(1, 'Jeudi', '07:30:00', '18:30:00'),
(1, 'Vendredi', '07:30:00', '18:30:00'),
(1, 'Samedi', '08:00:00', '14:00:00'),
(2, 'Lundi', '08:00:00', '17:00:00'),
(2, 'Mardi', '08:00:00', '17:00:00'),
(2, 'Mercredi', '08:00:00', '17:00:00'),
(2, 'Jeudi', '08:00:00', '17:00:00'),
(2, 'Vendredi', '08:00:00', '17:00:00'),
(3, 'Lundi', '08:00:00', '17:00:00'),
(3, 'Mardi', '08:00:00', '17:00:00'),
(3, 'Mercredi', '08:00:00', '17:00:00'),
(3, 'Jeudi', '08:00:00', '17:00:00'),
(3, 'Vendredi', '08:00:00', '17:00:00'),
(4, 'Lundi', '08:00:00', '17:30:00'),
(4, 'Mardi', '08:00:00', '17:30:00'),
(4, 'Mercredi', '08:00:00', '17:30:00'),
(4, 'Jeudi', '08:00:00', '17:30:00'),
(4, 'Vendredi', '08:00:00', '17:30:00');

-- Indisponibilités planifiées de médecins
INSERT INTO indisponibilites_medecins (id_medecin, date_debut, date_fin, type, motif) VALUES
(1, '2026-10-25 09:00:00', '2026-10-25 12:00:00', 'reunion', 'Réunion du comité médical UCC'),
(2, '2026-11-01 08:00:00', '2026-11-07 18:00:00', 'conge', 'Congé annuel de recherche');

-- Indisponibilités planifiées de salles (Maintenance)
INSERT INTO indisponibilites_salles (id_salle, date_debut, date_fin, type, motif) VALUES
(5, '2026-10-01 00:00:00', '2026-10-31 23:59:59', 'maintenance', 'Remplacement du système de ventilation stérile'),
(2, '2026-10-28 13:00:00', '2026-10-28 16:00:00', 'desinfection', 'Désinfection et étalonnage de l électrocardiographe');

-- Quelques rendez-vous initiaux de démonstration
INSERT INTO rendez_vous (id_patient, id_medecin, id_salle, date_heure, duree_minutes, date_heure_fin, equipement_requis, motif, statut, remarques) VALUES
(1, 1, 2, '2026-10-21 09:00:00', 45, '2026-10-21 09:45:00', 'ECG', 'Bilan cardiologique annuel et palpitations', 'confirme', 'Patient sous bêta-bloquants'),
(2, 2, 4, '2026-10-21 10:00:00', 30, '2026-10-21 10:30:00', 'Stéthoscope', 'Contrôle pédiatrique semestriel', 'confirme', 'Rien à signaler'),
(3, 3, 1, '2026-10-21 14:00:00', 30, '2026-10-21 14:30:00', 'Tensiomètre', 'Consultation générale et toux persistante', 'confirme', 'Prescription d analyse sanguine');
