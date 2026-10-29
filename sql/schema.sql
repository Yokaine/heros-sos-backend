-- Heros-SOS - schema de base de donnees
-- Charge automatiquement par MySQL au premier demarrage du conteneur

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    civility ENUM('M', 'Mme', 'Autre') NOT NULL DEFAULT 'Autre',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    pseudo VARCHAR(60) DEFAULT NULL,
    birthdate DATE DEFAULT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    address_street VARCHAR(190) DEFAULT NULL,
    address_complement VARCHAR(190) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    photo VARCHAR(190) DEFAULT NULL,

    is_admin TINYINT(1) NOT NULL DEFAULT 0,

    -- demande / statut super-heros (3.1.2 : verification obligatoire par l'administration)
    hero_status ENUM('none', 'pending', 'verified') NOT NULL DEFAULT 'none',
    hero_verified_at DATETIME DEFAULT NULL,

    account_active TINYINT(1) NOT NULL DEFAULT 1,

    -- confirmation d'inscription par email (4.3.1)
    email_verified_at DATETIME DEFAULT NULL,
    activation_token VARCHAR(100) DEFAULT NULL,

    newsletter_subscribed TINYINT(1) NOT NULL DEFAULT 0,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS villains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pseudo VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) DEFAULT NULL,
    specialty VARCHAR(150) DEFAULT NULL,
    sector VARCHAR(150) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 4.1 : workflow de declaration d'incident
CREATE TABLE IF NOT EXISTS incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    citizen_id INT NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NOT NULL,
    incident_type VARCHAR(100) NOT NULL,
    location VARCHAR(190) NOT NULL,
    villain_id INT DEFAULT NULL,
    priority ENUM('basse', 'normale', 'haute') NOT NULL DEFAULT 'normale',
    status ENUM('signale', 'intervention', 'reussi', 'echoue') NOT NULL DEFAULT 'signale',
    hero_id INT DEFAULT NULL,
    citizens_rescued INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (citizen_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hero_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (villain_id) REFERENCES villains(id) ON DELETE SET NULL
);

-- 6.1.3 : notation d'un super-heros par un citoyen apres intervention
CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    citizen_id INT NOT NULL,
    hero_id INT NOT NULL,
    note TINYINT NOT NULL,
    comment TEXT DEFAULT NULL,
    hero_reply TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
    FOREIGN KEY (citizen_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hero_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_rating_per_incident (incident_id)
);

-- 5.8.4 : vide-o-theque d'un super-heros (films IMDb)
CREATE TABLE IF NOT EXISTS hero_movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hero_id INT NOT NULL,
    imdb_id VARCHAR(30) DEFAULT NULL,
    title VARCHAR(190) NOT NULL,
    movie_year VARCHAR(10) DEFAULT NULL,
    poster_url VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hero_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4.2 : suivi de la newsletter, y compris pour les visiteurs anonymes
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    user_id INT DEFAULT NULL,
    subscribed TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 8 : articles du blog gere depuis le dashboard administrateur
CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    content TEXT NOT NULL,
    author_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Le compte administrateur par defaut est cree automatiquement au premier
-- lancement de l'application (voir includes/bootstrap.php) afin que son mot
-- de passe soit hache avec la version de PHP reellement utilisee.

-- Quelques super-vilains de depart
INSERT INTO villains (pseudo, first_name, last_name, specialty, sector, description) VALUES
('Le Corbeau Noir', NULL, NULL, 'Vol', 'Paris', 'Specialiste des cambriolages nocturnes.'),
('Cryos', 'Victor', 'Hiver', 'Prise d''otages', 'Lyon', 'Manipule le froid pour piéger ses victimes.'),
('La Vipere', NULL, NULL, 'Meurtre', 'Marseille', 'Criminel recherche pour plusieurs homicides.');
