-- tabel jabatan
create table jabatan(
id int auto_increment primary key not null,
nama varchar(100) not null,
description varchar(255) not null);

-- tabel user
create table users(
    id int auto_increment primary key not null,
    email varchar(255) unique not null,
    password varchar(255) not null,
    username varchar(100) not null
);


create table shifts(
    id int auto_increment primary key not null,
    nama_shift varchar(255) not null, 
    jam_masuk time not null,
    jam_pulang time not null,
    is_overnight boolean default false
);

create table karyawan(
    id int auto_increment primary key not null,
    user_id int,
    jabatan_id int,
    fullName varchar(255) not null,
    foreign key (user_id) references users(id)
    foreign key (jabatan_id) references jabatan(id)
);

create table jadwal_karyawan(
    id int auto_increment primary key not null,
    karyawan_id int,
    shift_id int,
    tanggal date,
    created_at datetime,
    foreign key(karyawan_id) references karyawan(id),
    foreign key(shift_id) references shifts(id)
);

create table lembur(
    id int auto_increment primary key not null,
    karyawan_id int,
    tanggal date,
    jam_mulai datetime,
    jam_selesai datetime,
    durasi_jam decimal(5,2),
    status enum('pending','approved','rejected') default 'pending',
    description text,

    foreign key(karyawan_id) references karyawan(id)
);

CREATE TABLE absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT,
    tanggal DATE,

    jam_masuk DATETIME,
    jam_keluar DATETIME null,

    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id)
);

CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT,
    tanggal DATE,
    tipe ENUM('izin','sakit','cuti','perbaikan_absen'),
    alasan TEXT,
    created_at DATETIME

    status ENUM('pending','approved','rejected'),
    approved_by INT,
    foreign key(approved_by) references users(id),
);
