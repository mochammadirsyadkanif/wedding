<?php
require __DIR__ . '/helpers.php';
$cfg = getSettings();
$t   = $cfg['theme'] ?? [];

$fotoPria     = findPhoto('uploads/mempelai/pria');
$fotoWanita   = findPhoto('uploads/mempelai/wanita');
$musikFile    = findMusik();
$galleryFiles = glob(__DIR__ . '/uploads/gallery/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [];
$namaTamu     = htmlspecialchars($_GET['to'] ?? '');

$cp  = $t['color_primary']   ?? '#0d1117';
$cs  = $t['color_secondary']  ?? '#161b22';
$ca  = $t['color_accent']     ?? '#c9a84c';
$ca2 = $t['color_accent2']    ?? '#e8c97a';
$ct  = $t['color_text']       ?? '#f0e6cc';
$ov  = $t['color_overlay']    ?? 'rgba(8,12,22,0.78)';

$showGift = !empty($cfg['show_gift']);
$rekeningAktif = array_filter($cfg['rekening'] ?? [], fn($r) => !empty($r['aktif']));

// RGB breakdown helpers

$ca_rgb  = rgb($ca);
$ct_rgb  = rgb($ct);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>The Wedding of <?=htmlspecialchars($cfg['pria']['nama_panggilan']??'')?> &amp; <?=htmlspecialchars($cfg['wanita']['nama_panggilan']??'')?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400;1,500;1,700&family=Cinzel+Decorative:wght@400;700&family=Cinzel:wght@400;500;600&family=Lato:wght@200;300;400&display=swap" rel="stylesheet">

<style>
/* ══════════════════════════════════════════
   VARIABLES & RESET
══════════════════════════════════════════ */
:root{
  --cp:<?=$cp?>;
  --cs:<?=$cs?>;
  --ca:<?=$ca?>;
  --ca2:<?=$ca2?>;
  --ct:<?=$ct?>;
  --ov:<?=$ov?>;
  --ca-rgb:<?=$ca_rgb?>;
  --ct-rgb:<?=$ct_rgb?>;
  --rose:#c97a8a;
  --rose2:#e8a0b0;
  --leaf:#3d7a4a;
  --leaf2:#5aaa6a;
}
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent;}
html{scroll-behavior:smooth;}
body{font-family:'Lato',sans-serif;background:var(--cp);color:var(--ct);overflow-x:hidden;}

/* ══════════════════════════════════════════
   FIXED FLOWER CANVAS (always on top layer)
══════════════════════════════════════════ */
#flower-canvas{
  position:fixed;top:0;left:0;width:100%;height:100%;
  pointer-events:none;z-index:1;opacity:.7;
}

/* ══════════════════════════════════════════
   FLOATING FLOWER PETALS (DOM elements)
══════════════════════════════════════════ */
.petal-float{
  position:fixed;pointer-events:none;z-index:2;
  font-size:1.2rem;opacity:0;
  animation:petalDrift linear infinite;
}

/* ══════════════════════════════════════════
   CORNER FLORAL ORNAMENTS
══════════════════════════════════════════ */
.corner-floral{
  position:fixed;pointer-events:none;z-index:3;
  width:180px;height:180px;opacity:.18;
}
.corner-floral.tl{top:0;left:0;}
.corner-floral.tr{top:0;right:0;transform:scaleX(-1);}
.corner-floral.bl{bottom:0;left:0;transform:scaleY(-1);}
.corner-floral.br{bottom:0;right:0;transform:scale(-1,-1);}

/* ══════════════════════════════════════════
   OPENER / COVER SCREEN
══════════════════════════════════════════ */
#opener{
  position:fixed;inset:0;z-index:9999;
  background:radial-gradient(ellipse at 50% 30%,#1a0a2e 0%,var(--cp) 70%);
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.2rem;
  transition:opacity 1.2s cubic-bezier(.4,0,.2,1),visibility 1.2s;
  overflow:hidden;
}
#opener.hidden{opacity:0;visibility:hidden;pointer-events:none;}
#opener-flowers{position:absolute;inset:0;pointer-events:none;}
.op-floral-top{
  position:absolute;top:-20px;left:50%;transform:translateX(-50%);
  width:420px;height:200px;opacity:.55;
  animation:floatY 4s ease-in-out infinite;
}
.op-floral-bl{position:absolute;bottom:-10px;left:-20px;width:240px;opacity:.35;animation:floatY 5s ease-in-out infinite .8s;}
.op-floral-br{position:absolute;bottom:-10px;right:-20px;width:240px;opacity:.35;transform:scaleX(-1);animation:floatY 5s ease-in-out infinite 1.2s;}

.op-bismillah{
  font-family:'Cinzel';font-size:.62rem;letter-spacing:.42em;
  color:rgba(var(--ca-rgb),.6);margin-bottom:.3rem;
  animation:fadeUp .7s .2s both;
}
.op-line-h{width:70px;height:1px;background:linear-gradient(to right,transparent,var(--ca),transparent);}
.op-names{
  font-family:'Playfair Display',serif;
  font-size:clamp(3rem,11vw,6rem);
  font-weight:400;font-style:italic;
  color:var(--ca2);text-align:center;line-height:.95;
  text-shadow:0 0 60px rgba(var(--ca-rgb),.35);
  animation:shimmer 4s ease-in-out infinite, fadeUp .9s .4s both;
}
.op-amp-big{display:block;font-size:.55em;color:var(--rose2);font-style:normal;}
.op-undangan{font-family:'Cinzel';font-size:.65rem;letter-spacing:.3em;color:rgba(var(--ct-rgb),.45);animation:fadeUp .7s .6s both;}
.op-tamu-wrap{font-family:'Lato';font-size:.82rem;color:rgba(var(--ct-rgb),.5);text-align:center;font-style:italic;animation:fadeUp .7s .7s both;}
.op-tamu-wrap strong{color:var(--ca2);font-style:normal;display:block;font-size:1.1rem;font-family:'Playfair Display',serif;}
.btn-open{
  margin-top:.8rem;padding:1rem 2.8rem;
  font-family:'Cinzel';font-size:.68rem;letter-spacing:.32em;
  background:transparent;color:var(--ca);
  border:1px solid var(--ca);cursor:pointer;
  position:relative;overflow:hidden;
  transition:all .4s;
  animation:fadeUp .8s .9s both;
}
.btn-open::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,var(--ca),var(--ca2));
  transform:translateX(-100%);transition:transform .45s cubic-bezier(.4,0,.2,1);
}
.btn-open:hover::before{transform:translateX(0);}
.btn-open span{position:relative;z-index:1;transition:color .45s;}
.btn-open:hover span{color:var(--cp);}

/* ══════════════════════════════════════════
   FLOATING NAV
══════════════════════════════════════════ */
.fnav{
  position:fixed;top:1rem;left:50%;transform:translateX(-50%);
  z-index:200;display:none;gap:.2rem;
  background:rgba(13,17,23,.75);
  border:1px solid rgba(var(--ca-rgb),.2);
  padding:.3rem .4rem;backdrop-filter:blur(20px);
}
.fnav a{
  font-family:'Cinzel';font-size:.52rem;letter-spacing:.14em;
  color:rgba(var(--ct-rgb),.55);
  padding:.32rem .55rem;text-decoration:none;transition:.2s;white-space:nowrap;
}
.fnav a:hover,.fnav a.active{color:var(--ca);}

/* ══════════════════════════════════════════
   MUSIC BUTTON
══════════════════════════════════════════ */
.mus-btn{
  position:fixed;bottom:1.5rem;right:1.5rem;z-index:200;
  width:46px;height:46px;border-radius:50%;
  background:rgba(var(--ca-rgb),.12);
  border:1px solid rgba(var(--ca-rgb),.45);
  color:var(--ca);font-size:1.05rem;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  backdrop-filter:blur(14px);transition:.3s;
}
.mus-btn:hover{background:var(--ca);color:var(--cp);}
.mus-btn.playing{animation:spin 6s linear infinite;}

/* ══════════════════════════════════════════
   SECTION WRAPPER
══════════════════════════════════════════ */
.sw{position:relative;background-size:cover;background-position:center;overflow:hidden;}
.sw::before{content:'';position:absolute;inset:0;background:var(--ov);pointer-events:none;z-index:0;}
/* floral side borders on sections */
.sw::after{
  content:'';position:absolute;inset:0;
  background:
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='300'%3E%3Ccircle cx='30' cy='30' r='8' fill='none' stroke='%23c9a84c' stroke-width='.5' opacity='.15'/%3E%3Ccircle cx='30' cy='80' r='5' fill='none' stroke='%23c9a84c' stroke-width='.5' opacity='.1'/%3E%3Ccircle cx='30' cy='130' r='9' fill='none' stroke='%23c9a84c' stroke-width='.5' opacity='.12'/%3E%3C/svg%3E")
    left top repeat-y,
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='300'%3E%3Ccircle cx='30' cy='30' r='8' fill='none' stroke='%23c9a84c' stroke-width='.5' opacity='.15'/%3E%3Ccircle cx='30' cy='80' r='5' fill='none' stroke='%23c9a84c' stroke-width='.5' opacity='.1'/%3E%3Ccircle cx='30' cy='130' r='9' fill='none' stroke='%23c9a84c' stroke-width='.5' opacity='.12'/%3E%3C/svg%3E")
    right top repeat-y;
  pointer-events:none;z-index:0;
}
.si{position:relative;z-index:1;max-width:780px;margin:0 auto;padding:6rem 2rem;}

/* ══════════════════════════════════════════
   TYPOGRAPHY
══════════════════════════════════════════ */
.stag{
  font-family:'Cinzel';font-size:.6rem;letter-spacing:.44em;
  color:var(--ca);text-align:center;display:block;
  margin-bottom:.6rem;text-transform:uppercase;
}
.stitle{
  font-family:'Playfair Display',serif;
  font-size:clamp(2rem,6vw,3rem);
  font-weight:400;font-style:italic;
  text-align:center;color:var(--ca2);line-height:1.1;margin-bottom:.7rem;
}
.divider{display:flex;align-items:center;gap:1rem;justify-content:center;margin:1rem 0 2.2rem;}
.dl{width:60px;height:1px;background:linear-gradient(to right,transparent,var(--ca));}
.dr{width:60px;height:1px;background:linear-gradient(to left,transparent,var(--ca));}
.dd{font-size:.9rem;color:var(--ca);}

/* Inline floral dividers */
.floral-divider{
  display:flex;align-items:center;justify-content:center;
  margin:1.4rem 0;gap:.7rem;opacity:.6;
}
.floral-divider svg{width:120px;height:30px;animation:floatY 3s ease-in-out infinite;}
.floral-divider svg:nth-child(3){animation-delay:.5s;}

/* ══════════════════════════════════════════
   COVER SECTION
══════════════════════════════════════════ */
#cover .sw{min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;}
.cover-content{position:relative;z-index:2;padding:2rem;}
.c-label{font-family:'Cinzel';font-size:.63rem;letter-spacing:.4em;color:var(--ca2);opacity:0;animation:fadeUp .8s .3s forwards;}
.c-names{
  font-family:'Playfair Display',serif;
  font-size:clamp(4.5rem,15vw,10rem);
  font-weight:400;font-style:italic;color:#fff;
  line-height:.9;text-shadow:0 4px 50px rgba(0,0,0,.6),0 0 80px rgba(var(--ca-rgb),.15);
  opacity:0;animation:fadeUp .9s .55s forwards;
}
.c-amp{display:block;color:var(--ca);font-size:.5em;animation:pulseGlow 3s ease-in-out infinite 1s;}
.c-date{
  font-family:'Cinzel';font-size:.68rem;letter-spacing:.3em;
  color:rgba(var(--ca-rgb),.75);margin-top:1.8rem;
  opacity:0;animation:fadeUp .8s .85s forwards;
}
.c-sub{
  font-family:'Lato';font-size:.8rem;letter-spacing:.15em;
  color:rgba(var(--ct-rgb),.4);margin-top:.5rem;
  opacity:0;animation:fadeUp .8s 1s forwards;
}
/* Animated rose bunch in cover */
.cover-roses{
  position:absolute;inset:0;pointer-events:none;z-index:1;overflow:hidden;
}
.cover-rose-item{
  position:absolute;opacity:0;
  animation:roseAppear ease-in-out forwards, floatSway ease-in-out infinite;
}
/* Scroll indicator */
.c-scroll{
  position:absolute;bottom:2rem;left:50%;transform:translateX(-50%);
  display:flex;flex-direction:column;align-items:center;gap:.4rem;
  cursor:pointer;opacity:0;animation:fadeUp .8s 1.4s forwards;
  z-index:2;
}
.scroll-mouse{
  width:22px;height:34px;border:1px solid rgba(var(--ca-rgb),.5);border-radius:11px;
  position:relative;
}
.scroll-mouse::before{
  content:'';position:absolute;top:6px;left:50%;transform:translateX(-50%);
  width:3px;height:7px;background:var(--ca);border-radius:2px;
  animation:scrollBounce 2s ease-in-out infinite;
}
.scroll-txt{font-family:'Cinzel';font-size:.52rem;letter-spacing:.22em;color:rgba(var(--ca-rgb),.5);}

/* ══════════════════════════════════════════
   BISMILLAH
══════════════════════════════════════════ */
#bismillah{text-align:center;}
.bismi-arab{
  font-size:2.2rem;color:var(--ca);
  line-height:1.6;margin-bottom:1rem;
  animation:shimmer 4s ease-in-out infinite;
  text-shadow:0 0 30px rgba(var(--ca-rgb),.4);
}
.ayat-text{
  font-family:'Lato';font-size:.95rem;font-style:italic;
  color:rgba(var(--ct-rgb),.68);line-height:2;
  max-width:520px;margin:0 auto;font-weight:300;
}
.ayat-src{font-family:'Cinzel';font-size:.62rem;letter-spacing:.2em;color:var(--ca);margin-top:.9rem;}
.tamu-box{
  margin-top:2.2rem;padding:1.5rem 2rem;
  border:1px solid rgba(var(--ca-rgb),.2);
  display:inline-block;
  background:rgba(var(--ca-rgb),.03);
  position:relative;
}
.tamu-box::before,.tamu-box::after{
  content:'❧';position:absolute;color:var(--ca);opacity:.4;font-size:1.2rem;
}
.tamu-box::before{top:.4rem;left:.7rem;}
.tamu-box::after{bottom:.4rem;right:.7rem;transform:rotate(180deg);}
.tamu-box p{font-family:'Cinzel';font-size:.55rem;letter-spacing:.25em;color:rgba(var(--ct-rgb),.45);}
.tamu-box strong{font-family:'Playfair Display',serif;font-size:1.9rem;font-style:italic;color:var(--ca2);display:block;margin-top:.3rem;}

/* ══════════════════════════════════════════
   MEMPELAI
══════════════════════════════════════════ */
.mp-grid{display:grid;grid-template-columns:1fr 80px 1fr;gap:2.5rem;align-items:center;margin-top:3rem;}
.mp-card{text-align:center;}
.mp-frame-wrap{position:relative;width:160px;height:210px;margin:0 auto 1.2rem;}
/* Animated floral ring around photo */
.mp-floral-ring{
  position:absolute;inset:-30px;
  animation:spin 20s linear infinite;
  opacity:.45;
}
.mp-floral-ring-rev{
  position:absolute;inset:-20px;
  animation:spin 15s linear infinite reverse;
  opacity:.25;
}
.mp-frame{
  position:absolute;inset:0;
  border:1px solid rgba(var(--ca-rgb),.3);
  overflow:hidden;
}
.mp-frame img{width:100%;height:100%;object-fit:cover;filter:sepia(.1) contrast(1.04);}
.mp-frame .nopic{width:100%;height:100%;background:rgba(255,255,255,.04);display:flex;align-items:center;justify-content:center;font-size:3rem;opacity:.2;}
.mp-nick{font-family:'Playfair Display',serif;font-size:2rem;font-style:italic;color:var(--ca2);margin-top:.3rem;}
.mp-full{font-family:'Cinzel';font-size:.6rem;letter-spacing:.14em;color:rgba(var(--ct-rgb),.5);margin:.25rem 0;}
.mp-ortu{font-family:'Lato';font-size:.83rem;color:rgba(var(--ct-rgb),.5);font-style:italic;line-height:1.8;font-weight:300;}
.amp-center{
  font-family:'Playfair Display',serif;font-size:5rem;font-weight:400;
  font-style:italic;color:var(--ca);opacity:.5;text-align:center;
  animation:pulseGlow 4s ease-in-out infinite;
}

/* ══════════════════════════════════════════
   COUNTDOWN
══════════════════════════════════════════ */
#countdown{text-align:center;}
.cd-wrap{display:flex;justify-content:center;gap:1.5rem;flex-wrap:wrap;margin-top:2.5rem;}
.cd-box{min-width:80px;position:relative;}
.cd-box::before{
  content:'';position:absolute;inset:-1px;
  border:1px solid rgba(var(--ca-rgb),.2);
  animation:framePulse 3s ease-in-out infinite;
}
.cd-num{
  font-family:'Playfair Display',serif;
  font-size:clamp(3rem,9vw,5rem);font-weight:500;
  color:var(--ca2);line-height:1;display:block;
  transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .15s;
}
.cd-num.flip{transform:translateY(-10px) scale(1.1);opacity:.6;}
.cd-lbl{font-family:'Cinzel';font-size:.52rem;letter-spacing:.22em;color:rgba(var(--ct-rgb),.4);margin-top:.3rem;display:block;}
.cd-sep{font-family:'Playfair Display',serif;font-size:3.5rem;color:var(--ca);opacity:.3;align-self:flex-start;padding-top:.2rem;animation:pulseGlow 2s ease-in-out infinite;}
/* Flower decoration on countdown */
.cd-flowers{display:flex;justify-content:center;gap:1.5rem;margin-top:2rem;opacity:.5;}
.cd-flowers span{font-size:1.5rem;animation:floatY 3s ease-in-out infinite;}
.cd-flowers span:nth-child(2){animation-delay:.5s;}
.cd-flowers span:nth-child(3){animation-delay:1s;}

/* ══════════════════════════════════════════
   ACARA
══════════════════════════════════════════ */
.acara-wrap{display:grid;grid-template-columns:1fr 1fr;gap:1.8rem;margin-top:2.5rem;}
.ac-card{
  border:1px solid rgba(var(--ca-rgb),.18);
  padding:2.2rem 1.8rem;text-align:center;
  position:relative;overflow:hidden;
  background:rgba(0,0,0,.18);
  transition:transform .5s cubic-bezier(.34,1.4,.64,1),border-color .3s;
}
.ac-card:hover{transform:translateY(-6px);border-color:rgba(var(--ca-rgb),.5);}
/* Top floral accent line */
.ac-card::before{
  content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);
  width:0;height:2px;
  background:linear-gradient(to right,transparent,var(--ca),var(--ca2),var(--ca),transparent);
  transition:width .6s cubic-bezier(.4,0,.2,1);
}
.ac-card:hover::before{width:100%;}
/* Radial glow */
.ac-card::after{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 50% 0%,rgba(var(--ca-rgb),.07),transparent 70%);
  pointer-events:none;
}
.ac-card-flower{font-size:1.6rem;display:block;margin-bottom:.7rem;animation:floatY 3s ease-in-out infinite;}
.ac-type{font-family:'Cinzel';font-size:.6rem;letter-spacing:.35em;color:var(--ca);margin-bottom:.7rem;}
.ac-hari{font-family:'Playfair Display',serif;font-size:1.8rem;font-style:italic;color:var(--ca2);}
.ac-tgl{font-family:'Cinzel';font-size:.68rem;letter-spacing:.12em;color:rgba(var(--ct-rgb),.5);margin:.2rem 0;}
.ac-jam{font-family:'Playfair Display',serif;font-size:1.35rem;color:var(--ca2);margin:.5rem 0;}
.ac-lok{font-family:'Lato';font-size:.83rem;color:rgba(var(--ct-rgb),.5);font-style:italic;line-height:1.7;margin:.7rem 0;font-weight:300;}
.btn-maps{
  display:inline-block;margin-top:.9rem;padding:.5rem 1.3rem;
  font-family:'Cinzel';font-size:.58rem;letter-spacing:.22em;
  border:1px solid var(--ca);color:var(--ca);text-decoration:none;
  position:relative;overflow:hidden;transition:color .3s;
}
.btn-maps::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,var(--ca),var(--ca2));
  transform:scaleX(0);transform-origin:left;
  transition:transform .35s cubic-bezier(.4,0,.2,1);
}
.btn-maps:hover::before{transform:scaleX(1);}
.btn-maps:hover{color:var(--cp);}
.btn-maps span{position:relative;z-index:1;}

/* ══════════════════════════════════════════
   GALLERY
══════════════════════════════════════════ */
#gallery{text-align:center;}
.gal-masonry{columns:2;gap:1rem;margin-top:2.5rem;}
.gal-masonry img{
  width:100%;break-inside:avoid;margin-bottom:1rem;display:block;
  filter:sepia(.08);transition:filter .5s,transform .5s,box-shadow .5s;
  cursor:pointer;border:1px solid transparent;
}
.gal-masonry img:hover{
  filter:none;transform:scale(1.03);
  box-shadow:0 15px 50px rgba(0,0,0,.5),0 0 0 1px rgba(var(--ca-rgb),.3);
}

/* ══════════════════════════════════════════
   UCAPAN
══════════════════════════════════════════ */
.wish-form{
  background:rgba(0,0,0,.2);
  border:1px solid rgba(var(--ca-rgb),.15);
  padding:2.2rem;margin-bottom:2.5rem;
  position:relative;
}
.wish-form::before{
  content:'🌸';position:absolute;top:-12px;left:50%;transform:translateX(-50%);
  font-size:1.4rem;background:var(--cp);padding:0 .5rem;
}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;}
.fg{display:flex;flex-direction:column;gap:.35rem;}
.fg label{font-family:'Cinzel';font-size:.58rem;letter-spacing:.22em;color:var(--ca);text-transform:uppercase;}
.fg input,.fg textarea,.fg select{
  background:rgba(255,255,255,.05);border:1px solid rgba(var(--ca-rgb),.18);
  padding:.65rem .95rem;color:var(--ct);font-family:'Lato';font-size:.93rem;
  resize:vertical;transition:.25s;
}
.fg input:focus,.fg textarea:focus,.fg select:focus{
  outline:none;border-color:var(--ca);
  background:rgba(var(--ca-rgb),.06);
  box-shadow:0 0 20px rgba(var(--ca-rgb),.1);
}
.fg select option{background:var(--cs);}
.btn-send{
  width:100%;padding:.88rem;margin-top:.6rem;
  font-family:'Cinzel';font-size:.66rem;letter-spacing:.25em;
  background:linear-gradient(135deg,var(--ca),var(--ca2));
  color:var(--cp);border:none;cursor:pointer;
  transition:opacity .3s,transform .2s,box-shadow .3s;
  position:relative;overflow:hidden;
}
.btn-send::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(255,255,255,.1),transparent);
  transform:translateX(-100%) skewX(-15deg);
  transition:transform .5s;
}
.btn-send:hover::before{transform:translateX(100%) skewX(-15deg);}
.btn-send:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 8px 30px rgba(var(--ca-rgb),.3);}
.btn-send:active{transform:translateY(0);}
.wish-list{display:flex;flex-direction:column;gap:1rem;}
.wi{
  background:rgba(0,0,0,.15);
  border-left:2px solid var(--ca);
  padding:1rem 1.3rem;
  animation:slideIn .5s cubic-bezier(.34,1.2,.64,1);
  position:relative;
}
.wi::after{content:'🌸';position:absolute;right:.8rem;top:.8rem;font-size:.8rem;opacity:.4;}
.wi-nm{font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--ca2);}
.wi-bdg{font-size:.65rem;padding:.1rem .45rem;background:rgba(var(--ca-rgb),.1);color:var(--ca);margin-left:.4rem;font-family:'Lato';}
.wi-tx{font-family:'Lato';font-size:.88rem;color:rgba(var(--ct-rgb),.62);font-style:italic;margin-top:.3rem;font-weight:300;line-height:1.7;}
.wi-tm{font-size:.7rem;color:rgba(var(--ct-rgb),.28);margin-top:.3rem;}

/* ══════════════════════════════════════════
   GIFT
══════════════════════════════════════════ */
#gift{text-align:center;}
.gift-wrap{display:flex;flex-wrap:wrap;gap:1.5rem;justify-content:center;margin-top:2.5rem;}
.gc{
  border:1px solid rgba(var(--ca-rgb),.2);padding:1.8rem 2.2rem;min-width:220px;
  background:rgba(0,0,0,.18);position:relative;overflow:hidden;
  transition:transform .5s cubic-bezier(.34,1.4,.64,1),border-color .3s;
}
.gc::before{content:'🌺';position:absolute;top:.5rem;right:.7rem;font-size:1rem;opacity:.3;animation:floatY 3s ease-in-out infinite;}
.gc::after{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 50% 110%,rgba(var(--ca-rgb),.12),transparent 60%);
  pointer-events:none;
}
.gc:hover{transform:translateY(-6px);border-color:rgba(var(--ca-rgb),.5);}
.gc-bank{font-family:'Cinzel';font-size:.7rem;letter-spacing:.22em;color:var(--ca);margin-bottom:.4rem;}
.gc-nama{font-family:'Lato';font-size:.85rem;color:rgba(var(--ct-rgb),.52);font-style:italic;font-weight:300;}
.gc-nomor{font-family:'Playfair Display',serif;font-size:1.6rem;color:var(--ca2);letter-spacing:.04em;margin:.3rem 0;position:relative;z-index:1;}
.btn-copy{
  display:inline-block;margin-top:.7rem;padding:.4rem 1rem;
  font-family:'Cinzel';font-size:.57rem;letter-spacing:.18em;
  background:transparent;border:1px solid var(--ca);color:var(--ca);
  cursor:pointer;transition:.3s;position:relative;z-index:1;
}
.btn-copy:hover,.btn-copy.copied{background:linear-gradient(135deg,var(--ca),var(--ca2));color:var(--cp);border-color:transparent;}

/* ══════════════════════════════════════════
   FOOTER
══════════════════════════════════════════ */
#footer{text-align:center;}
#footer .si{padding:5rem 2rem;}
.ft-flower-row{display:flex;justify-content:center;gap:.8rem;margin-bottom:1.5rem;font-size:1.3rem;opacity:.5;}
.ft-flower-row span{animation:floatY 3s ease-in-out infinite;}
.ft-flower-row span:nth-child(2){animation-delay:.4s;}
.ft-flower-row span:nth-child(3){animation-delay:.8s;}
.ft-flower-row span:nth-child(4){animation-delay:1.2s;}
.ft-flower-row span:nth-child(5){animation-delay:1.6s;}
.ft-names{font-family:'Playfair Display',serif;font-size:clamp(2.3rem,6vw,3.5rem);font-style:italic;color:var(--ca2);}
.ft-sub{font-family:'Cinzel';font-size:.6rem;letter-spacing:.32em;color:rgba(var(--ct-rgb),.35);margin-top:.6rem;}
.ft-text{font-family:'Lato';font-style:italic;color:rgba(var(--ct-rgb),.45);font-size:.9rem;margin-top:1.3rem;max-width:440px;margin-left:auto;margin-right:auto;line-height:1.9;font-weight:300;}

/* ══════════════════════════════════════════
   LIGHTBOX
══════════════════════════════════════════ */
#lb{position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,.95);display:none;align-items:center;justify-content:center;}
#lb.open{display:flex;animation:fadeIn .3s;}
#lb img{max-width:90vw;max-height:90vh;object-fit:contain;border:1px solid rgba(var(--ca-rgb),.2);animation:zoomIn .3s cubic-bezier(.34,1.4,.64,1);}
#lb-x{position:absolute;top:1.2rem;right:1.5rem;color:#fff;font-size:2rem;cursor:pointer;opacity:.5;transition:.2s;}
#lb-x:hover{opacity:1;}

/* ══════════════════════════════════════════
   REVEAL ANIMATIONS
══════════════════════════════════════════ */
.reveal{opacity:0;transform:translateY(40px);transition:opacity .9s cubic-bezier(.4,0,.2,1),transform .9s cubic-bezier(.4,0,.2,1);}
.reveal.on{opacity:1;transform:none;}
.reveal-l{opacity:0;transform:translateX(-40px);transition:opacity .9s cubic-bezier(.4,0,.2,1),transform .9s cubic-bezier(.4,0,.2,1);}
.reveal-l.on{opacity:1;transform:none;}
.reveal-r{opacity:0;transform:translateX(40px);transition:opacity .9s cubic-bezier(.4,0,.2,1),transform .9s cubic-bezier(.4,0,.2,1);}
.reveal-r.on{opacity:1;transform:none;}
.delay1{transition-delay:.1s;}.delay2{transition-delay:.2s;}.delay3{transition-delay:.35s;}.delay4{transition-delay:.5s;}

/* ══════════════════════════════════════════
   KEYFRAMES
══════════════════════════════════════════ */
@keyframes fadeUp{from{opacity:0;transform:translateY(28px);}to{opacity:1;transform:translateY(0);}}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
@keyframes spin{to{transform:rotate(360deg);}}
@keyframes pulseGlow{
  0%,100%{opacity:.5;text-shadow:0 0 15px rgba(var(--ca-rgb),.2);}
  50%{opacity:1;text-shadow:0 0 40px rgba(var(--ca-rgb),.6);}
}
@keyframes shimmer{
  0%,100%{filter:drop-shadow(0 0 8px rgba(var(--ca-rgb),.2));}
  50%{filter:drop-shadow(0 0 25px rgba(var(--ca-rgb),.6));}
}
@keyframes floatY{0%,100%{transform:translateY(0);}50%{transform:translateY(-9px);}}
@keyframes floatSway{0%,100%{transform:translateY(0) rotate(0deg);}33%{transform:translateY(-12px) rotate(3deg);}66%{transform:translateY(-6px) rotate(-2deg);}}
@keyframes framePulse{0%,100%{opacity:.2;}50%{opacity:.5;}}
@keyframes scrollBounce{0%,100%{transform:translateX(-50%) translateY(0);}50%{transform:translateX(-50%) translateY(8px);}}
@keyframes slideIn{from{opacity:0;transform:translateX(-25px);}to{opacity:1;transform:translateX(0);}}
@keyframes zoomIn{from{transform:scale(.82);opacity:0;}to{transform:scale(1);opacity:1;}}
@keyframes petalDrift{
  0%{transform:translateY(-80px) translateX(0) rotate(0deg);opacity:0;}
  5%{opacity:.9;}
  95%{opacity:.4;}
  100%{transform:translateY(110vh) translateX(var(--drift,40px)) rotate(var(--spin,540deg));opacity:0;}
}
@keyframes roseAppear{from{opacity:0;transform:scale(.5);}to{opacity:var(--target-op,.6);transform:scale(1);}}
@keyframes orbitRing{from{transform:rotate(0deg) translateX(var(--r,80px)) rotate(0deg);}to{transform:rotate(360deg) translateX(var(--r,80px)) rotate(-360deg);}}
@keyframes waveExpand{0%{transform:scale(.8);opacity:.8;}100%{transform:scale(2.5);opacity:0;}}

/* ══════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════ */
@media(max-width:580px){
  .mp-grid{grid-template-columns:1fr;}
  .amp-center{display:none;}
  .acara-wrap{grid-template-columns:1fr;}
  .frow{grid-template-columns:1fr;}
  .fnav{display:none!important;}
  .op-floral-top{width:280px;}
  .corner-floral{width:100px;height:100px;}
}
</style>
</head>
<body>

<!-- ═══════ FLOWER CANVAS (continuous bg animation) ═══════ -->
<canvas id="flower-canvas"></canvas>

<!-- ═══════ CORNER FLORAL ORNAMENTS ═══════ -->
<svg class="corner-floral tl" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg" style="animation:floatY 5s ease-in-out infinite;">
  <!-- Rose branch top-left -->
  <path d="M10 170 Q40 120 90 80 Q130 50 160 20" stroke="<?=$ca?>" stroke-width="1.5" fill="none" opacity=".7"/>
  <path d="M90 80 Q75 55 80 35 Q95 50 90 80" fill="<?=$ca?>" opacity=".5"/>
  <circle cx="80" cy="35" r="12" fill="<?=$ca?>" opacity=".4"/>
  <circle cx="73" cy="28" r="8" fill="<?=$ca2?>" opacity=".5"/>
  <circle cx="88" cy="27" r="7" fill="var(--rose,#c97a8a)" opacity=".6"/>
  <path d="M40 130 Q30 110 35 95 Q48 108 40 130" fill="<?=$ca?>" opacity=".4"/>
  <circle cx="35" cy="95" r="10" fill="<?=$ca?>" opacity=".3"/>
  <path d="M130 45 Q120 25 125 10 Q138 25 130 45" fill="<?=$ca?>" opacity=".4"/>
  <circle cx="125" cy="10" r="9" fill="var(--rose2,#e8a0b0)" opacity=".5"/>
  <!-- Leaves -->
  <ellipse cx="60" cy="110" rx="14" ry="6" fill="#3d7a4a" opacity=".5" transform="rotate(-45 60 110)"/>
  <ellipse cx="110" cy="62" rx="12" ry="5" fill="#3d7a4a" opacity=".4" transform="rotate(-55 110 62)"/>
</svg>
<svg class="corner-floral tr" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg" style="animation:floatY 5s ease-in-out infinite .7s;">
  <path d="M10 170 Q40 120 90 80 Q130 50 160 20" stroke="<?=$ca?>" stroke-width="1.5" fill="none" opacity=".7"/>
  <path d="M90 80 Q75 55 80 35 Q95 50 90 80" fill="<?=$ca?>" opacity=".5"/>
  <circle cx="80" cy="35" r="12" fill="<?=$ca?>" opacity=".4"/>
  <circle cx="73" cy="28" r="8" fill="<?=$ca2?>" opacity=".5"/>
  <circle cx="88" cy="27" r="7" fill="#c97a8a" opacity=".6"/>
  <path d="M40 130 Q30 110 35 95 Q48 108 40 130" fill="<?=$ca?>" opacity=".4"/>
  <circle cx="35" cy="95" r="10" fill="<?=$ca?>" opacity=".3"/>
  <ellipse cx="60" cy="110" rx="14" ry="6" fill="#3d7a4a" opacity=".5" transform="rotate(-45 60 110)"/>
</svg>
<svg class="corner-floral bl" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg" style="animation:floatY 5s ease-in-out infinite 1.4s;">
  <path d="M10 170 Q40 120 90 80 Q130 50 160 20" stroke="<?=$ca?>" stroke-width="1.5" fill="none" opacity=".7"/>
  <path d="M90 80 Q75 55 80 35 Q95 50 90 80" fill="<?=$ca?>" opacity=".5"/>
  <circle cx="80" cy="35" r="12" fill="<?=$ca?>" opacity=".4"/>
  <circle cx="73" cy="28" r="8" fill="#e8c97a" opacity=".5"/>
  <ellipse cx="110" cy="62" rx="12" ry="5" fill="#3d7a4a" opacity=".4" transform="rotate(-55 110 62)"/>
</svg>
<svg class="corner-floral br" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg" style="animation:floatY 5s ease-in-out infinite 2.1s;">
  <path d="M10 170 Q40 120 90 80 Q130 50 160 20" stroke="<?=$ca?>" stroke-width="1.5" fill="none" opacity=".7"/>
  <path d="M90 80 Q75 55 80 35 Q95 50 90 80" fill="<?=$ca?>" opacity=".5"/>
  <circle cx="80" cy="35" r="12" fill="<?=$ca?>" opacity=".4"/>
  <circle cx="88" cy="27" r="7" fill="#c97a8a" opacity=".6"/>
  <ellipse cx="60" cy="110" rx="14" ry="6" fill="#3d7a4a" opacity=".5" transform="rotate(-45 60 110)"/>
</svg>

<!-- ═══════ OPENER ═══════ -->
<div id="opener">
  <div id="opener-flowers">
    <!-- Top center floral bouquet -->
    <svg class="op-floral-top" viewBox="0 0 420 200" fill="none" xmlns="http://www.w3.org/2000/svg">
      <!-- Main stems -->
      <path d="M210 200 Q200 150 185 100 Q170 60 150 30" stroke="#3d7a4a" stroke-width="1.5" fill="none"/>
      <path d="M210 200 Q215 145 225 100 Q235 60 255 30" stroke="#3d7a4a" stroke-width="1.5" fill="none"/>
      <path d="M210 200 Q205 140 200 90 Q198 50 200 20" stroke="#3d7a4a" stroke-width="1.5" fill="none"/>
      <path d="M210 200 Q190 160 160 130 Q130 110 100 105" stroke="#3d7a4a" stroke-width="1.2" fill="none"/>
      <path d="M210 200 Q230 160 260 130 Q290 110 320 105" stroke="#3d7a4a" stroke-width="1.2" fill="none"/>
      <!-- Roses -->
      <!-- Center rose -->
      <circle cx="200" cy="20" r="18" fill="<?=$ca?>" opacity=".7"/>
      <circle cx="194" cy="13" r="12" fill="<?=$ca2?>" opacity=".8"/>
      <circle cx="205" cy="12" r="10" fill="<?=$ca?>" opacity=".6"/>
      <circle cx="200" cy="8" r="7" fill="<?=$ca2?>" opacity=".9"/>
      <!-- Left rose -->
      <circle cx="150" cy="30" r="16" fill="#c97a8a" opacity=".75"/>
      <circle cx="144" cy="23" r="11" fill="#e8a0b0" opacity=".8"/>
      <circle cx="150" cy="20" r="7" fill="#fff" opacity=".3"/>
      <!-- Right rose -->
      <circle cx="255" cy="30" r="16" fill="#c97a8a" opacity=".75"/>
      <circle cx="261" cy="23" r="11" fill="#e8a0b0" opacity=".8"/>
      <circle cx="255" cy="20" r="7" fill="#fff" opacity=".3"/>
      <!-- Side bouquet left -->
      <circle cx="100" cy="105" r="14" fill="<?=$ca?>" opacity=".6"/>
      <circle cx="95" cy="99" r="9" fill="<?=$ca2?>" opacity=".7"/>
      <!-- Side bouquet right -->
      <circle cx="320" cy="105" r="14" fill="<?=$ca?>" opacity=".6"/>
      <circle cx="326" cy="99" r="9" fill="<?=$ca2?>" opacity=".7"/>
      <!-- Leaves -->
      <ellipse cx="172" cy="72" rx="16" ry="7" fill="#3d7a4a" opacity=".6" transform="rotate(-50 172 72)"/>
      <ellipse cx="230" cy="68" rx="16" ry="7" fill="#3d7a4a" opacity=".6" transform="rotate(50 230 68)"/>
      <ellipse cx="130" cy="118" rx="13" ry="5" fill="#5aaa6a" opacity=".5" transform="rotate(-30 130 118)"/>
      <ellipse cx="290" cy="118" rx="13" ry="5" fill="#5aaa6a" opacity=".5" transform="rotate(30 290 118)"/>
      <!-- Small baby's breath -->
      <circle cx="175" cy="50" r="4" fill="white" opacity=".45"/>
      <circle cx="225" cy="48" r="3" fill="white" opacity=".4"/>
      <circle cx="135" cy="75" r="3.5" fill="white" opacity=".38"/>
      <circle cx="275" cy="70" r="3.5" fill="white" opacity=".38"/>
    </svg>
    <!-- Bottom left -->
    <svg class="op-floral-bl" viewBox="0 0 240 180" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0 180 Q60 140 100 100 Q140 60 170 20" stroke="#3d7a4a" stroke-width="1.5" fill="none"/>
      <path d="M50 160 Q40 130 60 110" stroke="#3d7a4a" stroke-width="1" fill="none"/>
      <circle cx="170" cy="20" r="16" fill="<?=$ca?>" opacity=".7"/>
      <circle cx="163" cy="13" r="10" fill="<?=$ca2?>" opacity=".8"/>
      <circle cx="100" cy="100" r="12" fill="#c97a8a" opacity=".65"/>
      <circle cx="60" cy="110" r="10" fill="<?=$ca?>" opacity=".55"/>
      <ellipse cx="125" cy="65" rx="14" ry="6" fill="#3d7a4a" opacity=".55" transform="rotate(-50 125 65)"/>
    </svg>
    <!-- Bottom right -->
    <svg class="op-floral-br" viewBox="0 0 240 180" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0 180 Q60 140 100 100 Q140 60 170 20" stroke="#3d7a4a" stroke-width="1.5" fill="none"/>
      <circle cx="170" cy="20" r="16" fill="<?=$ca?>" opacity=".7"/>
      <circle cx="163" cy="13" r="10" fill="<?=$ca2?>" opacity=".8"/>
      <circle cx="100" cy="100" r="12" fill="#e8a0b0" opacity=".65"/>
      <ellipse cx="125" cy="65" rx="14" ry="6" fill="#3d7a4a" opacity=".55" transform="rotate(-50 125 65)"/>
    </svg>
  </div>

  <div class="op-bismillah">﷽</div>
  <div class="op-line-h" style="animation:fadeUp .7s .3s both;opacity:0;"></div>
  <div class="op-names">
    <?=htmlspecialchars($cfg['pria']['nama_panggilan']??'')?>
    <span class="op-amp-big">&amp;</span>
    <?=htmlspecialchars($cfg['wanita']['nama_panggilan']??'')?>
  </div>
  <div class="op-undangan">Mengundang Anda ke Pernikahan Kami</div>
  <?php if($namaTamu):?>
  <div class="op-tamu-wrap">
    Kepada Yang Terhormat
    <strong><?=$namaTamu?></strong>
  </div>
  <?php endif;?>
  <button class="btn-open" onclick="openInvite()"><span>🌸 Buka Undangan 🌸</span></button>
</div>

<!-- ═══════ FLOAT NAV ═══════ -->
<nav class="fnav" id="fnav">
  <a href="#bismillah">Bismillah</a>
  <a href="#mempelai">Mempelai</a>
  <a href="#acara">Acara</a>
  <a href="#gallery">Galeri</a>
  <a href="#ucapan">Ucapan</a>
  <?php if($showGift&&$rekeningAktif):?><a href="#gift">Gift</a><?php endif;?>
</nav>

<!-- ═══════ MUSIC ═══════ -->
<?php if($musikFile):?>
<audio id="mus" loop><source src="<?=htmlspecialchars($musikFile)?>"></audio>
<button class="mus-btn" id="musBtn" onclick="toggleMus()" title="Musik">♪</button>
<?php endif;?>

<!-- ═══════ LIGHTBOX ═══════ -->
<div id="lb" onclick="closeLb()"><span id="lb-x" onclick="closeLb()">✕</span><img id="lb-img" src="" alt=""></div>

<!-- ═══════════════════════════════════════
     COVER
════════════════════════════════════════ -->
<div id="cover">
  <div class="sw" style="<?=bgSection($cfg,'cover',$cp,$cs)?>min-height:100vh;display:flex;align-items:center;justify-content:center;">
    <!-- Animated roses in cover -->
    <div class="cover-roses" id="coverRoses"></div>
    <!-- Orbit rings -->
    <svg style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:min(600px,90vw);height:min(600px,90vw);pointer-events:none;z-index:1;opacity:.07;animation:spin 40s linear infinite;" viewBox="0 0 600 600">
      <circle cx="300" cy="300" r="290" stroke="<?=$ca?>" stroke-width="1" fill="none"/>
      <circle cx="300" cy="300" r="240" stroke="<?=$ca?>" stroke-width=".6" fill="none"/>
      <circle cx="300" cy="300" r="190" stroke="<?=$ca?>" stroke-width=".4" fill="none"/>
    </svg>
    <svg style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:min(500px,80vw);height:min(500px,80vw);pointer-events:none;z-index:1;opacity:.05;animation:spin 25s linear infinite reverse;" viewBox="0 0 500 500">
      <circle cx="250" cy="250" r="240" stroke="<?=$ca2?>" stroke-width="1.5" fill="none" stroke-dasharray="8 16"/>
    </svg>
    <div class="cover-content">
      <div class="c-label">— The Wedding of —</div>
      <div class="c-names">
        <?=htmlspecialchars($cfg['pria']['nama_panggilan']??'')?>
        <span class="c-amp">&</span>
        <?=htmlspecialchars($cfg['wanita']['nama_panggilan']??'')?>
      </div>
      <div class="c-date"><?=htmlspecialchars($cfg['akad']['hari']??'')?> &nbsp;|&nbsp; <?=htmlspecialchars($cfg['akad']['tanggal']??'')?></div>
      <div class="c-sub"><?=htmlspecialchars($cfg['akad']['lokasi']??'')?></div>
    </div>
    <div class="c-scroll" onclick="document.getElementById('bismillah').scrollIntoView({behavior:'smooth'})">
      <div class="scroll-mouse"></div>
      <span class="scroll-txt">Scroll</span>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     BISMILLAH
════════════════════════════════════════ -->
<div id="bismillah">
  <div class="sw" style="<?=bgSection($cfg,'bismillah',$cp,$cs)?>">
    <div class="si" style="text-align:center;">
      <div class="reveal">
        <span class="stag">Bismillahirrahmanirrahim</span>
        <!-- Floral divider -->
        <div class="floral-divider">
          <svg viewBox="0 0 120 30" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 15 Q20 5 40 15 Q60 25 80 15 Q100 5 120 15" stroke="<?=$ca?>" stroke-width="1" fill="none" opacity=".5"/>
            <circle cx="30" cy="10" r="4" fill="<?=$ca?>" opacity=".5"/>
            <circle cx="60" cy="20" r="3" fill="<?=$ca2?>" opacity=".5"/>
            <circle cx="90" cy="10" r="4" fill="<?=$ca?>" opacity=".5"/>
          </svg>
          <span style="font-size:1.5rem;color:var(--ca);opacity:.7;animation:floatY 3s ease-in-out infinite;">🌸</span>
          <svg viewBox="0 0 120 30" fill="none" xmlns="http://www.w3.org/2000/svg" style="transform:scaleX(-1);">
            <path d="M0 15 Q20 5 40 15 Q60 25 80 15 Q100 5 120 15" stroke="<?=$ca?>" stroke-width="1" fill="none" opacity=".5"/>
            <circle cx="30" cy="10" r="4" fill="<?=$ca?>" opacity=".5"/>
            <circle cx="60" cy="20" r="3" fill="<?=$ca2?>" opacity=".5"/>
            <circle cx="90" cy="10" r="4" fill="<?=$ca?>" opacity=".5"/>
          </svg>
        </div>
        <p class="ayat-text">"<?=htmlspecialchars($cfg['ayat']??'')?>"</p>
        <p class="ayat-src"><?=htmlspecialchars($cfg['sumber_ayat']??'')?></p>
        <?php if(!empty($cfg['ucapan_pembuka'])):?>
        <p style="font-family:'Lato';font-style:italic;color:rgba(var(--ct-rgb),.58);margin-top:1.5rem;font-size:.9rem;line-height:1.9;max-width:500px;margin-left:auto;margin-right:auto;font-weight:300;"><?=htmlspecialchars($cfg['ucapan_pembuka'])?></p>
        <?php endif;?>
        <?php if($namaTamu):?>
        <div class="tamu-box">
          <p>KEPADA YANG TERHORMAT</p>
          <strong><?=$namaTamu?></strong>
        </div>
        <?php endif;?>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     MEMPELAI
════════════════════════════════════════ -->
<div id="mempelai">
  <div class="sw" style="<?=bgSection($cfg,'mempelai',$cp,$cs)?>">
    <div class="si">
      <div class="reveal">
        <span class="stag">Mempelai</span>
        <h2 class="stitle">Dua Hati yang Bersatu</h2>
        <div class="divider"><div class="dl"></div><div class="dd">🌸</div><div class="dr"></div></div>
      </div>
      <div class="mp-grid">
        <div class="mp-card reveal-l">
          <div class="mp-frame-wrap">
            <!-- Animated floral rings -->
            <svg class="mp-floral-ring" viewBox="0 0 220 270" fill="none">
              <?php for($i=0;$i<8;$i++): $angle=$i*45; ?>
              <circle cx="<?=110+85*cos(deg2rad($angle))?>" cy="<?=135+105*sin(deg2rad($angle))?>" r="8" fill="<?=$ca?>" opacity=".35"/>
              <circle cx="<?=110+85*cos(deg2rad($angle))?>" cy="<?=135+105*sin(deg2rad($angle))?>" r="4" fill="<?=$ca2?>" opacity=".5"/>
              <?php endfor;?>
            </svg>
            <svg class="mp-floral-ring-rev" viewBox="0 0 200 250" fill="none">
              <?php for($i=0;$i<12;$i++): $angle=$i*30; ?>
              <circle cx="<?=100+65*cos(deg2rad($angle))?>" cy="<?=125+80*sin(deg2rad($angle))?>" r="4" fill="<?=$ca?>" opacity=".25"/>
              <?php endfor;?>
            </svg>
            <div class="mp-frame">
              <?php if($fotoPria):?><img src="<?=htmlspecialchars($fotoPria)?>" alt=""><?php else:?><div class="nopic">♂</div><?php endif;?>
            </div>
          </div>
          <div class="mp-nick"><?=htmlspecialchars($cfg['pria']['nama_panggilan']??'')?></div>
          <div class="mp-full"><?=htmlspecialchars($cfg['pria']['nama_lengkap']??'')?></div>
          <div class="mp-ortu">Putra dari<br><?=htmlspecialchars($cfg['pria']['nama_ayah']??'')?> &amp; <?=htmlspecialchars($cfg['pria']['nama_ibu']??'')?></div>
        </div>
        <div class="amp-center reveal delay2">&</div>
        <div class="mp-card reveal-r">
          <div class="mp-frame-wrap">
            <svg class="mp-floral-ring" viewBox="0 0 220 270" fill="none">
              <?php for($i=0;$i<8;$i++): $angle=$i*45; ?>
              <circle cx="<?=110+85*cos(deg2rad($angle))?>" cy="<?=135+105*sin(deg2rad($angle))?>" r="8" fill="#c97a8a" opacity=".4"/>
              <circle cx="<?=110+85*cos(deg2rad($angle))?>" cy="<?=135+105*sin(deg2rad($angle))?>" r="4" fill="#e8a0b0" opacity=".5"/>
              <?php endfor;?>
            </svg>
            <svg class="mp-floral-ring-rev" viewBox="0 0 200 250" fill="none">
              <?php for($i=0;$i<12;$i++): $angle=$i*30; ?>
              <circle cx="<?=100+65*cos(deg2rad($angle))?>" cy="<?=125+80*sin(deg2rad($angle))?>" r="4" fill="#c97a8a" opacity=".28"/>
              <?php endfor;?>
            </svg>
            <div class="mp-frame">
              <?php if($fotoWanita):?><img src="<?=htmlspecialchars($fotoWanita)?>" alt=""><?php else:?><div class="nopic">♀</div><?php endif;?>
            </div>
          </div>
          <div class="mp-nick"><?=htmlspecialchars($cfg['wanita']['nama_panggilan']??'')?></div>
          <div class="mp-full"><?=htmlspecialchars($cfg['wanita']['nama_lengkap']??'')?></div>
          <div class="mp-ortu">Putri dari<br><?=htmlspecialchars($cfg['wanita']['nama_ayah']??'')?> &amp; <?=htmlspecialchars($cfg['wanita']['nama_ibu']??'')?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     COUNTDOWN
════════════════════════════════════════ -->
<div id="countdown">
  <div class="sw" style="<?=bgSection($cfg,'countdown',$cp,$cs)?>">
    <div class="si" style="text-align:center;">
      <div class="reveal">
        <span class="stag">Menuju Hari Bahagia</span>
        <h2 class="stitle">Hitung Mundur</h2>
        <div class="divider"><div class="dl"></div><div class="dd">🌺</div><div class="dr"></div></div>
        <div class="cd-wrap">
          <div class="cd-box"><span class="cd-num" id="cdH">00</span><span class="cd-lbl">Hari</span></div>
          <div class="cd-sep">:</div>
          <div class="cd-box"><span class="cd-num" id="cdJ">00</span><span class="cd-lbl">Jam</span></div>
          <div class="cd-sep">:</div>
          <div class="cd-box"><span class="cd-num" id="cdM">00</span><span class="cd-lbl">Menit</span></div>
          <div class="cd-sep">:</div>
          <div class="cd-box"><span class="cd-num" id="cdD">00</span><span class="cd-lbl">Detik</span></div>
        </div>
        <div class="cd-flowers">
          <span>🌸</span><span>🌺</span><span>🌹</span><span>🌸</span><span>🌺</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     ACARA
════════════════════════════════════════ -->
<div id="acara">
  <div class="sw" style="<?=bgSection($cfg,'acara',$cp,$cs)?>">
    <div class="si">
      <div class="reveal">
        <span class="stag">Waktu &amp; Tempat</span>
        <h2 class="stitle">Detail Acara</h2>
        <div class="divider"><div class="dl"></div><div class="dd">🌸</div><div class="dr"></div></div>
      </div>
      <div class="acara-wrap">
        <?php if(!empty($cfg['akad']['aktif'])):?>
        <div class="ac-card reveal-l delay1">
          <span class="ac-card-flower">🕌</span>
          <div class="ac-type">✦ Akad Nikah ✦</div>
          <div class="ac-hari"><?=htmlspecialchars($cfg['akad']['hari']??'')?></div>
          <div class="ac-tgl"><?=htmlspecialchars($cfg['akad']['tanggal']??'')?></div>
          <div class="ac-jam"><?=htmlspecialchars($cfg['akad']['jam']??'')?> — <?=htmlspecialchars($cfg['akad']['selesai']??'')?></div>
          <div class="ac-lok"><?=htmlspecialchars($cfg['akad']['lokasi']??'')?></div>
          <?php if(!empty($cfg['akad']['gmaps'])):?><a href="<?=htmlspecialchars($cfg['akad']['gmaps'])?>" target="_blank" class="btn-maps"><span>📍 Petunjuk Arah</span></a><?php endif;?>
        </div>
        <?php endif;?>
        <?php if(!empty($cfg['resepsi']['aktif'])):?>
        <div class="ac-card reveal-r delay2">
          <span class="ac-card-flower">🌸</span>
          <div class="ac-type">✦ Resepsi ✦</div>
          <div class="ac-hari"><?=htmlspecialchars($cfg['resepsi']['hari']??'')?></div>
          <div class="ac-tgl"><?=htmlspecialchars($cfg['resepsi']['tanggal']??'')?></div>
          <div class="ac-jam"><?=htmlspecialchars($cfg['resepsi']['jam']??'')?> — <?=htmlspecialchars($cfg['resepsi']['selesai']??'')?></div>
          <div class="ac-lok"><?=htmlspecialchars($cfg['resepsi']['lokasi']??'')?></div>
          <?php if(!empty($cfg['resepsi']['gmaps'])):?><a href="<?=htmlspecialchars($cfg['resepsi']['gmaps'])?>" target="_blank" class="btn-maps"><span>📍 Petunjuk Arah</span></a><?php endif;?>
        </div>
        <?php endif;?>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     GALLERY
════════════════════════════════════════ -->
<div id="gallery">
  <div class="sw" style="<?=bgSection($cfg,'gallery',$cp,$cs)?>">
    <div class="si" style="text-align:center;">
      <div class="reveal">
        <span class="stag">Momen</span>
        <h2 class="stitle">Our Story</h2>
        <div class="divider"><div class="dl"></div><div class="dd">🌹</div><div class="dr"></div></div>
      </div>
      <?php if($galleryFiles):?>
      <div class="gal-masonry">
        <?php foreach($galleryFiles as $gf):?>
          <img src="<?=htmlspecialchars('uploads/gallery/'.basename($gf)).'?t='.filemtime($gf)?>" loading="lazy" onclick="openLb(this.src)" class="reveal" alt="">
        <?php endforeach;?>
      </div>
      <?php else:?>
        <p class="reveal" style="font-family:'Lato';font-style:italic;color:rgba(var(--ct-rgb),.4);margin-top:2rem;">Foto galeri belum ditambahkan.</p>
      <?php endif;?>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     UCAPAN
════════════════════════════════════════ -->
<div id="ucapan">
  <div class="sw" style="<?=bgSection($cfg,'ucapan',$cp,$cs)?>">
    <div class="si">
      <div class="reveal">
        <span class="stag">Doa &amp; Ucapan</span>
        <h2 class="stitle">Wish Box</h2>
        <div class="divider"><div class="dl"></div><div class="dd">🌸</div><div class="dr"></div></div>
      </div>
      <div class="wish-form reveal">
        <div class="frow">
          <div class="fg"><label>Nama</label><input type="text" id="wN" placeholder="Nama kamu..." maxlength="80"></div>
          <div class="fg"><label>Kehadiran</label>
            <select id="wH">
              <option value="">— Pilih —</option>
              <option value="hadir">✅ Insya Allah Hadir</option>
              <option value="tidak">❌ Belum Bisa Hadir</option>
            </select>
          </div>
        </div>
        <div class="fg" style="margin-bottom:.9rem;"><label>Ucapan &amp; Doa</label><textarea id="wU" rows="3" placeholder="Tuliskan ucapan dan doa terbaikmu..."></textarea></div>
        <button class="btn-send" onclick="sendWish()">🌸 Kirim Ucapan 🌸</button>
        <div id="wMsg" style="text-align:center;font-family:'Lato';font-style:italic;font-size:.88rem;margin-top:.7rem;min-height:1.2rem;"></div>
      </div>
      <div class="wish-list" id="wList"></div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     GIFT
════════════════════════════════════════ -->
<?php if($showGift && $rekeningAktif):?>
<div id="gift">
  <div class="sw" style="<?=bgSection($cfg,'gift',$cp,$cs)?>">
    <div class="si" style="text-align:center;">
      <div class="reveal">
        <span class="stag">Wedding Gift</span>
        <h2 class="stitle">Hadiah Pernikahan</h2>
        <div class="divider"><div class="dl"></div><div class="dd">🌺</div><div class="dr"></div></div>
        <p style="font-family:'Lato';font-style:italic;color:rgba(var(--ct-rgb),.52);margin-bottom:1rem;font-size:.88rem;font-weight:300;">Doa restu Anda adalah hadiah terbesar bagi kami.</p>
      </div>
      <div class="gift-wrap">
        <?php foreach($rekeningAktif as $r):?>
        <div class="gc reveal">
          <div class="gc-bank"><?=htmlspecialchars($r['bank'])?></div>
          <div class="gc-nama"><?=htmlspecialchars($r['nama'])?></div>
          <div class="gc-nomor"><?=htmlspecialchars($r['nomor'])?></div>
          <button class="btn-copy" onclick="doCopy('<?=htmlspecialchars($r['nomor'])?>',this)">Salin Nomor</button>
        </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
</div>
<?php endif;?>

<!-- ═══════════════════════════════════════
     FOOTER
════════════════════════════════════════ -->
<div id="footer">
  <div class="sw" style="<?=bgSection($cfg,'footer',$cp,$cs)?>">
    <div class="si">
      <div class="ft-flower-row reveal">
        <span>🌸</span><span>🌺</span><span>🌹</span><span>🌺</span><span>🌸</span>
      </div>
      <div class="ft-names reveal"><?=htmlspecialchars($cfg['pria']['nama_panggilan']??'')?> &amp; <?=htmlspecialchars($cfg['wanita']['nama_panggilan']??'')?></div>
      <div class="ft-sub reveal"><?=htmlspecialchars($cfg['akad']['tanggal']??'')?></div>
      <?php if(!empty($cfg['footer_text'])):?>
      <p class="ft-text reveal"><?=htmlspecialchars($cfg['footer_text'])?></p>
      <?php endif;?>
      <!-- Bottom floral SVG -->
      <div class="reveal" style="margin-top:2.5rem;opacity:.25;">
        <svg viewBox="0 0 300 60" width="300" height="60" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;">
          <path d="M0 30 Q50 10 100 30 Q150 50 200 30 Q250 10 300 30" stroke="<?=$ca?>" stroke-width="1" fill="none"/>
          <circle cx="75" cy="18" r="5" fill="<?=$ca?>" opacity=".6"/>
          <circle cx="150" cy="42" r="5" fill="<?=$ca2?>" opacity=".6"/>
          <circle cx="225" cy="18" r="5" fill="<?=$ca?>" opacity=".6"/>
          <circle cx="30" cy="28" r="3" fill="<?=$ca?>" opacity=".4"/>
          <circle cx="270" cy="28" r="3" fill="<?=$ca?>" opacity=".4"/>
        </svg>
      </div>
      <div style="margin-top:2rem;font-family:'Cinzel';font-size:.5rem;letter-spacing:.15em;color:rgba(var(--ct-rgb),.12);">
        <a href="admin.php" style="color:inherit;text-decoration:none;">⚙</a>
      </div>
    </div>
  </div>
</div>

<script>
// ════════════════════════════════════════════════
// FLOWER CANVAS — continuous particles + petals
// ════════════════════════════════════════════════
(function(){
  var c=document.getElementById('flower-canvas');
  var ctx=c.getContext('2d');
  var W,H;
  var ca='<?=$ca?>';
  var ca2='<?=$ca2?>';
  function hexRgb(h){h=h.replace('#','');if(h.length==3)h=h[0]+h[0]+h[1]+h[1]+h[2]+h[2];return[parseInt(h.slice(0,2),16),parseInt(h.slice(2,4),16),parseInt(h.slice(4,6),16)];}
  var C1=hexRgb(ca), C2=[201,122,138], C3=[232,160,176];
  function resize(){W=c.width=window.innerWidth;H=c.height=window.innerHeight;}
  resize();window.addEventListener('resize',resize);

  // Particles: small glowing dots
  var pts=[];
  for(var i=0;i<70;i++){
    var r=Math.random();
    var col=r<0.5?C1:r<0.75?C2:C3;
    pts.push({x:Math.random()*3000,y:Math.random()*3000,r:Math.random()*1.8+.3,vx:(Math.random()-.5)*.25,vy:(Math.random()-.5)*.25,op:Math.random()*.4+.08,col:col,pulse:Math.random()*Math.PI*2});
  }

  // Rose petal shapes (pre-computed paths via canvas)
  function drawRose(ctx,x,y,size,rot,alpha,col){
    ctx.save();
    ctx.translate(x,y);
    ctx.rotate(rot);
    ctx.globalAlpha=alpha;
    // 5-petal flower
    for(var p=0;p<5;p++){
      ctx.save();
      ctx.rotate((p*Math.PI*2)/5);
      ctx.beginPath();
      ctx.ellipse(0,-size*.6,size*.3,size*.6,0,0,Math.PI*2);
      ctx.fillStyle='rgba('+col.join(',')+',0.85)';
      ctx.fill();
      ctx.restore();
    }
    // Center
    ctx.beginPath();
    ctx.arc(0,0,size*.22,0,Math.PI*2);
    ctx.fillStyle='rgba(255,220,100,.7)';
    ctx.fill();
    ctx.restore();
  }

  // Floating rose elements
  var roses=[];
  for(var i=0;i<12;i++){
    var col=Math.random()<.5?C1:Math.random()<.5?C2:C3;
    roses.push({
      x:Math.random()*3000,y:Math.random()*3000,
      size:Math.random()*7+4,
      rot:Math.random()*Math.PI*2,
      vx:(Math.random()-.5)*.15,vy:(Math.random()-.5)*.15,
      vrot:(Math.random()-.5)*.008,
      op:Math.random()*.15+.04,
      col:col,
      t:Math.random()*1000
    });
  }

  var t=0;
  function draw(){
    ctx.clearRect(0,0,W,H);
    t+=0.012;
    // Draw connection web
    for(var i=0;i<pts.length;i++){
      for(var j=i+1;j<pts.length;j++){
        var dx=pts[i].x-pts[j].x,dy=pts[i].y-pts[j].y,d=Math.sqrt(dx*dx+dy*dy);
        if(d<130){
          ctx.beginPath();
          ctx.strokeStyle='rgba('+C1.join(',')+','+(1-d/130)*.06+')';
          ctx.lineWidth=.4;
          ctx.moveTo(pts[i].x,pts[i].y);
          ctx.lineTo(pts[j].x,pts[j].y);
          ctx.stroke();
        }
      }
    }
    // Draw glow points
    pts.forEach(function(p,idx){
      p.x+=p.vx;p.y+=p.vy;
      if(p.x<0)p.x=W;if(p.x>W)p.x=0;
      if(p.y<0)p.y=H;if(p.y>H)p.y=0;
      var pulse=p.op*(0.6+0.4*Math.sin(t*2+p.pulse));
      ctx.beginPath();
      ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fillStyle='rgba('+p.col.join(',')+','+pulse+')';
      ctx.fill();
      // Glow
      if(pulse>0.15){
        var grad=ctx.createRadialGradient(p.x,p.y,0,p.x,p.y,p.r*4);
        grad.addColorStop(0,'rgba('+p.col.join(',')+','+pulse*.3+')');
        grad.addColorStop(1,'rgba('+p.col.join(',')+',0)');
        ctx.beginPath();ctx.arc(p.x,p.y,p.r*4,0,Math.PI*2);
        ctx.fillStyle=grad;ctx.fill();
      }
    });
    // Draw floating roses
    roses.forEach(function(r){
      r.x+=r.vx;r.y+=r.vy;r.rot+=r.vrot;
      if(r.x<-50)r.x=W+50;if(r.x>W+50)r.x=-50;
      if(r.y<-50)r.y=H+50;if(r.y>H+50)r.y=-50;
      drawRose(ctx,r.x,r.y,r.size,r.rot,r.op,r.col);
    });
    requestAnimationFrame(draw);
  }
  draw();
})();

// ════════════════════════════════════════════════
// FALLING PETALS (DOM)
// ════════════════════════════════════════════════
(function(){
  var petals=['🌸','🌺','🌹','🌷','✿','❀','❁'];
  for(var i=0;i<22;i++){
    var el=document.createElement('div');
    el.className='petal-float';
    var drift=(Math.random()-.5)*200;
    var spn=(Math.random()-.5)*720;
    el.style.cssText=
      'left:'+Math.random()*100+'%;'+
      'top:-60px;'+
      'font-size:'+(Math.random()*16+8)+'px;'+
      '--drift:'+drift+'px;'+
      '--spin:'+spn+'deg;'+
      'animation-duration:'+(Math.random()*10+8)+'s;'+
      'animation-delay:'+( Math.random()*12)+'s;';
    el.textContent=petals[Math.floor(Math.random()*petals.length)];
    document.body.appendChild(el);
  }
})();

// ════════════════════════════════════════════════
// COVER ROSES
// ════════════════════════════════════════════════
(function(){
  var container=document.getElementById('coverRoses');
  var positions=[
    {x:8,y:15,s:2.5,delay:.5},{x:92,y:10,s:2,delay:1},
    {x:5,y:75,s:1.8,delay:1.5},{x:94,y:80,s:2.2,delay:.8},
    {x:15,y:45,s:1.5,delay:2},{x:85,y:50,s:1.6,delay:2.5},
    {x:50,y:5,s:2,delay:.3},{x:50,y:92,s:1.8,delay:1.8},
    {x:25,y:88,s:1.4,delay:2.2},{x:75,y:85,s:1.4,delay:1.2},
    {x:30,y:8,s:1.6,delay:3},{x:70,y:12,s:1.5,delay:2.8},
  ];
  var flowers=['🌸','🌺','🌹','🌷'];
  positions.forEach(function(p){
    var el=document.createElement('div');
    el.style.cssText=
      'position:absolute;left:'+p.x+'%;top:'+p.y+'%;'+
      'font-size:'+(p.s*1.2)+'rem;'+
      '--target-op:0.45;'+
      'animation: roseAppear .8s '+p.delay+'s cubic-bezier(.34,1.4,.64,1) both, floatSway '+(4+Math.random()*3)+'s '+(p.delay+0.8)+'s ease-in-out infinite;'+
      'transform-origin:center;pointer-events:none;z-index:1;';
    el.textContent=flowers[Math.floor(Math.random()*flowers.length)];
    container.appendChild(el);
  });
})();

// ════════════════════════════════════════════════
// OPEN INVITATION
// ════════════════════════════════════════════════
function openInvite(){
  document.getElementById('opener').classList.add('hidden');
  document.getElementById('fnav').style.display='flex';
  <?php if($musikFile):?>
  var m=document.getElementById('mus');
  m.volume=.28;
  m.play().then(()=>document.getElementById('musBtn').classList.add('playing')).catch(()=>{});
  <?php endif;?>
}

// ════════════════════════════════════════════════
// MUSIC TOGGLE
// ════════════════════════════════════════════════
function toggleMus(){
  var m=document.getElementById('mus'),b=document.getElementById('musBtn');
  if(m.paused){m.play();b.classList.add('playing');b.textContent='♪';}
  else{m.pause();b.classList.remove('playing');b.textContent='♩';}
}

// ════════════════════════════════════════════════
// COUNTDOWN
// ════════════════════════════════════════════════
var prevVals={h:null,j:null,m:null,d:null};
function flip(id){var el=document.getElementById(id);el.classList.add('flip');setTimeout(()=>el.classList.remove('flip'),220);}
(function tick(){
  var target=new Date('<?=htmlspecialchars($cfg['tanggal_countdown']??'2025-12-31T08:00:00')?>').getTime();
  function upd(){
    var diff=target-Date.now();
    if(diff<=0){['cdH','cdJ','cdM','cdD'].forEach(id=>document.getElementById(id).textContent='00');return;}
    var h=Math.floor(diff/864e5),j=Math.floor(diff%864e5/36e5),m=Math.floor(diff%36e5/6e4),d=Math.floor(diff%6e4/1e3);
    var vals={h,j,m,d};var ids={h:'cdH',j:'cdJ',m:'cdM',d:'cdD'};
    for(var k in vals){if(prevVals[k]!==vals[k]){if(prevVals[k]!==null)flip(ids[k]);document.getElementById(ids[k]).textContent=String(vals[k]).padStart(2,'0');prevVals[k]=vals[k];}}
  }
  upd();setInterval(upd,1000);
})();

// ════════════════════════════════════════════════
// SCROLL REVEAL
// ════════════════════════════════════════════════
var io=new IntersectionObserver(function(entries){
  entries.forEach(function(e){if(e.isIntersecting)e.target.classList.add('on');});
},{threshold:.1});
document.querySelectorAll('.reveal,.reveal-l,.reveal-r').forEach(el=>io.observe(el));

// ════════════════════════════════════════════════
// ACTIVE NAV
// ════════════════════════════════════════════════
window.addEventListener('scroll',function(){
  var sections=['bismillah','mempelai','acara','gallery','ucapan','gift'];
  var nav=document.querySelectorAll('.fnav a');
  sections.forEach(function(id,i){
    var el=document.getElementById(id);if(!el)return;
    var rect=el.getBoundingClientRect();
    if(rect.top<=100&&rect.bottom>100&&nav[i]){nav.forEach(a=>a.classList.remove('active'));nav[i]&&nav[i].classList.add('active');}
  });
},{passive:true});

// ════════════════════════════════════════════════
// UCAPAN
// ════════════════════════════════════════════════
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function loadWishes(){
  fetch('ucapan.php').then(r=>r.json()).then(function(data){
    var el=document.getElementById('wList');
    if(!data.length){el.innerHTML='<p style="text-align:center;font-family:\'Lato\';font-style:italic;color:rgba(<?=$ct_rgb?>,.35);padding:2rem;">Jadilah yang pertama memberi ucapan 🌸</p>';return;}
    el.innerHTML=data.map(u=>'<div class="wi"><div class="wi-nm">'+esc(u.nama)+(u.hadir?'<span class="wi-bdg">'+(u.hadir==='hadir'?'✅ Hadir':'❌ Tidak hadir')+'</span>':'')+'</div><div class="wi-tx">'+esc(u.ucapan)+'</div><div class="wi-tm">'+esc(u.waktu||'')+'</div></div>').join('');
  }).catch(()=>{});
}
function sendWish(){
  var n=document.getElementById('wN').value.trim(),u=document.getElementById('wU').value.trim(),h=document.getElementById('wH').value,msg=document.getElementById('wMsg');
  if(!n||!u){msg.style.color='#e74c3c';msg.textContent='Nama dan ucapan wajib diisi.';return;}
  msg.style.color='var(--ca)';msg.textContent='Mengirim... 🌸';
  fetch('ucapan.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({nama:n,ucapan:u,hadir:h})})
    .then(r=>r.json()).then(function(d){
      if(d.ok){msg.style.color='var(--ca2)';msg.textContent='🌸 Ucapan terkirim! Terima kasih. 🌸';document.getElementById('wN').value='';document.getElementById('wU').value='';document.getElementById('wH').value='';loadWishes();}
      else{msg.style.color='#e74c3c';msg.textContent=d.msg||'Gagal.';}
    }).catch(()=>{msg.style.color='#e74c3c';msg.textContent='Gagal. Coba lagi.';});
}

// ════════════════════════════════════════════════
// COPY & LIGHTBOX
// ════════════════════════════════════════════════
function doCopy(t,btn){navigator.clipboard.writeText(t).then(()=>{btn.textContent='✓ Tersalin!';btn.classList.add('copied');setTimeout(()=>{btn.textContent='Salin Nomor';btn.classList.remove('copied');},2400);});}
function openLb(src){document.getElementById('lb-img').src=src;document.getElementById('lb').classList.add('open');}
function closeLb(){document.getElementById('lb').classList.remove('open');}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeLb();});

loadWishes();
</script>
</body>
</html>
