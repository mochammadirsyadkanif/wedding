<?php
function getSettings(): array {
    $file = __DIR__ . '/data/settings.json';
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}
function saveSettings(array $data): bool {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return file_put_contents($dir . '/settings.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}
function findPhoto(string $base): string {
    foreach (['jpg','jpeg','png','webp'] as $ext) {
        $p = __DIR__ . '/' . $base . '.' . $ext;
        if (file_exists($p)) return $base . '.' . $ext . '?t=' . filemtime($p);
    }
    return '';
}
function findMusik(): string {
    foreach (['mp3','ogg','wav'] as $ext) {
        $f = __DIR__ . '/uploads/musik/lagu.' . $ext;
        if (file_exists($f)) return 'uploads/musik/lagu.' . $ext . '?t=' . filemtime($f);
    }
    return '';
}
function uploadFile(string $key, string $dir, string $rename = ''): array {
    if (empty($_FILES[$key]['tmp_name'])) return ['ok'=>false,'msg'=>'Tidak ada file'];
    $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
    $allowed = $key === 'foto' ? ['jpg','jpeg','png','webp','gif'] : ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) return ['ok'=>false,'msg'=>'Format tidak didukung'];
    $name = $rename ? "$rename.$ext" : uniqid().".$ext";
    $dest_dir = __DIR__ . "/$dir";
    if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);
    if ($rename) foreach (['jpg','jpeg','png','webp','gif'] as $e) { $old=$dest_dir."/$rename.$e"; if(file_exists($old)) @unlink($old); }
    $dest = "$dest_dir/$name";
    move_uploaded_file($_FILES[$key]['tmp_name'], $dest);
    return ['ok'=>true,'path'=>"$dir/$name",'name'=>$name];
}
function bgSection(array $cfg, string $key, string $cp, string $cs): string {
    $path = $cfg['backgrounds'][$key] ?? '';
    if ($path && file_exists(__DIR__.'/'.$path))
        return "background-image:url('".htmlspecialchars($path)."');background-size:cover;background-position:center;";
    $gradients = [
        'cover'     => "background:linear-gradient(160deg,{$cp} 0%,#1a0a2e 50%,{$cp} 100%);",
        'bismillah' => "background:linear-gradient(180deg,{$cp},{$cs});",
        'mempelai'  => "background:linear-gradient(180deg,{$cs},{$cp});",
        'countdown' => "background:linear-gradient(180deg,{$cp},#0d1a0d);",
        'acara'     => "background:linear-gradient(180deg,#0d1a0d,{$cp});",
        'gallery'   => "background:linear-gradient(180deg,{$cp},{$cs});",
        'ucapan'    => "background:linear-gradient(180deg,{$cs},{$cp});",
        'gift'      => "background:linear-gradient(180deg,{$cp},{$cs});",
        'footer'    => "background:{$cp};",
    ];
    return $gradients[$key] ?? "background:{$cp};";
}
function rgb(string $hex): string {
    $hex = ltrim($hex,'#');
    if(strlen($hex)==3) $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $r=hexdec(substr($hex,0,2));$g=hexdec(substr($hex,2,2));$b=hexdec(substr($hex,4,2));
    return "$r,$g,$b";
}
