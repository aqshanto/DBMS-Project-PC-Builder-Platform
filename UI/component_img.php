<?php
// ============================================================
//  COMPONENT IMAGE GENERATOR
//  Generates beautiful SVG product cards — no external URLs needed
//  Usage: <img src="component_img.php?type=cpu&brand=Intel&name=Core+i9">
// ============================================================

$type  = strtolower(str_replace(' ', '', $_GET['type']  ?? 'cpu'));
$brand = htmlspecialchars($_GET['brand'] ?? '');
$name  = htmlspecialchars($_GET['name']  ?? '');

// Shorten name for display
$short_name = strlen($name) > 22 ? substr($name, 0, 22) . '…' : $name;

// Color themes per type — Cyberpunk palette
$themes = [
    'cpu'          => ['bg1' => '#020d1a', 'bg2' => '#041428', 'accent' => '#00d4ff', 'icon_bg' => '#041a35'],
    'motherboard'  => ['bg1' => '#0a0520', 'bg2' => '#120835', 'accent' => '#9b5dff', 'icon_bg' => '#150a40'],
    'ram'          => ['bg1' => '#021a0e', 'bg2' => '#031f0f', 'accent' => '#00ff9d', 'icon_bg' => '#042510'],
    'gpu'          => ['bg1' => '#1a0520', 'bg2' => '#250833', 'accent' => '#ff2d9b', 'icon_bg' => '#2a0838'],
    'powersupply'  => ['bg1' => '#1a1000', 'bg2' => '#251800', 'accent' => '#ffaa00', 'icon_bg' => '#2a1a00'],
    'storage'      => ['bg1' => '#020d1a', 'bg2' => '#031525', 'accent' => '#00aaff', 'icon_bg' => '#041a30'],
    'case'         => ['bg1' => '#0a0a14', 'bg2' => '#0f0f1e', 'accent' => '#7b8fff', 'icon_bg' => '#141425'],
];

$t = $themes[$type] ?? $themes['cpu'];

// SVG icons per type
$icons = [
    'cpu' => '
        <rect x="100" y="100" width="120" height="120" rx="8" fill="none" stroke="currentColor" stroke-width="6"/>
        <rect x="118" y="118" width="84" height="84" rx="4" fill="currentColor" opacity="0.3"/>
        <line x1="115" y1="130" x2="85" y2="130" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="115" y1="150" x2="85" y2="150" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="115" y1="170" x2="85" y2="170" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="115" y1="190" x2="85" y2="190" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="205" y1="130" x2="235" y2="130" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="205" y1="150" x2="235" y2="150" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="205" y1="170" x2="235" y2="170" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="205" y1="190" x2="235" y2="190" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="130" y1="115" x2="130" y2="85" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="150" y1="115" x2="150" y2="85" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="170" y1="115" x2="170" y2="85" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="190" y1="115" x2="190" y2="85" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="130" y1="205" x2="130" y2="235" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="150" y1="205" x2="150" y2="235" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="170" y1="205" x2="170" y2="235" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <line x1="190" y1="205" x2="190" y2="235" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <text x="160" y="166" text-anchor="middle" font-size="28" font-weight="bold" fill="currentColor" font-family="monospace">i9</text>',

    'motherboard' => '
        <rect x="70" y="70" width="180" height="180" rx="6" fill="none" stroke="currentColor" stroke-width="5"/>
        <rect x="85" y="85" width="50" height="50" rx="4" fill="currentColor" opacity="0.4"/>
        <rect x="145" y="85" width="30" height="10" rx="2" fill="currentColor" opacity="0.6"/>
        <rect x="145" y="100" width="30" height="10" rx="2" fill="currentColor" opacity="0.6"/>
        <rect x="145" y="115" width="30" height="10" rx="2" fill="currentColor" opacity="0.6"/>
        <rect x="85" y="150" width="70" height="20" rx="3" fill="currentColor" opacity="0.4"/>
        <rect x="85" y="180" width="70" height="10" rx="2" fill="currentColor" opacity="0.3"/>
        <rect x="85" y="200" width="70" height="10" rx="2" fill="currentColor" opacity="0.3"/>
        <rect x="170" y="150" width="60" height="60" rx="4" fill="currentColor" opacity="0.25"/>
        <line x1="70" y1="240" x2="250" y2="240" stroke="currentColor" stroke-width="3" opacity="0.5"/>',

    'ram' => '
        <rect x="90" y="80" width="40" height="160" rx="4" fill="currentColor" opacity="0.7"/>
        <rect x="140" y="80" width="40" height="160" rx="4" fill="currentColor" opacity="0.7"/>
        <rect x="90" y="80" width="40" height="20" rx="2" fill="currentColor"/>
        <rect x="140" y="80" width="40" height="20" rx="2" fill="currentColor"/>
        <rect x="95" y="110" width="30" height="8" rx="2" fill="currentColor" opacity="0.4"/>
        <rect x="95" y="125" width="30" height="8" rx="2" fill="currentColor" opacity="0.4"/>
        <rect x="95" y="140" width="30" height="8" rx="2" fill="currentColor" opacity="0.4"/>
        <rect x="145" y="110" width="30" height="8" rx="2" fill="currentColor" opacity="0.4"/>
        <rect x="145" y="125" width="30" height="8" rx="2" fill="currentColor" opacity="0.4"/>
        <rect x="145" y="140" width="30" height="8" rx="2" fill="currentColor" opacity="0.4"/>',

    'gpu' => '
        <rect x="60" y="110" width="200" height="110" rx="8" fill="none" stroke="currentColor" stroke-width="5"/>
        <rect x="75" y="125" width="60" height="60" rx="30" fill="currentColor" opacity="0.4"/>
        <circle cx="105" cy="155" r="22" fill="none" stroke="currentColor" stroke-width="4"/>
        <circle cx="105" cy="155" r="8" fill="currentColor" opacity="0.7"/>
        <rect x="150" y="130" width="95" height="12" rx="3" fill="currentColor" opacity="0.5"/>
        <rect x="150" y="150" width="80" height="10" rx="3" fill="currentColor" opacity="0.35"/>
        <rect x="150" y="168" width="90" height="10" rx="3" fill="currentColor" opacity="0.35"/>
        <rect x="75" y="220" width="160" height="12" rx="3" fill="currentColor" opacity="0.4"/>
        <rect x="60" y="90" width="30" height="22" rx="3" fill="currentColor" opacity="0.6"/>
        <rect x="100" y="90" width="30" height="22" rx="3" fill="currentColor" opacity="0.6"/>',

    'powersupply' => '
        <rect x="75" y="90" width="170" height="140" rx="8" fill="none" stroke="currentColor" stroke-width="5"/>
        <circle cx="130" cy="160" r="35" fill="none" stroke="currentColor" stroke-width="5"/>
        <circle cx="130" cy="160" r="15" fill="currentColor" opacity="0.5"/>
        <rect x="175" y="110" width="50" height="20" rx="3" fill="currentColor" opacity="0.5"/>
        <rect x="175" y="140" width="50" height="8" rx="2" fill="currentColor" opacity="0.4"/>
        <rect x="175" y="155" width="50" height="8" rx="2" fill="currentColor" opacity="0.4"/>
        <rect x="175" y="170" width="50" height="8" rx="2" fill="currentColor" opacity="0.4"/>
        <text x="130" y="165" text-anchor="middle" font-size="14" font-weight="bold" fill="currentColor" font-family="sans-serif">PSU</text>',

    'storage' => '
        <rect x="70" y="95" width="180" height="130" rx="8" fill="none" stroke="currentColor" stroke-width="5"/>
        <rect x="85" y="110" width="100" height="100" rx="50" fill="none" stroke="currentColor" stroke-width="3" opacity="0.5"/>
        <circle cx="135" cy="160" r="15" fill="currentColor" opacity="0.6"/>
        <circle cx="135" cy="160" r="5" fill="currentColor"/>
        <rect x="195" y="115" width="40" height="10" rx="2" fill="currentColor" opacity="0.5"/>
        <rect x="195" y="133" width="40" height="10" rx="2" fill="currentColor" opacity="0.5"/>
        <rect x="195" y="151" width="40" height="10" rx="2" fill="currentColor" opacity="0.5"/>',

    'case' => '
        <rect x="90" y="65" width="120" height="190" rx="8" fill="none" stroke="currentColor" stroke-width="5"/>
        <rect x="100" y="80" width="60" height="80" rx="4" fill="currentColor" opacity="0.2"/>
        <circle cx="155" cy="105" r="10" fill="none" stroke="currentColor" stroke-width="3"/>
        <rect x="100" y="175" width="30" height="12" rx="3" fill="currentColor" opacity="0.5"/>
        <rect x="100" y="195" width="20" height="8" rx="2" fill="currentColor" opacity="0.4"/>
        <rect x="100" y="210" width="20" height="8" rx="2" fill="currentColor" opacity="0.4"/>
        <rect x="170" y="175" width="30" height="60" rx="4" fill="currentColor" opacity="0.15"/>',
];

$svg_icon = $icons[$type] ?? $icons['cpu'];

// Brand short label
$brand_short = strtoupper(substr($brand, 0, 8));

header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 240" width="320" height="240">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:<?= $t['bg1'] ?>;stop-opacity:1"/>
      <stop offset="100%" style="stop-color:<?= $t['bg2'] ?>;stop-opacity:1"/>
    </linearGradient>
    <linearGradient id="glow" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:<?= $t['accent'] ?>;stop-opacity:0.15"/>
      <stop offset="100%" style="stop-color:<?= $t['accent'] ?>;stop-opacity:0.05"/>
    </linearGradient>
  </defs>

  <!-- Background -->
  <rect width="320" height="240" fill="url(#bg)" rx="12"/>
  <rect width="320" height="240" fill="url(#glow)" rx="12"/>

  <!-- Subtle grid pattern -->
  <g opacity="0.05" stroke="<?= $t['accent'] ?>" stroke-width="1">
    <line x1="0" y1="40" x2="320" y2="40"/>
    <line x1="0" y1="80" x2="320" y2="80"/>
    <line x1="0" y1="120" x2="320" y2="120"/>
    <line x1="0" y1="160" x2="320" y2="160"/>
    <line x1="0" y1="200" x2="320" y2="200"/>
    <line x1="80" y1="0" x2="80" y2="240"/>
    <line x1="160" y1="0" x2="160" y2="240"/>
    <line x1="240" y1="0" x2="240" y2="240"/>
  </g>

  <!-- Glow circle behind icon -->
  <circle cx="160" cy="115" r="75" fill="<?= $t['accent'] ?>" opacity="0.07"/>

  <!-- Component icon -->
  <g color="<?= $t['accent'] ?>" transform="translate(0, -5)">
    <?= $svg_icon ?>
  </g>

  <!-- Bottom bar -->
  <rect x="0" y="195" width="320" height="45" fill="<?= $t['icon_bg'] ?>" rx="0" opacity="0.7"/>
  <rect x="0" y="228" width="320" height="12" fill="<?= $t['icon_bg'] ?>" opacity="0.9"/>

  <!-- Brand badge -->
  <?php if ($brand_short): ?>
  <rect x="12" y="202" width="<?= min(strlen($brand_short) * 9 + 12, 100) ?>" height="20" rx="4" fill="<?= $t['accent'] ?>" opacity="0.25"/>
  <text x="18" y="216" font-family="'Segoe UI', Arial, sans-serif" font-size="11" font-weight="bold" fill="<?= $t['accent'] ?>"><?= $brand_short ?></text>
  <?php endif; ?>

  <!-- Component name -->
  <text x="160" y="216" text-anchor="middle" font-family="'Segoe UI', Arial, sans-serif"
        font-size="13" font-weight="600" fill="#e2e8f0" letter-spacing="0.3"><?= $short_name ?></text>
</svg>
