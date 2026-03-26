<?php
session_start();
require_once 'config.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=required");
    exit();
}

$conn    = get_db_connection();
$user_id = (int)$_SESSION['user_id'];
$msg     = "";
$msg_type = "";

// --- LOAD A SAVED BUILD INTO SESSION ---
if (isset($_GET['load'])) {
    $build_id = (int)$_GET['load'];

    // Verify this build belongs to the current user
    $check = $conn->prepare("SELECT build_id FROM builds WHERE build_id=? AND user_id=?");
    $check->bind_param("ii", $build_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Clear current session build
        $_SESSION['build'] = [];

        // Load components into session
        $stmt = $conn->prepare("SELECT component_id, slot_type FROM build_components WHERE build_id=?");
        $stmt->bind_param("i", $build_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $_SESSION['build'][$row['slot_type']] = $row['component_id'];
        }
        $stmt->close();
        header("Location: build.php?loaded=1");
        exit();
    } else {
        $msg = "Build not found or access denied.";
        $msg_type = "error";
    }
    $check->close();
}

// --- DELETE A SAVED BUILD ---
if (isset($_GET['delete'])) {
    $build_id = (int)$_GET['delete'];

    $check = $conn->prepare("SELECT build_id FROM builds WHERE build_id=? AND user_id=?");
    $check->bind_param("ii", $build_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM build_components WHERE build_id=$build_id");
        $conn->query("DELETE FROM builds WHERE build_id=$build_id");
        header("Location: my_builds.php?msg=deleted");
        exit();
    }
    $check->close();
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'saved')   { $msg = "✅ Build saved successfully!";  $msg_type = "success"; }
    if ($_GET['msg'] === 'deleted') { $msg = "🗑️ Build deleted.";             $msg_type = "info"; }
    if ($_GET['msg'] === 'updated') { $msg = "✅ Build updated successfully!"; $msg_type = "success"; }
}

// --- FETCH ALL BUILDS FOR THIS USER ---
$builds_data = [];
$builds_res = $conn->prepare("SELECT * FROM builds WHERE user_id=? ORDER BY created_at DESC");
$builds_res->bind_param("i", $user_id);
$builds_res->execute();
$builds_list = $builds_res->get_result();

while ($build = $builds_list->fetch_assoc()) {
    $bid = $build['build_id'];

    // Get components for this build
    $comp_res = $conn->query("
        SELECT bc.slot_type, c.Name, c.Price, c.Type
        FROM build_components bc
        JOIN components c ON bc.component_id = c.component_id
        WHERE bc.build_id = $bid
        ORDER BY bc.slot_type
    ");

    $components = [];
    $total = 0;
    while ($comp = $comp_res->fetch_assoc()) {
        $components[$comp['slot_type']] = $comp;
        $total += (float)$comp['Price'];
    }

    $builds_data[] = [
        'build'      => $build,
        'components' => $components,
        'total'      => $total,
        'count'      => count($components),
    ];
}
$builds_res->close();
$conn->close();

$slot_icons = [
    'cpu' => '🔲', 'motherboard' => '📋', 'ram' => '💾',
    'gpu' => '🎮', 'powersupply' => '⚡', 'storage' => '💿', 'case' => '📦',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Saved Builds — NextGen PC Builder</title>
    <style>
        :root {
            --bg: #031c34; --card: #083c6c; --accent: #0b609c;
            --text: #b6cddc; --white: #ffffff; --danger: #ef4444;
            --success: #10b981; --muted: #72a0b8; --warn: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--white); padding: 2rem 1rem; min-height: 100vh; }

        .container { max-width: 960px; margin: 0 auto; }

        /* NAV */
        .top-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .nav-links { display: flex; gap: 1rem; flex-wrap: wrap; }
        .back-link { color: var(--accent); text-decoration: none; font-weight: bold; font-size: .95rem; }
        .back-link:hover { text-decoration: underline; }
        .btn { padding: 9px 20px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: .88rem; transition: all .2s; }
        .btn-accent  { background: var(--accent); color: white; }
        .btn-accent:hover  { background: #0d73ba; }
        .btn-danger  { background: transparent; border: 1px solid var(--danger); color: var(--danger); }
        .btn-danger:hover  { background: var(--danger); color: white; }
        .btn-success { background: var(--success); color: #000; }
        .btn-success:hover { background: #34d399; }
        .btn-sm { padding: 6px 14px; font-size: .8rem; border-radius: 6px; }

        /* PAGE HEADER */
        h1 { font-size: 2rem; margin-bottom: .3rem; }
        .subtitle { color: var(--muted); margin-bottom: 2rem; font-size: .95rem; }

        /* ALERT */
        .alert { padding: 1rem 1.2rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: .95rem; }
        .alert.success { background: rgba(16,185,129,.12); border: 1px solid var(--success); color: #6ee7b7; }
        .alert.error   { background: rgba(239,68,68,.12);  border: 1px solid var(--danger);  color: #fca5a5; }
        .alert.info    { background: rgba(11,96,156,.2);   border: 1px solid var(--accent);  color: var(--text); }

        /* BUILD CARDS */
        .builds-grid { display: grid; gap: 1.5rem; }
        .build-card { background: var(--card); border-radius: 14px; border: 1px solid #1a4a7a; overflow: hidden; transition: border-color .2s; }
        .build-card:hover { border-color: var(--muted); }

        .build-header { display: flex; align-items: center; justify-content: space-between; padding: 1.2rem 1.5rem; border-bottom: 1px solid #1a4a7a; flex-wrap: wrap; gap: .8rem; }
        .build-name { font-size: 1.2rem; font-weight: bold; color: var(--white); }
        .build-meta { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
        .build-date { color: var(--muted); font-size: .82rem; }
        .build-count { background: rgba(11,96,156,.4); color: var(--text); padding: 3px 10px; border-radius: 20px; font-size: .78rem; font-weight: bold; }
        .build-price { color: var(--success); font-weight: bold; font-size: 1.1rem; }

        .build-actions { display: flex; gap: .6rem; }

        /* COMPONENTS LIST */
        .comp-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .6rem; padding: 1.2rem 1.5rem; }
        .comp-item { background: #042a50; border-radius: 8px; padding: .7rem 1rem; border: 1px solid #1a3a5c; }
        .comp-slot { font-size: .72rem; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: .2rem; }
        .comp-name { font-size: .88rem; color: var(--white); font-weight: 500; }
        .comp-price-sm { font-size: .78rem; color: var(--success); margin-top: .2rem; }

        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 4rem 2rem; background: var(--card); border-radius: 14px; border: 1px dashed #1a4a7a; }
        .empty-state .icon { font-size: 3.5rem; margin-bottom: 1rem; }
        .empty-state h2 { font-size: 1.5rem; margin-bottom: .5rem; }
        .empty-state p { color: var(--muted); margin-bottom: 1.5rem; }

        /* RENAME MODAL */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 100; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal { background: var(--card); border-radius: 14px; padding: 2rem; width: 100%; max-width: 420px; border: 1px solid #1a4a7a; }
        .modal h3 { margin-bottom: 1.2rem; font-size: 1.2rem; }
        .modal input { width: 100%; padding: 10px 14px; background: #042a50; border: 1px solid #1a4a7a; border-radius: 8px; color: var(--white); font-size: 1rem; outline: none; margin-bottom: 1rem; }
        .modal input:focus { border-color: var(--accent); }
        .modal-btns { display: flex; gap: .8rem; justify-content: flex-end; }
    </style>
</head>
<body>
<div class="container">

    <!-- TOP NAV -->
    <div class="top-nav">
        <div class="nav-links">
            <a href="index.php" class="back-link">← Home</a>
            <a href="build.php" class="back-link">🛒 Current Build</a>
        </div>
        <div style="display:flex;align-items:center;gap:1rem;">
            <span style="color:var(--muted);font-size:.9rem;">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="logout.php" class="btn btn-danger btn-sm">Sign Out</a>
        </div>
    </div>

    <h1>💾 My Saved Builds</h1>
    <p class="subtitle">All your saved PC configurations in one place.</p>

    <?php if ($msg): ?>
        <div class="alert <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (empty($builds_data)): ?>
    <!-- EMPTY STATE -->
    <div class="empty-state">
        <div class="icon">🖥️</div>
        <h2>No saved builds yet</h2>
        <p>Build your dream PC and save it to see it here!</p>
        <a href="index.php" class="btn btn-accent">Start Building →</a>
    </div>

    <?php else: ?>
    <div class="builds-grid">
        <?php foreach ($builds_data as $entry): ?>
        <?php $b = $entry['build']; $bid = $b['build_id']; ?>
        <div class="build-card">
            <div class="build-header">
                <div>
                    <div class="build-name">🖥️ <?= htmlspecialchars($b['build_name']) ?></div>
                    <div class="build-meta" style="margin-top:.4rem;">
                        <span class="build-date">📅 <?= date('M d, Y  H:i', strtotime($b['created_at'])) ?></span>
                        <span class="build-count"><?= $entry['count'] ?>/7 components</span>
                        <span class="build-price">৳ <?= number_format($entry['total'], 2) ?></span>
                    </div>
                </div>
                <div class="build-actions">
                    <a href="my_builds.php?load=<?= $bid ?>" class="btn btn-success btn-sm">▶ Load</a>
                    <button onclick="openRename(<?= $bid ?>, '<?= htmlspecialchars(addslashes($b['build_name'])) ?>')" class="btn btn-accent btn-sm" style="border:none;cursor:pointer;">✏️ Rename</button>
                    <a href="my_builds.php?delete=<?= $bid ?>"
                       onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($b['build_name'])) ?>\'? This cannot be undone.')"
                       class="btn btn-danger btn-sm">🗑️</a>
                </div>
            </div>

            <!-- COMPONENT LIST -->
            <div class="comp-list">
                <?php
                $slot_order = ['cpu','motherboard','ram','gpu','powersupply','storage','case'];
                foreach ($slot_order as $slot):
                    if (isset($entry['components'][$slot])):
                        $c = $entry['components'][$slot];
                ?>
                <div class="comp-item">
                    <div class="comp-slot"><?= ($slot_icons[$slot] ?? '') ?> <?= strtoupper($slot) ?></div>
                    <div class="comp-name"><?= htmlspecialchars($c['Name']) ?></div>
                    <div class="comp-price-sm">৳ <?= number_format($c['Price'], 2) ?></div>
                </div>
                <?php endif; endforeach; ?>

                <?php
                // Show empty slots
                foreach ($slot_order as $slot):
                    if (!isset($entry['components'][$slot])):
                ?>
                <div class="comp-item" style="opacity:.35;">
                    <div class="comp-slot"><?= ($slot_icons[$slot] ?? '') ?> <?= strtoupper($slot) ?></div>
                    <div class="comp-name" style="color:#475569;font-style:italic;">Not selected</div>
                </div>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div><!-- container -->

<!-- RENAME MODAL -->
<div class="modal-overlay" id="renameModal">
    <div class="modal">
        <h3>✏️ Rename Build</h3>
        <form method="POST" action="rename_build.php">
            <input type="hidden" name="build_id" id="rename_build_id">
            <input type="text" name="new_name" id="rename_input" placeholder="Enter new build name" maxlength="100">
            <div class="modal-btns">
                <button type="button" onclick="closeRename()" class="btn btn-danger btn-sm" style="border:none;cursor:pointer;padding:8px 16px;">Cancel</button>
                <button type="submit" class="btn btn-accent btn-sm" style="border:none;cursor:pointer;padding:8px 16px;">Save Name</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRename(id, name) {
    document.getElementById('rename_build_id').value = id;
    document.getElementById('rename_input').value = name;
    document.getElementById('renameModal').classList.add('open');
}
function closeRename() {
    document.getElementById('renameModal').classList.remove('open');
}
document.getElementById('renameModal').addEventListener('click', function(e) {
    if (e.target === this) closeRename();
});
</script>
</body>
</html>
