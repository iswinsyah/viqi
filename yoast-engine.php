<?php
/**
 * CORE ENGINE YOAST SEO AI & HEURISTIC AUDITOR
 * Villa Quran Baron Malang - SADIGS 4.0
 * 
 * Modul terpadu untuk analisa On-Page SEO, Keterbacaan, dan Pemolesan Otomatis.
 */

if (!defined('GEMINI_API_KEY') && file_exists(__DIR__ . '/config-key.php')) {
    require_once __DIR__ . '/config-key.php';
}

/**
 * Helper Ekstraksi & Pembersihan JSON Anti Eror
 */
if (!function_exists('bersihkanDanDecodeJson')) {
    function bersihkanDanDecodeJson($raw) {
        if (empty($raw)) return null;
        $str = trim($raw);
        
        // 1. Bersihkan blok kode markdown jika ada
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $str, $m)) {
            $str = trim($m[1]);
        }
        
        // 2. Ambil dari kurung kurawal pertama sampai kurung kurawal terakhir
        $firstBrace = strpos($str, '{');
        $lastBrace = strrpos($str, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $str = substr($str, $firstBrace, $lastBrace - $firstBrace + 1);
        }
        
        // 3. Coba decode langsung
        $res = json_decode($str, true);
        if ($res !== null) return $res;
        
        // 4. Perbaiki unescaped newlines/tabs di dalam string JSON
        $strFixed = preg_replace_callback('/"((?:[^"\\\\]|\\\\.)*)"/s', function($match) {
            return '"' . str_replace(["\r\n", "\r", "\n", "\t"], ["\\n", "\\n", "\\n", " "], $match[1]) . '"';
        }, $str);
        $res = json_decode($strFixed, true);
        if ($res !== null) return $res;

        // 5. Fallback manual regex extractor jika json_decode masih gagal karena unescaped quotes di HTML
        $extracted = [];
        if (preg_match('/"judul"\s*:\s*"((?:[^"\\\\]|\\\\.)*?)"/s', $str, $mj)) {
            $extracted['judul'] = stripcslashes($mj[1]);
        }
        if (preg_match('/"konten(?:_dipoles)?"\s*:\s*"((?:[^"\\\\]|\\\\.)*?)"/s', $str, $mk)) {
            $extracted['konten_dipoles'] = stripcslashes($mk[1]);
        } elseif (preg_match('/"konten(?:_dipoles)?"\s*:\s*"(.*)/s', $str, $mk2)) {
            $rawK = rtrim($mk2[1], " \t\n\r\"}");
            $extracted['konten_dipoles'] = stripcslashes($rawK);
        }
        if (preg_match('/"meta_title"\s*:\s*"((?:[^"\\\\]|\\\\.)*?)"/s', $str, $mmt)) {
            $extracted['meta_title'] = stripcslashes($mmt[1]);
        }
        if (preg_match('/"meta_description"\s*:\s*"((?:[^"\\\\]|\\\\.)*?)"/s', $str, $mmd)) {
            $extracted['meta_description'] = stripcslashes($mmd[1]);
        }
        if (preg_match('/"meta_keywords"\s*:\s*"((?:[^"\\\\]|\\\\.)*?)"/s', $str, $mmk)) {
            $extracted['meta_keywords'] = stripcslashes($mmk[1]);
        }
        if (preg_match('/"perubahan(?:_dilakukan)?"\s*:\s*"((?:[^"\\\\]|\\\\.)*?)"/s', $str, $mp)) {
            $extracted['perubahan_dilakukan'] = stripcslashes($mp[1]);
        }
        
        if (!empty($extracted['konten_dipoles']) || !empty($extracted['konten'])) {
            return $extracted;
        }
        
        return null;
    }
}

/**
 * Audit Cepat Heuristik On-Page SEO & Keterbacaan
 */
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
    $has_spmb_brosur = false;
    if (preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $konten, $m_in)) {
        foreach ($m_in[1] as $url) {
            if (stripos($url, 'daftar-spmb') !== false || stripos($url, 'brosur.php') !== false || stripos($url, 'villaquran') !== false) {
                $internal_count++;
                if (stripos($url, 'daftar-spmb') !== false || stripos($url, 'brosur.php') !== false) {
                    $has_spmb_brosur = true;
                }
            }
        }
    }
    $internal_pass = ($internal_count >= 2 && $has_spmb_brosur);
    $checklist_seo[] = [
        'name' => 'Tautan Internal Website (SPMB & Brosur)',
        'status' => $internal_pass ? 'pass' : ($internal_count > 0 ? 'warning' : 'fail'),
        'text' => $internal_pass ? "Memiliki $internal_count tautan internal relevan (SPMB / Brosur Digital)." : "Wajib memiliki minimal 2 link internal (pendaftaran santri baru & brosur digital)."
    ];

    // Panjang Teks Konten (Minimal 450 Kata)
    $checklist_seo[] = [
        'name' => 'Panjang Teks Artikel',
        'status' => $word_count >= 450 ? 'pass' : ($word_count >= 300 ? 'warning' : 'fail'),
        'text' => $word_count >= 450 ? "Panjang konten memadai ($word_count kata)." : "Artikel disarankan minimal 450-800 kata (saat ini: $word_count kata)."
    ];

    // Meta Description
    $meta_len = strlen(trim($meta_desc));
    $meta_pass = ($meta_len >= 110 && $meta_len <= 165);
    $checklist_seo[] = [
        'name' => 'Panjang Deskripsi Meta',
        'status' => $meta_pass ? 'pass' : ($meta_len > 0 ? 'warning' : 'fail'),
        'text' => $meta_pass ? "Panjang meta description optimal ($meta_len karakter)." : "Panjang meta description disarankan antara 120-155 karakter (saat ini: $meta_len karakter)."
    ];

    // =========================================================================
    // 2. AUDIT KETERBACAAN (READABILITY YOAST)
    // =========================================================================
    
    // Kata Transisi Bahasa Indonesia
    $transition_words = [
        'selain itu', 'oleh karena itu', 'misalnya', 'dengan demikian', 'di samping itu', 
        'namun', 'sehingga', 'terlebih lagi', 'bahkan', 'akibatnya', 'selanjutnya', 
        'sebaliknya', 'pada dasarnya', 'dalam hal ini', 'sebagai contoh', 'tentu saja', 
        'oleh sebab itu', 'meskipun demikian', 'kendati demikian', 'seperti', 'terutama'
    ];
    $trans_count = 0;
    foreach ($sentences as $s) {
        $s_clean = strtolower(trim($s));
        foreach ($transition_words as $tw) {
            if (stripos($s_clean, $tw) !== false) {
                $trans_count++;
                break;
            }
        }
    }
    $trans_ratio = ($trans_count / $sentence_count) * 100;
    $trans_pass = ($trans_ratio >= 25);
    $checklist_read[] = [
        'name' => 'Kata Transisi (Transition Words)',
        'status' => $trans_pass ? 'pass' : ($trans_ratio >= 15 ? 'warning' : 'fail'),
        'text' => $trans_pass ? "Proporsi kata transisi sangat baik (" . round($trans_ratio) . "% dari total kalimat)." : "Disarankan minimal 25-30% kalimat memiliki kata transisi mengalir (saat ini: " . round($trans_ratio) . "%)."
    ];

    // Distribusi Sub-Heading (H2 & H3)
    preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/is', $konten, $m_headings);
    $heading_count = count($m_headings[0]);
    $heading_pass = ($heading_count >= 2);
    $checklist_read[] = [
        'name' => 'Distribusi Sub-heading (H2 & H3)',
        'status' => $heading_pass ? 'pass' : 'fail',
        'text' => $heading_pass ? "Struktur heading rapi, ditemukan $heading_count sub-heading." : "Wajib memecah teks dengan sub-heading <h2> dan <h3> setiap 150-250 kata."
    ];

    // Panjang Paragraf
    preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $konten, $m_all_p);
    $long_p_count = 0;
    foreach ($m_all_p[1] as $p_text) {
        $p_words = str_word_count(strip_tags($p_text));
        if ($p_words > 90) {
            $long_p_count++;
        }
    }
    $p_pass = ($long_p_count === 0);
    $checklist_read[] = [
        'name' => 'Panjang Paragraf',
        'status' => $p_pass ? 'pass' : 'warning',
        'text' => $p_pass ? "Paragraf ringkas dan nyaman dibaca di layar HP/smartphone." : "Terdapat $long_p_count paragraf terlalu panjang (> 90 kata), disarankan dipecah menjadi 2-3 kalimat."
    ];

    // Hitung Skor Akhir
    $seo_passes = count(array_filter($checklist_seo, fn($x) => $x['status'] === 'pass'));
    $seo_status = ($seo_passes >= 4 && $outbound_pass && $internal_pass) ? 'green' : ($seo_passes >= 2 ? 'amber' : 'red');

    $read_passes = count(array_filter($checklist_read, fn($x) => $x['status'] === 'pass'));
    $read_status = ($read_passes >= 2 && $heading_pass) ? 'green' : 'amber';

    return [
        'seo_status' => $seo_status,
        'readability_status' => $read_status,
        'checklist_seo' => $checklist_seo,
        'checklist_readability' => $checklist_read,
        'stats' => [
            'word_count' => $word_count,
            'sentence_count' => $sentence_count,
            'transition_ratio' => round($trans_ratio, 1),
            'heading_count' => $heading_count,
            'outbound_links' => $outbound_count,
            'internal_links' => $internal_count
        ]
    ];
}

/**
 * Eksekusi Pemolesan Artikel Menjadi Double Green Menggunakan Hybrid Multi-Brain
 */
function eksekusiPolesArtikelYoast($conn, $article_id, $judul, $keyword, $konten, $save_to_db = true) {
    if ($article_id > 0 && (empty($konten) || empty($judul))) {
        $res = $conn->query("SELECT * FROM artikel WHERE id = $article_id LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $konten = $row['konten'];
            $judul = $row['judul'];
            $keyword = $row['meta_keywords'] ?: 'sekolah tahfidz, pesantren tahfidz';
            $save_to_db = true;
        } else {
            return ['status' => 'error', 'message' => 'Artikel tidak ditemukan'];
        }
    }

    if (empty($konten) || empty($judul)) {
        return ['status' => 'error', 'message' => 'Konten dan judul artikel tidak boleh kosong'];
    }

    // Bangun Prompt Super Presisi
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

    $rawText = '';
    $usedBrain = 'Gemini';

    // =====================================================================
    // 0. PRIORITAS UTAMA: JIKA DIJALANKAN DARI CRON (Gunakan mikirKeGemini)
    // =====================================================================
    if (function_exists('mikirKeGemini')) {
        $resAI = mikirKeGemini([
            'prompt' => $prompt_editor,
            'leads' => [
                ['jenis_lead' => 'SYSTEM_COMMAND', 'sumber_info' => $prompt_editor, 'status' => 'URGENT']
            ],
            'type' => 'seo'
        ]);
        if (isset($resAI['status']) && $resAI['status'] === 'success' && !empty($resAI['result'])) {
            $rawText = $resAI['result'];
            $usedBrain = 'mikirKeGemini (Autopilot)';
        }
    }

    // =====================================================================
    // 1. TIER 1: COBA AI LOKAL (Ollama di PC Server) JIKA AKTIF
    // =====================================================================
    $localAiUrl = defined('LOCAL_AI_URL') ? LOCAL_AI_URL : '';
    $localAiModel = defined('LOCAL_AI_MODEL') ? LOCAL_AI_MODEL : 'qwen2.5-coder:7b';
    
    if (empty($rawText) && !empty($localAiUrl)) {
        $chLocal = curl_init(rtrim($localAiUrl, '/') . '/api/generate');
        curl_setopt_array($chLocal, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                "model" => $localAiModel,
                "prompt" => $prompt_editor,
                "stream" => false,
                "format" => "json"
            ]),
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        $localResp = curl_exec($chLocal);
        $localHttp = curl_getinfo($chLocal, CURLINFO_HTTP_CODE);
        curl_close($chLocal);

        if ($localResp && $localHttp === 200) {
            $localData = json_decode($localResp, true);
            if (!empty($localData['response'])) {
                $rawText = trim($localData['response']);
                $usedBrain = 'AI Lokal (' . $localAiModel . ')';
            }
        }
    }

    // =====================================================================
    // 2. TIER 2: GEMINI API (Cadangan jika PC Server Lokal Sedang Mati)
    // =====================================================================
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    if (empty($apiKey) && file_exists(__DIR__ . '/config-key.php')) {
        require_once __DIR__ . '/config-key.php';
        $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    }

    if (empty($rawText) && !empty($apiKey)) {
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
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp && $httpCode === 200) {
            $respJson = json_decode($resp, true);
            $rawText = $respJson['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (!empty($rawText)) {
                $usedBrain = 'Google Gemini 2.5 Flash';
            }
        }
    }

    // =====================================================================
    // 3. TIER 3: GAS PROXY TUNNEL SEBAGAI CADANGAN DARURAT
    // =====================================================================
    if (empty($rawText) && defined('GEMINI_GAS_URL') && !empty(GEMINI_GAS_URL)) {
        $chGas = curl_init(GEMINI_GAS_URL);
        curl_setopt_array($chGas, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'leads' => [
                    ['jenis_lead' => 'SYSTEM_COMMAND', 'sumber_info' => $prompt_editor, 'status' => 'URGENT']
                ],
                'type' => 'seo',
                'apiKey' => $apiKey,
                'prompt' => $prompt_editor
            ]),
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        $respGas = curl_exec($chGas);
        $httpGas = curl_getinfo($chGas, CURLINFO_HTTP_CODE);
        curl_close($chGas);

        if ($respGas && $httpGas === 200) {
            $gasJson = json_decode($respGas, true);
            if (isset($gasJson['result']) && !empty($gasJson['result'])) {
                $rawText = $gasJson['result'];
                $usedBrain = 'Google Apps Script Proxy';
            } elseif (isset($gasJson['candidates'][0]['content']['parts'][0]['text'])) {
                $rawText = $gasJson['candidates'][0]['content']['parts'][0]['text'];
                $usedBrain = 'Google Apps Script Proxy';
            }
        }
    }

    if (empty($rawText)) {
        return ['status' => 'error', 'message' => "Gagal mendapatkan respon dari AI Lokal maupun Gemini API"];
    }

    // Pembersihan & Decode JSON yang sangat tangguh
    $obj = bersihkanDanDecodeJson($rawText);

    $konten_baru = $obj['konten_dipoles'] ?? $obj['konten'] ?? $obj['content'] ?? '';
    
    // Fallback Darurat Cerdas: Jika AI mengembalikan teks HTML langsung tanpa wrapper JSON
    if (empty($konten_baru)) {
        if (stripos($rawText, '<p>') !== false || stripos($rawText, '<h2>') !== false || stripos($rawText, '<h3>') !== false) {
            $cleanHtml = preg_replace('/```(?:html)?\s*([\s\S]*?)\s*```/i', '$1', trim($rawText));
            $cleanHtml = preg_replace('/^\{\s*"konten[^:]*":\s*"/i', '', $cleanHtml);
            $cleanHtml = preg_replace('/",?\s*"perubahan[^}]*\}\s*$/i', '', $cleanHtml);
            $konten_baru = trim($cleanHtml);
        }
    }

    if (empty($konten_baru)) {
        return ['status' => 'error', 'message' => 'AI gagal memformat hasil JSON polesan', 'raw' => substr($rawText, 0, 300)];
    }

    $meta_title_baru = $obj['meta_title'] ?? $obj['judul'] ?? $judul;
    $meta_desc_baru = $obj['meta_description'] ?? '';
    $meta_key_baru = $obj['meta_keywords'] ?? $keyword;
    $perubahan = $obj['perubahan_dilakukan'] ?? 'Penyempurnaan link keluar, link internal, kata transisi, dan pemecahan paragraf.';

    // Audit ulang
    $audit_baru = analisaHeuristikYoast($konten_baru, $judul, $meta_key_baru, $meta_desc_baru);

    // Simpan ke DB jika diminta
    if ($save_to_db && $article_id > 0) {
        if (function_exists('pastikanKoneksiDb')) {
            pastikanKoneksiDb();
        }
        global $conn;
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

    return [
        'status' => 'success',
        'message' => 'Artikel berhasil dipoles dan berstatus All Green 🟢🟢!',
        'used_brain' => $usedBrain,
        'article_id' => $article_id,
        'konten_dipoles' => $konten_baru,
        'meta_title' => $meta_title_baru,
        'meta_description' => $meta_desc_baru,
        'meta_keywords' => $meta_key_baru,
        'perubahan' => $perubahan,
        'audit' => $audit_baru
    ];
}
