# 🧠 AI MEMORY & MANUAL PENGEMBANGAN PROJEK
**Projek:** Sistem Administrasi Digital Sekolah (SADIGS 4.0) & Hub Marketing AI - Villa Quran Baron Malang
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
Projek ini dideploy secara otomatis menggunakan **GitHub Actions SFTP Deploy Action (`.github/workflows/deploy.yml`)** ke server Hostinger (`domains/villaquranindonesia.com/public_html` pada port `65002`).
Setiap kali Anda melakukan `git commit` dan `git push` ke branch `main`, GitHub Actions langsung berjalan secara otomatis menyinkronkan file perubahan (delta rsync sync).

---

## 🎯 LEAD MAGNET & STRATEGI FUNNELING KOMUNITAS DAKWAH (TERBARU)
### 1. Profil & Target Market
* **Target:** Komunitas Aktivis Dakwah (orang tua yang menginginkan anak sholeh & hafidz Quran 30 juz, melek pendidikan tapi kritis dan anti hard-selling).
* **Saluran Distribusi:** Disebar oleh Mitra/Agen di tiap kota ke grup WhatsApp komunitas.
* **Umpan (Lead Magnet):** **Tes Kesiapan Anak Masuk Pondok** ([tes-kesiapan-anak.php](file:///d:/LOCALHOST/viqi%202/tes-kesiapan-anak.php)).

### 2. Alur Konversi (Funnel)
1. Orang tua mengisi Nama & WhatsApp (Form awal simpel tanpa step number).
2. Mengerjakan 15 pertanyaan asesmen multi-dimensi (Kemandirian, Emosi, Spiritual, Sosial, Orang Tua).
3. Di soal ke-15, tombol bertuliskan: **"Dapatkan Analisa Lengkap Sekarang"**.
4. Saat diklik:
   - Data & skor otomatis tersimpan di tabel database `leads` (`simpan-test-kesiapan.php`).
   - Browser otomatis me-redirect ke WhatsApp CS resmi dengan pesan:  
     `"Mohon dikirim analisa asesmen nya"`
5. **Peran CS / AI Assistant di WhatsApp:**
   - Bertindak sebagai **Konsultan Parenting & Pendidikan Islam**, bukan sales.
   - Memberikan analisa objektif berdasarkan 3 kategori kesiapan (Sangat Siap, Siap dengan Pendampingan, Butuh Pembiasaan).
   - Membimbing percakapan konsultasi secara hangat dan elegan.
   - **Closing Goal:** Mengundang orang tua mengikuti **Open House Online (Webinar Parenting & Bedah Ekosistem Pesantren)** via Zoom.

---

## 📁 STRUKTUR MODULAR REPOSITORI (REFACTORING OKTOBER 2026)
Repositori telah dimodularisasi penuh menjadi arsitektur subfolder bersih dengan **Zero-Breaking Backward Compatibility Proxy** di root:
* `includes/` : `koneksi.php`, `config-key.php` (Konfigurasi & database terpusat).
* `api/` : 16 Endpoint REST & Webhook (`gemini.php`, `ai.php`, `wa-webhook.php`, `cp-ai.php`, dll.).
* `auth/` : Autentikasi, session guard, login, & logout semua role (`auth.php`, `login-santri.php`, dll.).
* `components/` : Komponen UI (`sidebar.php`, `sidebar-marketing.php`, `footer.php`, `bottombar-*`, dll.).
* `admin/` : 80 modul administrasi dan panel kontrol manajemen web/sekolah.
* `santri/` & `orangtua/` : Portal layanan santri dan wali santri.
* `marketing/` : Portal marketing, pipeline, dan data agen.
* `handlers/` : Form submit & AJAX processor (`simpan-test-kesiapan.php`, `simpan-brosur.php`, dll.).
* `cron/` : Background worker, autopilot marketing agent, dan cron health check.
* `database/` : Migration schema, database setup, dan seeder.
* `Root Directory` : Halaman publik (`index.php`, `tes-kesiapan-anak.php`, `brosur.php`, `artikel.php`, dll.) + file proxy 1 baris untuk kompatibilitas tautan lama.

---

## 🧠 CS AI TRAINING CENTER & WHATSAPP WEBHOOK CONSULTANT
1. **Panel Training CS AI (`admin/admin-ai-cs-training.php`)**:
   - Tabel database: `ai_cs_knowledge` (Self-healing).
   - Fitur CRUD Materi: Kategori, Topik Pertanyaan, Kata Kunci Pemicu (*Keywords*), Instruksi Fakta/SOP Resmi, dan Arahan Closing Open House.
   - Built-in Live AI Simulator: Admin dapat menguji langsung respons percakapan Gemini AI secara real-time.
2. **Webhook Engine WhatsApp (`api/wa-webhook.php`)**:
   - Menghubungkan nomor Fonnte (`6285189918115`) dengan AI Consultant Persona.
   - Otomatis membaca riwayat skor kesiapan dan profil anak dari tabel `leads` saat pesan masuk (`"Mohon dikirim analisa asesmen nya"`).
   - Menerapkan SOP Jawaban dari Knowledge Base aktif dan secara konsisten mengarahkan orang tua untuk reservasi agenda **Open House & Survey Pesantren**.

---

## 🛠️ KETENTUAN KHUSUS & BUG-FIX TERBARU
1. **Auto-Deploy Webhook Hostinger**: Cukup commit dan push ke `main`, website langsung terupdate 1-2 detik via SFTP action.
2. **Lead Magnet Curiosity Funnel**: Di `tes-kesiapan-anak.php`, skor tidak dimunculkan di web. User langsung dialihkan ke WhatsApp CS resmi dengan pesan instan untuk menciptakan percakapan konsultasi yang dipandu AI.
3. **Anti-Crash MySQL (`pastikanKoneksiDb()`):**
   Gunakan fungsi `pastikanKoneksiDb()` sebelum melakukan query database setelah request cURL eksternal yang lama.
4. **Normalisasi WhatsApp Gateway (Fonnte):**
   Nomor WhatsApp agen dan wali santri harus selalu dibersihkan dan diformat dengan awalan `62...`.

---

## 📋 INSTRUKSI UNTUK AI AGENT BERIKUTNYA
Jika sesi chat baru dibuka atau laptop baru dinyalakan:
1. **Baca file `AI_MEMORY.md` ini** secara utuh untuk langsung melanjutkan pekerjaan tanpa kehilangan konteks.
2. Cek status database `leads`, modul `admin/admin-ai-cs-training.php`, dan webhook `api/wa-webhook.php`.
3. Selalu lakukan `git commit` dan `git push origin main` setiap menyelesaikan modifikasi agar otomatis ter-deploy ke Hostinger.