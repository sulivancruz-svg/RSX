<?php
/**
 * RSX Travel — Baila Costão pricing, live from Google Sheets
 * GET → { periodos: [ { id, label, periodo, noites, dias, crianca, cats:[{nome,detalhe,preco}] } ], fetched_at }
 * Caches the parsed result for a few minutes to avoid hitting Google on every admin load.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$SHEET_ID   = '1Cdrg13ruqhitv9z9o31_KfTejpR1sU0B';
$CACHE_FILE = __DIR__ . '/baila-sheet-cache.json';
$CACHE_TTL  = 300; // 5 minutes

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function readCache($file) {
    if (file_exists($file)) {
        $j = json_decode(file_get_contents($file), true);
        if ($j && !empty($j['periodos'])) return $j;
    }
    return null;
}

if (file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) < $CACHE_TTL) {
    $cached = readCache($CACHE_FILE);
    if ($cached) respond($cached);
}

$url = "https://docs.google.com/spreadsheets/d/{$SHEET_ID}/export?format=xlsx";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$xlsxData = curl_exec($ch);
$curlErr  = curl_error($ch);
curl_close($ch);

if (!$xlsxData) {
    $cached = readCache($CACHE_FILE);
    if ($cached) respond($cached);
    respond(['error' => 'Falha ao buscar a planilha: ' . $curlErr], 502);
}

if (!class_exists('ZipArchive')) {
    $cached = readCache($CACHE_FILE);
    if ($cached) respond($cached);
    respond(['error' => 'Extensão ZipArchive indisponível no servidor'], 500);
}

$tmpFile = tempnam(sys_get_temp_dir(), 'baila') . '.xlsx';
file_put_contents($tmpFile, $xlsxData);

$zip = new ZipArchive();
if ($zip->open($tmpFile) !== true) {
    @unlink($tmpFile);
    $cached = readCache($CACHE_FILE);
    if ($cached) respond($cached);
    respond(['error' => 'Falha ao abrir o arquivo xlsx'], 500);
}

function colToIndex($col) {
    $col = preg_replace('/[0-9]/', '', $col);
    $result = 0;
    for ($i = 0; $i < strlen($col); $i++) {
        $result = $result * 26 + (ord($col[$i]) - ord('A') + 1);
    }
    return $result;
}

// ── Shared strings ──
$sharedStrings = [];
$sstXml = $zip->getFromName('xl/sharedStrings.xml');
if ($sstXml) {
    $sst = simplexml_load_string($sstXml);
    $sst->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    foreach ($sst->xpath('//a:si') as $si) {
        $text = '';
        foreach ($si->xpath('.//a:t') as $t) { $text .= (string)$t; }
        $sharedStrings[] = $text;
    }
}

// ── Sheet order/names ──
$wbXml = $zip->getFromName('xl/workbook.xml');
$wb = simplexml_load_string($wbXml);
$wb->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
$sheets = [];
$idx = 1;
foreach ($wb->xpath('//a:sheets/a:sheet') as $sheet) {
    $sheets[] = ['name' => (string)$sheet['name'], 'index' => $idx];
    $idx++;
}

$weekdayMap = [
    'Dom' => 'domingo', 'Seg' => 'segunda', 'Ter' => 'terça', 'Qua' => 'quarta',
    'Qui' => 'quinta', 'Sex' => 'sexta', 'Sab' => 'sábado', 'Sáb' => 'sábado',
];

$periodos = [];

foreach ($sheets as $sheetInfo) {
    $sheetXml = $zip->getFromName('xl/worksheets/sheet' . $sheetInfo['index'] . '.xml');
    if (!$sheetXml) continue;
    $sheetDoc = simplexml_load_string($sheetXml);
    $sheetDoc->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    $rows = [];
    foreach ($sheetDoc->xpath('//a:row') as $row) {
        $rowNum = (int)$row['r'];
        $cells = [];
        foreach ($row->c as $c) {
            $ref = (string)$c['r'];
            $col = preg_replace('/[0-9]/', '', $ref);
            $colIdx = colToIndex($col);
            $type = (string)$c['t'];
            $value = null;
            if ($type === 's' && isset($c->v)) {
                $sIdx = (int)$c->v;
                $value = $sharedStrings[$sIdx] ?? '';
            } elseif (isset($c->v)) {
                $value = (string)$c->v;
            }
            if ($value !== null) $cells[$colIdx] = $value;
        }
        if (!empty($cells)) $rows[$rowNum] = $cells;
    }

    $title     = trim($rows[1][1] ?? '');
    $dateRange = trim($rows[2][1] ?? '');

    preg_match('/(\d+)\s*Noites?/iu', $sheetInfo['name'], $nm);
    $noites = isset($nm[1]) ? (int)$nm[1] : 0;

    $dias = '';
    if (preg_match('/\(([A-Za-zÀ-ú]+)-([A-Za-zÀ-ú]+)\)/u', $sheetInfo['name'], $wm)) {
        $d1 = $weekdayMap[$wm[1]] ?? $wm[1];
        $d2 = $weekdayMap[$wm[2]] ?? $wm[2];
        $dias = 'De ' . $d1 . ' a ' . $d2;
    } else {
        $dias = 'Pacote completo';
    }

    $idSuffix = '';
    if (preg_match('/\(([A-Za-zÀ-ú]+)-([A-Za-zÀ-ú]+)\)/u', $sheetInfo['name'], $wm2)) {
        $idSuffix = strtolower(substr($wm2[1], 0, 1) . substr($wm2[2], 0, 1));
    }
    $id = 'p' . $noites . $idSuffix;

    $cats = [];
    $crianca = 0;
    $rowNums = array_keys($rows);
    sort($rowNums);
    foreach ($rowNums as $rn) {
        if ($rn < 5) continue; // skip title/date/blank/header rows
        $rawName  = trim($rows[$rn][1] ?? '');
        $rawPrice = trim($rows[$rn][2] ?? '');
        if ($rawName === '' || $rawPrice === '') continue;

        $priceNum = (float) str_replace(['R$', '.', ' ', ','], ['', '', '', '.'], $rawPrice);
        // handles "R$ 4.320" -> 4320 and any stray comma decimal

        if (preg_match('/crian[çc]a/iu', $rawName)) {
            $crianca = $priceNum;
            continue;
        }

        $cleanName = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $rawName);
        $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));

        $building = '';
        $detalhe  = '';
        if (preg_match('/^Apto/iu', $cleanName)) {
            $building = 'Vilas Portuguesas'; $detalhe = 'quarto, banheiro, sacada';
        } elseif (preg_match('/^Superior/iu', $cleanName)) {
            $building = 'Vilas Portuguesas'; $detalhe = 'quarto e banheiro';
        } elseif (preg_match('/^Su[ií]te/iu', $cleanName)) {
            $building = 'Ala Internacional'; $detalhe = 'suite prime';
        }
        $fullDetalhe = $building ? ($building . ($detalhe ? ' · ' . $detalhe : '')) : $detalhe;

        $displayName = $cleanName;
        if (!preg_match('/Individual|Single/iu', $displayName) && stripos($displayName, 'por pessoa') === false) {
            $displayName .= ' — por pessoa';
        }

        $cats[] = ['nome' => $displayName, 'detalhe' => $fullDetalhe, 'preco' => $priceNum];
    }

    if (empty($cats)) continue; // skip sheets that aren't pricing tables

    $periodos[] = [
        'id'      => $id,
        'label'   => $sheetInfo['name'],
        'periodo' => $dateRange,
        'noites'  => $noites,
        'dias'    => $dias,
        'crianca' => $crianca,
        'cats'    => $cats,
    ];
}

$zip->close();
@unlink($tmpFile);

if (empty($periodos)) {
    $cached = readCache($CACHE_FILE);
    if ($cached) respond($cached);
    respond(['error' => 'Nenhum período encontrado na planilha'], 500);
}

$result = ['periodos' => $periodos, 'fetched_at' => date('c')];
@file_put_contents($CACHE_FILE, json_encode($result, JSON_UNESCAPED_UNICODE));
respond($result);
