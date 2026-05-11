-- ============================================
-- ABSENSI KOPIKUNI — Database Schema
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- Jabatan (posisi karyawan)
CREATE TABLE IF NOT EXISTS jabatan (
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    nama VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL
);

-- Users (auth)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL,
    role ENUM('admin','karyawan') DEFAULT 'karyawan',
    telegram_id VARCHAR(100) NULL,
    telegram_verified BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(email)
);

-- Shifts (pagi/siang)
CREATE TABLE IF NOT EXISTS shifts (
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    nama_shift VARCHAR(255) NOT NULL,
    jam_masuk TIME NOT NULL,
    toleransi_menit INT DEFAULT 15,
    jam_pulang TIME NOT NULL,
    is_overnight BOOLEAN DEFAULT FALSE
);

-- Karyawan (profil lengkap)
CREATE TABLE IF NOT EXISTS karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    user_id INT,
    jabatan_id INT,
    fullName VARCHAR(255) NOT NULL,
    qr_code VARCHAR(255) NULL COMMENT 'token unik untuk QR absensi',
    foto VARCHAR(255) NULL,
    no_hp VARCHAR(20) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (jabatan_id) REFERENCES jabatan(id) ON DELETE SET NULL
);

-- Jadwal karyawan per hari
CREATE TABLE IF NOT EXISTS jadwal_karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    karyawan_id INT,
    shift_id INT,
    tanggal DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE
);

-- Absensi utama
CREATE TABLE IF NOT EXISTS absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT,
    jadwal_id INT NULL,
    tanggal DATE,
    jam_masuk DATETIME NULL,
    jam_keluar DATETIME NULL,
    status_masuk ENUM('tepat_waktu','terlambat','alpha') DEFAULT 'tepat_waktu',
    status_pulang ENUM('tepat_waktu','lebih_awal','lembur') NULL,
    menit_terlambat INT DEFAULT 0,
    alasan_telat_pulang TEXT NULL,
    verified_by INT NULL,
    verified_at DATETIME NULL,
    catatan_admin TEXT NULL,
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Izin / ketidakhadiran
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT,
    tanggal DATE,
    tipe ENUM('izin','sakit','cuti') NOT NULL,
    alasan TEXT,
    bukti_file VARCHAR(500) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at DATETIME NULL,
    catatan_admin TEXT NULL,
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Lembur
CREATE TABLE IF NOT EXISTS lembur (
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    karyawan_id INT,
    tanggal DATE,
    jam_mulai DATETIME,
    jam_selesai DATETIME,
    durasi_jam DECIMAL(5,2),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    description TEXT,
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- DEFAULT DATA — Jabatan
-- ============================================
INSERT IGNORE INTO jabatan (id, nama, description) VALUES
(1, 'Cleaning Service', 'Bertanggung jawab atas kebersihan area kafe'),
(2, 'Server', 'Melayani pelanggan dan mengantarkan pesanan'),
(3, 'Barista', 'Membuat dan menyajikan minuman kopi');

-- ============================================
-- DEFAULT DATA — Shifts
-- ============================================
INSERT IGNORE INTO shifts (id, nama_shift, jam_masuk, toleransi_menit, jam_pulang, is_overnight) VALUES
(1, 'Pagi - Cleaning Service', '07:00:00', 15, '15:00:00', FALSE),
(2, 'Pagi - Server',           '08:00:00', 15, '16:00:00', FALSE),
(3, 'Pagi - Barista',          '08:00:00', 15, '16:00:00', FALSE),
(4, 'Siang - Cleaning Service','15:00:00', 15, '23:00:00', FALSE),
(5, 'Siang - Server',          '15:00:00', 15, '23:00:00', FALSE),
(6, 'Siang - Barista',         '15:00:00', 15, '23:00:00', FALSE);

-- ============================================
-- DEFAULT DATA — Admin user
-- password: admin123 (bcrypt)
-- ============================================
INSERT IGNORE INTO users (id, email, password, username, role, telegram_verified) VALUES
(1, 'admin@kopikuni.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXFQJ0a1K', 'Admin Kopikuni', 'admin', TRUE);
