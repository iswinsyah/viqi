# 🧠 AI MEMORY & MANUAL PENGEMBANGAN PROJEK
**Projek:** Sistem Informasi Manajemen (SIM) & Hub Marketing AI - Villa Quran Indonesia
**Platform:** PHP Native, MySQL, Tailwind CSS, JavaScript (Fetch API)
**Integrasi Utama:** Google Gemini 2.5 Flash (via Google Apps Script loopback), Fonnte API (WhatsApp Gateway), Pixabay API (Cover Otomatis)

---

## 💾 KREDENSIAL UTAMA & INTEGRASI API
* **Fonnte Token (WA Gateway):** `Dtw72oRiQr8FympzpMHL`
* **URL Google Apps Script (GAS / Otak Gemini):** 
  `https://script.google.com/macros/s/AKfycbyU1T58tS5e1GqxNz_n8lHuRrE5lBJZ6uLEqXCDcXqYC6wsMkRF48FLdIcqpt93ffg/exec`
* **Database Kredensial (`koneksi.php`):**
  * Host: `localhost`
  * Database: `u829486010_viqi`
  * User: `u829486010_viqi` (fallback ke `root` di lokal)
  * Password: `Khilafet@1924` (kosong di lokal)

---

## 🤖 ARSITEKTUR AI AGENTS & FITUR UTAMA
Sistem ini menggunakan **Daisy Chaining** (rantaian tugas AI) yang diatur di dalam `cron-agent.php` dan dapat dipantau di halaman Admin AI Hub (`admin-ai-hub.php`).

### 1. Rangkaian AI Agent Harian & Bulanan
* **Agent "Analisa Persona" (Bulanan):** Berjalan tiap tanggal 1. Menganalisis jejak pengunjung (`visitor_footprints`) dan lead masuk untuk merumuskan Persona Wali Santri (TOFU, MOFU, BOFU). Hasil disimpan di `saved_persona.txt`.
* **Agent "Trend Scout" (Bulanan/Harian):** Menganalisis tren makro bulanan (disimpan di `saved_trends_macro.txt`) dan mikro harian (disimpan di `saved_trends_micro.txt`) dari data eksternal untuk menentukan tema & keyword viral.
* **Agent "Kalender Konten" (Bulanan):** Menghasilkan rancangan topik konten selama 30 hari ke depan berdasarkan persona dan tren makro. Hasil disimpan di `saved_kalender.txt`.
* **Agent "Penulis Artikel SEO" (Harian, Jam 07:00):** Menulis artikel panjang standar EEAT Google berdasarkan kalender konten hari ini. Menghasilkan konten HTML, meta title, meta keyword, meta description, serta copywriting promosi.
* **Agent "Publisher" (Harian, Setelah Artikel Terbit):** Mendistribusikan link artikel baru beserta **copywriting promosi AI otomatis** ke WhatsApp semua agen pemasaran yang terdaftar di database, lengkap dengan penambahan parameter referral unik agen (`?ref=KODE_AGEN`).
* **Agent "Community Scout" (Independen - Setiap Jam / Manual):** Dipisahkan dari alur SEO agar berjalan mandiri. Mencari grup komunitas baru (Facebook, WhatsApp, Telegram) yang ramai & aktif (FB minimal 5-10 postingan baru/hari), mengecualikan grup milik kompetitor, serta memiliki memori eksklusi agar tidak menyarankan grup yang sudah pernah disimpan. Hasil disimpan ke tabel `grup_komunitas`.

### 2. Billing & Penagihan SPP Otomatis
* **Agent Penagihan (Tanggal 1, 3, 6, 10):** Mengirim pengingat tagihan SPP dan sisa uang masuk secara otomatis melalui WhatsApp ke wali santri.
* **Konfirmasi Janji Bayar (`konfirmasi-janji-bayar.php`):** Halaman publik ber-token pengaman MD5. Jika orang tua santri menolak/menunda bayar, mereka diarahkan ke halaman ini untuk berkomitmen memilih tanggal janji bayar baru. Hasil masuk ke tabel `keuangan_janji_bayar`.
* **AI Auditor (`yayasan2/pembukuan.php`):** Terintegrasi di dashboard keuangan bendahara untuk mengaudit jurnal masuk-keluar dan melacak janji pembayaran tertunggak secara cerdas.

---

## 🗄️ SKEMA DATABASE PENTING
Berikut adalah tabel-tabel utama yang sering diakses oleh AI Agents:

### 1. Tabel `artikel`
Menyimpan artikel yang dibuat otomatis oleh AI Writer atau dimasukkan manual oleh admin.
```sql
CREATE TABLE IF NOT EXISTS artikel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    kategori VARCHAR(100) DEFAULT 'Berita',
    gambar_cover VARCHAR(255),
    konten TEXT,
    status VARCHAR(50) DEFAULT 'publish',
    published_at DATETIME NULL,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords VARCHAR(255),
    copywriting_promo TEXT,
    status_broadcast ENUM('menunggu', 'terkirim') DEFAULT 'menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 2. Tabel `leads`
Mencatat calon wali santri yang mengisi form lead magnet (unduh brosur/ebook).
```sql
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    status VARCHAR(50) DEFAULT 'Level 1', -- Level leads 1 sd 6
    jenis_lead VARCHAR(50) DEFAULT 'brosur',
    sumber_info VARCHAR(100) DEFAULT '',
    kode_ref VARCHAR(50) DEFAULT 'organik',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3. Tabel `grup_komunitas`
Menampung grup sosmed hasil riset Community Scout untuk penyebaran artikel.
```sql
CREATE TABLE IF NOT EXISTS grup_komunitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_grup VARCHAR(255) NOT NULL,
    platform VARCHAR(50) NOT NULL,
    link_gabung VARCHAR(255) NOT NULL UNIQUE,
    analisa_relevansi TEXT,
    skor_kualitas INT DEFAULT 5,
    saran_pembuka TEXT,
    status VARCHAR(50) DEFAULT 'Belum Dihubungi',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 4. Tabel `visitor_footprints` (Mata AI)
Mencatat informasi lalu lintas kunjungan web secara detail untuk dianalisis oleh AI Persona.
```sql
CREATE TABLE IF NOT EXISTS visitor_footprints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device VARCHAR(50),
    os_browser TEXT,
    language VARCHAR(50),
    source VARCHAR(255),
    campaign VARCHAR(100),
    traffic_type VARCHAR(50),
    location VARCHAR(100),
    isp VARCHAR(100),
    visit_time VARCHAR(100),
    timezone VARCHAR(50),
    page_viewed VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 5. Tabel `keuangan_janji_bayar`
Mencatat komitmen tanggal bayar dari wali santri yang menunggak SPP.
```sql
CREATE TABLE IF NOT EXISTS keuangan_janji_bayar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    bulan VARCHAR(20) NOT NULL,
    tahun VARCHAR(4) NOT NULL,
    tanggal_janji DATE NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (santri_id) REFERENCES buku_induk_santri(id) ON DELETE CASCADE
);
```

---

## ⚙️ CARA MENJALANKAN AGENT SECARA MANUAL (MENGGUNAKAN QUERY STRING)
Cron job server berjalan otomatis pada jam-jam tertentu. Namun, Anda dapat memaksa (`force`) agent berjalan langsung dari browser dengan mengakses URL berikut:

1. **Jalankan Ulang Riset Tren & Artikel Harian (SEO):**
   `https://villaquranindonesia.com/cron-agent.php?force=seo`
2. **Jalankan Riset Grup Sosial Media (Community Scout):**
   `https://villaquranindonesia.com/cron-agent.php?force=community`
3. **Jalankan Pengingat Tagihan SPP (Billing):**
   `https://villaquranindonesia.com/cron-agent.php?force=billing`

*Catatan: Pastikan status Autopilot menyala (`ON`) di halaman Admin AI Hub, atau file `autopilot_status.txt` berisi tulisan `ON`.*

---

## 🚀 ALUR DEPLOYMENT & SINKRONISASI
Projek ini dideploy secara otomatis menggunakan **GitHub Actions** (`.github/workflows/deploy.yml`).
Setiap kali Anda melakukan `git commit` dan `git push` ke branch `main`, workflow GitHub akan memicu proses sinkronisasi file menggunakan FTP/SFTP langsung ke server Hostinger.

*   **Penting:** Selalu edit file di repositori lokal terlebih dahulu, lalu push ke GitHub. Jangan mengedit langsung di File Manager Hostinger agar kode lokal dan server tidak mengalami konflik (out of sync).

---

## 🛠️ KETENTUAN KHUSUS & BUG-FIX TERBARU
Jika Anda melanjutkan pengerjaan projek ini, harap perhatikan arsitektur penting berikut yang telah diperbaiki:

1.  **Anti-Crash MySQL (`pastikanKoneksiDb()`):**
    Karena request Gemini API memakan waktu respons yang lama (hingga 2 menit), koneksi MySQL di server Hostinger sering terputus dengan error `MySQL server has gone away`. Gunakan fungsi `pastikanKoneksiDb()` sebelum melakukan query database setelah request cURL eksternal yang lama.
2.  **Normalisasi WhatsApp Gateway (Fonnte):**
    Nomor WhatsApp agen dan wali santri harus selalu dibersihkan dan diformat dengan awalan `62...`. Di dalam `cron-agent.php`, normalisasi dilakukan dengan mengganti awalan `0` atau `+` menjadi `62`. Status balasan dari API Fonnte juga dicatat secara lengkap di file log `agent_cron_log.txt`.
3.  **Dynamic Host Resolution Bug (GAS cURL Loopback):**
    Di dalam `cron-agent.php`, variabel host penentu GAS dinamis diubah dari `$host` menjadi `$http_host`. Hal ini penting karena nama variabel `$host` bentrok dengan variabel koneksi database `$host = "localhost"`. Jika bentrok, database Hostinger akan gagal diakses karena mencoba tersambung ke domain web, bukan ke localhost database.
4.  **Format Tautan Thumbnail Sosmed (Open Graph):**
    Halaman detail artikel (`artikel-detail.php`) telah disesuaikan agar mengubah letak link cover gambar lokal yang relatif (`/upload/cover.jpg`) menjadi link absolut (`https://villaquranindonesia.com/upload/cover.jpg`). Ini wajib agar card thumbnail di Facebook dan WhatsApp terrender dengan ukuran penuh secara horizontal (landscape large card).

---

## 📋 INSTRUKSI UNTUK AI AGENT BERIKUTNYA
Jika quota AI habis dan Anda digantikan oleh agent lain:
1.  **Baca file ini (`AI_MEMORY.md`)** secara utuh untuk memahami semua relasi file dan arsitektur database.
2.  **Periksa status terakhir di file log:**
    *   Log Harian/Koneksi: [agent_cron_log.txt](file:///d:/LOCALHOST/viqi/agent_cron_log.txt)
    *   Log Komunitas: `agent_community_log.txt`
3.  **Gunakan file `config-key.php`** untuk melihat atau menambah API Key rahasia (seperti Pixabay Key atau Google API Key). File ini diabaikan oleh git (`.gitignore`), jadi pastikan nilainya sesuai di server Hostinger.
4.  Jangan membuat ulang roda (tabel atau helper) jika sudah ada. Manfaatkan helper koneksi dan normalisasi yang sudah tersedia.