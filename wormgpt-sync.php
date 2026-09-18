<?php
/* ============================================================
   VEYZEN OPS — Global Config Sync (single-file backend)
   Upload file ini ke folder YANG SAMA dengan pandzz-ai.html
   di hosting kamu. Bukan aplikasi/website kedua — cuma
   1 file PHP pendamping. Tidak butuh database.
   ============================================================ */

header('Content-Type: application/json');

/* ── GANTI INI SEBELUM UPLOAD! ── */
define('SECRET_KEY', 'ganti-kunci-rahasia-kamu-disini');
/* Domain resmi kamu (kosongkan array = tidak dibatasi, TIDAK disarankan untuk produksi) */
define('ALLOWED_ORIGINS', array(
    'https://situskamu.com',
    'https://www.situskamu.com',
));

define('DATA_FILE', __DIR__ . '/veyzen-config.json');

function originAllowed() {
    if (empty(ALLOWED_ORIGINS)) return true;
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    foreach (ALLOWED_ORIGINS as $allowed) {
        $allowed = rtrim($allowed, '/');
        if ($origin === $allowed) return true;
        if ($referer && strpos($referer, $allowed) === 0) return true;
    }
    return false;
}

$originOk = originAllowed();
if ($originOk) {
    $reqOrigin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
    header('Access-Control-Allow-Origin: ' . $reqOrigin);
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if (!$originOk) {
    http_response_code(403);
    echo json_encode(array('status' => false, 'message' => 'Domain tidak diizinkan mengakses server ini.'));
    exit;
}

function readConfig() {
    if (!file_exists(DATA_FILE)) return array();
    $raw = @file_get_contents(DATA_FILE);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : array();
}

function writeConfig($data) {
    $fp = @fopen(DATA_FILE, 'c+');
    if (!$fp) return false;
    $ok = false;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
        flock($fp, LOCK_UN);
        $ok = true;
    }
    fclose($fp);
    return $ok;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(array('status' => true, 'result' => readConfig()));
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body) || !isset($body['key']) || !hash_equals(SECRET_KEY, (string)$body['key'])) {
        http_response_code(403);
        echo json_encode(array('status' => false, 'message' => 'Kunci rahasia salah atau tidak ada.'));
        exit;
    }

    $config = (isset($body['config']) && is_array($body['config'])) ? $body['config'] : array();

    if (writeConfig($config)) {
        echo json_encode(array('status' => true, 'message' => 'Config berhasil disimpan ke server.'));
    } else {
        http_response_code(500);
        echo json_encode(array('status' => false, 'message' => 'Gagal menulis file. Cek permission folder (harus writable, biasanya chmod 755/775).'));
    }
    exit;
}

http_response_code(405);
echo json_encode(array('status' => false, 'message' => 'Method tidak didukung.'));
