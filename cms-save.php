<?php
/**
 * RSX Travel — CMS Save Endpoint
 * Recebe JSON via POST e grava em cms-data.json
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$DATA_FILE = __DIR__ . '/cms-data.json';
$PASS_FILE = __DIR__ . '/cms-pass.txt';

// Garante que o arquivo existe e é gravável
if (!file_exists($DATA_FILE)) {
    @file_put_contents($DATA_FILE, '{}');
    @chmod($DATA_FILE, 0664);
}
if (!is_writable($DATA_FILE)) {
    @chmod($DATA_FILE, 0664);
}

// Senha padrão (muda pelo admin)
function getPass($file) {
    if (file_exists($file)) {
        $p = trim(file_get_contents($file));
        if ($p) return $p;
    }
    return 'rsx2024';
}

// GET — retorna os dados ou diagnóstico
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ?diag=1 mostra diagnóstico
    if (isset($_GET['diag'])) {
        echo json_encode([
            'php'       => phpversion(),
            'file'      => $DATA_FILE,
            'exists'    => file_exists($DATA_FILE),
            'writable'  => is_writable($DATA_FILE),
            'dir_write' => is_writable(__DIR__),
            'size'      => file_exists($DATA_FILE) ? filesize($DATA_FILE) : 0,
            'post_max'  => ini_get('post_max_size'),
        ], JSON_PRETTY_PRINT);
        exit;
    }
    if (file_exists($DATA_FILE)) {
        echo file_get_contents($DATA_FILE);
    } else {
        echo '{}';
    }
    exit;
}

// POST — salva os dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    if (!$body) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
        exit;
    }

    $pass = isset($body['_pass']) ? $body['_pass'] : '';
    if ($pass !== getPass($PASS_FILE)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit;
    }

    // Remove campo de senha antes de salvar
    unset($body['_pass']);

    // Salva nova senha se enviada
    if (!empty($body['_newpass'])) {
        file_put_contents($PASS_FILE, trim($body['_newpass']));
        unset($body['_newpass']);
    }

    $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (file_put_contents($DATA_FILE, $json) === false) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to write file. Check permissions.']);
        exit;
    }

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
