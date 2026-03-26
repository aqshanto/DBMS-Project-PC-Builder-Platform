<?php
session_start();
require_once 'config.php';

$conn = get_db_connection();

// --- LOAD BUILD ITEMS ---
$slots = [
    'cpu'         => ['label' => 'Processor (CPU)',     'icon' => '🔲'],
    'motherboard' => ['label' => 'Motherboard',         'icon' => '📋'],
    'ram'         => ['label' => 'Memory (RAM)',         'icon' => '💾'],
    'gpu'         => ['label' => 'Graphics Card (GPU)', 'icon' => '🎮'],
    'powersupply' => ['label' => 'Power Supply (PSU)',  'icon' => '⚡'],
    'storage'     => ['label' => 'Storage',              'icon' => '💿'],
    'case'        => ['label' => 'PC Case',              'icon' => '📦'],
];

$build_items = [];
$total_price = 0;

if (!empty($_SESSION['build'])) {
    foreach ($_SESSION['build'] as $type => $id) {
        $id = (int)$id;
        $r  = $conn->query("SELECT * FROM components WHERE component_id=$id");
        if ($r && $r->num_rows > 0) {
            $build_items[$type] = $r->fetch_assoc();
            $total_price += (float)$build_items[$type]['Price'];
        }
    }
}

// --- COMPATIBILITY CHECKS ---
$compat_issues = [];
$compat_ok     = [];
$cpu_tdp = $gpu_tdp = $psu_wattage = 0;

if (!empty($build_items['cpu'])) {
    $r = $conn->query("SELECT Socket, tdp_watt FROM cpus WHERE component_id=" . (int)$build_items['cpu']['component_id'])->fetch_assoc();
    if ($r) { $cpu_socket = $r['Socket']; $cpu_tdp = (float)$r['tdp_watt']; }
}
if (!empty($build_items['motherboard'])) {
    $r = $conn->query("SELECT Socket, supported_ram_type, Form_Factor FROM motherboards WHERE component_id=" . (int)$build_items['motherboard']['component_id'])->fetch_assoc();
    if ($r) { $mb_socket = $r['Socket']; $mb_ram = $r['supported_ram_type']; $mb_ff = $r['Form_Factor']; }
}
if (!empty($build_items['ram'])) {
    $r = $conn->query("SELECT DDR_Version FROM rams WHERE component_id=" . (int)$build_items['ram']['component_id'])->fetch_assoc();
    if ($r) $ram_ddr = $r['DDR_Version'];
}
if (!empty($build_items['gpu'])) {
    $r = $conn->query("SELECT TDP_Watt, GPU_Length_mm FROM gpus WHERE component_id=" . (int)$build_items['gpu']['component_id'])->fetch_assoc();
    if ($r) { $gpu_tdp = (float)$r['TDP_Watt']; $gpu_len = (int)$r['GPU_Length_mm']; }
}
if (!empty($build_items['powersupply'])) {
    $r = $conn->query("SELECT Wattage FROM powersupplies WHERE component_id=" . (int)$build_items['powersupply']['component_id'])->fetch_assoc();
    if ($r) $psu_wattage = (int)$r['Wattage'];
}
if (!empty($build_items['case'])) {
    $r = $conn->query("SELECT Form_Factor, Max_GPU_Length FROM cases WHERE component_id=" . (int)$build_items['case']['component_id'])->fetch_assoc();
    if ($r) { $case_ff = $r['Form_Factor']; $max_gpu = (int)$r['Max_GPU_Length']; }
}

// Run checks
if (!empty($build_items['cpu']) && !empty($build_items['motherboard']) && isset($cpu_socket, $mb_socket)) {
    if ($cpu_socket === $mb_socket) $compat_ok[] = "CPU socket ({$cpu_socket}) matches Motherboard";
    else $compat_issues[] = "Socket mismatch: CPU needs {$cpu_socket}, Motherboard has {$mb_socket}";
}
if (!empty($build_items['motherboard']) && !empty($build_items['ram']) && isset($mb_ram, $ram_ddr)) {
    if ($mb_ram === $ram_ddr) $compat_ok[] = "RAM type ({$ram_ddr}) supported by Motherboard";
    else $compat_issues[] = "RAM mismatch: Motherboard supports {$mb_ram}, selected {$ram_ddr}";
}
$total_tdp = $cpu_tdp + $gpu_tdp;
$rec_psu   = $total_tdp > 0 ? (int)($total_tdp * 1.3 + 50) : 0;
if ($psu_wattage > 0 && $rec_psu > 0) {
    if ($psu_wattage >= $rec_psu) $compat_ok[] = "PSU ({$psu_wattage}W) is sufficient (recommended: {$rec_psu}W)";
    else $compat_issues[] = "PSU too weak: {$psu_wattage}W selected, {$rec_psu}W recommended";
}
if (!empty($build_items['gpu']) && !empty($build_items['case']) && isset($gpu_len, $max_gpu)) {
    if ($gpu_len <= $max_gpu) $compat_ok[] = "GPU length ({$gpu_len}mm) fits in Case (max {$max_gpu}mm)";
    else $compat_issues[] = "GPU too long: {$gpu_len}mm exceeds case max of {$max_gpu}mm";
}

$print_date   = date('F d, Y');
$build_count  = count($build_items);
$username     = $_SESSION['username'] ?? 'Guest';
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Build Summary — NextGen PC Builder</title>
    <style>
        /* ---- SCREEN STYLES ---- */
        :root {
            --accent: #0b609c; --success: #10b981; --danger: #ef4444;
            --warn: #f59e0b; --muted: #64748b; --dark: #0f172a;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f1f5f9; color: #1e293b;
            padding: 2rem 1rem; min-height: 100vh;
        }

        /* Screen-only action bar */
        .action-bar {
            max-width: 820px; margin: 0 auto 1.5rem;
            display: flex; gap: .8rem; flex-wrap: wrap; align-items: center;
        }
        .btn {
            padding: 10px 22px; border-radius: 8px; border: none;
            font-weight: bold; font-size: .9rem; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
            transition: all .2s;
        }
        .btn-print  { background: var(--accent); color: white; }
        .btn-print:hover  { background: #0d73ba; }
        .btn-pdf    { background: #7c3aed; color: white; }
        .btn-pdf:hover    { background: #6d28d9; }
        .btn-back   { background: #e2e8f0; color: var(--dark); }
        .btn-back:hover   { background: #cbd5e1; }

        /* ---- DOCUMENT ---- */
        .document {
            max-width: 820px; margin: 0 auto;
            background: white; border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
            overflow: hidden;
        }

        /* Header */
        .doc-header {
            background: linear-gradient(135deg, #031c34, #0b609c);
            color: white; padding: 2.5rem;
            display: flex; justify-content: space-between; align-items: flex-start;
            flex-wrap: wrap; gap: 1rem;
        }
        .doc-logo { font-size: 1.8rem; font-weight: bold; letter-spacing: -.5px; }
        .doc-logo span { color: #72a0b8; }
        .doc-tagline { color: #b6cddc; font-size: .9rem; margin-top: .2rem; }
        .doc-meta { text-align: right; font-size: .85rem; color: #b6cddc; line-height: 1.8; }
        .doc-meta strong { color: white; }

        /* Body */
        .doc-body { padding: 2rem 2.5rem; }
        .section-title {
            font-size: .75rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 2px; color: var(--muted); border-bottom: 2px solid #e2e8f0;
            padding-bottom: .5rem; margin: 1.8rem 0 1rem;
        }
        .section-title:first-child { margin-top: 0; }

        /* Components table */
        .components-table { width: 100%; border-collapse: collapse; }
        .components-table th {
            background: #f8fafc; text-align: left; padding: .7rem 1rem;
            font-size: .78rem; text-transform: uppercase; letter-spacing: 1px;
            color: var(--muted); border-bottom: 2px solid #e2e8f0;
        }
        .components-table td { padding: .9rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: .92rem; }
        .components-table tr:last-child td { border-bottom: none; }
        .components-table tr:hover td { background: #f8fafc; }
        .comp-slot-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; color: var(--muted); }
        .comp-name-cell  { font-weight: 600; color: #1e293b; }
        .comp-brand-cell { font-size: .82rem; color: var(--muted); }
        .comp-price-cell { text-align: right; font-weight: 700; color: var(--accent); white-space: nowrap; }
        .empty-slot td   { color: #cbd5e1 !important; font-style: italic; }

        /* Compatibility */
        .compat-list { list-style: none; }
        .compat-list li { padding: .45rem 0; font-size: .9rem; display: flex; align-items: center; gap: .6rem; }
        .compat-list li::before { font-size: 1rem; flex-shrink: 0; }
        .compat-ok   { color: #065f46; }
        .compat-ok::before { content: "✅"; }
        .compat-fail { color: #991b1b; }
        .compat-fail::before { content: "❌"; }

        /* PSU bar */
        .psu-row { display: flex; align-items: center; gap: 1rem; margin-top: .8rem; flex-wrap: wrap; }
        .psu-bar-wrap { flex: 1; background: #e2e8f0; border-radius: 20px; height: 10px; overflow: hidden; min-width: 120px; }
        .psu-bar-fill { height: 100%; border-radius: 20px; }
        .psu-note { font-size: .82rem; color: var(--muted); }

        /* Total */
        .total-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.5rem 2.5rem; background: #f8fafc;
            border-top: 2px solid #e2e8f0; margin-top: 1rem;
        }
        .total-label { font-size: 1.1rem; font-weight: bold; color: #1e293b; }
        .total-amt   { font-size: 2rem; font-weight: 800; color: var(--accent); }

        /* Footer */
        .doc-footer {
            background: #f8fafc; border-top: 1px solid #e2e8f0;
            padding: 1rem 2.5rem; display: flex; justify-content: space-between;
            font-size: .78rem; color: var(--muted); flex-wrap: wrap; gap: .5rem;
        }

        /* Badges */
        .badge {
            display: inline-block; padding: 2px 10px; border-radius: 20px;
            font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        }
        .badge-ok   { background: #d1fae5; color: #065f46; }
        .badge-warn { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }

        /* ---- PRINT STYLES ---- */
        @media print {
            body { background: white; padding: 0; }
            .action-bar { display: none !important; }
            .document { box-shadow: none; border-radius: 0; max-width: 100%; }
            .doc-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .total-row  { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge      { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 0.5in; size: A4; }
        }
    </style>
</head>
<body>

<!-- ACTION BAR (hidden on print) -->
<div class="action-bar">
    <a href="build.php" class="btn btn-back">← Back to Build</a>
    <button onclick="window.print()" class="btn btn-print">🖨️ Print</button>
    <button onclick="downloadPDF()" class="btn btn-pdf">📄 Save as PDF</button>
</div>

<!-- THE DOCUMENT -->
<div class="document" id="printDoc">

    <!-- HEADER -->
    <div class="doc-header">
        <div>
            <div class="doc-logo">⚡ NextGen <span>PC Builder</span></div>
            <div class="doc-tagline">Custom PC Build Summary</div>
        </div>
        <div class="doc-meta">
            <div>📅 <strong><?= $print_date ?></strong></div>
            <div>👤 <strong><?= htmlspecialchars($username) ?></strong></div>
            <div>🖥️ <strong><?= $build_count ?>/7</strong> components</div>
            <?php if ($build_count === 7): ?>
                <div><span class="badge badge-ok" style="margin-top:.3rem;">✔ Complete Build</span></div>
            <?php else: ?>
                <div><span class="badge badge-warn" style="margin-top:.3rem;">⚠ Incomplete Build</span></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="doc-body">

        <!-- COMPONENTS TABLE -->
        <div class="section-title">Selected Components</div>
        <table class="components-table">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:25%">Category</th>
                    <th style="width:45%">Component</th>
                    <th style="width:15%">Brand</th>
                    <th style="width:10%">Price</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($slots as $slot_key => $slot_meta): ?>
                <?php if (isset($build_items[$slot_key])): $c = $build_items[$slot_key]; ?>
                <tr>
                    <td style="color:var(--muted);font-size:.82rem;"><?= $i++ ?></td>
                    <td>
                        <span class="comp-slot-label"><?= $slot_meta['icon'] ?> <?= $slot_meta['label'] ?></span>
                    </td>
                    <td><span class="comp-name-cell"><?= htmlspecialchars($c['Name']) ?></span></td>
                    <td><span class="comp-brand-cell"><?= htmlspecialchars($c['Brand']) ?></span></td>
                    <td class="comp-price-cell">৳ <?= number_format($c['Price'], 2) ?></td>
                </tr>
                <?php else: ?>
                <tr class="empty-slot">
                    <td style="color:#cbd5e1;font-size:.82rem;">—</td>
                    <td><span class="comp-slot-label"><?= $slot_meta['icon'] ?> <?= $slot_meta['label'] ?></span></td>
                    <td colspan="3" style="color:#cbd5e1;font-style:italic;">Not selected</td>
                </tr>
                <?php endif; endforeach; ?>
            </tbody>
        </table>

        <!-- COMPATIBILITY REPORT -->
        <?php if (!empty($compat_ok) || !empty($compat_issues)): ?>
        <div class="section-title">Compatibility Report</div>
        <ul class="compat-list">
            <?php foreach ($compat_ok as $ok): ?>
                <li class="compat-ok"><?= htmlspecialchars($ok) ?></li>
            <?php endforeach; ?>
            <?php foreach ($compat_issues as $issue): ?>
                <li class="compat-fail"><?= htmlspecialchars($issue) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <!-- PSU POWER BUDGET -->
        <?php if ($total_tdp > 0): ?>
        <div class="section-title">Power Budget</div>
        <?php
            $psu_pct   = $psu_wattage > 0 ? min(100, round($psu_wattage / max($rec_psu, $psu_wattage) * 100)) : 0;
            $psu_color = ($psu_wattage >= $rec_psu && $rec_psu > 0) ? '#10b981' : '#ef4444';
        ?>
        <div class="psu-row">
            <span class="psu-note">System load: ~<?= (int)$total_tdp ?>W</span>
            <div class="psu-bar-wrap">
                <div class="psu-bar-fill" style="width:<?= $psu_pct ?>%;background:<?= $psu_color ?>;"></div>
            </div>
            <?php if ($psu_wattage > 0): ?>
                <span class="psu-note" style="color:<?= $psu_color ?>;font-weight:bold;">
                    PSU: <?= $psu_wattage ?>W / Recommended: <?= $rec_psu ?>W
                </span>
            <?php else: ?>
                <span class="psu-note">No PSU selected — recommended: <?= $rec_psu ?>W+</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- doc-body -->

    <!-- TOTAL -->
    <div class="total-row">
        <span class="total-label">Total Estimated Price</span>
        <span class="total-amt">৳ <?= number_format($total_price, 2) ?></span>
    </div>

    <!-- FOOTER -->
    <div class="doc-footer">
        <span>Generated by NextGen PC Builder — pcbuilder.infinityfreeapp.com</span>
        <span>Prices are estimates and may vary. <?= $print_date ?></span>
    </div>

</div><!-- document -->

<script>
// Save as PDF using browser's built-in print-to-PDF
function downloadPDF() {
    // Set document title so the PDF filename is meaningful
    const original = document.title;
    document.title = 'PC_Build_Summary_<?= date('Y-m-d') ?>';

    // Trigger print dialog (user selects "Save as PDF")
    window.print();

    // Restore title
    setTimeout(() => document.title = original, 1000);
}
</script>

</body>
</html>
