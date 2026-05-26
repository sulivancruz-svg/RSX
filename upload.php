<?php
/**
 * RSX Travel — Media Upload Endpoint
 * Recebe multipart/form-data, salva em /midia/, retorna URL.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-RSX-Pass');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$PASS_FILE  = __DIR__ . '/cms-pass.txt';
$MEDIA_DIR  = __DIR__ . '/midia';
$MEDIA_URL  = '/midia'; // caminho público

function getPass($file) {
    if (file_exists($file)) {
        $p = trim(file_get_contents($file));
        if ($p) return $p;
    }
    return 'rsx2024';
}

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

// GET ?diag=1 — diagnóstico
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['diag'])) {
        echo json_encode([
            'php'          => phpversion(),
            'upload_max'   => ini_get('upload_max_filesize'),
            'post_max'     => ini_get('post_max_size'),
            'midia_exists' => is_dir($MEDIA_DIR),
            'midia_write'  => is_dir($MEDIA_DIR) && is_writable($MEDIA_DIR),
            'dir_write'    => is_writable(__DIR__),
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['ok' => true, 'status' => 'upload endpoint ready']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Method not allowed', 405);
}

// Autenticação via header ou campo de formulário
$pass = isset($_SERVER['HTTP_X_RSX_PASS'])
    ? $_SERVER['HTTP_X_RSX_PASS']
    : (isset($_POST['_pass']) ? $_POST['_pass'] : '');

if ($pass !== getPass($PASS_FILE)) {
    fail('Unauthorized', 401);
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $err = isset($_FILES['file']) ? $_FILES['file']['error'] : 'no file';
    fail('Upload error: ' . $err);
}

// Tipos permitidos
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
$mime = mime_content_type($_FILES['file']['tmp_name']);
if (!in_array($mime, $allowed)) {
    fail('Tipo de arquivo não permitido: ' . $mime);
}

// Cria pasta midia/ se não existir
if (!is_dir($MEDIA_DIR)) {
    if (!@mkdir($MEDIA_DIR, 0755, true)) {
        fail('Não foi possível criar a pasta midia/. Verifique permissões.', 500);
    }
}

// Nome de arquivo seguro: slug + timestamp + extensão original
$orig = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
$orig = preg_replace('/[^a-z0-9_-]/i', '-', $orig);
$orig = strtolower(substr($orig, 0, 40));
$ext  = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!$ext) {
    $extMap = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif','image/svg+xml'=>'svg'];
    $ext = $extMap[$mime] ?? 'jpg';
}
$fname = $orig . '-' . time() . '.' . $ext;
$dest  = $MEDIA_DIR . '/' . $fname;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
    fail('Falha ao mover o arquivo. Verifique permissões em midia/.', 500);
}

$url = $MEDIA_URL . '/' . $fname;
echo json_encode(['ok' => true, 'url' => $url, 'filename' => $fname]);
