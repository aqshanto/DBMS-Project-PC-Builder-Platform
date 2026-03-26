<?php
session_start();
require_once 'config.php';
$conn = get_db_connection();

// --- REMOVE ITEM ---
if (isset($_GET['remove'])) {
    $key = $_GET['remove'];
    unset($_SESSION['build'][$key]);
    header("Location: build.php");
    exit();
}

// --- SAVE BUILD ---
$save_msg      = "";
$save_msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_build'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?msg=required");
        exit();
    }
    if (empty($_SESSION['build'])) {
        $save_msg      = "⚠️ Add at least one component before saving.";
        $save_msg_type = "warn";
    } else {
        $user_id    = (int)$_SESSION['user_id'];
        $build_name = trim($_POST['build_name'] ?? '');
        if (empty($build_name)) $build_name = "My Build " . date('M d, Y');
        if (strlen($build_name) > 100) $build_name = substr($build_name, 0, 100);

        // Check if overwriting existing build
        $overwrite_id = (int)($_POST['overwrite_id'] ?? 0);

        if ($overwrite_id > 0) {
            // Verify ownership then overwrite
            $chk = $conn->prepare("SELECT build_id FROM builds WHERE build_id=? AND user_id=?");
            $chk->bind_param("ii", $overwrite_id, $user_id);
            $chk->execute(); $chk->store_result();
            if ($chk->num_rows > 0) {
                $conn->query("DELETE FROM build_components WHERE build_id=$overwrite_id");
                $upd = $conn->prepare("UPDATE builds SET build_name=?, created_at=NOW() WHERE build_id=?");
                $upd->bind_param("si", $build_name, $overwrite_id);
                $upd->execute(); $upd->close();
                $new_build_id = $overwrite_id;
            }
            $chk->close();
        } else {
            // Create new build
            $stmt = $conn->prepare("INSERT INTO builds (user_id, build_name, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("is", $user_id, $build_name);
            $stmt->execute();
            $new_build_id = $conn->insert_id;
            $stmt->close();
        }

        // Save each component with its slot type
        if (!empty($new_build_id)) {
            foreach ($_SESSION['build'] as $slot_type => $component_id) {
                $cid  = (int)$component_id;
                $slot = $conn->real_escape_string($slot_type);
                $stmt = $conn->prepare("INSERT INTO build_components (build_id, component_id, slot_type) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $new_build_id, $cid, $slot);
                $stmt->execute();
                $stmt->close();
            }
            $save_msg      = "✅ Build saved as <strong>" . htmlspecialchars($build_name) . "</strong>!";
            $save_msg_type = "success";
        }
    }
}

if (isset($_GET['loaded'])) {
    $save_msg      = "▶️ Build loaded into your current session!";
    $save_msg_type = "success";
}

// --- Fetch user's existing builds for overwrite dropdown ---
$user_builds = [];
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $ubr = $conn->query("SELECT build_id, build_name FROM builds WHERE user_id=$uid ORDER BY created_at DESC");
    if ($ubr) while ($ub = $ubr->fetch_assoc()) $user_builds[] = $ub;
}

// --- ALL REQUIRED SLOTS ---
$slots = [
    'cpu'          => ['label' => 'Processor (CPU)',    'icon' => '🔲'],
    'motherboard'  => ['label' => 'Motherboard',        'icon' => '📋'],
    'ram'          => ['label' => 'Memory (RAM)',        'icon' => '💾'],
    'gpu'          => ['label' => 'Graphics Card (GPU)', 'icon' => '🎮'],
    'powersupply'  => ['label' => 'Power Supply (PSU)', 'icon' => '⚡'],
    'storage'      => ['label' => 'Storage',             'icon' => '💿'],
    'case'         => ['label' => 'PC Case',             'icon' => '📦'],
];

// --- LOAD BUILD ITEMS FROM DB ---
$build_items = [];
$total_price = 0;
if (!empty($_SESSION['build'])) {
    foreach ($_SESSION['build'] as $type => $id) {
        $id = (int)$id;
        $r = $conn->query("SELECT * FROM components WHERE component_id=$id");
        if ($r && $r->num_rows > 0) {
            $build_items[$type] = $r->fetch_assoc();
            $total_price += (float)$build_items[$type]['Price'];
        }
    }
}

// --- COMPATIBILITY ANALYSIS ---
$compat_issues  = [];
$compat_ok      = [];
$cpu_tdp        = 0;
$gpu_tdp        = 0;
$psu_wattage    = 0;
$mb_form_factor = "";
$case_form_factor = "";
$max_gpu_len    = 9999;
$gpu_len        = 0;
$gpu_perf       = 0;
$cpu_passmark   = 0;

function ff_fits($case_ff, $mb_ff) {
    $rank = ['ATX' => 3, 'Micro-ATX' => 2, 'Mini-ITX' => 1];
    return ($rank[$case_ff] ?? 0) >= ($rank[$mb_ff] ?? 0);
}

if (!empty($build_items['cpu'])) {
    $id = (int)$build_items['cpu']['component_id'];
    $r = $conn->query("SELECT Socket, tdp_watt, passmark_score FROM cpus WHERE component_id=$id")->fetch_assoc();
    if ($r) { $cpu_socket = $r['Socket']; $cpu_tdp = (float)$r['tdp_watt']; $cpu_passmark = (int)$r['passmark_score']; }
}
if (!empty($build_items['motherboard'])) {
    $id = (int)$build_items['motherboard']['component_id'];
    $r = $conn->query("SELECT Socket, supported_ram_type, Form_Factor FROM motherboards WHERE component_id=$id")->fetch_assoc();
    if ($r) { $mb_socket = $r['Socket']; $mb_ram_type = $r['supported_ram_type']; $mb_form_factor = $r['Form_Factor']; }
}
if (!empty($build_items['ram'])) {
    $id = (int)$build_items['ram']['component_id'];
    $r = $conn->query("SELECT DDR_Version FROM rams WHERE component_id=$id")->fetch_assoc();
    if ($r) $ram_ddr = $r['DDR_Version'];
}
if (!empty($build_items['gpu'])) {
    $id = (int)$build_items['gpu']['component_id'];
    $r = $conn->query("SELECT TDP_Watt, GPU_Length_mm, perf_score FROM gpus WHERE component_id=$id")->fetch_assoc();
    if ($r) { $gpu_tdp = (float)$r['TDP_Watt']; $gpu_len = (int)$r['GPU_Length_mm']; $gpu_perf = (int)$r['perf_score']; }
}
if (!empty($build_items['powersupply'])) {
    $id = (int)$build_items['powersupply']['component_id'];
    $r = $conn->query("SELECT Wattage FROM powersupplies WHERE component_id=$id")->fetch_assoc();
    if ($r) $psu_wattage = (int)$r['Wattage'];
}
if (!empty($build_items['case'])) {
    $id = (int)$build_items['case']['component_id'];
    $r = $conn->query("SELECT Form_Factor, Max_GPU_Length FROM cases WHERE component_id=$id")->fetch_assoc();
    if ($r) { $case_form_factor = $r['Form_Factor']; $max_gpu_len = (int)$r['Max_GPU_Length']; }
}

// Run checks
if (!empty($build_items['cpu']) && !empty($build_items['motherboard'])) {
    if (isset($cpu_socket, $mb_socket)) {
        if ($cpu_socket === $mb_socket) $compat_ok[] = "✅ CPU socket ({$cpu_socket}) matches Motherboard";
        else $compat_issues[] = "❌ Socket mismatch: CPU needs {$cpu_socket}, Motherboard has {$mb_socket}";
    }
}
if (!empty($build_items['motherboard']) && !empty($build_items['ram'])) {
    if (isset($mb_ram_type, $ram_ddr)) {
        if ($mb_ram_type === $ram_ddr) $compat_ok[] = "✅ RAM type ({$ram_ddr}) supported by Motherboard";
        else $compat_issues[] = "❌ RAM mismatch: Motherboard supports {$mb_ram_type}, you chose {$ram_ddr}";
    }
}
if (!empty($build_items['motherboard']) && !empty($build_items['case'])) {
    if ($mb_form_factor && $case_form_factor) {
        if (ff_fits($case_form_factor, $mb_form_factor)) $compat_ok[] = "✅ Motherboard ({$mb_form_factor}) fits in Case ({$case_form_factor})";
        else $compat_issues[] = "❌ Form factor mismatch: {$mb_form_factor} motherboard won't fit in {$case_form_factor} case";
    }
}
if (!empty($build_items['gpu']) && !empty($build_items['case']) && $gpu_len > 0) {
    if ($gpu_len <= $max_gpu_len) $compat_ok[] = "✅ GPU length ({$gpu_len}mm) fits in Case (max {$max_gpu_len}mm)";
    else $compat_issues[] = "❌ GPU too long: {$gpu_len}mm exceeds case maximum of {$max_gpu_len}mm";
}
$total_tdp = $cpu_tdp + $gpu_tdp;
$rec_psu = $total_tdp > 0 ? (int)($total_tdp * 1.3 + 50) : 0;
if (!empty($build_items['powersupply']) && $rec_psu > 0) {
    if ($psu_wattage >= $rec_psu) $compat_ok[] = "✅ PSU ({$psu_wattage}W) is sufficient (recommended: {$rec_psu}W)";
    else $compat_issues[] = "❌ PSU too weak: {$psu_wattage}W selected, {$rec_psu}W recommended";
}

// --- BOTTLENECK CALCULATION ---
$bottleneck_pct    = 0;
$bottleneck_type   = "";   // 'cpu' or 'gpu'
$bottleneck_level  = "";   // 'ok', 'minor', 'moderate', 'severe'
$bottleneck_msg    = "";
$bottleneck_fix    = "";

if ($cpu_passmark > 0 && $gpu_perf > 0) {
    $cpu_s = $cpu_passmark;
    $gpu_s = $gpu_perf;
    $bottleneck_pct  = round((1 - min($cpu_s, $gpu_s) / max($cpu_s, $gpu_s)) * 100);
    $bottleneck_type = ($cpu_s < $gpu_s) ? 'cpu' : 'gpu';

    if ($bottleneck_pct <= 10) {
        $bottleneck_level = 'ok';
        $bottleneck_msg   = "Well balanced build — CPU and GPU are well matched.";
    } elseif ($bottleneck_pct <= 25) {
        $bottleneck_level = 'minor';
        $bottleneck_msg   = "Minor bottleneck (~{$bottleneck_pct}%) — This is normal and acceptable in most builds.";
    } elseif ($bottleneck_pct <= 40) {
        $bottleneck_level = 'moderate';
        if ($bottleneck_type === 'cpu') {
            $bottleneck_msg = "Moderate CPU bottleneck (~{$bottleneck_pct}%) — Your CPU may limit GPU performance in demanding tasks.";
            $bottleneck_fix = "Consider upgrading to a stronger CPU (e.g. Core i5/i7 or Ryzen 5/7).";
        } else {
            $bottleneck_msg = "Moderate GPU bottleneck (~{$bottleneck_pct}%) — Your GPU may limit visual performance.";
            $bottleneck_fix = "Consider upgrading to a more powerful GPU.";
        }
    } else {
        $bottleneck_level = 'severe';
        if ($bottleneck_type === 'cpu') {
            $bottleneck_msg = "Severe CPU bottleneck (~{$bottleneck_pct}%) — Your CPU is significantly holding back the GPU. You are wasting a large portion of your GPU's performance.";
            $bottleneck_fix = "Upgrade your CPU. A " . htmlspecialchars($build_items['cpu']['Name']) . " is too weak for a " . htmlspecialchars($build_items['gpu']['Name']) . ".";
        } else {
            $bottleneck_msg = "Severe GPU bottleneck (~{$bottleneck_pct}%) — Your GPU is significantly underutilized compared to the CPU.";
            $bottleneck_fix = "Upgrade your GPU to better match your CPU's performance.";
        }
    }
}

$build_complete = count($build_items) === count($slots);
$psu_pct = ($psu_wattage > 0 && $rec_psu > 0) ? min(100, round($psu_wattage / max($rec_psu, $psu_wattage) * 100)) : 0;
$psu_color = ($psu_wattage >= $rec_psu && $rec_psu > 0) ? '#10b981' : (($rec_psu > 0) ? '#ef4444' : '#72a0b8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My PC Build</title>
    <style>
        :root {
            --bg: #0f172a; --card: #1e293b; --text: #f8fafc;
            --muted: #94a3b8; --accent: #3b82f6;
            --success: #10b981; --danger: #ef4444; --warn: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); padding: 2rem 1rem; }

        .container { max-width: 920px; margin: 0 auto; }
        .back-link { display: inline-block; color: var(--accent); text-decoration: none; margin-bottom: 1.5rem; font-weight: bold; }
        .back-link:hover { text-decoration: underline; }
        h1 { font-size: 2.2rem; border-bottom: 2px solid #334155; padding-bottom: 1rem; margin-bottom: 1.5rem; }

        /* ---- PROGRESS ---- */
        .progress-section { background: #0b1120; border-radius: 10px; padding: 1.2rem 1.5rem; margin-bottom: 1.5rem; border: 1px solid #334155; }
        .progress-header { display: flex; justify-content: space-between; margin-bottom: .7rem; font-size: .9rem; color: var(--muted); }
        .progress-bar { background: #1e293b; border-radius: 20px; height: 10px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 20px; background: linear-gradient(90deg, var(--success), #34d399); transition: width .5s; }

        /* ---- COMPATIBILITY REPORT ---- */
        .compat-report { border-radius: 10px; padding: 1.2rem 1.5rem; margin-bottom: 1.5rem; border: 1px solid #334155; }
        .compat-report.has-issues { border-color: var(--danger); background: rgba(239,68,68,.05); }
        .compat-report.all-ok     { border-color: var(--success); background: rgba(16,185,129,.05); }
        .compat-report.empty      { border-color: #334155; background: #0b1120; }
        .compat-title { font-weight: bold; font-size: 1.05rem; margin-bottom: .8rem; }
        .compat-item { font-size: .9rem; padding: .3rem 0; color: var(--muted); }
        .compat-item.issue { color: #fca5a5; }
        .compat-item.ok    { color: #6ee7b7; }

        /* ---- BUILD SLOTS ---- */
        .build-list { background: var(--card); border-radius: 12px; overflow: hidden; border: 1px solid #334155; margin-bottom: 1.5rem; }
        .slot {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.3rem 1.5rem; border-bottom: 1px solid #334155;
            gap: 1rem; transition: background .2s;
        }
        .slot:last-child { border-bottom: none; }
        .slot:hover { background: #0b1120; }
        .slot-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
        .slot-icon { font-size: 1.6rem; flex-shrink: 0; }
        .slot-info { min-width: 0; }
        .slot-label { font-size: .8rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: .3rem; }
        .slot-name  { font-size: 1.1rem; font-weight: bold; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .slot-empty { color: #475569; font-style: italic; }
        .slot-right { display: flex; align-items: center; gap: 1rem; flex-shrink: 0; }
        .slot-price { font-size: 1.15rem; color: var(--success); font-weight: bold; }
        .btn-choose { background: var(--accent); color: white; padding: 7px 16px; border-radius: 6px; text-decoration: none; font-size: .85rem; font-weight: bold; white-space: nowrap; }
        .btn-remove { background: transparent; border: 1px solid var(--danger); color: var(--danger); padding: 7px 14px; border-radius: 6px; text-decoration: none; font-size: .85rem; transition: all .2s; white-space: nowrap; }
        .btn-remove:hover { background: var(--danger); color: white; }

        /* ---- PSU METER ---- */
        .psu-meter { background: #0b1120; border-radius: 10px; padding: 1.3rem 1.5rem; margin-bottom: 1.5rem; border: 1px solid #334155; }
        .psu-header { display: flex; justify-content: space-between; margin-bottom: .8rem; }
        .psu-title { font-weight: bold; }
        .psu-stats { font-size: .85rem; color: var(--muted); }
        .psu-bar  { background: #1e293b; border-radius: 20px; height: 12px; overflow: hidden; }
        .psu-fill { height: 100%; border-radius: 20px; transition: width .5s, background .5s; }
        .psu-labels { display: flex; justify-content: space-between; font-size: .78rem; color: var(--muted); margin-top: .5rem; }

        /* ---- BOTTLENECK ---- */
        .bn-section { border-radius: 10px; padding: 1.3rem 1.5rem; margin-bottom: 1.5rem; border: 1px solid #334155; }
        .bn-ok       { background: rgba(16,185,129,.06); border-color: var(--success); }
        .bn-minor    { background: rgba(59,130,246,.06); border-color: #3b82f6; }
        .bn-moderate { background: rgba(245,158,11,.07); border-color: var(--warn); }
        .bn-severe   { background: rgba(239,68,68,.08);  border-color: var(--danger); }
        .bn-header   { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: .6rem; }
        .bn-title    { font-weight: bold; font-size: 1.05rem; }
        .bn-badge    { padding: 4px 14px; border-radius: 20px; font-size: .82rem; font-weight: bold; }
        .bn-badge.ok       { background: rgba(16,185,129,.2); color: #6ee7b7; border: 1px solid var(--success); }
        .bn-badge.minor    { background: rgba(59,130,246,.2); color: #93c5fd; border: 1px solid #3b82f6; }
        .bn-badge.moderate { background: rgba(245,158,11,.2); color: #fcd34d; border: 1px solid var(--warn); }
        .bn-badge.severe   { background: rgba(239,68,68,.2);  color: #fca5a5; border: 1px solid var(--danger); }
        .bn-bar-wrap { margin-bottom: .8rem; }
        .bn-bar-labels { display: flex; justify-content: space-between; font-size: .75rem; color: var(--muted); margin-bottom: .4rem; }
        .bn-bar-track  { background: #1e293b; border-radius: 20px; height: 14px; overflow: hidden; position: relative; }
        .bn-bar-fill   { height: 100%; border-radius: 20px; transition: width .6s; }
        .bn-bar-ok       { background: linear-gradient(90deg, #10b981, #34d399); }
        .bn-bar-minor    { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .bn-bar-moderate { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .bn-bar-severe   { background: linear-gradient(90deg, #ef4444, #f87171); }
        .bn-scores { display: flex; gap: 1rem; margin-bottom: .8rem; flex-wrap: wrap; }
        .bn-score-box { background: #0b1120; border-radius: 8px; padding: .7rem 1rem; flex: 1; min-width: 130px; border: 1px solid #1e3a5f; }
        .bn-score-lbl { font-size: .72rem; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: .25rem; }
        .bn-score-val { font-size: 1.2rem; font-weight: bold; }
        .bn-score-bar { height: 4px; border-radius: 4px; margin-top: .4rem; }
        .bn-msg  { font-size: .88rem; color: var(--muted); line-height: 1.6; }
        .bn-fix  { margin-top: .5rem; font-size: .85rem; padding: .7rem 1rem; border-radius: 8px; background: rgba(245,158,11,.1); border: 1px solid var(--warn); color: #fcd34d; }
        .bn-fix strong { color: var(--warn); }

        /* ---- SAVE BUILD ---- */
        .save-section { background: #0b1120; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid var(--accent); }
        .save-title { font-size: 1.1rem; font-weight: bold; margin-bottom: 1rem; }
        .save-row { display: flex; gap: .8rem; flex-wrap: wrap; align-items: flex-end; }
        .save-row input[type=text] {
            flex: 1; min-width: 200px; padding: 10px 14px; background: #042a50;
            border: 1px solid #1a4a7a; border-radius: 8px; color: white;
            font-size: .95rem; outline: none;
        }
        .save-row input:focus { border-color: var(--accent); }
        .save-row select {
            padding: 10px 14px; background: #042a50; border: 1px solid #1a4a7a;
            border-radius: 8px; color: white; font-size: .95rem; outline: none;
        }
        .btn-save { background: var(--success); color: #000; border: none; padding: 10px 24px; border-radius: 8px; font-weight: bold; font-size: .95rem; cursor: pointer; transition: background .2s; white-space: nowrap; }
        .btn-save:hover { background: #34d399; }
        .btn-mybuilds { display: inline-block; background: transparent; border: 1px solid var(--accent); color: var(--accent); padding: 9px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: .9rem; transition: all .2s; }
        .btn-mybuilds:hover { background: var(--accent); color: white; }
        .alert { padding: 1rem 1.2rem; border-radius: 8px; margin-bottom: 1.2rem; font-size: .92rem; }
        .alert.success { background: rgba(16,185,129,.12); border: 1px solid var(--success); color: #6ee7b7; }
        .alert.warn    { background: rgba(245,158,11,.1);  border: 1px solid var(--warn);    color: #fcd34d; }
        .login-prompt { background: rgba(11,96,156,.15); border: 1px solid var(--accent); border-radius: 10px; padding: 1.2rem 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        .login-prompt p { color: var(--muted); font-size: .95rem; }
        .btn-login-sm { background: var(--accent); color: white; padding: 8px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: .88rem; }

        /* ---- TOTAL ---- */
        .total-section {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.5rem 2rem; background: #0b1120; border-radius: 10px;
            border: 1px solid var(--success);
        }
        .total-label { font-size: 1.3rem; font-weight: bold; }
        .total-price { font-size: 2.2rem; color: var(--success); font-weight: bold; }
        .complete-badge { display: inline-block; background: var(--success); color: #000; padding: 4px 12px; border-radius: 20px; font-size: .8rem; font-weight: bold; margin-left: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <a href="index.php" class="back-link">← Continue Shopping</a>
    <h1>
        🖥️ Your Custom Build
        <?php if ($build_complete): ?><span class="complete-badge">✔ Complete</span><?php endif; ?>
    </h1>

    <!-- BUILD PROGRESS -->
    <?php $pct = count($build_items) / count($slots) * 100; ?>
    <div class="progress-section">
        <div class="progress-header">
            <span><?= count($build_items) ?>/<?= count($slots) ?> components selected</span>
            <span><?= round($pct) ?>% complete</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width:<?= $pct ?>%"></div>
        </div>
    </div>

    <!-- COMPATIBILITY REPORT -->
    <?php
    $report_class = (count($compat_issues) > 0) ? 'has-issues' : ((count($compat_ok) > 0) ? 'all-ok' : 'empty');
    ?>
    <div class="compat-report <?= $report_class ?>">
        <div class="compat-title">
            <?php if (count($compat_issues) > 0): ?>
                ⚠️ Compatibility Issues Found (<?= count($compat_issues) ?>)
            <?php elseif (count($compat_ok) > 0): ?>
                ✅ All Checks Passed
            <?php else: ?>
                🔗 Compatibility Report (add components to see checks)
            <?php endif; ?>
        </div>
        <?php foreach ($compat_issues as $issue): ?>
            <div class="compat-item issue"><?= htmlspecialchars($issue) ?></div>
        <?php endforeach; ?>
        <?php foreach ($compat_ok as $ok): ?>
            <div class="compat-item ok"><?= htmlspecialchars($ok) ?></div>
        <?php endforeach; ?>
        <?php if (empty($compat_issues) && empty($compat_ok)): ?>
            <div class="compat-item">Add at least two components to start checking compatibility.</div>
        <?php endif; ?>
    </div>

    <!-- BUILD SLOTS -->
    <div class="build-list">
        <?php foreach ($slots as $slot_key => $slot_meta): ?>
        <div class="slot">
            <div class="slot-left">
                <span class="slot-icon"><?= $slot_meta['icon'] ?></span>
                <div class="slot-info">
                    <div class="slot-label"><?= $slot_meta['label'] ?></div>
                    <?php if (isset($build_items[$slot_key])): ?>
                        <div class="slot-name"><?= htmlspecialchars($build_items[$slot_key]['Name']) ?></div>
                    <?php else: ?>
                        <div class="slot-name slot-empty">Not Selected</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="slot-right">
                <?php if (isset($build_items[$slot_key])): ?>
                    <span class="slot-price">৳ <?= number_format($build_items[$slot_key]['Price'], 2) ?></span>
                    <a href="build.php?remove=<?= $slot_key ?>" class="btn-remove">Remove</a>
                <?php else: ?>
                    <a href="index.php?slot=<?= $slot_key ?>" class="btn-choose">Choose →</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- BOTTLENECK DETECTOR -->
    <?php if ($cpu_passmark > 0 && $gpu_perf > 0): ?>
    <?php
        $bn_icons = ['ok' => '✅', 'minor' => '🔵', 'moderate' => '⚠️', 'severe' => '🔴'];
        $bn_labels = ['ok' => 'Well Balanced', 'minor' => 'Minor Bottleneck', 'moderate' => 'Moderate Bottleneck', 'severe' => 'Severe Bottleneck'];
        $bn_icon  = $bn_icons[$bottleneck_level];
        $bn_label = $bn_labels[$bottleneck_level];
        // Scores normalized to % of max for bar display
        $max_score   = max($cpu_passmark, $gpu_perf);
        $cpu_bar_pct = round($cpu_passmark / $max_score * 100);
        $gpu_bar_pct = round($gpu_perf / $max_score * 100);
        $cpu_name    = $build_items['cpu']['Name'] ?? 'CPU';
        $gpu_name    = $build_items['gpu']['Name'] ?? 'GPU';
    ?>
    <div class="bn-section bn-<?= $bottleneck_level ?>">
        <div class="bn-header">
            <div class="bn-title">⚡ Bottleneck Analysis</div>
            <span class="bn-badge <?= $bottleneck_level ?>"><?= $bn_icon ?> <?= $bn_label ?></span>
        </div>

        <!-- BOTTLENECK BAR -->
        <div class="bn-bar-wrap">
            <div class="bn-bar-labels">
                <span>0% Bottleneck</span>
                <span style="color:<?= $bottleneck_level==='ok'?'#10b981':($bottleneck_level==='minor'?'#60a5fa':($bottleneck_level==='moderate'?'#fbbf24':'#f87171')) ?>">
                    <?= $bottleneck_pct ?>% bottleneck
                    <?= $bottleneck_type === 'cpu' ? '(CPU limited)' : '(GPU limited)' ?>
                </span>
                <span>100%</span>
            </div>
            <div class="bn-bar-track">
                <div class="bn-bar-fill bn-bar-<?= $bottleneck_level ?>" style="width:<?= $bottleneck_pct ?>%"></div>
            </div>
        </div>

        <!-- CPU vs GPU SCORE BOXES -->
        <div class="bn-scores">
            <div class="bn-score-box" style="<?= $bottleneck_type==='cpu' && $bottleneck_level!=='ok' ? 'border-color:var(--danger);' : '' ?>">
                <div class="bn-score-lbl">🔲 CPU Score <?= $bottleneck_type==='cpu' && $bottleneck_level!=='ok' ? '⚠️ Bottleneck' : '' ?></div>
                <div class="bn-score-val" style="color:<?= $bottleneck_type==='cpu'&&$bottleneck_level!=='ok'?'#f87171':'#6ee7b7' ?>"><?= number_format($cpu_passmark) ?></div>
                <div class="bn-score-bar" style="background:<?= $bottleneck_type==='cpu'&&$bottleneck_level!=='ok'?'#ef4444':'#10b981' ?>;width:<?= $cpu_bar_pct ?>%"></div>
                <div style="font-size:.72rem;color:var(--muted);margin-top:.3rem;"><?= htmlspecialchars($cpu_name) ?></div>
            </div>
            <div style="display:flex;align-items:center;font-size:1.3rem;color:var(--muted);">vs</div>
            <div class="bn-score-box" style="<?= $bottleneck_type==='gpu' && $bottleneck_level!=='ok' ? 'border-color:var(--danger);' : '' ?>">
                <div class="bn-score-lbl">🎮 GPU Score <?= $bottleneck_type==='gpu' && $bottleneck_level!=='ok' ? '⚠️ Bottleneck' : '' ?></div>
                <div class="bn-score-val" style="color:<?= $bottleneck_type==='gpu'&&$bottleneck_level!=='ok'?'#f87171':'#93c5fd' ?>"><?= number_format($gpu_perf) ?></div>
                <div class="bn-score-bar" style="background:<?= $bottleneck_type==='gpu'&&$bottleneck_level!=='ok'?'#ef4444':'#3b82f6' ?>;width:<?= $gpu_bar_pct ?>%"></div>
                <div style="font-size:.72rem;color:var(--muted);margin-top:.3rem;"><?= htmlspecialchars($gpu_name) ?></div>
            </div>
        </div>

        <!-- MESSAGE -->
        <div class="bn-msg"><?= $bottleneck_msg ?></div>
        <?php if ($bottleneck_fix): ?>
        <div class="bn-fix"><strong>💡 Recommendation:</strong> <?= $bottleneck_fix ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- PSU WATTAGE METER (only when CPU or GPU chosen) -->
    <?php if ($total_tdp > 0): ?>
    <div class="psu-meter">
        <div class="psu-header">
            <span class="psu-title">⚡ Power Budget</span>
            <span class="psu-stats">
                System load: ~<?= (int)$total_tdp ?>W
                (CPU <?= (int)$cpu_tdp ?>W<?= $gpu_tdp > 0 ? " + GPU " . (int)$gpu_tdp . "W" : "" ?>)
                &nbsp;|&nbsp; Recommended PSU: <?= $rec_psu ?>W
            </span>
        </div>
        <div class="psu-bar">
            <?php if ($psu_wattage > 0): ?>
                <div class="psu-fill" style="width:<?= $psu_pct ?>%;background:<?= $psu_color ?>"></div>
            <?php else: ?>
                <div class="psu-fill" style="width:0%"></div>
            <?php endif; ?>
        </div>
        <div class="psu-labels">
            <span>0W</span>
            <?php if ($psu_wattage > 0): ?>
                <span style="color:<?= $psu_color ?>">
                    <?php if ($psu_wattage >= $rec_psu): ?>
                        ✅ PSU: <?= $psu_wattage ?>W — Sufficient
                    <?php else: ?>
                        ⚠️ PSU: <?= $psu_wattage ?>W — Need <?= $rec_psu ?>W+
                    <?php endif; ?>
                </span>
            <?php else: ?>
                <span style="color:var(--muted)">No PSU selected yet</span>
            <?php endif; ?>
            <span><?= max($psu_wattage, $rec_psu, 100) ?>W</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- SAVE BUILD / LOGIN PROMPT -->
    <?php if ($save_msg): ?>
        <div class="alert <?= $save_msg_type ?>"><?= $save_msg ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="save-section">
        <div class="save-title">💾 Save This Build</div>
        <form method="POST">
            <div class="save-row">
                <input type="text" name="build_name"
                       placeholder="Give your build a name (e.g. Gaming Beast 2026)"
                       maxlength="100">
                <?php if (!empty($user_builds)): ?>
                <select name="overwrite_id" title="Overwrite an existing build (optional)">
                    <option value="0">— Save as new build —</option>
                    <?php foreach ($user_builds as $ub): ?>
                        <option value="<?= $ub['build_id'] ?>">
                            Overwrite: <?= htmlspecialchars($ub['build_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                <button type="submit" name="save_build" class="btn-save">💾 Save Build</button>
            </div>
        </form>
        <?php if (!empty($user_builds)): ?>
        <div style="margin-top:1rem;">
            <a href="my_builds.php" class="btn-mybuilds">📂 View My Saved Builds (<?= count($user_builds) ?>)</a>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="login-prompt">
        <p>🔒 <strong>Sign in</strong> to save this build and access it anytime!</p>
        <div style="display:flex;gap:.8rem;flex-wrap:wrap;">
            <a href="login.php" class="btn-login-sm">Sign In</a>
            <a href="register.php" class="btn-login-sm" style="background:var(--success);color:#000;">Register Free</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- TOTAL + PRINT + BUY BUTTONS -->
    <div class="total-section">
        <div class="total-label">Total Estimated Price:</div>
        <div class="total-price">৳ <?= number_format($total_price, 2) ?></div>
    </div>

    <?php if (!empty($build_items)): ?>
    <?php
    // Check if any item is out of stock
    $out_of_stock_items = [];
    foreach ($build_items as $slot => $item) {
        if ((int)$item['stock_quantity'] === 0) {
            $out_of_stock_items[] = $item['Name'];
        }
    }
    $can_buy = empty($out_of_stock_items);
    ?>
    <div style="display:flex;gap:.8rem;margin-top:1rem;flex-wrap:wrap;">
        <?php if ($can_buy): ?>
        <a href="checkout.php?mode=build"
           style="flex:2;min-width:160px;text-align:center;padding:13px;background:var(--success);color:#000;border-radius:8px;text-decoration:none;font-weight:bold;font-size:1rem;transition:background .2s;"
           onmouseover="this.style.background='#34d399'" onmouseout="this.style.background='var(--success)'">
            🛍️ Buy This Build
        </a>
        <?php else: ?>
        <div style="flex:2;min-width:160px;text-align:center;padding:13px;background:#1e293b;color:#64748b;border-radius:8px;font-weight:bold;font-size:.9rem;border:1px solid #334155;">
            ❌ Out of Stock: <?= htmlspecialchars(implode(', ', $out_of_stock_items)) ?>
        </div>
        <?php endif; ?>
        <a href="print_build.php" target="_blank"
           style="flex:1;min-width:130px;text-align:center;padding:13px;background:#334155;color:#e2e8f0;border-radius:8px;text-decoration:none;font-weight:bold;font-size:.9rem;transition:background .2s;"
           onmouseover="this.style.background='#475569'" onmouseout="this.style.background='#334155'">
            🖨️ Print
        </a>
        <a href="print_build.php" target="_blank"
           style="flex:1;min-width:130px;text-align:center;padding:13px;background:#7c3aed;color:white;border-radius:8px;text-decoration:none;font-weight:bold;font-size:.9rem;transition:background .2s;"
           onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">
            📄 PDF
        </a>
    </div>
    <?php endif; ?>

</div>
<?php $conn->close(); ?>
</body>
</html>