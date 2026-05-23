# 🧠 AI MEMORY & PROJECT CONTEXT
**Project:** Sistem Informasi Manajemen (SIM) & Marketing Web - Villa Quran Indonesia
**Tech Stack:** PHP Native, MySQL, Tailwind CSS, JavaScript (Fetch API)
**AI Integrations:** Google Gemini 2.5 Flash (via Google Apps Script), Fonnte API (WhatsApp Gateway)

## 🚀 FITUR YANG SUDAH SELESAI DIBANGUN

### 1. Frontend & Landing Page (Tampilan Publik)
- Beranda dengan Hero Section, USP, Testimoni, dan Countdown SPMB otomatis.
- Halaman Fasilitas, Asrama, Biaya (Gated Content/Terkunci form lead), Kurikulum, Profil Pengajar.
- Form Pendaftaran SPMB Multi-step.
- Pop-up Lead Magnet (Download E-Book / Daftar Open House) dengan timer pintar (session storage).

### 2. Marketing Pipeline & Leads Management
- **Mata AI Tracker:** Merekam jejak pengunjung (Device, Browser, Lokasi, Sumber Traffic).
- **Kanban Board:** Menampilkan status leads dalam 6 level (Level 1 sd Level 6). Bisa Drag & Drop.
- **Sistem Referral Agen:** Pencatatan link `?ref=0812xxx` agar pendaftar masuk ke komisi agen yang tepat.

### 3. Mode Dewa: Pusat Kendali AI Agent (admin-ai-hub.php)
Sistem orkestrasi berantai (Daisy Chaining) yang berjalan otomatis dengan 2 mode (Harian & Borongan 1 Bulan).
- **Agent "Analisa Persona":** Menganalisa data leads dan jejak pengunjung untuk membuat Buyer Persona (TOFU, MOFU, BOFU).
- **Agent "Kalender Konten":** Meracik tabel Kalender Konten editorial untuk 30 hari berdasarkan persona yang sudah dibuat.
- **Agent "Penulis Artikel SEO":** Menulis artikel panjang standar EEAT Google setiap hari sesuai jadwal dari Kalender Konten, lalu mempublikasikannya secara otomatis ke website.
- **Agent "Publisher" (d/h Kurir WA):** Setelah artikel terbit, agent ini langsung mendistribusikan link artikel baru ke semua agen marketing melalui WhatsApp, lengkap dengan link afiliasi unik.

### 4. Admin, CMS & Monitoring
- **CRUD Master Data:** Pengajar, Fasilitas, Kurikulum, Testimoni, Biaya, Galeri, Jadwal Parenting.
- **Generator Artikel SEO:** Tersambung dengan Gemini, bisa input draft ke editor TinyMCE, atur meta SEO (Title, Keyword, Desc), publish/jadwalkan.
- **Data Santri Baru:** Tabel filter pendaftar SPMB yang lulus seleksi, fitur toggle Daftar Ulang, dan Export to Excel.
- **Penyimpanan Media:** Upload & hapus gambar/video lokal ke folder `/uploads/`.
- **AI Publisher Monitor:** Halaman untuk memantau status pengiriman pesan WA dari Agent Publisher ke semua agen.

---

## ⚙️ CATATAN PENTING (KUNCI API)
- **Fonnte Token (WA Gateway):** `Dtw72oRiQr8FympzpMHL`
- **URL GAS (Gemini API):** `https://script.google.com/macros/s/AKfycbyU1T58tS5e1GqxNz_n8lHuRrE5lBJZ6uLEqXCDcXqYC6wsMkRF48FLdIcqpt93ffg/exec`
- *Prompt dinamis diinjeksi via variabel `SYSTEM_COMMAND` dari PHP ke GAS.*

---

## 🎯 NEXT PROJECT (Rencana Selanjutnya)
*(Belum ada proyek baru yang ditetapkan. Menunggu arahan selanjutnya dari bos...)*

---
*Catatan untuk AI Assistant: Jika pengguna meminta untuk "melanjutkan proyek", jadikan file ini sebagai titik acuan pemahaman konteks (context baseline).*