<?php
/**
 * ENGINE API AGENT AI YOAST SEO EDITOR & AUDITOR
 * Villa Quran Baron Malang - SADIGS 4.0
 * 
 * Melakukan audit On-Page SEO & Keterbacaan (Readability) ala Yoast SEO,
 * serta secara otomatis memoles/mengedit artikel lama dan baru agar berstatus All Green 🟢🟢.
 */

ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/yoast-engine.php';

header('Content-Type: application/json');

// Pastikan kolom Yoast tersedia di tabel artikel
$conn->query("ALTER TABLE artikel ADD COLUMN IF NOT EXISTS yoast_seo_score VARCHAR(20) DEFAULT 'untested'");
$conn->query("ALTER TABLE artikel ADD COLUMN IF NOT EXISTS yoast_readability_score VARCHAR(20) DEFAULT 'untested'");
$conn->query("ALTER TABLE artikel ADD COLUMN IF NOT EXISTS yoast_audit_log TEXT NULL");

// Tangkap input JSON atau POST
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? $_GET['action'] ?? '';

// =========================================================================
// ACTION: AUDIT_ARTICLE (Diagnosa Tanpa Ubah Database)
// =========================================================================
if ($action === 'audit') {
    $article_id = (int)($input['article_id'] ?? 0);
    $konten = $input['konten'] ?? '';
    $judul = $input['judul'] ?? '';
    $keyword = $input['keyword'] ?? '';
    $meta_desc = $input['meta_description'] ?? '';

    if ($article_id > 0) {
        $res = $conn->query("SELECT * FROM artikel WHERE id = $article_id LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $konten = $row['konten'];
            $judul = $row['judul'];
            $keyword = $row['meta_keywords'] ?: 'sekolah tahfidz, pesantren tahfidz';
            $meta_desc = $row['meta_description'] ?: '';
        }
    }

    $hasil = analisaHeuristikYoast($konten, $judul, $keyword, $meta_desc);
    echo json_encode([
        'status' => 'success',
        'data' => $hasil
    ]);
    exit;
}

// =========================================================================
// ACTION: OPTIMIZE_YOAST (Perbaiki & Poles Otomatis Jadi Double Green)
// =========================================================================
if ($action === 'optimize') {
    $article_id = (int)($input['article_id'] ?? 0);
    $konten = $input['konten'] ?? '';
    $judul = $input['judul'] ?? '';
    $keyword = $input['keyword'] ?? '';
    $save_to_db = !empty($input['save_to_db']);

    $hasil = eksekusiPolesArtikelYoast($conn, $article_id, $judul, $keyword, $konten, $save_to_db);
    echo json_encode($hasil);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali']);
exit;
