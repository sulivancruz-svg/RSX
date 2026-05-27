<?php
/**
 * RSX Travel — Media Upload Endpoint
 * Recebe multipart/form-data, salva em /midia/ (ou /Videos/ para MP4 de resort), retorna URL.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-RSX-Pass');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$PASS_FILE = __DIR__ . '/cms-pass.txt';

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
        $midiaDir  = __DIR__ . '/midia';
        $videosDir = __DIR__ . '/Videos';
        echo json_encode([
            'php'            => phpversion(),
            'upload_max'     => ini_get('upload_max_filesize'),
            'post_max'       => ini_get('post_max_size'),
            'memory_limit'   => ini_get('memory_limit'),
            'midia_exists'   => is_dir($midiaDir),
            'midia_write'    => is_dir($midiaDir) && is_writable($midiaDir),
            'videos_exists'  => is_dir($videosDir),
            'videos_write'   => is_dir($videosDir) && is_writable($videosDir),
            'dir_write'      => is_writable(__DIR__),
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
    $errCodes = [
        UPLOAD_ERR_INI_SIZE   => 'Arquivo maior que upload_max_filesize (' . ini_get('upload_max_filesize') . ')',
        UPLOAD_ERR_FORM_SIZE  => 'Arquivo maior que MAX_FILE_SIZE do formulário',
        UPLOAD_ERR_PARTIAL    => 'Upload incompleto',
        UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo enviado',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever no disco',
        UPLOAD_ERR_EXTENSION  => 'Upload bloqueado por extensão PHP',
    ];
    $errCode = isset($_FILES['file']) ? $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
    $errMsg  = isset($errCodes[$errCode]) ? $errCodes[$errCode] : 'Erro desconhecido: ' . $errCode;
    fail('Upload error: ' . $errMsg);
}

// Tipos permitidos
$allowed = [
    'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml',
    'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime',
];
$mime = mime_content_type($_FILES['file']['tmp_name']);
if (!in_array($mime, $allowed)) {
    fail('Tipo de arquivo não permitido: ' . $mime);
}

$isVideo = strpos($mime, 'video/') === 0;

// Destino: Videos/ para MP4 com nome fixo, midia/ para o resto
$fixedName = isset($_POST['filename']) ? preg_replace('/[^a-z0-9_\-\.]/i', '', $_POST['filename']) : '';

if ($isVideo && $fixedName) {
    // Salva em Videos/ com o nome exato passado pelo admin
    $destDir  = __DIR__ . '/Videos';
    $destUrl  = 'Videos/' . $fixedName;
    $fname    = $fixedName;
} else {
    // Salva em midia/ com nome único
    $destDir  = __DIR__ . '/midia';
    $destUrl  = '/midia';
    $orig     = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
    $orig     = preg_replace('/[^a-z0-9_-]/i', '-', $orig);
    $orig     = strtolower(substr($orig, 0, 40));
    $ext      = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if (!$ext) {
        $extMap = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif','image/svg+xml'=>'svg','video/mp4'=>'mp4','video/webm'=>'webm'];
        $ext = $extMap[$mime] ?? 'bin';
    }
    $fname    = $orig . '-' . time() . '.' . $ext;
    $destUrl  = '/midia/' . $fname;
}

// Cria pasta se não existir
if (!is_dir($destDir)) {
    if (!@mkdir($destDir, 0755, true)) {
        fail('Não foi possível criar a pasta ' . basename($destDir) . '/. Verifique permissões.', 500);
    }
}

$dest = $destDir . '/' . $fname;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
    fail('Falha ao mover o arquivo. Verifique permissões em ' . basename($destDir) . '/.', 500);
}

echo json_encode(['ok' => true, 'url' => $destUrl, 'filename' => $fname]);
