<?php
/**
 * RSX Travel — Proposals CRUD Endpoint
 * GET  → retorna proposals.json completo
 * POST → { _pass, action:"save"|"delete", proposal?:{...}, id?:"..." }
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$DATA_FILE = __DIR__ . '/proposals.json';
$PASS_FILE = __DIR__ . '/cms-pass.txt';

if (!file_exists($DATA_FILE)) {
    @file_put_contents($DATA_FILE, '{"proposals":{}}');
    @chmod($DATA_FILE, 0664);
}
if (!is_writable($DATA_FILE)) { @chmod($DATA_FILE, 0664); }

function getPass($f) {
    if (file_exists($f)) { $p = trim(file_get_contents($f)); if ($p) return $p; }
    return 'rsx2024';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo file_exists($DATA_FILE) ? file_get_contents($DATA_FILE) : '{"proposals":{}}';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit; }

    if (($body['_pass'] ?? '') !== getPass($PASS_FILE)) {
        http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit;
    }

    $current = json_decode(file_get_contents($DATA_FILE), true) ?: ['proposals'=>[]];

    $action = $body['action'] ?? '';
    if ($action === 'save') {
        $p = $body['proposal'] ?? null;
        if (!is_array($p)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Missing proposal']); exit; }
        if (empty($p['id'])) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }
        $current['proposals'][$p['id']] = $p;
    } elseif ($action === 'delete') {
        $id = $body['id'] ?? '';
        if (!$id || !isset($current['proposals'][$id])) {
            http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Not found']); exit;
        }
        unset($current['proposals'][$id]);
    } else {
        http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Unknown action']); exit;
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
