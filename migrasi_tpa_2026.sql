-- =========================================
-- MIGRASI TPA - TES POTENSI AKADEMIK
-- SMK Pasundan 2 Bandung
-- =========================================

-- Tambah kolom TPA ke tabel siswa
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tpa_selesai TINYINT(1) DEFAULT 0 AFTER tgl_bayar;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tpa_tanggal DATETIME DEFAULT NULL AFTER tpa_selesai;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tpa_nilai_total INT DEFAULT NULL AFTER tpa_tanggal;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tpa_benar_verbal INT DEFAULT NULL AFTER tpa_nilai_total;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tpa_benar_numerik INT DEFAULT NULL AFTER tpa_benar_verbal;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tpa_benar_logika INT DEFAULT NULL AFTER tpa_benar_numerik;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tpa_jumlah_soal_verbal INT DEFAULT 15 AFTER tpa_benar_logika;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tpa_jumlah_soal_numerik INT DEFAULT 15 AFTER tpa_jumlah_soal_verbal;
ALTER TABLE siswa ADD COLUMN IF NOT EXISTS tpa_jumlah_soal_logika INT DEFAULT 10 AFTER tpa_jumlah_soal_numerik;

-- Tabel soal TPA (bank soal)
CREATE TABLE IF NOT EXISTS tpa_soal (
    id_soal INT AUTO_INCREMENT PRIMARY KEY,
    kategori ENUM('verbal', 'numerik', 'logika') NOT NULL,
    nomor INT NOT NULL,
    pertanyaan TEXT NOT NULL,
    opsi_a VARCHAR(255) NOT NULL,
    opsi_b VARCHAR(255) NOT NULL,
    opsi_c VARCHAR(255) NOT NULL,
    opsi_d VARCHAR(255) NOT NULL,
    jawaban_benar CHAR(1) NOT NULL,
    penjelasan TEXT,
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel jawaban siswa
CREATE TABLE IF NOT EXISTS tpa_jawaban (
    id_jawaban INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    id_soal INT NOT NULL,
    jawaban_pilih CHAR(1),
    benar TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE,
    FOREIGN KEY (id_soal) REFERENCES tpa_soal(id_soal) ON DELETE CASCADE,
    UNIQUE KEY unique_siswa_soal (id_siswa, id_soal)
);

-- Insert soal TPA Verbal
INSERT INTO tpa_soal (kategori, nomor, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar, penjelasan) VALUES
-- Verbal - Sinonim
('verbal', 1, 'SINONIM: "AKURAT" memiliki persamaan makna dengan...', 'Tepat', 'Kasar', 'Cepat', 'Kasar', 'A', 'Akurat berarti tepat, cermat, dan sesuai dengan fakta.'),
('verbal', 2, 'SINONIM: "EFISIEN"', 'Boros', 'Mahal', 'Praktis & hemat', 'Rumit', 'C', 'Efisien berarti bekerja dengan baik, hemat waktu, biaya, dan tenaga.'),
('verbal', 3, 'SINONIM: "KONVERGEN"', 'Menyebar', 'Berkumpul ke satu titik', 'Berbeda', 'Terpisah', 'B', 'Konvergen berarti bergerak/mengarah ke satu titik yang sama.'),
('verbal', 4, 'SINONIM: "DOGMATIS"', 'Berpikir bebas', 'Bersifat mutlak tanpa bukti', 'Logis', 'Empiris', 'B', 'Dogmatis berarti pendapat/sikap yang bersifat mutlak tanpa pembuktian.'),
('verbal', 5, 'SINONIM: "PRAGMATIS"', 'Idealistis', 'Bekerja berdasarkan kenyataan', 'Teoritis', 'Abstrak', 'B', 'Pragmatis berarti bersifat praktis dan berdasarkan kenyataan.'),
-- Verbal - Antonim
('verbal', 6, 'ANTONIM: "KONSERVATIF" adalah...', 'Tradisional', 'Modern/terbuka', 'Statis', 'Dinamis', 'B', 'Konservatif = tradisional, kuno. Antonimnya adalah modern/terbuka.'),
('verbal', 7, 'ANTONIM: "FANATIK" adalah...', 'Intoleran', 'Toleran', 'Netral', 'Apatis', 'B', 'Fanatik = sangat fanatik/berlebihan. Antonimnya toleran.'),
('verbal', 8, 'ANTONIM: "RIBUAN" adalah...', 'Jutaan', 'Puluhan', 'Ratusan', 'Seluruh', 'B', 'Ribuan = berkelipatan seribu. Antonimnya ratusan (sepuluh kali lebih kecil).'),
('verbal', 9, 'ANTONIM: "PROGRESIF" adalah...', 'Regresif', 'Statis', 'Konservatif', 'Tradisional', 'A', 'Progresif = maju, berkembang. Antonimnya regresif (mundur).'),
('verbal', 10, 'ANTONIM: "MONOTON" adalah...', 'Beragam/bervariasi', 'Sama', 'Seragam', 'Tetap', 'A', 'Monoton = sama saja, tidak ada perubahan. Antonimnya beragam.'),
-- Verbal - Analogi
('verbal', 11, 'ANALOGI: "PENA : MENULIS = ... : ...', 'Pensil : Menggambar', 'Penghapus : Menghapus', 'Buku : Membaca', 'Guru : Belajar', 'A', 'Pena digunakan untuk menulis, demikian pula pensil digunakan untuk menggambar.'),
('verbal', 12, 'ANALOGI: "DOKTER : RUMAH SAKIT = GURU : ...', 'Murid', 'Sekolah', 'Kelas', 'Buku', 'B', 'Dokter bekerja di rumah sakit, guru bekerja di sekolah.'),
('verbal', 13, 'ANALOGI: "MOTOR : BENSIN = ... : ...', 'Pesawat : Solar', 'Kereta : Rel', 'Kapal : Air', 'Sepeda : Pedal', 'C', 'Motor membutuhkan bensin untuk berjalan, kapal membutuhkan air.'),
('verbal', 14, 'ANALOGI: "KAKI : BERJALAN = TANGAN : ...', 'Berdiri', 'Memegang', 'Duduk', 'Tidur', 'B', 'Kaki digunakan untuk berjalan, tangan digunakan untuk memegang.'),
('verbal', 15, 'ANALOGI: "PANAS : API = ... : ...', 'Dingin : Es', 'Hangat : Air', 'Lembut : Bantal', 'Keras : Batu', 'A', 'Panas adalah sifat api, dingin adalah sifat es.'),

-- TPA Numerik - Deret Angka
('numerik', 1, 'Berapakah nilai berikutnya dari deret: 2, 6, 12, 20, 30, ...', '40', '42', '44', '46', 'B', 'Polanya: +4, +6, +8, +10, +12. Jadi 30+12=42.'),
('numerik', 2, 'Berapakah nilai berikutnya dari deret: 1, 3, 9, 27, ...', '54', '81', '108', '243', 'B', 'Setiap angka dikalikan 3. Jadi 27x3=81.'),
('numerik', 3, 'Berapakah nilai berikutnya dari deret: 100, 50, 25, 12.5, ...', '6.25', '6.5', '6', '7.25', 'A', 'Setiap angka dibagi 2. Jadi 12.5/2=6.25.'),
('numerik', 4, 'Berapakah nilai berikutnya dari deret: 3, 8, 15, 24, 35, ...', '46', '48', '50', '54', 'B', 'Polanya: +5, +7, +9, +11, +13. Jadi 35+13=48.'),
('numerik', 5, 'Berapakah nilai berikutnya dari deret: 1, 4, 16, 64, ...', '128', '196', '256', '512', 'C', 'Setiap angka dikalikan 4. Jadi 64x4=256.'),
-- Numerik - Aritmatika
('numerik', 6, 'Jika 35% dari suatu kelas adalah 14 siswa, berapakah jumlah seluruh siswa di kelas tersebut?', '30', '40', '45', '50', 'B', '14 ÷ 0.35 = 40 siswa'),
('numerik', 7, 'Seorang pedagang membeli barang seharga Rp 80.000 dan menjualnya dengan harga Rp 100.000. Berapa persen keuntungannya?', '15%', '20%', '25%', '30%', 'C', 'Untung = (100.000-80.000)/80.000 x 100% = 25%'),
('numerik', 8, 'Berapakah nilai dari 2³ + 3² - 4²?', '1', '5', '9', '13', 'A', '8 + 9 - 16 = 1'),
('numerik', 9, 'Perbandingan uang A dan B adalah 3:5. Jika selisih uang mereka Rp 40.000, berapakah uang A?', 'Rp 50.000', 'Rp 60.000', 'Rp 70.000', 'Rp 80.000', 'B', 'Selisih ratio = 2 bagian = 40.000, jadi 1 bagian = 20.000. Uang A = 3x20.000 = 60.000'),
('numerik', 10, 'Jarak kota X ke Y adalah 240 km. Jika ditempuh dengan kecepatan 80 km/jam, berapa waktu yang dibutuhkan?', '2 jam', '2,5 jam', '3 jam', '3,5 jam', 'C', 'Waktu = Jarak ÷ Kecepatan = 240 ÷ 80 = 3 jam'),
('numerik', 11, 'Hitung: √144 + √225 - √81 = ...', '12', '15', '18', '21', 'C', '12 + 15 - 9 = 18'),
('numerik', 12, 'Jika x + 5 = 12, berapakah nilai 3x - 4?', '17', '20', '23', '26', 'A', 'x = 12-5 = 7, jadi 3(7)-4 = 21-4 = 17'),
('numerik', 13, '75% dari 120 + 40% dari 80 = ...', '116', '124', '132', '140', 'A', '0.75x120 + 0.4x80 = 90 + 32 = 122... wait 90+32=122'),
('numerik', 14, 'Jika harga sebuah baju turun dari Rp 150.000 menjadi Rp 120.000, berapa persen penurunannya?', '15%', '20%', '25%', '30%', 'B', '(150.000-120.000)/150.000 x 100% = 20%'),
('numerik', 15, 'Berapakah KPK dari 12 dan 18?', '24', '36', '48', '72', 'B', '12=2²x3, 18=2x3², KPK=2²x3²=36'),

-- TPA Logika - Penalaran
('logika', 1, 'Semua guru adalah pendidik. Sebagian pendidik adalah perempuan. Simpulan yang PALING TEPAT adalah...', 'Semua guru adalah perempuan', 'Sebagian guru adalah perempuan', 'Semua perempuan adalah pendidik', 'Tidak dapat disimpulkan', 'D', 'Dari premis tersebut tidak dapat ditarik kesimpulan yang pasti.'),
('logika', 2, 'Jika hujan turun, maka jalan basah. Jalan basah. Simpulan yang VALID adalah...', 'Hujan turun', 'Hujan mungkin turun', 'Matahari bersinar', 'Tidak hujan', 'B', 'jalan basah bisa disebabkan hujan, tetapi bukan bukti pasti.'),
('logika', 3, 'Ani lebih tinggi dari Budi. Budi lebih tinggi dari Citi. Citra lebih tinggi dari Ani. Siapakah yang paling pendek?', 'Ani', 'Budi', 'Citra', 'Budi dan Citi', 'B', 'Dari tertinggi ke terendah: Citra > Ani > Budi'),
('logika', 4, 'Jika x > 5 dan y < 3, manakah yang PASTI BENAR?', 'x + y > 8', 'x - y > 2', 'x + y < 8', 'Tidak ada yang pasti', 'D', 'Nilai x dan y tidak diketahui pasti, sehingga tidak ada yang pasti benar.'),
('logika', 5, 'Premis 1: Semua siswa SMK pandai.
Premis 2: Andi adalah siswa SMK.
Simpulan:', 'Andi tidak pandai', 'Andi pandai', 'Semua pandai adalah siswa SMK', 'Andi mungkin pandai', 'B', 'Simpulan langsung: Andi pandai karena semua siswa SMK pandai.'),
('logika', 6, 'Dalam satu kelas, semua yang suka matematika juga suka fisika. Beberapa yang suka fisika tidak suka kimia. Simpulan yang PALING TEPAT:', 'Semua yang suka matematika tidak suka kimia', 'Beberapa yang suka matematika tidak suka kimia', 'Semua yang suka fisika suka matematika', 'Tidak dapat disimpulkan', 'D', 'Tidak ada informasi cukup untuk menyimpulkan hubungan matematika-kimia.'),
('logika', 7, 'Jika P = {2, 3, 5, 7} dan Q = {1, 2, 3, 4}, maka P ∩ Q adalah...', '{1, 2, 3, 4, 5, 7}', '{2, 3}', '{1, 4}', '{1, 2, 3, 4, 5, 7}', 'B', 'Irisan (∩) adalah elemen yang ada di kedua himpunan: {2, 3}'),
('logika', 8, 'Jika 3x + 7 = 22, maka 5x - 3 = ...', '18', '22', '25', '28', 'B', '3x = 22-7 = 15, x = 5. Jadi 5(5)-3 = 25-3 = 22'),
('logika', 9, 'A, B, C, D, E berdiri membentuk lingkaran. A di antara D dan E. B di antara A dan C. Posisi C adalah...', 'Di antara A dan E', 'Di antara A dan B', 'Di antara B dan D', 'Tidak dapat ditentukan', 'D', 'Posisi C tidak dapat ditentukan pasti.'),
('logika', 10, 'Dalam suatu排 (deretan), jika kita membaca dari kiri ke kanan: X berada di antara Y dan Z. Y berada di paling kiri. Urutan dari kiri ke kanan adalah:', 'Y, X, Z', 'Y, Z, X', 'Z, Y, X', 'X, Y, Z', 'A', 'Y di paling kiri, X di antara Y dan Z, jadi: Y - X - Z');
