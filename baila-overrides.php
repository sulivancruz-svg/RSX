<?php
/**
 * RSX Travel — Baila Costão manual availability overrides
 * GET  → retorna { "periodoId": { "Nome da categoria": true|false, ... }, ... }
 *        true = marcado indisponível manualmente pelo admin
 * POST → { _pass, periodoId, categoria, disponivel: bool }
 *        Grava/atualiza o override para essa categoria dentro desse período.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$DATA_FILE = __DIR__ . '/baila-overrides.json';
$PASS_FILE = __DIR__ . '/cms-pass.txt';

if (!file_exists($DATA_FILE)) {
    @file_put_contents($DATA_FILE, '{}');
    @chmod($DATA_FILE, 0664);
}
if (!is_writable($DATA_FILE)) { @chmod($DATA_FILE, 0664); }

function getPass($f) {
    if (file_exists($f)) { $p = trim(file_get_contents($f)); if ($p) return $p; }
    return 'rsx2024';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo file_exists($DATA_FILE) ? file_get_contents($DATA_FILE) : '{}';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit; }

    if (($body['_pass'] ?? '') !== getPass($PASS_FILE)) {
        http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit;
    }

    $periodoId  = trim($body['periodoId'] ?? '');
    $categoria  = trim($body['categoria'] ?? '');
    $disponivel = $body['disponivel'] ?? null;

    if ($periodoId === '' || $categoria === '' || !is_bool($disponivel)) {
        http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Missing fields']); exit;
    }

    $current = json_decode(file_get_contents($DATA_FILE), true) ?: [];
    if (!isset($current[$periodoId])) $current[$periodoId] = [];

    if ($disponivel) {
        // "disponível" is the default; clear the override instead of storing it
        unset($current[$periodoId][$categoria]);
        if (empty($current[$periodoId])) unset($current[$periodoId]);
    } else {
        $current[$periodoId][$categoria] = true; // true = marcado indisponível
    }

    $json = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (file_put_contents($DATA_FILE, $json) === false) {
        http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Write failed']); exit;
    }
    echo json_encode(['ok'=>true]);
    exit;
}

http_response_code(405);
echo json_encode(['ok'=>false,'error'=>'Method not allowed']);
