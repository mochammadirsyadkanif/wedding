<?php
header('Content-Type: application/json');
$file = __DIR__ . '/data/ucapan.json';
if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);
if (!file_exists($file)) file_put_contents($file, '[]');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    $nama   = htmlspecialchars(trim($d['nama']   ?? ''));
    $ucapan = htmlspecialchars(trim($d['ucapan'] ?? ''));
    $hadir  = in_array($d['hadir'] ?? '', ['hadir','tidak']) ? $d['hadir'] : '';
    if (!$nama || !$ucapan) { echo json_encode(['ok'=>false,'msg'=>'Nama & ucapan wajib diisi']); exit; }
    $list = json_decode(file_get_contents($file), true) ?: [];
    $list[] = [
        'id'     => uniqid(),
        'nama'   => $nama,
        'ucapan' => $ucapan,
        'hadir'  => $hadir,
        'waktu'  => date('d M Y, H:i'),
    ];
    file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(array_reverse(json_decode(file_get_contents($file), true) ?: []));
}
