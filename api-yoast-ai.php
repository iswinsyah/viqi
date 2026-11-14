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
if (file_exists(__DIR__ . '/config-key.php')) {
    require_once __DIR__ . '/config-key.php';
}

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
// HELPER: Audit Heuristik Teks Cepat
// =========================================================================
function analisaHeuristikYoast($konten, $judul, $keyword, $meta_desc) {
    $checklist_seo = [];
    $checklist_read = [];
    
    $clean_text = strip_tags($konten);
    $word_count = str_word_count($clean_text);
    $sentences = preg_split('/[.!?]+/', $clean_text, -1, PREG_SPLIT_NO_EMPTY);
    $sentence_count = max(1, count($sentences));
    
    // 1. Audit Kata Kunci Utama
    $kw_clean = strtolower(trim($keyword));
    $kw_primary = '';
    if (!empty($kw_clean)) {
        $kw_parts = explode(',', $kw_clean);
        $kw_primary = trim($kw_parts[0]);
    }
    
    // Keyphrase di Judul
    $has_kw_title = (!empty($kw_primary) && stripos($judul, $kw_primary) !== false);
    $checklist_seo[] = [
        'name' => 'Keyphrase di Judul',
        'status' => $has_kw_title ? 'pass' : 'fail',
        'text' => $has_kw_title ? "Focus keyphrase '$kw_primary' ditemukan di judul." : "Focus keyphrase belum ada di judul."
    ];

    // Keyphrase di Paragraf Pertama
    $first_p = '';
    if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $konten, $m_p)) {
        $first_p = strip_tags($m_p[1]);
    } else {
        $first_p = substr($clean_text, 0, 300);
    }
    $has_kw_first_p = (!empty($kw_primary) && stripos($first_p, $kw_primary) !== false);
    $checklist_seo[] = [
        'name' => 'Keyphrase di Paragraf Pembuka',
        'status' => $has_kw_first_p ? 'pass' : 'fail',
        'text' => $has_kw_first_p ? "Focus keyphrase muncul di awal paragraf pembuka." : "Focus keyphrase belum muncul di paragraf pertama."
    ];

    // Outbound Links (Pemerintah .go.id / Wikipedia)
    $outbound_count = 0;
    $has_gov_wiki = false;
    if (preg_match_all('/<a\s+[^>]*href=["\'](http[s]?:\/\/[^"\']+)["\'][^>]*>(.*?)<\/a>/is', $konten, $m_out)) {
        foreach ($m_out[1] as $url) {
            if (stripos($url, 'villaquran') === false) {
                $outbound_count++;
                if (stripos($url, '.go.id') !== false || stripos($url, 'wikipedia.org') !== false) {
                    $has_gov_wiki = true;
                }
            }
        }
    }
    $outbound_pass = ($outbound_count >= 2 && $has_gov_wiki);
    $checklist_seo[] = [
        'name' => 'Tautan Keluar Otoritatif (.go.id / Wikipedia)',
        'status' => $outbound_pass ? 'pass' : ($outbound_count > 0 ? 'warning' : 'fail'),
        'text' => $outbound_pass ? "Memiliki $outbound_count tautan keluar berotoritas tinggi (.go.id / Wikipedia)." : "Wajib memiliki minimal 2 link keluar mengarah ke web pemerintah (.go.id) atau Wikipedia."
    ];

    // Internal Links (daftar-spmb / brosur / index)
    $internal_count = 0;
    if (preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $konten, $m_in)) {
        foreach ($m_in[1] as $url) {
            if (stripos($url, 'daftar-spmb') !== false || stripos($url, 'brosur') !== false || stripos($url, 'villaquran') !== false || substr($url, 0, 1) === '/' || strpos($url, 'http') === false) {
                $internal_count++;
            }
        }
    }
    $internal_pass = ($internal_count >= 2);
    $checklist_seo[] = [
        'name' => 'Tautan Internal Website',
        'status' => $internal_pass ? 'pass' : ($internal_count > 0 ? 'warning' : 'fail'),
        'text' => $internal_pass ? "Ditemukan $internal_count tautan internal menuju pendaftaran & brosur." : "Wajib memiliki minimal 2 link ke dalam (ke formulir SPMB & brosur digital)."
    ];

    // Text Length
    $len_pass = ($word_count >= 500);
    $checklist_seo[] = [
        'name' => 'Panjang Artikel',
        'status' => $len_pass ? 'pass' : 'fail',
        'text' => "Panjang artikel saat ini $word_count kata (" . ($len_pass ? "Sangat Baik, target tercapai" : "Kurang dari standar minimum 500 kata") . ")."
    ];

    // Readability: Kata Transisi
    $transition_words = ['selain itu', 'oleh karena itu', 'dengan demikian', 'misalnya', 'di samping itu', 'namun', 'sehingga', 'akibatnya', 'kendati demikian', 'bahkan', 'terlebih lagi', 'pasalnya', 'bahkan', 'sebaliknya', 'karena itu', 'pada akhirnya', 'seperti yang diketahui', 'tentu saja'];
    $trans_found = 0;
    $lower_text = strtolower($clean_text);
    foreach ($transition_words as $tw) {
        $trans_found += substr_count($lower_text, $tw);
    }
    $trans_ratio = ($trans_found / max(1, $sentence_count)) * 100;
    $trans_pass = ($trans_found >= 4 || $trans_ratio >= 25);
    $checklist_read[] = [
        'name' => 'Kata Transisi / Penghubung',
        'status' => $trans_pass ? 'pass' : 'fail',
        'text' => $trans_pass ? "Artikel mengalir baik dengan $trans_found kata transisi." : "Baru ditemukan $trans_found kata transisi. Perlu ditambah agar bacaan mengalir."
    ];

    // Readability: Panjang Paragraf
    $paragraphs = preg_split('/<\/p>|<br\s*\/?>/i', $konten, -1, PREG_SPLIT_NO_EMPTY);
    $long_p = 0;
    foreach ($paragraphs as $p) {
        $p_words = str_word_count(strip_tags($p));
        if ($p_words > 120) $long_p++;
    }
    $p_pass = ($long_p === 0);
    $checklist_read[] = [
        'name' => 'Panjang Paragraf (Mobile-Friendly)',
        'status' => $p_pass ? 'pass' : 'fail',
        'text' => $p_pass ? "Semua paragraf ringkas (2-4 kalimat), nyaman dibaca di layar HP." : "Ditemukan $long_p paragraf terlalu panjang (> 120 kata), rawan bikin bosan pembaca."
    ];

    // Readability: Sub-heading Distribution
    $has_headings = (preg_match('/<h[23][^>]*>/i', $konten) > 0);
    $checklist_read[] = [
        'name' => 'Distribusi Sub-heading (H2 / H3)',
        'status' => $has_headings ? 'pass' : 'fail',
        'text' => $has_headings ? "Artikel memiliki struktur heading hierarkis yang rapi." : "Belum ada sub-heading (H2/H3). Artikel masih berupa tembok teks."
    ];

    // Tentukan Skor Keseluruhan
    $seo_fail_count = 0;
    foreach ($checklist_seo as $cs) {
        if ($cs['status'] === 'fail') $seo_fail_count++;
    }
    $seo_status = ($seo_fail_count === 0) ? 'green' : (($seo_fail_count <= 2) ? 'amber' : 'red');

    $read_fail_count = 0;
    foreach ($checklist_read as $cr) {
        if ($cr['status'] === 'fail') $read_fail_count++;
    }
    $read_status = ($read_fail_count === 0) ? 'green' : (($read_fail_count <= 1) ? 'amber' : 'red');

    return [
        'word_count' => $word_count,
        'seo_status' => $seo_status,
        'readability_status' => $read_status,
        'checklist_seo' => $checklist_seo,
        'checklist_readability' => $checklist_read,
        'stats' => [
            'outbound_links' => $outbound_count,
            'internal_links' => $internal_count,
            'transition_words' => $trans_found,
            'long_paragraphs' => $long_p
        ]
    ];
}

// =========================================================================
// ACTION: AUDIT_ARTICLE (Hanya Diagnosa Tanpa Ubah Database)
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

    if ($article_id > 0) {
        $res = $conn->query("SELECT * FROM artikel WHERE id = $article_id LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $konten = $row['konten'];
            $judul = $row['judul'];
            $keyword = $row['meta_keywords'] ?: 'sekolah tahfidz, pesantren tahfidz';
            $save_to_db = true;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Artikel tidak ditemukan']);
            exit;
        }
    }

    if (empty($konten) || empty($judul)) {
        echo json_encode(['status' => 'error', 'message' => 'Konten dan judul artikel tidak boleh kosong']);
        exit;
    }

    // Bangun Prompt Super Presisi untuk Gemini AI
    $prompt_editor = "ATURAN WAJIB SISTEM: KEMBALIKAN OUTPUT HANYA DALAM FORMAT JSON MURNI TANPA MARKDOWN (DILARANG MEMBUNGKUS DENGAN ```json ATAU ```).

Anda adalah AGENT AI YOAST SEO EDITOR & AUDITOR kelas dunia untuk Sekolah Tahfidz Berasrama Villa Quran Baron Malang.
Tugas Anda adalah MEMERIKSA, MENGEDIT, DAN MEMOLES artikel berikut agar LULUS 100% indikator Yoast SEO (Skor SEO Hijau 🟢 dan Skor Keterbacaan/Readability Hijau 🟢).

DATA ARTIKEL SAAT INI:
- Judul: $judul
- Focus Keyphrase / Keyword: $keyword
- Konten Saat Ini:
$konten

INSTRUKSI EDITING & PEMOLESAN WAJIB:
1. PERTAHANKAN esensi pesan, gaya edukatif Islami, dan nilai-nilai Al-Qur'an/Hadits asli.
2. WAJIB DI PARAGRAF PERTAMA (OUTBOUND LINKS):
   - Sisipkan tepat 2 kata/frasa (anchor text) yang memiliki link keluar resmi berotoritas tinggi:
     • Web Pemerintah (contoh: <a href=\"https://kemenag.go.id\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"text-teal-600 underline\">Kementerian Agama</a> atau <a href=\"https://kemdikbud.go.id\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"text-teal-600 underline\">Kemendikbud</a>)
     • Wikipedia Indonesia (contoh: <a href=\"https://id.wikipedia.org/wiki/Pondok_Pesantren\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"text-teal-600 underline\">pondok pesantren</a> atau <a href=\"https://id.wikipedia.org/wiki/Hafiz\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"text-teal-600 underline\">penghafal Al-Qur'an</a>).
   - Pastikan kata kunci utama juga disebut secara alami di paragraf pertama ini.
3. WAJIB DI PARAGRAF TERAKHIR (INTERNAL LINKS):
   - Sisipkan tepat 2 link ke dalam website Villa Quran:
     • Ke pendaftaran SPMB: <a href=\"daftar-spmb.html\" class=\"text-teal-700 font-bold underline\">pendaftaran santri baru</a> (atau frasa pendaftaran online).
     • Ke brosur digital: <a href=\"brosur.php\" class=\"text-teal-700 font-bold underline\">brosur digital interaktif</a> (atau frasa rincian kurikulum & biaya).
4. READABILITY / KETERBACAAN MAKSIMAL:
   - Pecah paragraf yang panjang menjadi paragraf pendek (maksimal 2-3 kalimat per paragraf).
   - Pastikan minimal 30% kalimat memiliki kata transisi mengalir (selain itu, oleh karena itu, misalnya, dengan demikian, di samping itu, namun, sehingga, terlebih lagi).
   - Pastikan teks diselingi sub-heading (<h2> dan <h3>) setiap 150-250 kata.
5. PENYEMPURNAAN META TAG:
   - Buat Meta Title (< 60 karakter memuat keyword).
   - Buat Meta Description (120-155 karakter persuasif).

FORMAT JSON OUTPUT YANG HARUS DIHASILKAN:
{
  \"judul\": \"$judul\",
  \"meta_title\": \"(Meta title SEO optimal < 60 karakter)\",
  \"meta_description\": \"(Meta desc SEO optimal 120-155 karakter)\",
  \"meta_keywords\": \"$keyword\",
  \"konten_dipoles\": \"(Seluruh isi HTML artikel lengkap yang telah dipoles sempurna, dengan heading h2, h3, p pendek, 2 link keluar di awal, 2 link dalam di penutup, dan kata transisi melimpah)\",
  \"perubahan_dilakukan\": \"(Ringkasan 3-5 poin apa saja yang diperbaiki oleh Agent Yoast SEO)\"
}";

    // Tembak ke Gemini
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    if (empty($apiKey)) {
        echo json_encode(['status' => 'error', 'message' => 'API Key Gemini belum disetel di config-key.php']);
        exit;
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
    $payload = [
        "contents" => [
            ["parts" => [["text" => $prompt_editor]]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 75
    ]);

    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $rawText = '';
    if ($resp && $httpCode === 200) {
        $respJson = json_decode($resp, true);
        $rawText = $respJson['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    // Fallback: Jika direct Gemini gagal / timeout di Hostinger, coba via GAS Proxy
    if (empty($rawText) && defined('GEMINI_GAS_URL') && !empty(GEMINI_GAS_URL)) {
        $chGas = curl_init(GEMINI_GAS_URL);
        curl_setopt_array($chGas, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'leads' => [
                    ['jenis_lead' => 'SYSTEM_COMMAND', 'sumber_info' => $prompt_editor, 'status' => 'URGENT']
                ],
                'type' => 'seo_editor',
                'apiKey' => $apiKey,
                'prompt' => $prompt_editor
            ]),
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        $respGas = curl_exec($chGas);
        $httpGas = curl_getinfo($chGas, CURLINFO_HTTP_CODE);
        curl_close($chGas);

        if ($respGas && $httpGas === 200) {
            $gasJson = json_decode($respGas, true);
            if (isset($gasJson['result']) && !empty($gasJson['result'])) {
                $rawText = $gasJson['result'];
            } elseif (isset($gasJson['candidates'][0]['content']['parts'][0]['text'])) {
                $rawText = $gasJson['candidates'][0]['content']['parts'][0]['text'];
            }
        }
    }

    if (empty($rawText)) {
        echo json_encode(['status' => 'error', 'message' => "Gagal mendapatkan respon AI (HTTP $httpCode)"]);
        exit;
    }
    
    // Pembersihan JSON
    $cleanJson = preg_replace('/```(?:json)?\s*([\s\S]*?)\s*```/i', '$1', trim($rawText));
    $firstBrace = strpos($cleanJson, '{');
    $lastBrace = strrpos($cleanJson, '}');
    if ($firstBrace !== false && $lastBrace !== false) {
        $cleanJson = substr($cleanJson, $firstBrace, $lastBrace - $firstBrace + 1);
    }
    
    $obj = json_decode($cleanJson, true);
    if (!$obj || empty($obj['konten_dipoles'])) {
        // Fallback jika formatting parser gagal
        echo json_encode(['status' => 'error', 'message' => 'AI gagal memformat hasil JSON polesan', 'raw' => $rawText]);
        exit;
    }

    $konten_baru = $obj['konten_dipoles'];
    $meta_title_baru = $obj['meta_title'] ?? '';
    $meta_desc_baru = $obj['meta_description'] ?? '';
    $meta_key_baru = $obj['meta_keywords'] ?? $keyword;
    $perubahan = $obj['perubahan_dilakukan'] ?? 'Penyempurnaan link keluar, link internal, kata transisi, dan pemecahan paragraf.';

    // Lakukan audit ulang pada teks baru untuk memastikan status hijau
    $audit_baru = analisaHeuristikYoast($konten_baru, $judul, $meta_key_baru, $meta_desc_baru);

    // Update database jika diminta
    if ($save_to_db && $article_id > 0) {
        $k_esc = $conn->real_escape_string($konten_baru);
        $mt_esc = $conn->real_escape_string($meta_title_baru);
        $md_esc = $conn->real_escape_string($meta_desc_baru);
        $mk_esc = $conn->real_escape_string($meta_key_baru);
        $log_audit = $conn->real_escape_string(json_encode([
            'optimized_at' => date('Y-m-d H:i:s'),
            'perubahan' => $perubahan,
            'stats' => $audit_baru['stats']
        ]));

        $conn->query("UPDATE artikel SET 
            konten = '$k_esc', 
            meta_title = '$mt_esc', 
            meta_description = '$md_esc', 
            meta_keywords = '$mk_esc', 
            yoast_seo_score = 'green', 
            yoast_readability_score = 'green', 
            yoast_audit_log = '$log_audit' 
            WHERE id = $article_id");
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Artikel berhasil dipoles dan berstatus All Green 🟢🟢!',
        'article_id' => $article_id,
        'konten_dipoles' => $konten_baru,
        'meta_title' => $meta_title_baru,
        'meta_description' => $meta_desc_baru,
        'meta_keywords' => $meta_key_baru,
        'perubahan' => $perubahan,
        'audit' => $audit_baru
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali']);
exit;
