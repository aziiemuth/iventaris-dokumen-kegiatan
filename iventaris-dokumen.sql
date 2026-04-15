CREATE DATABASE IF NOT EXISTS iventaris_dokumen;
USE iventaris_dokumen;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'user') DEFAULT 'user'
);

INSERT IGNORE INTO users (username, password, role) VALUES 
('admin', 'admin', 'admin'),
('user', 'user', 'user');

CREATE TABLE IF NOT EXISTS folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_folder VARCHAR(100),
    parent_id INT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul_dokumen VARCHAR(255),
    folder_id INT DEFAULT NULL,
    keterangan TEXT,
    user_id INT NULL,
    latitude VARCHAR(50) NULL,
    longitude VARCHAR(50) NULL,
    tanggal_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    nama_file VARCHAR(255),
    nama_asli VARCHAR(255),
    ukuran INT,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(50),
    document_id INT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
