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
- **Agent 1 (Analis):** Membuat Buyer Persona (TOFU, MOFU, BOFU) dari data Leads + Tracker.
- **Agent 2 (Perencana):** Meracik tabel Kalender Konten 30 Hari.
- **Agent 3 (Penulis SEO):** Menulis artikel panjang standar EEAT Google dan mempublikasikannya (atau menjadwalkannya).
- **Agent 4 (Sosmed):** Menulis prompt DALL-E/Kling, Script Voice Over, dan Caption sosmed.
- **Agent 5 (WA Broadcaster):** Membuat pesan WA natural dan mengirimkannya massal ke Agen via Fonnte API dengan jeda delay anti-banned (rentang 10 jam).

### 4. Admin & Content Management System (CMS)
- **CRUD Master Data:** Pengajar, Fasilitas, Kurikulum, Testimoni, Biaya, Galeri, Jadwal Parenting.
- **Generator Artikel SEO:** Tersambung dengan Gemini, bisa input draft ke editor TinyMCE, atur meta SEO (Title, Keyword, Desc), publish/jadwalkan.
- **Data Santri Baru:** Tabel filter pendaftar SPMB yang lulus seleksi, fitur toggle Daftar Ulang, dan Export to Excel.
- **Penyimpanan Media:** Upload & hapus gambar/video lokal ke folder `/uploads/`.

---

## ⚙️ CATATAN PENTING (KUNCI API)
- **Fonnte Token (WA Gateway):** `Dtw72oRiQr8FympzpMHL`
- **URL GAS (Gemini API):** `https://script.google.com/macros/s/AKfycbyU1T58tS5e1GqxNz_n8lHuRrE5lBJZ6uLEqXCDcXqYC6wsMkRF48FLdIcqpt93ffg/exec`
- *Prompt dinamis diinjeksi via variabel `SYSTEM_COMMAND` dari PHP ke GAS.*

---

## 🎯 NEXT PROJECT (Rencana Selanjutnya)
**Proyek 2: Web Penggalangan Dana (Crowdfunding) Pembangunan Pondok Pesantren Tahfidz + AI Agent.**

*Ide AI Agent untuk Crowdfunding:*
1. **Agen Copywriter:** Menulis landing page/artikel kisah kampanye donasi yang menyentuh (*emotional appeal*).
2. **Agen CS / Follow-up:** Mengingatkan donatur yang belum transfer (pending) atau mengirim ucapan terima kasih otomatis dan doa.
3. **Agen Reporter:** Mengirim *update* progres pembangunan pesantren (teks & foto) via WA secara berkala ke donatur lama agar berdonasi kembali (Retensi).

---
*Catatan untuk AI Assistant: Jika pengguna meminta untuk "melanjutkan proyek", jadikan file ini sebagai titik acuan pemahaman konteks (context baseline).*