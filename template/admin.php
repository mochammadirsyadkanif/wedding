<?php
/**
 * ADMIN PANEL - Per Customer
 * File ini ada di setiap folder customer (customers/[slug]/admin.php)
 * Juga dipakai di template/admin.php sebagai master
 */
session_start();

// Deteksi apakah ini di dalam folder customer atau standalone
$isInCustomer = basename(dirname(__FILE__)) !== 'template' && is_dir(__DIR__ . '/data');
$customerSlug = $isInCustomer ? basename(__DIR__) : '';
$dashboardUrl = $isInCustomer ? '../../customers.php' : '../customers.php';

require __DIR__ . '/helpers.php';
$cfg = getSettings();

// ── Auth (per-customer session key) ──────────────────
$sessKey = 'admin_' . md5(__DIR__);
if (($_POST['action']??'')==='login') {
    if ($_POST['password']===($cfg['admin_password']??'admin123')) $_SESSION[$sessKey]=true;
    else $loginErr='Password salah!';
}
if (isset($_GET['logout'])) {
    unset($_SESSION[$sessKey]);
    header('Location: admin.php'); exit;
}
$ok = !empty($_SESSION[$sessKey]);

$msg=''; $msgType='ok';

if ($ok && $_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action']??'';

    if ($act==='save_content') {
        $cfg['pria']['nama_panggilan']  = trim($_POST['pria_panggilan']??'');
        $cfg['pria']['nama_lengkap']    = trim($_POST['pria_lengkap']??'');
        $cfg['pria']['nama_ayah']       = trim($_POST['pria_ayah']??'');
        $cfg['pria']['nama_ibu']        = trim($_POST['pria_ibu']??'');
        $cfg['wanita']['nama_panggilan']= trim($_POST['wanita_panggilan']??'');
        $cfg['wanita']['nama_lengkap']  = trim($_POST['wanita_lengkap']??'');
        $cfg['wanita']['nama_ayah']     = trim($_POST['wanita_ayah']??'');
        $cfg['wanita']['nama_ibu']      = trim($_POST['wanita_ibu']??'');
        foreach(['akad','resepsi'] as $e) {
            $cfg[$e]['aktif']   = isset($_POST[$e.'_aktif']);
            $cfg[$e]['hari']    = trim($_POST[$e.'_hari']??'');
            $cfg[$e]['tanggal'] = trim($_POST[$e.'_tanggal']??'');
            $cfg[$e]['jam']     = trim($_POST[$e.'_jam']??'');
            $cfg[$e]['selesai'] = trim($_POST[$e.'_selesai']??'');
            $cfg[$e]['lokasi']  = trim($_POST[$e.'_lokasi']??'');
            $cfg[$e]['gmaps']   = trim($_POST[$e.'_gmaps']??'');
        }
        $cfg['tanggal_countdown'] = trim($_POST['tanggal_countdown']??'');
        $cfg['ayat']              = trim($_POST['ayat']??'');
        $cfg['sumber_ayat']       = trim($_POST['sumber_ayat']??'');
        $cfg['ucapan_pembuka']    = trim($_POST['ucapan_pembuka']??'');
        $cfg['footer_text']       = trim($_POST['footer_text']??'');
        $cfg['show_gift']         = isset($_POST['show_gift']);
        $banks  = $_POST['rek_bank']??[];
        $namas  = $_POST['rek_nama']??[];
        $nomors = $_POST['rek_nomor']??[];
        $aktifs = $_POST['rek_aktif']??[];
        $cfg['rekening']=[];
        foreach($banks as $i=>$b) {
            if(trim($b)) $cfg['rekening'][]=['aktif'=>isset($aktifs[$i]),'bank'=>trim($b),'nama'=>trim($namas[$i]??''),'nomor'=>trim($nomors[$i]??'')];
        }
        saveSettings($cfg); $msg='✅ Konten berhasil disimpan!';

        // Update meta.json juga (untuk dashboard)
        if ($isInCustomer) {
            $mf = __DIR__ . '/meta.json';
            $meta = file_exists($mf) ? (json_decode(file_get_contents($mf), true) ?? []) : [];
            $meta['pria']   = $cfg['pria']['nama_panggilan'];
            $meta['wanita'] = $cfg['wanita']['nama_panggilan'];
            file_put_contents($mf, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    if ($act==='save_theme') {
        $cfg['theme'] = [
            'color_primary'  => trim($_POST['color_primary']??'#0d1117'),
            'color_secondary'=> trim($_POST['color_secondary']??'#161b22'),
            'color_accent'   => trim($_POST['color_accent']??'#c9a84c'),
            'color_accent2'  => trim($_POST['color_accent2']??'#e8c97a'),
            'color_text'     => trim($_POST['color_text']??'#f0e6cc'),
            'color_overlay'  => trim($_POST['color_overlay']??'rgba(8,12,22,0.78)'),
        ];
        saveSettings($cfg); $msg='✅ Tema warna disimpan!';
    }

    if ($act==='reset_theme') {
        $cfg['theme']=['color_primary'=>'#0d1117','color_secondary'=>'#161b22','color_accent'=>'#c9a84c','color_accent2'=>'#e8c97a','color_text'=>'#f0e6cc','color_overlay'=>'rgba(8,12,22,0.78)'];
        saveSettings($cfg); $msg='✅ Tema direset ke default.';
    }

    if ($act==='upload_bg') {
        $section=$_POST['section']??'';
        $sections=['cover','bismillah','mempelai','countdown','acara','gallery','ucapan','gift','footer'];
        if(in_array($section,$sections)) {
            $r=uploadFile('foto','uploads/backgrounds','bg_'.$section);
            if($r['ok']) {
                $old=$cfg['backgrounds'][$section]??'';
                if($old&&file_exists(__DIR__.'/'.$old)&&$old!==$r['path']) @unlink(__DIR__.'/'.$old);
                $cfg['backgrounds'][$section]=$r['path'];
                saveSettings($cfg); $msg='✅ Background '.ucfirst($section).' diupload!';
            } else { $msg='❌ '.$r['msg']; $msgType='err'; }
        }
    }
    if ($act==='delete_bg') {
        $section=$_POST['section']??'';
        $old=$cfg['backgrounds'][$section]??'';
        if($old&&file_exists(__DIR__.'/'.$old)) @unlink(__DIR__.'/'.$old);
        $cfg['backgrounds'][$section]=''; saveSettings($cfg); $msg='✅ Background dihapus.';
    }
    if ($act==='upload_mempelai') {
        $side=$_POST['side']??'';
        if(in_array($side,['pria','wanita'])) {
            $r=uploadFile('foto','uploads/mempelai',$side);
            $msg=$r['ok']?"✅ Foto $side diupload!":'❌ '.$r['msg'];
            if(!$r['ok'])$msgType='err';
        }
    }
    if ($act==='upload_gallery') {
        $r=uploadFile('foto','uploads/gallery');
        $msg=$r['ok']?'✅ Foto galeri ditambahkan!':'❌ '.$r['msg'];
        if(!$r['ok'])$msgType='err';
    }
    if ($act==='delete_gallery') {
        $f=__DIR__.'/uploads/gallery/'.basename($_POST['file']??'');
        if(file_exists($f)){@unlink($f);$msg='✅ Foto dihapus.';}
    }
    if ($act==='upload_musik') {
        if(!empty($_FILES['musik']['tmp_name'])) {
            $ext=strtolower(pathinfo($_FILES['musik']['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['mp3','ogg','wav'])) {
                foreach(['mp3','ogg','wav'] as $e){$old=__DIR__.'/uploads/musik/lagu.'.$e;if(file_exists($old))@unlink($old);}
                move_uploaded_file($_FILES['musik']['tmp_name'],__DIR__.'/uploads/musik/lagu.'.$ext);
                $msg='✅ Musik diupload!';
            } else {$msg='❌ Format tidak didukung';$msgType='err';}
        }
    }
    if ($act==='clear_ucapan') {
        file_put_contents(__DIR__.'/data/ucapan.json','[]');
        $msg='✅ Semua ucapan telah dihapus.';
    }
    if ($act==='save_password') {
        $np=trim($_POST['new_password']??'');
        if(strlen($np)>=6){$cfg['admin_password']=$np;saveSettings($cfg);$msg='✅ Password diubah!';}
        else{$msg='❌ Minimal 6 karakter';$msgType='err';}
    }
    $cfg=getSettings();
}

$galleryFiles=glob(__DIR__.'/uploads/gallery/*.{jpg,jpeg,png,webp,gif}',GLOB_BRACE)?:[];
$musikFile=findMusik();
$t=$cfg['theme']??[];
$sectionLabels=['cover'=>'Cover / Hero','bismillah'=>'Bismillah & Ayat','mempelai'=>'Profil Mempelai','countdown'=>'Countdown','acara'=>'Detail Acara','gallery'=>'Galeri','ucapan'=>'Ucapan & RSVP','gift'=>'Wedding Gift','footer'=>'Footer'];

$presets=[
    ['name'=>'Navy & Gold','p'=>'#0a1628','s'=>'#1a2e50','a'=>'#c9a84c','a2'=>'#e8c97a','t'=>'#f0e6cc','o'=>'rgba(8,15,28,0.72)'],
    ['name'=>'Midnight Rose','p'=>'#0d1117','s'=>'#161b22','a'=>'#c9a84c','a2'=>'#e8c97a','t'=>'#f0e6cc','o'=>'rgba(8,12,22,0.78)'],
    ['name'=>'Blush & Rose','p'=>'#1a0810','s'=>'#3d1a2e','a'=>'#e8937a','a2'=>'#f5b8a0','t'=>'#fdf0ec','o'=>'rgba(26,8,16,0.70)'],
    ['name'=>'Sage & Cream','p'=>'#0f1a10','s'=>'#1e3320','a'=>'#8fbc8f','a2'=>'#b5d5b5','t'=>'#f0f5ec','o'=>'rgba(10,20,10,0.68)'],
    ['name'=>'Maroon & Gold','p'=>'#1a0505','s'=>'#3d1010','a'=>'#d4a017','a2'=>'#f0c040','t'=>'#fdf5e6','o'=>'rgba(20,5,5,0.72)'],
];

// Nama customer untuk header
$namaCustomer = ($cfg['pria']['nama_panggilan']??'') . ' & ' . ($cfg['wanita']['nama_panggilan']??'');
if (trim($namaCustomer, ' &') === '') $namaCustomer = $customerSlug ?: 'Template';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — <?=htmlspecialchars($namaCustomer)?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--navy:#060c18;--n2:#0b1422;--n3:#101d30;--gold:#c9a84c;--gold2:#e8c97a;--err:#e74c3c;--suc:#2ecc71;--border:rgba(201,168,76,.14);}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Jost',sans-serif;background:#060c18;color:#ccd;min-height:100vh;}
.topbar{position:sticky;top:0;z-index:100;background:rgba(5,9,18,.97);border-bottom:1px solid var(--border);padding:.72rem 1.4rem;display:flex;align-items:center;justify-content:space-between;backdrop-filter:blur(12px);}
.topbar-left{display:flex;align-items:center;gap:.8rem;}
.back-btn{font-size:.72rem;color:var(--gold);border:1px solid rgba(201,168,76,.3);padding:.3rem .7rem;text-decoration:none;transition:.15s;border-radius:3px;}
.back-btn:hover{background:rgba(201,168,76,.1);}
.topbar h1{font-family:'Cormorant Garamond',serif;font-size:1.2rem;color:var(--gold);}
.topbar-customer{font-size:.72rem;color:#667;font-style:italic;margin-left:.4rem;}
.t-btns{display:flex;gap:.5rem;}
.tbtn{font-family:'Jost';font-size:.72rem;letter-spacing:.08em;padding:.38rem .8rem;cursor:pointer;border:1px solid;text-decoration:none;transition:.2s;}
.tbtn-g{color:var(--gold);border-color:var(--gold);background:transparent;} .tbtn-g:hover{background:var(--gold);color:#050a14;}
.tbtn-d{color:#556;border-color:#223;background:transparent;} .tbtn-d:hover{color:#aab;border-color:#445;}
.layout{display:flex;min-height:calc(100vh - 50px);}
.sidebar{width:210px;flex-shrink:0;background:rgba(5,9,18,.8);border-right:1px solid var(--border);padding:1rem 0;position:sticky;top:50px;height:calc(100vh - 50px);overflow-y:auto;}
.sb-hd{padding:.35rem 1.1rem;font-size:.58rem;letter-spacing:.22em;color:#334;text-transform:uppercase;margin-top:.7rem;}
.sidebar a{display:block;padding:.52rem 1.1rem;font-size:.78rem;color:#667;text-decoration:none;border-left:2px solid transparent;transition:.15s;}
.sidebar a:hover,.sidebar a.act{color:var(--gold2);border-left-color:var(--gold);background:rgba(201,168,76,.05);}
.main{flex:1;padding:1.4rem;overflow-y:auto;max-width:900px;}
.card{background:#0d1826;border:1px solid var(--border);border-radius:10px;padding:1.3rem;margin-bottom:1.4rem;}
.card-title{font-family:'Cormorant Garamond',serif;font-size:1.05rem;color:var(--gold2);margin-bottom:1rem;padding-bottom:.55rem;border-bottom:1px solid var(--border);}
.anc{scroll-margin-top:60px;}
.fg{margin-bottom:.85rem;}
.fg label{display:block;font-size:.72rem;letter-spacing:.1em;color:#99a;margin-bottom:.32rem;}
.fg input[type=text],.fg input[type=password],.fg textarea,.fg select{width:100%;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:4px;padding:.5rem .8rem;color:#ccd;font-family:'Jost';font-size:.86rem;resize:vertical;transition:.15s;}
.fg input:focus,.fg textarea:focus{outline:none;border-color:var(--gold);}
.fg input[type=file]{background:transparent;border:1px dashed rgba(201,168,76,.25);padding:.45rem;}
.fg input[type=color]{width:50px;height:34px;border-radius:4px;border:1px solid rgba(201,168,76,.3);background:transparent;cursor:pointer;padding:2px;}
.cb-row{display:flex;align-items:center;gap:.5rem;font-size:.83rem;color:#99a;cursor:pointer;}
.cb-row input[type=checkbox]{width:15px;height:15px;accent-color:var(--gold);}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:.9rem;}
.g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem;}
.btn{font-family:'Jost';font-size:.75rem;letter-spacing:.08em;padding:.48rem 1.1rem;border-radius:5px;cursor:pointer;border:none;transition:.2s;}
.btn-g{background:linear-gradient(135deg,var(--gold),var(--gold2));color:#080e1c;} .btn-g:hover{opacity:.85;}
.btn-o{background:transparent;border:1px solid var(--gold);color:var(--gold);} .btn-o:hover{background:var(--gold);color:#080e1c;}
.btn-r{background:linear-gradient(135deg,#b03030,#e05050);color:#fff;} .btn-r:hover{opacity:.85;}
.btn-sm{padding:.28rem .65rem;font-size:.7rem;}
.msg-bar{padding:.7rem 1rem;border-radius:6px;margin-bottom:1.1rem;font-size:.85rem;position:sticky;top:56px;z-index:90;}
.msg-ok{background:rgba(46,204,113,.12);border:1px solid rgba(46,204,113,.35);color:#7debb0;}
.msg-err{background:rgba(231,76,60,.12);border:1px solid rgba(231,76,60,.35);color:#f1948a;}
.bg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:.9rem;}
.bg-item{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:7px;overflow:hidden;}
.bg-prev{width:100%;height:90px;object-fit:cover;display:block;}
.bg-ph{width:100%;height:90px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;opacity:.18;background:rgba(255,255,255,.02);}
.bg-body{padding:.7rem;}
.bg-name{font-size:.75rem;color:var(--gold2);margin-bottom:.5rem;}
.tag-on{font-size:.65rem;background:rgba(201,168,76,.12);color:var(--gold);padding:.1rem .4rem;border-radius:8px;margin-left:.3rem;}
.tag-off{font-size:.65rem;background:rgba(255,255,255,.04);color:#556;padding:.1rem .4rem;border-radius:8px;margin-left:.3rem;}
.gal-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:.6rem;margin-top:.8rem;}
.gal-it{position:relative;aspect-ratio:1;border-radius:5px;overflow:hidden;}
.gal-it img{width:100%;height:100%;object-fit:cover;}
.gal-it .dx{position:absolute;top:3px;right:3px;background:rgba(0,0,0,.75);border:none;color:#fff;border-radius:50%;width:20px;height:20px;cursor:pointer;font-size:.75rem;line-height:20px;text-align:center;}
.rek-row{display:grid;grid-template-columns:30px 100px 1fr 1fr auto;gap:.6rem;align-items:center;margin-bottom:.6rem;background:rgba(255,255,255,.03);padding:.6rem .8rem;border-radius:5px;border:1px solid var(--border);}
.color-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.9rem;margin-top:1rem;}
.color-item{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:7px;padding:.8rem;}
.color-item label{font-size:.72rem;color:#99a;display:block;margin-bottom:.5rem;}
.color-row{display:flex;align-items:center;gap:.6rem;}
.color-hex{background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:4px;padding:.3rem .5rem;color:#ccd;font-family:'Jost';font-size:.82rem;width:100%;}
.preset-grid{display:flex;flex-wrap:wrap;gap:.6rem;margin-top:1rem;}
.preset-btn{display:flex;align-items:center;gap:.5rem;padding:.4rem .8rem;border-radius:5px;cursor:pointer;border:1px solid var(--border);background:rgba(255,255,255,.04);font-size:.78rem;color:#aab;font-family:'Jost';transition:.2s;}
.preset-btn:hover{border-color:var(--gold);color:var(--gold2);}
.preset-dot{width:12px;height:12px;border-radius:50%;flex-shrink:0;}
.uc-list{max-height:300px;overflow-y:auto;display:flex;flex-direction:column;gap:.6rem;}
.uc-item{background:rgba(255,255,255,.03);border-left:2px solid var(--gold);padding:.6rem .8rem;border-radius:0 4px 4px 0;font-size:.8rem;}
.uc-item strong{color:var(--gold2);}
.uc-item span{color:#889;display:block;margin-top:.15rem;}
.link-share{display:flex;align-items:center;gap:.6rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:6px;padding:.6rem .9rem;margin-bottom:.8rem;flex-wrap:wrap;}
.link-share code{flex:1;font-size:.78rem;color:#7aabff;word-break:break-all;}
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;}
.login-box{background:#0d1826;border:1px solid rgba(201,168,76,.2);border-radius:12px;padding:2.2rem;width:100%;max-width:340px;}
.login-box h1{font-family:'Cormorant Garamond',serif;font-size:1.9rem;color:var(--gold);margin-bottom:.25rem;}
.login-box p{color:#556;font-size:.82rem;margin-bottom:1.6rem;}
@media(max-width:680px){.sidebar{display:none;}.g2,.g3{grid-template-columns:1fr;}.rek-row{grid-template-columns:30px 1fr;}}
</style>
</head>
<body>
<?php if(!$ok): ?>
<div class="login-wrap">
  <div class="login-box">
    <h1>⚙ Admin</h1>
    <p><?= $isInCustomer ? '📁 Customer: <strong style="color:var(--gold2)">' . htmlspecialchars($customerSlug) . '</strong>' : 'Template Admin' ?></p>
    <?php if(!empty($loginErr)):?><div class="msg-bar msg-err"><?=$loginErr?></div><?php endif;?>
    <form method="POST"><input type="hidden" name="action" value="login">
      <div class="fg"><label>Password</label><input type="password" name="password" autofocus required></div>
      <button type="submit" class="btn btn-g" style="width:100%">Masuk →</button>
    </form>
    <?php if($isInCustomer):?>
    <div style="margin-top:1rem;text-align:center;">
      <a href="<?=$dashboardUrl?>" style="font-size:.75rem;color:#556;text-decoration:none;">← Kembali ke Dashboard</a>
    </div>
    <?php endif;?>
  </div>
</div>
<?php else: ?>
<div class="topbar">
  <div class="topbar-left">
    <?php if($isInCustomer):?>
    <a href="<?=$dashboardUrl?>" class="back-btn">← Dashboard</a>
    <?php endif;?>
    <h1>⚙ Admin Panel <span class="topbar-customer"><?= $isInCustomer ? '— '.htmlspecialchars($namaCustomer) : '(Template)' ?></span></h1>
  </div>
  <div class="t-btns">
    <a href="index.php" target="_blank" class="tbtn tbtn-g">👁 Lihat Undangan</a>
    <a href="?logout" class="tbtn tbtn-d">Keluar</a>
  </div>
</div>
<div class="layout">
<nav class="sidebar">
  <?php if($isInCustomer):?>
  <div style="padding:.8rem 1.1rem;border-bottom:1px solid rgba(201,168,76,.1);margin-bottom:.5rem;">
    <div style="font-size:.65rem;color:#445;letter-spacing:.1em;">CUSTOMER</div>
    <div style="font-size:.88rem;color:var(--gold2);font-family:'Cormorant Garamond',serif;font-style:italic;margin-top:.2rem;"><?=htmlspecialchars($namaCustomer)?></div>
  </div>
  <?php endif;?>
  <div class="sb-hd">Konten</div>
  <a href="#sec-mempelai">👥 Mempelai</a>
  <a href="#sec-acara">📅 Acara</a>
  <a href="#sec-teks">📝 Teks & Ayat</a>
  <a href="#sec-gift">💳 Gift & Rekening</a>
  <div class="sb-hd">Tampilan</div>
  <a href="#sec-theme">🎨 Tema Warna</a>
  <a href="#sec-backgrounds">🖼 Background</a>
  <a href="#sec-foto">🤵 Foto Mempelai</a>
  <a href="#sec-galeri">📷 Galeri</a>
  <a href="#sec-musik">🎵 Musik</a>
  <div class="sb-hd">Lainnya</div>
  <a href="#sec-link">🔗 Link Undangan</a>
  <a href="#sec-ucapan">💬 Ucapan</a>
  <a href="#sec-pw">🔒 Password</a>
</nav>
<div class="main">
<?php if($msg):?><div class="msg-bar <?=$msgType==='err'?'msg-err':'msg-ok'?>"><?=$msg?></div><?php endif;?>

<!-- KONTEN -->
<form method="POST" id="fMain"><input type="hidden" name="action" value="save_content">

<div class="card anc" id="sec-mempelai">
  <div class="card-title">👥 Data Mempelai</div>
  <p style="font-size:.78rem;color:#667;margin-bottom:1rem;">Mempelai Pria</p>
  <div class="g2">
    <div class="fg"><label>Nama Panggilan</label><input type="text" name="pria_panggilan" value="<?=htmlspecialchars($cfg['pria']['nama_panggilan']??'')?>"></div>
    <div class="fg"><label>Nama Lengkap</label><input type="text" name="pria_lengkap" value="<?=htmlspecialchars($cfg['pria']['nama_lengkap']??'')?>"></div>
    <div class="fg"><label>Nama Ayah</label><input type="text" name="pria_ayah" value="<?=htmlspecialchars($cfg['pria']['nama_ayah']??'')?>"></div>
    <div class="fg"><label>Nama Ibu</label><input type="text" name="pria_ibu" value="<?=htmlspecialchars($cfg['pria']['nama_ibu']??'')?>"></div>
  </div>
  <p style="font-size:.78rem;color:#667;margin:.5rem 0 1rem;">Mempelai Wanita</p>
  <div class="g2">
    <div class="fg"><label>Nama Panggilan</label><input type="text" name="wanita_panggilan" value="<?=htmlspecialchars($cfg['wanita']['nama_panggilan']??'')?>"></div>
    <div class="fg"><label>Nama Lengkap</label><input type="text" name="wanita_lengkap" value="<?=htmlspecialchars($cfg['wanita']['nama_lengkap']??'')?>"></div>
    <div class="fg"><label>Nama Ayah</label><input type="text" name="wanita_ayah" value="<?=htmlspecialchars($cfg['wanita']['nama_ayah']??'')?>"></div>
    <div class="fg"><label>Nama Ibu</label><input type="text" name="wanita_ibu" value="<?=htmlspecialchars($cfg['wanita']['nama_ibu']??'')?>"></div>
  </div>
</div>

<div class="card anc" id="sec-acara">
  <div class="card-title">📅 Detail Acara</div>
  <label class="cb-row" style="margin-bottom:.8rem;"><input type="checkbox" name="akad_aktif" <?=!empty($cfg['akad']['aktif'])?'checked':''?>> Tampilkan Akad Nikah</label>
  <p style="font-size:.78rem;color:var(--gold2);margin-bottom:.6rem;">Akad Nikah</p>
  <div class="g3">
    <div class="fg"><label>Hari</label><input type="text" name="akad_hari" value="<?=htmlspecialchars($cfg['akad']['hari']??'')?>"></div>
    <div class="fg"><label>Tanggal</label><input type="text" name="akad_tanggal" value="<?=htmlspecialchars($cfg['akad']['tanggal']??'')?>"></div>
    <div class="fg"><label>Jam Mulai</label><input type="text" name="akad_jam" value="<?=htmlspecialchars($cfg['akad']['jam']??'')?>"></div>
    <div class="fg"><label>Jam Selesai</label><input type="text" name="akad_selesai" value="<?=htmlspecialchars($cfg['akad']['selesai']??'')?>"></div>
    <div class="fg" style="grid-column:span 2"><label>Lokasi</label><input type="text" name="akad_lokasi" value="<?=htmlspecialchars($cfg['akad']['lokasi']??'')?>"></div>
    <div class="fg" style="grid-column:span 3"><label>Link Google Maps</label><input type="text" name="akad_gmaps" value="<?=htmlspecialchars($cfg['akad']['gmaps']??'')?>"></div>
  </div>
  <label class="cb-row" style="margin:.8rem 0 .6rem;"><input type="checkbox" name="resepsi_aktif" <?=!empty($cfg['resepsi']['aktif'])?'checked':''?>> Tampilkan Resepsi</label>
  <p style="font-size:.78rem;color:var(--gold2);margin-bottom:.6rem;">Resepsi</p>
  <div class="g3">
    <div class="fg"><label>Hari</label><input type="text" name="resepsi_hari" value="<?=htmlspecialchars($cfg['resepsi']['hari']??'')?>"></div>
    <div class="fg"><label>Tanggal</label><input type="text" name="resepsi_tanggal" value="<?=htmlspecialchars($cfg['resepsi']['tanggal']??'')?>"></div>
    <div class="fg"><label>Jam Mulai</label><input type="text" name="resepsi_jam" value="<?=htmlspecialchars($cfg['resepsi']['jam']??'')?>"></div>
    <div class="fg"><label>Jam Selesai</label><input type="text" name="resepsi_selesai" value="<?=htmlspecialchars($cfg['resepsi']['selesai']??'')?>"></div>
    <div class="fg" style="grid-column:span 2"><label>Lokasi</label><input type="text" name="resepsi_lokasi" value="<?=htmlspecialchars($cfg['resepsi']['lokasi']??'')?>"></div>
    <div class="fg" style="grid-column:span 3"><label>Link Google Maps</label><input type="text" name="resepsi_gmaps" value="<?=htmlspecialchars($cfg['resepsi']['gmaps']??'')?>"></div>
  </div>
  <div class="fg"><label>Target Countdown (contoh: 2025-06-15T08:00:00)</label><input type="text" name="tanggal_countdown" value="<?=htmlspecialchars($cfg['tanggal_countdown']??'')?>"></div>
</div>

<div class="card anc" id="sec-teks">
  <div class="card-title">📝 Teks & Ayat</div>
  <div class="fg"><label>Ayat Al-Quran</label><textarea name="ayat" rows="3"><?=htmlspecialchars($cfg['ayat']??'')?></textarea></div>
  <div class="fg"><label>Sumber Ayat</label><input type="text" name="sumber_ayat" value="<?=htmlspecialchars($cfg['sumber_ayat']??'')?>"></div>
  <div class="fg"><label>Teks Ucapan Pembuka</label><textarea name="ucapan_pembuka" rows="2"><?=htmlspecialchars($cfg['ucapan_pembuka']??'')?></textarea></div>
  <div class="fg"><label>Teks Footer</label><textarea name="footer_text" rows="2"><?=htmlspecialchars($cfg['footer_text']??'')?></textarea></div>
</div>

<div class="card anc" id="sec-gift">
  <div class="card-title">💳 Wedding Gift & Rekening</div>
  <label class="cb-row" style="margin-bottom:1.1rem;">
    <input type="checkbox" name="show_gift" <?=!empty($cfg['show_gift'])?'checked':''?>>
    <span>Tampilkan section Wedding Gift di undangan</span>
  </label>
  <div id="rek-list">
    <?php foreach(($cfg['rekening']??[]) as $i=>$r): ?>
    <div class="rek-row">
      <div><input type="checkbox" name="rek_aktif[<?=$i?>]" <?=!empty($r['aktif'])?'checked':''?> style="accent-color:var(--gold);width:15px;height:15px;"></div>
      <div class="fg" style="margin:0"><input type="text" name="rek_bank[]" value="<?=htmlspecialchars($r['bank'])?>" placeholder="Bank"></div>
      <div class="fg" style="margin:0"><input type="text" name="rek_nama[]" value="<?=htmlspecialchars($r['nama'])?>" placeholder="Nama penerima"></div>
      <div class="fg" style="margin:0"><input type="text" name="rek_nomor[]" value="<?=htmlspecialchars($r['nomor'])?>" placeholder="Nomor rekening"></div>
      <button type="button" class="btn btn-r btn-sm" onclick="this.closest('.rek-row').remove()">✕</button>
    </div>
    <?php endforeach;?>
  </div>
  <button type="button" class="btn btn-o btn-sm" onclick="addRek()" style="margin-top:.5rem;">+ Tambah Rekening</button>
</div>

<div style="text-align:right;margin-bottom:1.5rem;">
  <button type="submit" class="btn btn-g" style="padding:.6rem 1.8rem;font-size:.82rem;">💾 Simpan Semua Perubahan</button>
</div>
</form>

<!-- TEMA -->
<div class="card anc" id="sec-theme">
  <div class="card-title">🎨 Tema Warna</div>
  <div class="preset-grid">
    <?php foreach($presets as $pr): ?>
    <button type="button" class="preset-btn" onclick="applyPreset('<?=$pr['p']?>','<?=$pr['s']?>','<?=$pr['a']?>','<?=$pr['a2']?>','<?=$pr['t']?>','<?=$pr['o']?>')">
      <span class="preset-dot" style="background:<?=$pr['a']?>"></span><?=$pr['name']?>
    </button>
    <?php endforeach;?>
  </div>
  <form method="POST" style="margin-top:1.2rem;"><input type="hidden" name="action" value="save_theme">
  <div class="color-grid">
    <?php $colorFields=['color_primary'=>'Warna Latar Utama','color_secondary'=>'Warna Latar Sekunder','color_accent'=>'Aksen (Gold)','color_accent2'=>'Aksen Terang','color_text'=>'Warna Teks'];
    foreach($colorFields as $key=>$label): $val=$t[$key]??'#ffffff'; ?>
    <div class="color-item">
      <label><?=$label?></label>
      <div class="color-row">
        <input type="color" id="cp_<?=$key?>" value="<?=$val?>" oninput="syncHex(this,'hex_<?=$key?>')">
        <input type="text" class="color-hex" id="hex_<?=$key?>" name="<?=$key?>" value="<?=$val?>" oninput="syncColor(this,'cp_<?=$key?>')">
      </div>
    </div>
    <?php endforeach;?>
    <div class="color-item" style="grid-column:span 2"><label>Overlay Gelap (rgba)</label>
      <input type="text" name="color_overlay" value="<?=htmlspecialchars($t['color_overlay']??'rgba(8,12,22,0.78)')?>" style="width:100%;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:4px;padding:.4rem .7rem;color:#ccd;font-family:'Jost';font-size:.82rem;">
    </div>
  </div>
  <div style="display:flex;gap:.7rem;margin-top:1rem;">
    <button type="submit" class="btn btn-g">💾 Simpan Tema</button>
  </div>
  </form>
  <form method="POST" style="margin-top:.8rem;display:inline;"><input type="hidden" name="action" value="reset_theme">
    <button type="submit" class="btn btn-r btn-sm" onclick="return confirm('Reset tema?')">↺ Reset Default</button>
  </form>
</div>

<!-- BACKGROUNDS -->
<div class="card anc" id="sec-backgrounds">
  <div class="card-title">🖼 Background Setiap Section</div>
  <p style="font-size:.78rem;color:#667;margin-bottom:1rem;">Rekomendasi: 1920×1080px. Kosong = pakai warna tema.</p>
  <div class="bg-grid">
    <?php foreach($sectionLabels as $key=>$label):
      $bp=$cfg['backgrounds'][$key]??'';$has=$bp&&file_exists(__DIR__.'/'.$bp);?>
    <div class="bg-item">
      <?php if($has):?><img src="<?=htmlspecialchars($bp).'?t='.time()?>" class="bg-prev" alt="">
      <?php else:?><div class="bg-ph">🖼</div><?php endif;?>
      <div class="bg-body">
        <div class="bg-name"><?=$label?><?php if($has):?><span class="tag-on">Foto</span><?php else:?><span class="tag-off">Default</span><?php endif;?></div>
        <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:.4rem;">
          <input type="hidden" name="action" value="upload_bg">
          <input type="hidden" name="section" value="<?=$key?>">
          <input type="file" name="foto" accept="image/*" required style="font-size:.68rem;">
          <button type="submit" class="btn btn-o btn-sm">Upload</button>
        </form>
        <?php if($has):?>
        <form method="POST" style="margin-top:.4rem;" onsubmit="return confirm('Hapus?')">
          <input type="hidden" name="action" value="delete_bg">
          <input type="hidden" name="section" value="<?=$key?>">
          <button type="submit" class="btn btn-r btn-sm" style="width:100%">Hapus</button>
        </form>
        <?php endif;?>
      </div>
    </div>
    <?php endforeach;?>
  </div>
</div>

<!-- FOTO MEMPELAI -->
<div class="card anc" id="sec-foto">
  <div class="card-title">🤵 Foto Mempelai</div>
  <div class="g2">
    <?php foreach(['pria'=>'Pria','wanita'=>'Wanita'] as $s=>$l):$fp=findPhoto("uploads/mempelai/$s");?>
    <div>
      <p style="font-size:.78rem;color:var(--gold2);margin-bottom:.6rem;"><?=$l?> (600×800px)</p>
      <?php if($fp):?>
        <img src="<?=htmlspecialchars($fp)?>" style="width:100px;height:133px;object-fit:cover;border-radius:5px;display:block;margin-bottom:.6rem;border:1px solid rgba(201,168,76,.2);" alt="">
      <?php else:?>
        <div style="width:100px;height:133px;background:rgba(255,255,255,.04);border:1px dashed rgba(201,168,76,.2);border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:2rem;opacity:.2;margin-bottom:.6rem;">👤</div>
      <?php endif;?>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_mempelai">
        <input type="hidden" name="side" value="<?=$s?>">
        <div class="fg"><input type="file" name="foto" accept="image/*" required></div>
        <button type="submit" class="btn btn-o btn-sm">Upload <?=$l?></button>
      </form>
    </div>
    <?php endforeach;?>
  </div>
</div>

<!-- GALERI -->
<div class="card anc" id="sec-galeri">
  <div class="card-title">📷 Galeri Foto</div>
  <form method="POST" enctype="multipart/form-data" style="display:flex;gap:.6rem;align-items:flex-end;flex-wrap:wrap;">
    <input type="hidden" name="action" value="upload_gallery">
    <div class="fg" style="margin:0;flex:1;min-width:200px;"><label>Tambah Foto</label><input type="file" name="foto" accept="image/*" required></div>
    <button type="submit" class="btn btn-o">+ Tambah</button>
  </form>
  <p style="font-size:.73rem;color:#445;margin:.7rem 0 .5rem;"><?=count($galleryFiles)?> foto</p>
  <div class="gal-grid">
    <?php foreach($galleryFiles as $gf):?>
    <div class="gal-it">
      <img src="<?=htmlspecialchars('uploads/gallery/'.basename($gf)).'?t='.filemtime($gf)?>" alt="">
      <form method="POST"><input type="hidden" name="action" value="delete_gallery"><input type="hidden" name="file" value="<?=basename($gf)?>">
        <button type="submit" class="dx" onclick="return confirm('Hapus?')">×</button>
      </form>
    </div>
    <?php endforeach;?>
  </div>
</div>

<!-- MUSIK -->
<div class="card anc" id="sec-musik">
  <div class="card-title">🎵 Musik Latar</div>
  <form method="POST" enctype="multipart/form-data" style="display:flex;gap:.6rem;align-items:flex-end;flex-wrap:wrap;">
    <input type="hidden" name="action" value="upload_musik">
    <div class="fg" style="margin:0;flex:1;min-width:200px;"><label>Upload MP3/OGG/WAV</label><input type="file" name="musik" accept="audio/*" required></div>
    <button type="submit" class="btn btn-o">Upload</button>
  </form>
  <?php if($musikFile):?><audio controls style="width:100%;margin-top:.9rem;opacity:.75;"><source src="<?=htmlspecialchars($musikFile)?>"></audio><?php endif;?>
</div>

<!-- LINK UNDANGAN -->
<div class="card anc" id="sec-link">
  <div class="card-title">🔗 Link Undangan</div>
  <p style="font-size:.78rem;color:#667;margin-bottom:.9rem;">Tambahkan <code style="background:rgba(255,255,255,.07);padding:.1rem .4rem;border-radius:3px;">?to=NamaTamu</code> di akhir URL untuk nama personal.</p>
  <?php
  $protocol = (!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!='off')?'https':'http';
  $host = $_SERVER['HTTP_HOST'];
  $baseUrl = $protocol.'://'.$host.dirname($_SERVER['PHP_SELF']);
  ?>
  <div class="link-share">
    <code id="linkBase"><?=htmlspecialchars($baseUrl)?>/index.php</code>
    <button onclick="copyText('linkBase',this)" style="background:none;border:1px solid rgba(100,150,255,.3);color:#7aabff;font-size:.65rem;padding:.2rem .6rem;cursor:pointer;border-radius:3px;">Salin</button>
  </div>
  <div class="link-share">
    <code id="linkSample"><?=htmlspecialchars($baseUrl)?>/index.php?to=Nama+Tamu</code>
    <button onclick="copyText('linkSample',this)" style="background:none;border:1px solid rgba(100,150,255,.3);color:#7aabff;font-size:.65rem;padding:.2rem .6rem;cursor:pointer;border-radius:3px;">Salin</button>
  </div>
</div>

<!-- UCAPAN -->
<div class="card anc" id="sec-ucapan">
  <div class="card-title">💬 Ucapan & RSVP Masuk</div>
  <?php $ul=file_exists(__DIR__.'/data/ucapan.json')?array_reverse(json_decode(file_get_contents(__DIR__.'/data/ucapan.json'),true)?:[]):[];?>
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.7rem;">
    <p style="font-size:.78rem;color:#667;">Total: <strong style="color:var(--gold)"><?=count($ul)?></strong> ucapan</p>
    <?php if($ul):?>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus SEMUA ucapan?')">
      <input type="hidden" name="action" value="clear_ucapan">
      <button type="submit" class="btn btn-r btn-sm">🗑 Hapus Semua Ucapan</button>
    </form>
    <?php endif;?>
  </div>
  <div class="uc-list">
    <?php foreach($ul as $u):?>
    <div class="uc-item">
      <strong><?=htmlspecialchars($u['nama'])?></strong>
      <?php if(!empty($u['hadir'])):?><span style="font-size:.68rem;background:rgba(201,168,76,.1);color:var(--gold);padding:.1rem .4rem;border-radius:7px;margin-left:.3rem;"><?=$u['hadir']==='hadir'?'✅ Hadir':'❌ Tidak hadir'?></span><?php endif;?>
      <span><?=htmlspecialchars($u['ucapan'])?></span>
      <span style="font-size:.68rem;color:#445;"><?=htmlspecialchars($u['waktu']??'')?></span>
    </div>
    <?php endforeach;?>
    <?php if(!$ul):?><p style="color:#445;font-size:.8rem;">Belum ada ucapan.</p><?php endif;?>
  </div>
</div>

<!-- PASSWORD -->
<div class="card anc" id="sec-pw">
  <div class="card-title">🔒 Ganti Password Admin</div>
  <form method="POST">
    <input type="hidden" name="action" value="save_password">
    <div class="g2">
      <div class="fg"><label>Password Baru (min. 6 karakter)</label><input type="password" name="new_password" placeholder="Password baru..."></div>
    </div>
    <button type="submit" class="btn btn-g btn-sm">Simpan Password</button>
  </form>
</div>

</div></div>

<script>
function syncHex(p,id){document.getElementById(id).value=p.value;}
function syncColor(h,id){document.getElementById(id).value=h.value;}
function applyPreset(p,s,a,a2,t,o){
  var map={color_primary:p,color_secondary:s,color_accent:a,color_accent2:a2,color_text:t};
  for(var k in map){var pi=document.getElementById('cp_'+k),hi=document.getElementById('hex_'+k);if(pi)pi.value=map[k];if(hi)hi.value=map[k];}
  var ov=document.querySelector('[name=color_overlay]');if(ov)ov.value=o;
}
var rekIdx=<?=count($cfg['rekening']??[])?>;
function addRek(){
  var i=rekIdx++;
  var html='<div class="rek-row">'+
    '<div><input type="checkbox" name="rek_aktif['+i+']" checked style="accent-color:var(--gold);width:15px;height:15px;"></div>'+
    '<div class="fg" style="margin:0"><input type="text" name="rek_bank[]" placeholder="Bank"></div>'+
    '<div class="fg" style="margin:0"><input type="text" name="rek_nama[]" placeholder="Nama penerima"></div>'+
    '<div class="fg" style="margin:0"><input type="text" name="rek_nomor[]" placeholder="Nomor rekening"></div>'+
    '<button type="button" class="btn btn-r btn-sm" onclick="this.closest(\'.rek-row\').remove()">✕</button>'+
  '</div>';
  document.getElementById('rek-list').insertAdjacentHTML('beforeend',html);
}
function copyText(id,btn){
  var t=document.getElementById(id).textContent;
  navigator.clipboard.writeText(t).then(function(){var o=btn.textContent;btn.textContent='✓ Tersalin!';setTimeout(function(){btn.textContent=o;},2000);});
}
document.querySelectorAll('.sidebar a').forEach(function(a){
  a.addEventListener('click',function(){document.querySelectorAll('.sidebar a').forEach(x=>x.classList.remove('act'));this.classList.add('act');});
});
</script>
<?php endif;?>
</body></html>
