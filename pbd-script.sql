create table role(
idrole INT not null,
nama_role VARCHAR(100) not NULL
); 
alter table role
add constraint pk_role primary key (idrole);

create table vendor (
idvendor INT not null, 
nama_vendor VARCHAR(100) not null,
badan_hukum CHAR(1),
status CHAR(1)
);
alter table vendor
add constraint pk_vendor primary key (idvendor);

create table satuan(
idsatuan INT not null,
nama_satuan VARCHAR(45)not null,
status TINYINT
);
alter table satuan 
add constraint pk_satuan primary key (idsatuan);

create table margin_penjualan (
    idmargin_penjualan int not null,
    created_at timestamp,
    persen double,
    status tinyint,
    iduser int,
    updated_at timestamp,
    ppm int,
    total_nilai int
);
alter table margin_penjualan
add constraint pk_margin_penjualan primary key (idmargin_penjualan);
alter table margin_penjualan
add constraint fk_margin_user foreign key (iduser) references user (iduser);



create table user (
iduser INT not null,
username VARCHAR(45) not null,
password VARCHAR(100) not null,
idrole INT not NULL
);
alter table user 
add constraint pk_user primary key (iduser);
alter table user
add constraint fk_user_role foreign key (idrole) references role (idrole);

create table barang (
idbarang INT not null,
jenis CHAR(1),
nama VARCHAR(45) not null,
idsatuan INT not null,
harga INT,
status TINYINT
);
alter table barang
add constraint pk_barang primary key (idbarang);
alter table barang
add constraint fk_brg_satuan foreign key (idsatuan) references satuan (idsatuan);

create table pengadaan (
idpengadaan BIGINT not null,
time_stamp TIMESTAMP not null,
user_iduser INT not null,
vendor_idvendor INT,
subtotal_nilai INT,
total_nilai INT,
ppn INT,
status CHAR(1)
);
alter table pengadaan
add constraint pk_pengadaan primary key (idpengadaan);
alter table pengadaan
add constraint fk_peng_user foreign key (user_iduser) references user (iduser);
alter table pengadaan
add constraint fk_peng_vendor foreign key (vendor_idvendor) references vendor (idvendor);

create table penjualan (
idpenjualan INT not null,
created_at TIMESTAMP not null,
total_nilai INT,
subtotal_nilai INT,
ppn INT,
iduser INT not null,
idmargin_penjualan INT not null
);
alter table penjualan
add constraint pk_penjualan primary key (idpenjualan);
alter table penjualan
add constraint fk_penj_user foreign key (iduser) references user (iduser);
alter table penjualan
add constraint fk_penj_margin foreign key (idmargin_penjualan) references margin_penjualan (idmargin_penjualan);

create table penerimaan (
idpenerimaan BIGINT not null,
created_at TIMESTAMP not null,
idpengadaan BIGINT not null,
iduser INT not null,
status CHAR(1)
);
alter table penerimaan
add constraint pk_penerimaan primary key (idpenerimaan);
alter table penerimaan
add constraint fk_penr_pengadaan foreign key (idpengadaan) references pengadaan (idpengadaan);
alter table penerimaan
add constraint fk_penr_user foreign key (iduser) references user (iduser);

create table kartu_stok (
idkartu_stok BIGINT not null,
idbarang INT not null,
jenis_transaksi CHAR(1) not null,
masuk INT,
keluar INT,
stok INT,
created_at TIMESTAMP,
id_transaksi INT
);
alter table kartu_stok
add constraint pk_kartu_stok primary key (idkartu_stok);
alter table kartu_stok
add constraint fk_kstok_barang foreign key (idbarang) references barang (idbarang);

create table retur (
idretur BIGINT not null,
created_at TIMESTAMP not null,
iduser INT not null,
idpenerimaan BIGINT not null
);
alter table retur
add constraint pk_retur primary key (idretur);
alter table retur
add constraint fk_retur_user foreign key (iduser) references user (iduser);
alter table retur
add constraint fk_retur_penerimaan foreign key (idpenerimaan) references penerimaan (idpenerimaan);

create table detail_pengadaan (
    iddetail_pengadaan bigint not null,
    idpengadaan bigint not null,
    idbarang int not null,
    harga_satuan int,
    jumlah int,
    sub_total int
);
alter table detail_pengadaan
add constraint pk_det_pengadaan primary key (iddetail_pengadaan, idpengadaan);
alter table detail_pengadaan
add constraint fk_detpeng_pengadaan foreign key (idpengadaan) references pengadaan (idpengadaan);
alter table detail_pengadaan
add constraint fk_detpeng_barang foreign key (idbarang) references barang (idbarang);

create table detail_penjualan (
iddetail_penjualan BIGINT not null,
penjualan_idpenjualan INT not null,
idbarang INT not null,
harga_satuan INT,
jumlah INT,
subtotal INT
);
alter table detail_penjualan
add constraint pk_det_penjualan primary key (iddetail_penjualan);
alter table detail_penjualan
add constraint fk_detpenj_penjualan foreign key (penjualan_idpenjualan) references penjualan (idpenjualan);
alter table detail_penjualan
add constraint fk_detpenj_barang foreign key (idbarang) references barang (idbarang);

create table detail_penerimaan(
iddetail_penerimaan BIGINT not null,
idpenerimaan BIGINT not null,
barang_idbarang INT not null,
jumlah_terima INT,
harga_satuan_terima INT,
sub_total_terima INT
);
alter table detail_penerimaan
add constraint pk_det_penerimaan primary key (iddetail_penerimaan);
alter table detail_penerimaan
add constraint fk_detpenr_penerimaan foreign key (idpenerimaan) references penerimaan (idpenerimaan);
alter table detail_penerimaan
add constraint fk_detpenr_barang foreign key (barang_idbarang) references barang (idbarang);
alter table detail_penerimaan
add constraint uk_iddetpenr unique (iddetail_penerimaan);


create table detail_retur (
iddetail_retur bigint not null,
idretur bigint not null,
iddetail_penerimaan bigint not null,
jumlah int,
alasan varchar(200)
);

alter table detail_retur
add constraint pk_det_retur primary key (iddetail_retur);
alter table detail_retur
add constraint fk_detret_retur foreign key (idretur) references retur (idretur);
alter table detail_retur
add constraint fk_detret_detpenr foreign key (iddetail_penerimaan) references detail_penerimaan (iddetail_penerimaan);


insert into role (idrole, nama_role) values
(1, 'super admin'),
(2, 'administrator');

insert into satuan (idsatuan, nama_satuan, status) values
(1, 'buah', 1),
(2, 'kotak', 1),
(3, 'kilogram', 1),
(4, 'meter', 1),
(5, 'unit', 1);

insert into vendor (idvendor, nama_vendor, badan_hukum, status) values
(101, 'pt berkah selalu', 'c', 1),
(102, 'cv jaya abadi', 'c', 1),
(103, 'ud mitra sejahtera', 'p', 1),
(104, 'toko lima bersaudara', 'p', 0),
(105, 'pt sinar baja', 'c', 1);

insert into user (iduser, username, password, idrole) values
(201, 'admin.pusat', 'pass_hash_1', 1),
(202, 'andi.sup', 'pass_hash_2', 1),
(203, 'siti.kasir', 'pass_hash_3', 2),
(204, 'doni.gudang', 'pass_hash_4', 2),
(205, 'maya.mankeu', 'pass_hash_5', 2);

insert into barang (idbarang, jenis, nama, idsatuan, harga, status) values
(701, 'm', 'susu uht full cream 1l', 5, 20000, 1),
(702, 'p', 'sabun mandi cair 450ml', 5, 25000, 1),
(703, 'm', 'beras premium 5kg', 3, 65000, 1),
(704, 'k', 'telur ayam negeri per kg', 3, 30000, 1),
(705, 'p', 'pasta gigi pepsodent', 5, 12000, 1),
(706, 'm', 'mie instan multipack rasa soto', 2, 95000, 1);

CREATE OR REPLACE VIEW view_barang_details AS
SELECT
    b.idbarang,
    b.nama,
    s.nama_satuan,
    b.jenis,
    b.harga,
    b.status,
    COALESCE((SELECT stok FROM kartu_stok WHERE idbarang = b.idbarang ORDER BY created_at DESC LIMIT 1), 0) AS stok
FROM barang b
LEFT JOIN satuan s ON b.idsatuan = s.idsatuan;

CREATE VIEW view_data_vendor AS
SELECT
    idvendor,
    nama_vendor,
    badan_hukum,
    status
FROM
    vendor;

CREATE VIEW view_satuan AS
SELECT 
    idsatuan,
    nama_satuan,
    status
FROM satuan;

CREATE VIEW view_user_role AS
SELECT 
    u.iduser,
    u.username,
    r.nama_role AS role
FROM user u
JOIN role r ON u.idrole = r.idrole;

CREATE VIEW view_role AS
SELECT idrole, nama_role
FROM role;

CREATE VIEW view_margin_user AS
SELECT 
    mp.idmargin_penjualan,
    mp.persen,
    mp.status,
    mp.created_at,
    mp.updated_at,
    u.iduser,
    u.username
FROM margin_penjualan mp
JOIN user u ON mp.iduser = u.iduser;

CREATE VIEW view_barang_aktif AS
SELECT 
    idbarang, 
    nama, 
    harga_pokok, 
    nama_satuan
FROM 
    view_barang_details
WHERE 
    status_barang_label = 'Aktif';

-- 8. VIEW MARGIN PENJUALAN AKTIF (Hanya ambil yang statusnya Aktif)
CREATE VIEW view_margin_aktif AS
SELECT 
    idmargin_penjualan, 
    persen
FROM 
    view_data_margin
WHERE 
    status_margin_label = 'Aktif';

-- 9. VIEW SATUAN AKTIF
CREATE  VIEW view_satuan_aktif AS
SELECT 
    idsatuan, 
    nama_satuan
FROM 
    view_data_satuan
WHERE 
    status_satuan_label = 'Aktif';

-- 10. VIEW VENDOR AKTIF
CREATE VIEW view_vendor_aktif AS
SELECT 
    idvendor, 
    nama_vendor, 
    badan_hukum
FROM 
    view_data_vendor
WHERE 
    status_vendor_label = 'Aktif';

DELIMITER $$
-- Trigger ini akan menghitung sub_total = jumlah * harga_satuan 
-- sebelum data dimasukkan ke detail_pengadaan.
CREATE TRIGGER trg_hitung_subtotal
BEFORE INSERT ON detail_pengadaan
FOR EACH ROW
BEGIN
    -- Menghitung sub_total
    SET NEW.sub_total = NEW.jumlah * NEW.harga_satuan;
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_hitung_dan_finalisasi_pengadaan (
    IN p_idpengadaan BIGINT
)
BEGIN
    DECLARE v_subtotal_nilai DECIMAL(15, 2);
    DECLARE v_ppn DECIMAL(15, 2);
    DECLARE v_total_nilai DECIMAL(15, 2);

    -- 1. Hitung Subtotal Nilai KESELURUHAN (Penjumlahan dari kolom sub_total)
    SELECT COALESCE(SUM(sub_total), 0)
    INTO v_subtotal_nilai
    FROM detail_pengadaan
    WHERE idpengadaan = p_idpengadaan;

    -- 2. Hitung PPN (11%) dan Total Akhir
    SET v_ppn = v_subtotal_nilai * 0.11;
    SET v_total_nilai = v_subtotal_nilai + v_ppn;

    -- 3. Update Header Pengadaan
    UPDATE pengadaan
    SET subtotal_nilai = v_subtotal_nilai,
        ppn = v_ppn,
        total_nilai = v_total_nilai,
        status = 'F' -- Opsional: langsung finalisasi status
    WHERE idpengadaan = p_idpengadaan;

END $$

DELIMITER ;

-- penerimaan
DROP PROCEDURE IF EXISTS finalisasi_status_penerimaan; -- Perbaikan sintaks DROP

DELIMITER $$
-- Menghilangkan 'OR REPLACE' karena tidak selalu didukung saat ada DELIMITER baru
CREATE PROCEDURE finalisasi_status_penerimaan (
    IN p_idpenerimaan BIGINT
)
BEGIN
    -- Deklarasi variabel harus di awal
    DECLARE v_idpengadaan BIGINT;
    DECLARE v_count_pengadaan INT;
    DECLARE v_count_diterima INT;

    -- Dapatkan ID Pengadaan yang terkait (BIGINT)
    SELECT idpengadaan INTO v_idpengadaan 
    FROM penerimaan 
    WHERE idpenerimaan = p_idpenerimaan;

    -- 1. Hitung jumlah item unik yang DI-ORDER
    SELECT COUNT(dp.idbarang) INTO v_count_pengadaan 
    FROM detail_pengadaan dp 
    WHERE dp.idpengadaan = v_idpengadaan;

    -- 2. Hitung jumlah item yang SUDAH DITERIMA
    SELECT COUNT(dt.barang_idbarang) INTO v_count_diterima
    FROM detail_penerimaan dt 
    WHERE dt.idpenerimaan = p_idpenerimaan;
    
    -- 3. Tentukan Status Akhir (F, C, atau P)
    IF v_count_diterima = v_count_pengadaan THEN
        UPDATE penerimaan SET status = 'F' WHERE idpenerimaan = p_idpenerimaan;
        UPDATE pengadaan SET status = 'F' WHERE idpengadaan = v_idpengadaan;
        
    ELSEIF v_count_diterima > 0 THEN
        UPDATE penerimaan SET status = 'C' WHERE idpenerimaan = p_idpenerimaan;
        
    ELSE 
        UPDATE penerimaan SET status = 'P' WHERE idpenerimaan = p_idpenerimaan;
    END IF;

END $$

DELIMITER ;

-- Hapus trigger yang lama terlebih dahulu
DROP TRIGGER IF EXISTS trg_stok_masuk_penerimaan;
DELIMITER $$
CREATE TRIGGER trg_stok_masuk_penerimaan
AFTER INSERT ON detail_penerimaan
FOR EACH ROW
BEGIN
    DECLARE v_stok_terakhir INT;

    SET v_stok_terakhir = COALESCE(stok_terakhir(NEW.barang_idbarang), 0);

    INSERT INTO kartu_stok (idbarang, jenis_transaksi, masuk, keluar, stok, created_at, idtransaksi)
    VALUES (
        NEW.barang_idbarang,
        'M',
        NEW.jumlah_terima,
        0,
        v_stok_terakhir + NEW.jumlah_terima,
        NOW(),
        NEW.idpenerimaan
    );
END $$
DELIMITER ;

DROP PROCEDURE IF EXISTS proses_penjualan_transaksi;

DELIMITER $$
CREATE PROCEDURE proses_penjualan_transaksi(
    IN p_idpenjualan INT,
    IN p_idbarang INT,
    IN p_jumlah INT
)
BEGIN
    DECLARE v_stok_terakhir INT;

    SET v_stok_terakhir = COALESCE(stok_terakhir(p_idbarang), 0);

    -- Perbaikan: Menggunakan nama kolom id_transaksi (dengan underscore)
    INSERT INTO kartu_stok (idbarang, jenis_transaksi, masuk, keluar, stok, created_at, id_transaksi)
    VALUES (
        p_idbarang,
        'K', -- K = Keluar (Penjualan)
        0,
        p_jumlah,
        v_stok_terakhir - p_jumlah,
        NOW(),
        p_idpenjualan -- INT dari idpenjualan
    );
END$$

DELIMITER ;

drop function stok_terakhir

DELIMITER $$
CREATE FUNCTION stok_terakhir(p_idbarang INT)
RETURNS INT
READS SQL DATA -- FIX: Menambahkan karakteristik yang diwajibkan server
BEGIN
    DECLARE v_stok_terakhir INT;

    -- Mencari stok terakhir berdasarkan created_at
    SELECT stok INTO v_stok_terakhir
    FROM kartu_stok
    WHERE idbarang = p_idbarang
    ORDER BY created_at DESC
    LIMIT 1;

    -- Mengembalikan 0 jika v_stok_terakhir masih NULL (barang belum ada di kartu_stok)
    RETURN COALESCE(v_stok_terakhir, 0);
END $$
DELIMITER ;

-- Fungsi untuk menghitung harga jual berdasarkan margin aktif
DROP FUNCTION IF EXISTS hitung_harga_jual_dengan_margin;
DELIMITER $$
CREATE FUNCTION hitung_harga_jual_dengan_margin(p_idbarang INT)
RETURNS DECIMAL(15, 2)
READS SQL DATA
BEGIN
    DECLARE v_harga_pokok DECIMAL(15, 2);
    DECLARE v_margin_persen DOUBLE;
    DECLARE v_harga_jual DECIMAL(15, 2);

    -- 1. Ambil harga pokok barang
    SELECT harga INTO v_harga_pokok FROM barang WHERE idbarang = p_idbarang;

    -- 2. Ambil persentase margin yang sedang aktif
    SELECT persen INTO v_margin_persen FROM margin_penjualan WHERE status = 1 ORDER BY created_at DESC LIMIT 1;

    -- Jika tidak ada margin aktif, kembalikan harga pokok
    IF v_margin_persen IS NULL THEN
        RETURN v_harga_pokok;
    END IF;

    -- 3. Hitung harga jual
    SET v_harga_jual = v_harga_pokok * (1 + (v_margin_persen / 100));

    RETURN v_harga_jual;
END $$
DELIMITER ;

SELECT * FROM kartu_stok ORDER BY created_at DESC LIMIT 10;


INSERT INTO penerimaan (
    idpengadaan,
    iduser,
    status,
    created_at
) 
VALUES (
    10,               -- p_idpengadaan
    1,              -- p_iduser
    'P',              -- Status awal: Pending
    NOW()             -- Waktu saat ini
);

select * from kartu_stok ks  
delete from penerimaan 
describe penerimaan
describe pengadaan 
select * from barang 
sELECT * FROM detail_penerimaan ORDER BY idpenerimaan DESC LIMIT 5;

SHOW CREATE PROCEDURE proses_penerimaan_transaksi;
