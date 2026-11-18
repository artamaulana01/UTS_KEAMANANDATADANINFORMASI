# Brankas Data: Sistem Informasi PHP dengan Login Kunci Simetris dan Asimetris

Tugas UTS [KEAMANAN DATA DAN INFORMASI]
**Nama:** [ARTA MAULANA] | **NIM:** [2430511085] | **Kelas:** [5C]
**Dosen:** [ASRIL ADI SUNARTO, M.Kom.]

## Deskripsi
Brankas Data adalah aplikasi sistem informasi berbasis PHP yang menyimpan data dalam bentuk terenkripsi. Landing page menjadi pintu masuk ke halaman admin. Login admin tidak memakai username dan password, melainkan **input kunci simetris dan kunci asimetris (RSA)**. Setelah kedua kunci valid, halaman admin menampilkan seluruh data yang tersimpan dalam bentuk ciphertext berikut hasil dekripsinya.

## Fitur
- Landing page sebagai pintu masuk ke halaman admin
- Pembuatan kunci otomatis (`setup.php`): kunci simetris acak dan pasangan kunci RSA 2048-bit
- Login admin dengan kunci simetris dan kunci privat RSA (tempel PEM atau unggah file `.pem`)
- Penyimpanan data (judul dan isi) dalam bentuk ciphertext
- Halaman admin yang menampilkan ciphertext dan hasil dekripsi, serta fitur hapus data
- Logout dan proteksi halaman admin dengan session

## Teknologi
PHP (PDO, ekstensi OpenSSL), MySQL, HTML dan CSS. Dijalankan dengan Laragon.

## Cara kerja enkripsi
Aplikasi memakai **enkripsi hibrida**:

| Tahap | Proses |
|---|---|
| Menyimpan data | Tiap data memiliki kunci acak (DEK). Judul dan isi dienkripsi dengan **AES-256-CBC** memakai DEK tersebut. |
| Pembungkusan kunci | DEK dibungkus AES memakai **kunci simetris** admin, lalu hasilnya dienkripsi lagi dengan **RSA-OAEP** memakai kunci publik. |
| Login | Kunci simetris diverifikasi dengan `password_verify` terhadap hash-nya. Kunci privat RSA diverifikasi dengan challenge acak yang dienkripsi dengan kunci publik, lalu harus berhasil didekripsi dengan kunci privat yang diinput. |
| Membaca data | Dekripsi hanya berhasil jika **kedua kunci** benar. |

Database hanya menyimpan hash kunci simetris dan kunci publik RSA. Kunci simetris asli dan kunci privat **tidak pernah disimpan** di database.

## Struktur proyek
```
uts-kripto/
├── index.php      # landing page
├── setup.php      # pembuatan kunci (sekali jalan)
├── login.php      # login dengan kunci simetris + asimetris
├── admin.php      # halaman admin (tambah, lihat, hapus data)
├── logout.php     # keluar dan hapus session
├── crypto.php     # fungsi enkripsi dan dekripsi
├── config.php     # koneksi database dan session
├── style.css      # tampilan
└── db.sql         # skema database
```

## Instalasi
1. Salin folder proyek ke `C:\laragon\www\uts-kripto`.
2. Nyalakan Apache dan MySQL di Laragon (**Start All**).
3. Import `db.sql` melalui phpMyAdmin (tab **SQL** atau **Import**). Database `uts_kripto` akan dibuat.
4. Sesuaikan kredensial database di `config.php` bila perlu (bawaan: user `root`, password kosong).
5. Buka `http://uts-kripto.test/setup.php` atau `http://localhost/uts-kripto/setup.php`, lalu klik **Buat kunci**.
6. **Simpan kunci simetris dan unduh `private_key.pem`.** Kunci hanya ditampilkan sekali.

## Cara penggunaan
1. Buka landing page, lalu klik **Masuk sebagai admin**.
2. Masukkan kunci simetris dan kunci privat RSA, lalu klik **Buka brankas**.
3. Di halaman admin, isi form untuk menyimpan data baru. Data langsung dienkripsi sebelum masuk database.
4. Tabel menampilkan ciphertext (seperti di database) berdampingan dengan hasil dekripsinya.
5. Klik **Keluar** untuk mengakhiri session.

## Keterbatasan
- Kunci simetris diturunkan dengan SHA-256 tanpa salt (PBKDF2 akan lebih kuat).
- Kunci disimpan di session selama admin login.
- Belum ada proteksi CSRF dan pembatasan percobaan login.
- Kunci yang hilang tidak bisa dipulihkan, sehingga data lama tidak dapat dibuka lagi.

## Catatan keamanan
Jangan mengunggah `private_key.pem` maupun kunci simetris ke repository.