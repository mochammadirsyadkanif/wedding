<?php
/**
 * MULTI-CUSTOMER MANAGER
 * Halaman utama untuk kelola semua customer undangan
 */
session_start();

define('CUSTOMERS_DIR', __DIR__ . '/customers');
define('TEMPLATE_DIR',  __DIR__ . '/template');
define('MASTER_PASS',   'admin123'); // Ganti password master di sini

// ── Auth ──────────────────────────────────────────────
if ($_POST['action'] ?? '' === 'login_master') {
    if ($_POST['password'] === MASTER_PASS) $_SESSION['master'] = true;
    else $loginErr = 'Password salah!';
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: customers.php'); exit; }
$ok = !empty($_SESSION['master']);

// ── Helpers ───────────────────────────────────────────
function customerDir(string $slug): string { return CUSTOMERS_DIR . '/' . $slug; }
function customerSettings(string $slug): array {
    $f = customerDir($slug) . '/data/settings.json';
    return file_exists($f) ? (json_decode(file_get_contents($f), true) ?? []) : [];
}
function saveCustomerSettings(string $slug, array $data): void {
    file_put_contents(customerDir($slug) . '/data/settings.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function listCustomers(): array {
    if (!is_dir(CUSTOMERS_DIR)) return [];
    $dirs = array_filter(scandir(CUSTOMERS_DIR), fn($d) => $d[0] !== '.' && is_dir(CUSTOMERS_DIR . '/' . $d));
    $list = [];
    foreach ($dirs as $slug) {
        $cfg = customerSettings($slug);
        $meta = [];
        $meta_file = customerDir($slug) . '/meta.json';
        if (file_exists($meta_file)) $meta = json_decode(file_get_contents($meta_file), true) ?? [];
        $list[] = [
            'slug'      => $slug,
            'pria'      => $cfg['pria']['nama_panggilan'] ?? '-',
            'wanita'    => $cfg['wanita']['nama_panggilan'] ?? '-',
            'tanggal'   => $cfg['akad']['tanggal'] ?? '-',
            'status'    => $meta['status'] ?? 'active',
            'note'      => $meta['note'] ?? '',
            'created'   => $meta['created'] ?? '',
            'ucapan'    => countUcapan($slug),
        ];
    }
    usort($list, fn($a, $b) => strcmp($b['created'], $a['created']));
    return $list;
}
function countUcapan(string $slug): int {
    $f = customerDir($slug) . '/data/ucapan.json';
    if (!file_exists($f)) return 0;
    return count(json_decode(file_get_contents($f), true) ?? []);
}
function slugify(string $str): string {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-');
}
function copyTemplate(string $slug): bool {
    $dst = customerDir($slug);
    if (!is_dir(TEMPLATE_DIR)) return false;
    if (!is_dir($dst)) mkdir($dst, 0755, true);
    // Copy template files
    foreach (['index.php', 'ucapan.php', 'helpers.php'] as $f) {
        if (file_exists(TEMPLATE_DIR . '/' . $f))
            copy(TEMPLATE_DIR . '/' . $f, $dst . '/' . $f);
    }
    // Create required dirs
    foreach (['data', 'uploads/mempelai', 'uploads/gallery', 'uploads/backgrounds', 'uploads/musik'] as $d) {
        if (!is_dir($dst . '/' . $d)) mkdir($dst . '/' . $d, 0755, true);
    }
    // Init empty files
    if (!file_exists($dst . '/data/settings.json'))  file_put_contents($dst . '/data/settings.json', '{}');
    if (!file_exists($dst . '/data/ucapan.json'))    file_put_contents($dst . '/data/ucapan.json', '[]');
    return true;
}
function deleteDir(string $dir): void {
    if (!is_dir($dir)) return;
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) $item->isDir() ? rmdir($item) : unlink($item);
    rmdir($dir);
}
function dirSizeKb(string $dir): int {
    if (!is_dir($dir)) return 0;
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f)
        $size += $f->getSize();
    return (int)($size / 1024);
}

// ── Actions ───────────────────────────────────────────
$msg = ''; $msgType = 'ok';
if ($ok && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    // Create new customer
    if ($act === 'create_customer') {
        $pria   = trim($_POST['pria'] ?? '');
        $wanita = trim($_POST['wanita'] ?? '');
        $note   = trim($_POST['note'] ?? '');
        if ($pria && $wanita) {
            $slug = slugify($pria . '-' . $wanita);
            $base = $slug; $i = 2;
            while (is_dir(customerDir($slug))) $slug = $base . '-' . $i++;
            if (copyTemplate($slug)) {
                // Save initial settings
                $cfg = [
                    'pria'   => ['nama_panggilan' => $pria,  'nama_lengkap' => $pria,  'nama_ayah' => '', 'nama_ibu' => ''],
                    'wanita' => ['nama_panggilan' => $wanita, 'nama_lengkap' => $wanita, 'nama_ayah' => '', 'nama_ibu' => ''],
                    'akad'   => ['aktif' => true, 'hari' => '', 'tanggal' => '', 'jam' => '', 'selesai' => '', 'lokasi' => '', 'gmaps' => ''],
                    'resepsi'=> ['aktif' => true, 'hari' => '', 'tanggal' => '', 'jam' => '', 'selesai' => '', 'lokasi' => '', 'gmaps' => ''],
                    'tanggal_countdown' => '', 'rekening' => [], 'show_gift' => false,
                    'ayat' => 'Dan di antara tanda-tanda (kebesaran)-Nya ialah Dia menciptakan pasangan-pasangan untukmu dari jenismu sendiri, agar kamu cenderung dan merasa tenteram kepadanya, dan Dia menjadikan di antaramu rasa kasih dan sayang.',
                    'sumber_ayat' => 'QS. Ar-Rum : 21',
                    'admin_password' => 'admin123',
                ];
                saveCustomerSettings($slug, $cfg);
                // Save meta
                file_put_contents(customerDir($slug) . '/meta.json', json_encode([
                    'status' => 'active', 'note' => $note,
                    'created' => date('Y-m-d H:i:s'), 'pria' => $pria, 'wanita' => $wanita,
                ], JSON_PRETTY_PRINT));
                $msg = "✅ Customer <strong>$pria &amp; $wanita</strong> berhasil dibuat! Slug: <code>$slug</code>";
            } else {
                $msg = '❌ Gagal membuat customer. Pastikan folder <code>template/</code> sudah ada.';
                $msgType = 'err';
            }
        } else { $msg = '❌ Nama pria & wanita wajib diisi!'; $msgType = 'err'; }
    }

    // Archive / restore
    if ($act === 'set_status') {
        $slug   = basename($_POST['slug'] ?? '');
        $status = $_POST['status'] ?? 'active';
        if (is_dir(customerDir($slug))) {
            $meta_f = customerDir($slug) . '/meta.json';
            $meta = file_exists($meta_f) ? (json_decode(file_get_contents($meta_f), true) ?? []) : [];
            $meta['status'] = $status;
            file_put_contents($meta_f, json_encode($meta, JSON_PRETTY_PRINT));
            $msg = '✅ Status diubah ke ' . ucfirst($status) . '.';
        }
    }

    // Update note
    if ($act === 'update_note') {
        $slug = basename($_POST['slug'] ?? '');
        $note = trim($_POST['note'] ?? '');
        if (is_dir(customerDir($slug))) {
            $meta_f = customerDir($slug) . '/meta.json';
            $meta = file_exists($meta_f) ? (json_decode(file_get_contents($meta_f), true) ?? []) : [];
            $meta['note'] = $note;
            file_put_contents($meta_f, json_encode($meta, JSON_PRETTY_PRINT));
            $msg = '✅ Catatan disimpan.';
        }
    }

    // Delete customer
    if ($act === 'delete_customer') {
        $slug = basename($_POST['slug'] ?? '');
        $confirm = trim($_POST['confirm_delete'] ?? '');
        if ($confirm === 'HAPUS' && is_dir(customerDir($slug))) {
            deleteDir(customerDir($slug));
            $msg = "✅ Customer <strong>$slug</strong> berhasil dihapus.";
        } else { $msg = '❌ Ketik HAPUS untuk konfirmasi.'; $msgType = 'err'; }
    }

    // Change master password
    if ($act === 'save_master_pass') {
        $np = trim($_POST['new_password'] ?? '');
        if (strlen($np) >= 6) {
            $content = file_get_contents(__FILE__);
            $content = preg_replace("/define\('MASTER_PASS',\s*'[^']*'\)/", "define('MASTER_PASS', '$np')", $content);
            file_put_contents(__FILE__, $content);
            $msg = '✅ Master password diubah!';
        } else { $msg = '❌ Minimal 6 karakter.'; $msgType = 'err'; }
    }
}

$customers = $ok ? listCustomers() : [];
$activeCount   = count(array_filter($customers, fn($c) => $c['status'] === 'active'));
$archiveCount  = count(array_filter($customers, fn($c) => $c['status'] === 'archived'));
$filterStatus  = $_GET['filter'] ?? 'all';
$search        = trim($_GET['q'] ?? '');
if ($filterStatus !== 'all') $customers = array_filter($customers, fn($c) => $c['status'] === $filterStatus);
if ($search) $customers = array_filter($customers, fn($c) => stripos($c['pria'].$c['wanita'].$c['slug'], $search) !== false);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — Multi Customer</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#060c18;--bg2:#0b1422;--bg3:#101d30;
  --gold:#c9a84c;--gold2:#e8c97a;
  --rose:#c97a8a;
  --green:#3db87a;
  --err:#e74c3c;--suc:#2ecc71;
  --border:rgba(201,168,76,.14);
  --text:#ccd;--text2:#889;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}

/* ── Topbar ── */
.topbar{
  position:sticky;top:0;z-index:100;
  background:rgba(6,12,24,.95);
  border-bottom:1px solid var(--border);
  padding:.75rem 1.5rem;
  display:flex;align-items:center;justify-content:space-between;
  backdrop-filter:blur(12px);
}
.topbar h1{font-family:'Cormorant Garamond',serif;font-size:1.4rem;color:var(--gold);}
.topbar h1 span{font-size:.75rem;color:var(--text2);font-family:'Jost';letter-spacing:.08em;margin-left:.5rem;}
.t-btns{display:flex;gap:.5rem;}
.tbtn{font-size:.72rem;letter-spacing:.08em;padding:.38rem .8rem;cursor:pointer;border:1px solid;text-decoration:none;transition:.2s;font-family:'Jost';}
.tbtn-g{color:var(--gold);border-color:var(--gold);background:transparent;}
.tbtn-g:hover{background:var(--gold);color:var(--bg);}
.tbtn-d{color:var(--text2);border-color:rgba(255,255,255,.08);background:transparent;}
.tbtn-d:hover{color:var(--text);border-color:rgba(255,255,255,.2);}

/* ── Layout ── */
.layout{display:flex;min-height:calc(100vh - 52px);}
.sidebar{
  width:220px;flex-shrink:0;
  background:rgba(5,10,20,.8);
  border-right:1px solid var(--border);
  padding:1.2rem 0;
  position:sticky;top:52px;
  height:calc(100vh - 52px);
  overflow-y:auto;
}
.sb-sect{padding:.3rem 1.1rem;font-size:.58rem;letter-spacing:.25em;color:#334;text-transform:uppercase;margin-top:.8rem;}
.sidebar a{display:block;padding:.5rem 1.1rem;font-size:.78rem;color:var(--text2);text-decoration:none;border-left:2px solid transparent;transition:.15s;}
.sidebar a:hover,.sidebar a.act{color:var(--gold2);border-left-color:var(--gold);background:rgba(201,168,76,.05);}
.main{flex:1;padding:1.5rem;max-width:1100px;}

/* ── Stats bar ── */
.stats-row{display:flex;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;}
.stat-card{
  flex:1;min-width:130px;
  background:var(--bg3);border:1px solid var(--border);
  border-radius:10px;padding:1rem 1.3rem;
}
.stat-num{font-family:'Cormorant Garamond',serif;font-size:2.5rem;color:var(--gold2);line-height:1;}
.stat-lbl{font-size:.7rem;color:var(--text2);letter-spacing:.1em;margin-top:.2rem;}

/* ── Toolbar ── */
.toolbar{display:flex;gap:.7rem;align-items:center;flex-wrap:wrap;margin-bottom:1.2rem;}
.search-box{
  flex:1;min-width:200px;
  background:rgba(255,255,255,.05);border:1px solid var(--border);
  padding:.45rem .85rem;color:var(--text);font-family:'Jost';font-size:.84rem;
  border-radius:5px;
}
.search-box:focus{outline:none;border-color:var(--gold);}
.filter-tabs{display:flex;gap:.3rem;}
.ftab{font-size:.72rem;padding:.35rem .75rem;cursor:pointer;border:1px solid var(--border);text-decoration:none;color:var(--text2);transition:.15s;border-radius:4px;}
.ftab:hover,.ftab.act{border-color:var(--gold);color:var(--gold);}

/* ── Customer cards ── */
.customer-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.1rem;}
.ccard{
  background:var(--bg3);
  border:1px solid var(--border);
  border-radius:12px;
  overflow:hidden;
  transition:border-color .2s,transform .2s;
}
.ccard:hover{border-color:rgba(201,168,76,.35);transform:translateY(-2px);}
.ccard.archived{opacity:.6;border-style:dashed;}
.ccard-head{
  padding:1.2rem 1.3rem .9rem;
  background:linear-gradient(135deg,rgba(201,168,76,.06),transparent);
  border-bottom:1px solid var(--border);
  display:flex;align-items:flex-start;gap:.9rem;
}
.ccard-avatar{
  width:46px;height:46px;border-radius:50%;
  background:linear-gradient(135deg,var(--gold),var(--gold2));
  display:flex;align-items:center;justify-content:center;
  font-size:1.2rem;flex-shrink:0;
}
.ccard-avatar.arch{background:linear-gradient(135deg,#334,#445);}
.ccard-info{flex:1;min-width:0;}
.ccard-names{font-family:'Cormorant Garamond',serif;font-size:1.25rem;font-style:italic;color:var(--gold2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ccard-slug{font-size:.68rem;color:var(--text2);letter-spacing:.08em;margin-top:.1rem;font-family:'Jost';}
.ccard-date{font-size:.7rem;color:rgba(201,168,76,.65);margin-top:.25rem;}
.ccard-badge{
  font-size:.62rem;padding:.15rem .55rem;border-radius:20px;
  font-weight:500;letter-spacing:.06em;flex-shrink:0;margin-top:.2rem;
}
.badge-active{background:rgba(61,184,122,.15);color:var(--green);border:1px solid rgba(61,184,122,.3);}
.badge-archived{background:rgba(255,255,255,.06);color:#556;border:1px solid rgba(255,255,255,.1);}
.ccard-body{padding:.9rem 1.3rem;}
.ccard-meta{display:flex;gap:1.2rem;font-size:.75rem;color:var(--text2);margin-bottom:.75rem;}
.ccard-meta span{display:flex;align-items:center;gap:.3rem;}
.ccard-note{
  font-size:.77rem;color:var(--text2);font-style:italic;
  background:rgba(255,255,255,.04);
  border-left:2px solid rgba(201,168,76,.3);
  padding:.4rem .6rem;margin-bottom:.75rem;
  min-height:1.8rem;
}
.ccard-actions{display:flex;gap:.5rem;flex-wrap:wrap;}
.btn{font-family:'Jost';font-size:.7rem;letter-spacing:.06em;padding:.38rem .8rem;cursor:pointer;border:none;transition:.2s;border-radius:4px;}
.btn-g{background:linear-gradient(135deg,var(--gold),var(--gold2));color:var(--bg);}
.btn-g:hover{opacity:.85;}
.btn-o{background:transparent;border:1px solid var(--gold);color:var(--gold);}
.btn-o:hover{background:var(--gold);color:var(--bg);}
.btn-b{background:transparent;border:1px solid rgba(100,150,255,.4);color:#7aabff;}
.btn-b:hover{background:rgba(100,150,255,.1);}
.btn-r{background:transparent;border:1px solid rgba(231,76,60,.4);color:#f1948a;}
.btn-r:hover{background:rgba(231,76,60,.1);}
.btn-gray{background:transparent;border:1px solid rgba(255,255,255,.12);color:var(--text2);}
.btn-gray:hover{border-color:rgba(255,255,255,.25);color:var(--text);}

/* ── New customer form ── */
.new-card{
  background:var(--bg3);border:2px dashed rgba(201,168,76,.25);
  border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;
  display:none;
}
.new-card.open{display:block;}
.form-row{display:grid;grid-template-columns:1fr 1fr 2fr auto;gap:.8rem;align-items:end;}

/* ── Modal overlay ── */
.modal-bg{
  position:fixed;inset:0;background:rgba(0,0,0,.8);
  z-index:500;display:flex;align-items:center;justify-content:center;
  backdrop-filter:blur(4px);
  animation:fadeIn .2s;
}
.modal-bg.hidden{display:none;}
.modal{
  background:#0d1a2a;border:1px solid var(--border);
  border-radius:14px;padding:1.8rem;width:100%;max-width:460px;
  position:relative;
}
.modal h3{font-family:'Cormorant Garamond',serif;font-size:1.2rem;color:var(--gold2);margin-bottom:1rem;}
.modal-close{position:absolute;top:1rem;right:1rem;background:none;border:none;color:var(--text2);font-size:1.2rem;cursor:pointer;}
.modal-close:hover{color:var(--text);}

/* ── Inputs ── */
.fg{margin-bottom:.85rem;}
.fg label{display:block;font-size:.7rem;letter-spacing:.1em;color:#99a;margin-bottom:.3rem;}
.fg input,.fg textarea,.fg select{
  width:100%;background:rgba(255,255,255,.05);border:1px solid var(--border);
  border-radius:4px;padding:.48rem .8rem;color:var(--text);
  font-family:'Jost';font-size:.84rem;resize:vertical;transition:.15s;
}
.fg input:focus,.fg textarea:focus{outline:none;border-color:var(--gold);}

/* ── Msg bar ── */
.msg-bar{padding:.7rem 1rem;border-radius:7px;margin-bottom:1.2rem;font-size:.84rem;}
.msg-ok{background:rgba(46,204,113,.1);border:1px solid rgba(46,204,113,.3);color:#7debb0;}
.msg-err{background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.3);color:#f1948a;}

/* ── Link cards ── */
.link-preview{
  font-size:.72rem;background:rgba(255,255,255,.04);
  border:1px solid rgba(255,255,255,.08);
  border-radius:4px;padding:.35rem .65rem;
  color:#7aabff;word-break:break-all;margin-bottom:.75rem;
  display:flex;align-items:center;gap:.5rem;
}
.link-copy{
  flex-shrink:0;background:none;border:1px solid rgba(100,150,255,.3);
  color:#7aabff;font-size:.65rem;padding:.18rem .5rem;cursor:pointer;
  border-radius:3px;transition:.15s;
}
.link-copy:hover{background:rgba(100,150,255,.15);}

/* ── Empty state ── */
.empty-state{text-align:center;padding:4rem 2rem;color:var(--text2);}
.empty-state .emoji{font-size:3rem;display:block;margin-bottom:1rem;}

/* ── Login ── */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;}
.login-box{
  background:#0d1a2a;border:1px solid rgba(201,168,76,.2);
  border-radius:14px;padding:2.5rem;width:100%;max-width:350px;text-align:center;
}
.login-box .logo{font-family:'Cormorant Garamond',serif;font-size:2.5rem;color:var(--gold);margin-bottom:.3rem;}
.login-box p{color:var(--text2);font-size:.84rem;margin-bottom:1.8rem;}
.login-box .flowers{font-size:1.5rem;letter-spacing:.5rem;display:block;margin-bottom:.8rem;opacity:.5;}

@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
@media(max-width:680px){
  .sidebar{display:none;}
  .form-row{grid-template-columns:1fr;}
  .customer-grid{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<?php if (!$ok): ?>
<div class="login-wrap">
  <div class="login-box">
    <div class="flowers">🌸🌺🌹</div>
    <div class="logo">Wedding CMS</div>
    <p>Multi-Customer Dashboard</p>
    <?php if (!empty($loginErr)): ?><div class="msg-bar msg-err"><?= $loginErr ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="action" value="login_master">
      <div class="fg"><label>Master Password</label><input type="password" name="password" autofocus required placeholder="Masukkan password..."></div>
      <button type="submit" class="btn btn-g" style="width:100%;padding:.65rem;">Masuk →</button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════ TOPBAR ══════════════════ -->
<div class="topbar">
  <h1>🌸 Wedding CMS <span>Multi-Customer</span></h1>
  <div class="t-btns">
    <button class="tbtn tbtn-g" onclick="toggleNewForm()">+ Buat Customer Baru</button>
    <a href="?logout" class="tbtn tbtn-d">Keluar</a>
  </div>
</div>

<div class="layout">
<!-- ══════════════════ SIDEBAR ══════════════════ -->
<nav class="sidebar">
  <div class="sb-sect">Customer</div>
  <a href="?filter=all" class="<?= $filterStatus==='all'?'act':'' ?>">📋 Semua (<?= count(listCustomers()) ?>)</a>
  <a href="?filter=active" class="<?= $filterStatus==='active'?'act':'' ?>">✅ Aktif (<?= $activeCount ?>)</a>
  <a href="?filter=archived" class="<?= $filterStatus==='archived'?'act':'' ?>">📦 Arsip (<?= $archiveCount ?>)</a>
  <div class="sb-sect">Pengaturan</div>
  <a href="#sec-setup">⚙ Setup Template</a>
  <a href="#sec-master-pass">🔒 Master Password</a>
</nav>

<!-- ══════════════════ MAIN ══════════════════ -->
<div class="main">
  <?php if ($msg): ?><div class="msg-bar <?= $msgType==='err'?'msg-err':'msg-ok' ?>"><?= $msg ?></div><?php endif; ?>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-num"><?= count(listCustomers()) ?></div>
      <div class="stat-lbl">TOTAL CUSTOMER</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--green);"><?= $activeCount ?></div>
      <div class="stat-lbl">AKTIF</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:#556;"><?= $archiveCount ?></div>
      <div class="stat-lbl">DIARSIPKAN</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--rose);"><?= array_sum(array_column(listCustomers(), 'ucapan')) ?></div>
      <div class="stat-lbl">TOTAL UCAPAN</div>
    </div>
  </div>

  <!-- New Customer Form -->
  <div class="new-card" id="newCustomerForm">
    <div style="font-family:'Cormorant Garamond',serif;font-size:1.1rem;color:var(--gold2);margin-bottom:1rem;">✨ Buat Customer Baru</div>
    <form method="POST">
      <input type="hidden" name="action" value="create_customer">
      <div class="form-row">
        <div class="fg" style="margin:0">
          <label>Nama Pria (panggilan)</label>
          <input type="text" name="pria" placeholder="contoh: Budi" required>
        </div>
        <div class="fg" style="margin:0">
          <label>Nama Wanita (panggilan)</label>
          <input type="text" name="wanita" placeholder="contoh: Ani" required>
        </div>
        <div class="fg" style="margin:0">
          <label>Catatan Internal (opsional)</label>
          <input type="text" name="note" placeholder="contoh: Paket Silver, WA: 08xxx...">
        </div>
        <div style="padding-bottom:.85rem;">
          <button type="submit" class="btn btn-g" style="padding:.5rem 1.2rem;">Buat →</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <input type="text" class="search-box" placeholder="🔍 Cari nama / slug..." value="<?= htmlspecialchars($search) ?>" onkeyup="doSearch(this.value)">
    <div class="filter-tabs">
      <a href="?filter=all" class="ftab <?= $filterStatus==='all'?'act':'' ?>">Semua</a>
      <a href="?filter=active" class="ftab <?= $filterStatus==='active'?'act':'' ?>">✅ Aktif</a>
      <a href="?filter=archived" class="ftab <?= $filterStatus==='archived'?'act':'' ?>">📦 Arsip</a>
    </div>
  </div>

  <!-- Customer Grid -->
  <?php if (empty($customers)): ?>
  <div class="empty-state">
    <span class="emoji">🌸</span>
    <p>Belum ada customer<?= $filterStatus!=='all' ? ' dalam kategori ini' : '' ?>.</p>
    <p style="margin-top:.5rem;font-size:.83rem;">Klik <strong>+ Buat Customer Baru</strong> untuk mulai.</p>
  </div>
  <?php else: ?>
  <div class="customer-grid" id="customerGrid">
    <?php foreach ($customers as $c):
      $isArch = $c['status'] === 'archived';
      $baseUrl = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/customers/' . $c['slug'];
    ?>
    <div class="ccard <?= $isArch ? 'archived' : '' ?>" data-search="<?= htmlspecialchars(strtolower($c['pria'].$c['wanita'].$c['slug'])) ?>">
      <div class="ccard-head">
        <div class="ccard-avatar <?= $isArch ? 'arch' : '' ?>"><?= $isArch ? '📦' : '💍' ?></div>
        <div class="ccard-info">
          <div class="ccard-names"><?= htmlspecialchars($c['pria']) ?> &amp; <?= htmlspecialchars($c['wanita']) ?></div>
          <div class="ccard-slug"><?= htmlspecialchars($c['slug']) ?></div>
          <div class="ccard-date">📅 <?= htmlspecialchars($c['tanggal']) ?: 'Tanggal belum diset' ?></div>
        </div>
        <span class="ccard-badge <?= $isArch ? 'badge-archived' : 'badge-active' ?>"><?= $isArch ? 'Arsip' : 'Aktif' ?></span>
      </div>
      <div class="ccard-body">
        <div class="ccard-meta">
          <span>💬 <?= $c['ucapan'] ?> ucapan</span>
          <span>📁 <?= $c['created'] ? date('d M Y', strtotime($c['created'])) : '-' ?></span>
        </div>
        <?php if ($c['note']): ?>
        <div class="ccard-note">📝 <?= htmlspecialchars($c['note']) ?></div>
        <?php endif; ?>

        <!-- Link undangan -->
        <div class="link-preview">
          <span style="flex:1;"><?= htmlspecialchars($baseUrl . '/index.php?to=Nama+Tamu') ?></span>
          <button class="link-copy" onclick="copyLink('<?= htmlspecialchars($baseUrl . '/index.php?to=Nama+Tamu') ?>', this)">Salin</button>
        </div>

        <div class="ccard-actions">
          <a href="customers/<?= $c['slug'] ?>/admin.php" target="_blank" class="btn btn-g">⚙ Edit</a>
          <a href="customers/<?= $c['slug'] ?>/index.php" target="_blank" class="btn btn-o">👁 Preview</a>
          <button class="btn btn-b" onclick="openNoteModal('<?= $c['slug'] ?>', <?= json_encode($c['note']) ?>)">📝 Catatan</button>
          <?php if (!$isArch): ?>
          <button class="btn btn-gray" onclick="setStatus('<?= $c['slug'] ?>', 'archived')">📦 Arsipkan</button>
          <?php else: ?>
          <button class="btn btn-gray" onclick="setStatus('<?= $c['slug'] ?>', 'active')">♻️ Aktifkan</button>
          <?php endif; ?>
          <button class="btn btn-r" onclick="openDeleteModal('<?= $c['slug'] ?>', '<?= htmlspecialchars($c['pria'].' & '.$c['wanita']) ?>')">🗑 Hapus</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ══ SETUP INFO ══ -->
  <div style="margin-top:2.5rem;">
  <div style="background:var(--bg3);border:1px solid var(--border);border-radius:12px;padding:1.3rem;margin-bottom:1.2rem;" id="sec-setup">
    <div style="font-family:'Cormorant Garamond',serif;font-size:1.05rem;color:var(--gold2);margin-bottom:.9rem;padding-bottom:.55rem;border-bottom:1px solid var(--border);">⚙ Cara Setup Template</div>
    <div style="font-size:.83rem;color:var(--text2);line-height:1.9;">
      <p>Sistem ini menggunakan folder <code style="background:rgba(255,255,255,.07);padding:.1rem .4rem;border-radius:3px;">template/</code> sebagai master template. Setiap customer baru akan di-copy dari sana.</p>
      <br>
      <p><strong style="color:var(--gold);">Struktur folder yang dibutuhkan:</strong></p>
      <pre style="background:rgba(255,255,255,.04);padding:.8rem 1rem;border-radius:6px;margin:.6rem 0;font-size:.77rem;overflow-x:auto;border:1px solid rgba(255,255,255,.06);">
📁 root/
├── customers.php          ← Halaman ini
├── 📁 template/           ← Master template (wajib ada!)
│   ├── index.php          ← Template undangan (versi bunga)
│   ├── ucapan.php
│   ├── helpers.php
│   └── admin.php
└── 📁 customers/          ← Dibuat otomatis
    ├── 📁 budi-ani/
    │   ├── index.php
    │   ├── admin.php
    │   ├── helpers.php
    │   ├── ucapan.php
    │   ├── 📁 data/
    │   │   ├── settings.json
    │   │   └── ucapan.json
    │   └── 📁 uploads/
    └── 📁 tofa-dwi/
        └── ...</pre>
      <p style="color:var(--text2);font-size:.78rem;">Salin file <code>index.php</code>, <code>admin.php</code>, <code>helpers.php</code>, <code>ucapan.php</code> ke dalam folder <code>template/</code> untuk menggunakan template baru.</p>
    </div>
  </div>

  <!-- Master Password -->
  <div style="background:var(--bg3);border:1px solid var(--border);border-radius:12px;padding:1.3rem;" id="sec-master-pass">
    <div style="font-family:'Cormorant Garamond',serif;font-size:1.05rem;color:var(--gold2);margin-bottom:.9rem;padding-bottom:.55rem;border-bottom:1px solid var(--border);">🔒 Ganti Master Password</div>
    <form method="POST" style="display:flex;gap:.8rem;align-items:flex-end;flex-wrap:wrap;">
      <input type="hidden" name="action" value="save_master_pass">
      <div class="fg" style="margin:0;flex:1;min-width:200px;">
        <label>Password Baru (min. 6 karakter)</label>
        <input type="password" name="new_password" placeholder="Password baru...">
      </div>
      <div style="padding-bottom:.85rem;">
        <button type="submit" class="btn btn-g">Simpan</button>
      </div>
    </form>
  </div>
  </div>
</div><!-- /main -->
</div><!-- /layout -->

<!-- ════ MODAL: Status Change ════ -->
<div class="modal-bg hidden" id="statusModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('statusModal')">✕</button>
    <h3 id="statusModalTitle">Ubah Status</h3>
    <form method="POST" id="statusForm">
      <input type="hidden" name="action" value="set_status">
      <input type="hidden" name="slug" id="statusSlug">
      <input type="hidden" name="status" id="statusValue">
      <p style="color:var(--text2);font-size:.84rem;margin-bottom:1.2rem;" id="statusModalMsg"></p>
      <div style="display:flex;gap:.7rem;">
        <button type="submit" class="btn btn-g">Ya, Lanjutkan</button>
        <button type="button" class="btn btn-gray" onclick="closeModal('statusModal')">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ════ MODAL: Note ════ -->
<div class="modal-bg hidden" id="noteModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('noteModal')">✕</button>
    <h3>📝 Edit Catatan</h3>
    <form method="POST">
      <input type="hidden" name="action" value="update_note">
      <input type="hidden" name="slug" id="noteSlug">
      <div class="fg">
        <label>Catatan Internal (paket, kontak, status bayar, dll)</label>
        <textarea name="note" id="noteText" rows="4" placeholder="contoh: Paket Gold, DP 50% sudah, WA: 08xxx..."></textarea>
      </div>
      <div style="display:flex;gap:.7rem;">
        <button type="submit" class="btn btn-g">💾 Simpan</button>
        <button type="button" class="btn btn-gray" onclick="closeModal('noteModal')">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- ════ MODAL: Delete ════ -->
<div class="modal-bg hidden" id="deleteModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('deleteModal')">✕</button>
    <h3 style="color:#f1948a;">⚠️ Hapus Customer</h3>
    <p style="color:var(--text2);font-size:.84rem;margin-bottom:1rem;">Anda akan menghapus: <strong id="deleteNameLabel" style="color:var(--gold2);"></strong></p>
    <p style="color:#f1948a;font-size:.82rem;margin-bottom:1.2rem;">⚠️ Tindakan ini <strong>tidak bisa dibatalkan</strong>. Semua data, foto, dan ucapan customer akan terhapus permanen!</p>
    <form method="POST">
      <input type="hidden" name="action" value="delete_customer">
      <input type="hidden" name="slug" id="deleteSlug">
      <div class="fg">
        <label>Ketik <strong>HAPUS</strong> untuk konfirmasi</label>
        <input type="text" name="confirm_delete" placeholder="HAPUS" autocomplete="off">
      </div>
      <div style="display:flex;gap:.7rem;">
        <button type="submit" class="btn btn-r" style="background:linear-gradient(135deg,#b03030,#e05050);color:#fff;border:none;">🗑 Hapus Permanen</button>
        <button type="button" class="btn btn-gray" onclick="closeModal('deleteModal')">Batal</button>
      </div>
    </form>
  </div>
</div>

<?php endif; ?>

<script>
function toggleNewForm(){
  var el=document.getElementById('newCustomerForm');
  el.classList.toggle('open');
  if(el.classList.contains('open')) el.querySelector('input[name=pria]').focus();
}
function closeModal(id){document.getElementById(id).classList.add('hidden');}

function setStatus(slug, status){
  document.getElementById('statusSlug').value=slug;
  document.getElementById('statusValue').value=status;
  document.getElementById('statusModalTitle').textContent=status==='archived'?'📦 Arsipkan Customer':'♻️ Aktifkan Customer';
  document.getElementById('statusModalMsg').textContent=status==='archived'
    ?'Customer akan diarsipkan. Data tetap tersimpan dan bisa diaktifkan kembali kapan saja.'
    :'Customer akan diaktifkan kembali.';
  document.getElementById('statusModal').classList.remove('hidden');
}
function openNoteModal(slug, note){
  document.getElementById('noteSlug').value=slug;
  document.getElementById('noteText').value=note||'';
  document.getElementById('noteModal').classList.remove('hidden');
}
function openDeleteModal(slug, name){
  document.getElementById('deleteSlug').value=slug;
  document.getElementById('deleteNameLabel').textContent=name;
  document.getElementById('deleteModal').classList.remove('hidden');
}
// Close modal on bg click
document.querySelectorAll('.modal-bg').forEach(function(bg){
  bg.addEventListener('click',function(e){if(e.target===bg)bg.classList.add('hidden');});
});

function copyLink(link, btn){
  navigator.clipboard.writeText(link).then(function(){
    var orig=btn.textContent;btn.textContent='✓ Tersalin!';
    setTimeout(function(){btn.textContent=orig;},2000);
  });
}

function doSearch(q){
  q=q.toLowerCase();
  document.querySelectorAll('.ccard').forEach(function(card){
    var data=card.dataset.search||'';
    card.style.display=data.includes(q)?'':'none';
  });
}
</script>
</body>
</html>
