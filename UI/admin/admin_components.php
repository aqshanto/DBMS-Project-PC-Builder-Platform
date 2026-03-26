<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header("Location: ../login.php"); exit(); }

$conn = get_db_connection();
$msg  = ""; $msg_type = "";

// ── DELETE COMPONENT ──
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    // Delete from all spec tables first (FK constraint)
    foreach (['cpus','motherboards','rams','gpus','powersupplies','storages','cases'] as $t) {
        $conn->query("DELETE FROM `$t` WHERE component_id=$did");
    }
    $conn->query("DELETE FROM build_components WHERE component_id=$did");
    $conn->query("DELETE FROM components WHERE component_id=$did");
    header("Location: admin_components.php?msg=deleted");
    exit();
}

// ── UPDATE STOCK / PRICE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_component'])) {
    $uid   = (int)$_POST['component_id'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $name  = $conn->real_escape_string(trim($_POST['name']));
    $stmt  = $conn->prepare("UPDATE components SET Name=?, Price=?, stock_quantity=? WHERE component_id=?");
    $stmt->bind_param("sdii", $name, $price, $stock, $uid);
    $stmt->execute(); $stmt->close();
    $msg = "✅ Component updated successfully!"; $msg_type = "success";
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') { $msg = "🗑️ Component deleted."; $msg_type = "warn"; }
    if ($_GET['msg'] === 'added')   { $msg = "✅ Component added successfully!"; $msg_type = "success"; }
}

// ── FILTERS ──
$search_q  = isset($_GET['q']) ? trim($_GET['q']) : '';
$type_f    = isset($_GET['type']) ? $_GET['type'] : '';
$stock_f   = isset($_GET['filter']) ? $_GET['filter'] : '';
$sort_c    = isset($_GET['sort']) ? $_GET['sort'] : 'component_id';

$where = ["1=1"];
if ($search_q) $where[] = "(Name LIKE '%" . $conn->real_escape_string($search_q) . "%' OR Brand LIKE '%" . $conn->real_escape_string($search_q) . "%')";
if ($type_f)   $where[] = "LOWER(REPLACE(Type,' ',''))='" . $conn->real_escape_string($type_f) . "'";
if ($stock_f === 'low')  $where[] = "stock_quantity BETWEEN 1 AND 5";
if ($stock_f === 'out')  $where[] = "stock_quantity = 0";

$sort_map = ['component_id'=>'component_id ASC','name'=>'Name ASC','price_asc'=>'Price ASC','price_desc'=>'Price DESC','stock'=>'stock_quantity ASC'];
$order = $sort_map[$sort_c] ?? 'component_id ASC';

$components = $conn->query("SELECT * FROM components WHERE " . implode(' AND ', $where) . " ORDER BY $order");
$total      = $components ? $components->num_rows : 0;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Components — Admin</title>
    <style>
        :root{--bg:#0f172a;--card:#1e293b;--accent:#3b82f6;--success:#10b981;--danger:#ef4444;--warn:#f59e0b;--muted:#94a3b8;--text:#f8fafc;--border:#334155;--sidebar:#0b1120;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}
        .sidebar{width:220px;background:var(--sidebar);border-right:1px solid var(--border);padding:1.5rem 1rem;display:flex;flex-direction:column;gap:.3rem;flex-shrink:0;}
        .sb-logo{font-size:1.1rem;font-weight:bold;color:var(--accent);margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:1px solid var(--border);}
        .sb-logo span{color:var(--muted);font-size:.8rem;display:block;font-weight:normal;margin-top:.2rem;}
        .sb-link{display:flex;align-items:center;gap:.6rem;padding:.65rem .9rem;border-radius:8px;text-decoration:none;color:var(--muted);font-size:.88rem;transition:all .2s;}
        .sb-link:hover{background:var(--card);color:var(--text);}
        .sb-link.active{background:var(--accent);color:white;}
        .sb-divider{height:1px;background:var(--border);margin:.5rem 0;}
        .sb-bottom{margin-top:auto;}
        .main{flex:1;padding:2rem;overflow-y:auto;}
        .page-title{font-size:1.6rem;font-weight:bold;margin-bottom:.3rem;}
        .page-sub{color:var(--muted);font-size:.9rem;margin-bottom:1.5rem;}

        /* TOOLBAR */
        .toolbar{display:flex;gap:.7rem;flex-wrap:wrap;align-items:center;margin-bottom:1.2rem;background:var(--card);padding:.9rem 1rem;border-radius:10px;border:1px solid var(--border);}
        .toolbar input,.toolbar select{padding:7px 12px;background:var(--sidebar);border:1px solid var(--border);border-radius:7px;color:var(--text);font-size:.85rem;outline:none;}
        .toolbar input:focus,.toolbar select:focus{border-color:var(--accent);}
        .btn{padding:8px 16px;border-radius:7px;border:none;font-weight:bold;font-size:.85rem;cursor:pointer;text-decoration:none;display:inline-block;transition:all .2s;}
        .btn-accent{background:var(--accent);color:white;}
        .btn-accent:hover{background:#2563eb;}
        .btn-success{background:var(--success);color:#000;}
        .btn-danger{background:transparent;border:1px solid var(--danger);color:var(--danger);padding:5px 10px;font-size:.78rem;}
        .btn-danger:hover{background:var(--danger);color:white;}
        .btn-edit{background:transparent;border:1px solid var(--accent);color:var(--accent);padding:5px 10px;font-size:.78rem;}
        .btn-edit:hover{background:var(--accent);color:white;}

        /* ALERT */
        .alert{padding:.9rem 1.1rem;border-radius:8px;margin-bottom:1.2rem;font-size:.9rem;}
        .alert.success{background:rgba(16,185,129,.12);border:1px solid var(--success);color:#6ee7b7;}
        .alert.warn{background:rgba(245,158,11,.1);border:1px solid var(--warn);color:#fcd34d;}

        /* TABLE */
        .table-wrap{background:var(--card);border-radius:12px;border:1px solid var(--border);overflow:hidden;}
        .result-info{padding:.7rem 1.1rem;font-size:.82rem;color:var(--muted);border-bottom:1px solid var(--border);}
        table{width:100%;border-collapse:collapse;}
        th{background:var(--sidebar);text-align:left;padding:.65rem 1rem;font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);}
        td{padding:.7rem 1rem;border-bottom:1px solid var(--border);font-size:.85rem;vertical-align:middle;}
        tr:last-child td{border-bottom:none;}
        tr:hover td{background:rgba(255,255,255,.02);}
        .stock-ok{color:var(--success);}
        .stock-low{color:var(--warn);}
        .stock-out{color:var(--danger);}

        /* INLINE EDIT FORM */
        .edit-row{display:none;background:#0b1120;}
        .edit-row.open{display:table-row;}
        .edit-form{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;padding:.8rem;}
        .edit-form input{padding:6px 10px;background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:.83rem;outline:none;width:180px;}
        .edit-form input[type=number]{width:100px;}
        .edit-form input:focus{border-color:var(--accent);}

        @media(max-width:900px){.sidebar{display:none;}}
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sb-logo">⚡ PC Builder <span>Admin Panel</span></div>
    <a href="admin_dashboard.php" class="sb-link">🏠 Dashboard</a>
    <a href="admin_components.php" class="sb-link active">🖥️ Components</a>
    <a href="admin_add_component.php" class="sb-link">➕ Add Component</a>
    <a href="admin_users.php" class="sb-link">👥 Users</a>
    <div class="sb-divider"></div>
    <div class="sb-bottom">
        <a href="../index.php" class="sb-link">🌐 View Site</a>
        <a href="../logout.php" class="sb-link" style="color:var(--danger);">🚪 Sign Out</a>
    </div>
</aside>

<main class="main">
    <div class="page-title">🖥️ Manage Components</div>
    <div class="page-sub">View, edit, update stock, and delete components.</div>

    <?php if ($msg): ?><div class="alert <?= $msg_type ?>"><?= $msg ?></div><?php endif; ?>

    <!-- TOOLBAR -->
    <form method="GET" action="admin_components.php" class="toolbar">
        <input type="text" name="q" placeholder="🔍 Search name or brand..." value="<?= htmlspecialchars($search_q) ?>">
        <select name="type" onchange="this.form.submit()">
            <option value="">All Types</option>
            <?php foreach (['cpu','motherboard','ram','gpu','powersupply','storage','case'] as $t): ?>
            <option value="<?=$t?>" <?=$type_f===$t?'selected':''?>><?=strtoupper($t)?></option>
            <?php endforeach; ?>
        </select>
        <select name="filter" onchange="this.form.submit()">
            <option value="">All Stock</option>
            <option value="low" <?=$stock_f==='low'?'selected':''?>>⚠️ Low Stock (≤5)</option>
            <option value="out" <?=$stock_f==='out'?'selected':''?>>❌ Out of Stock</option>
        </select>
        <select name="sort" onchange="this.form.submit()">
            <option value="component_id" <?=$sort_c==='component_id'?'selected':''?>>Sort: ID</option>
            <option value="name"         <?=$sort_c==='name'?'selected':''?>>Sort: Name</option>
            <option value="price_asc"    <?=$sort_c==='price_asc'?'selected':''?>>Price: Low→High</option>
            <option value="price_desc"   <?=$sort_c==='price_desc'?'selected':''?>>Price: High→Low</option>
            <option value="stock"        <?=$sort_c==='stock'?'selected':''?>>Sort: Low Stock</option>
        </select>
        <button type="submit" class="btn btn-accent">Search</button>
        <a href="admin_components.php" class="btn" style="background:var(--sidebar);color:var(--muted);">Reset</a>
        <a href="admin_add_component.php" class="btn btn-success" style="margin-left:auto;">➕ Add New</a>
    </form>

    <!-- TABLE -->
    <div class="table-wrap">
        <div class="result-info">Showing <strong style="color:var(--text);"><?= $total ?></strong> components</div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Brand</th>
                    <th>Type</th>
                    <th>Price (৳)</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($components && $components->num_rows > 0): ?>
                <?php while ($c = $components->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--muted);"><?= $c['component_id'] ?></td>
                    <td style="font-weight:bold;"><?= htmlspecialchars($c['Name']) ?></td>
                    <td style="color:var(--muted);"><?= htmlspecialchars($c['Brand']) ?></td>
                    <td><span style="background:rgba(59,130,246,.15);color:#93c5fd;padding:2px 8px;border-radius:20px;font-size:.72rem;font-weight:bold;"><?= htmlspecialchars($c['Type']) ?></span></td>
                    <td style="font-weight:bold;">৳<?= number_format($c['Price'], 2) ?></td>
                    <td>
                        <?php if ($c['stock_quantity'] == 0): ?>
                            <span class="stock-out">❌ Out of stock</span>
                        <?php elseif ($c['stock_quantity'] <= 5): ?>
                            <span class="stock-low">⚠️ <?= $c['stock_quantity'] ?></span>
                        <?php else: ?>
                            <span class="stock-ok"><?= $c['stock_quantity'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:.4rem;">
                            <button class="btn btn-edit" onclick="toggleEdit(<?= $c['component_id'] ?>)">✏️ Edit</button>
                            <a href="admin_components.php?delete=<?= $c['component_id'] ?>"
                               onclick="return confirm('Delete <?= htmlspecialchars(addslashes($c['Name'])) ?>? This cannot be undone.')"
                               class="btn btn-danger">🗑️</a>
                        </div>
                    </td>
                </tr>
                <!-- INLINE EDIT ROW -->
                <tr class="edit-row" id="edit-<?= $c['component_id'] ?>">
                    <td colspan="7">
                        <form method="POST" class="edit-form">
                            <input type="hidden" name="component_id" value="<?= $c['component_id'] ?>">
                            <label style="font-size:.8rem;color:var(--muted);">Name:</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($c['Name']) ?>" required>
                            <label style="font-size:.8rem;color:var(--muted);">Price (৳):</label>
                            <input type="number" name="price" value="<?= $c['Price'] ?>" step="0.01" min="0" required>
                            <label style="font-size:.8rem;color:var(--muted);">Stock:</label>
                            <input type="number" name="stock" value="<?= $c['stock_quantity'] ?>" min="0" required>
                            <button type="submit" name="update_component" class="btn btn-accent">💾 Save</button>
                            <button type="button" onclick="toggleEdit(<?= $c['component_id'] ?>)" class="btn" style="background:var(--sidebar);color:var(--muted);">Cancel</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem;">No components found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
function toggleEdit(id) {
    const row = document.getElementById('edit-' + id);
    row.classList.toggle('open');
}
</script>
</body>
</html>
