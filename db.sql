CREATE DATABASE IF NOT EXISTS uts_kripto CHARACTER SET utf8mb4;
USE uts_kripto;

-- Konfigurasi kunci: hanya hash kunci simetris & kunci PUBLIK RSA yang disimpan
CREATE TABLE IF NOT EXISTS kunci_config (
  id INT PRIMARY KEY,
  sym_key_hash VARCHAR(255) NOT NULL,
  rsa_public_key TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Semua isi data tersimpan sebagai ciphertext
CREATE TABLE IF NOT EXISTS data_terenkripsi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul_enc TEXT NOT NULL,
  isi_enc LONGTEXT NOT NULL,
  key_enc TEXT NOT NULL,   -- kunci data (DEK) yang dibungkus AES lalu RSA
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
