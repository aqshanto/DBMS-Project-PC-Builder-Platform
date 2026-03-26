<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header("Location: ../login.php"); exit(); }

$conn = get_db_connection();
$msg  = ""; $msg_type = "";

// ── UPDATE ORDER STATUS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid    = (int)$_POST['order_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $pay_st = $conn->real_escape_string($_POST['payment_status']);
    $conn->query("UPDATE orders SET status='$status', payment_status='$pay_st' WHERE order_id=$oid");
    $msg = "✅ Order #" . str_pad($oid,6,'0',STR_PAD_LEFT) . " updated."; $msg_type = "success";
}

// ── CANCEL ORDER ──
if (isset($_GET['cancel'])) {
    $oid = (int)$_GET['cancel'];
    $conn->query("UPDATE orders SET status='cancelled' WHERE order_id=$oid");
    // Restore stock
    $items = $conn->query("SELECT component_id FROM order_items WHERE order_id=$oid");
    while ($it = $items->fetch_assoc()) {
        $conn->query("UPDATE components SET stock_quantity = stock_quantity + 1 WHERE component_id=" . (int)$it['component_id']);
    }
    header("Location: admin_orders.php?msg=cancelled"); exit();
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'cancelled') { $msg = "❌ Order cancelled & stock restored."; $msg_type = "warn"; }
}

// ── FILTERS ──
$status_f = isset($_GET['status']) ? $_GET['status'] : '';
$pay_f    = isset($_GET['pay'])    ? $_GET['pay']    : '';
$search_q = isset($_GET['q'])      ? trim($_GET['q']): '';

$where = ["1=1"];
if ($status_f) $where[] = "o.status='" . $conn->real_escape_string($status_f) . "'";
if ($pay_f)    $where[] = "o.payment_status='" . $conn->real_escape_string($pay_f) . "'";
if ($search_q) {
    $sq = $conn->real_escape_string($search_q);
    $where[] = "(o.full_name LIKE '%$sq%' OR o.phone LIKE '%$sq%' OR CAST(o.order_id AS CHAR) LIKE '%$sq%')";
}

$orders = $conn->query("
    SELECT o.*, u.username
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY o.created_at DESC
");

// Stats
$stats = $conn->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
$stat_counts = [];
while ($s = $stats->fetch_assoc()) $stat_counts[$s['status']] = $s['cnt'];
$total_orders   = array_sum($stat_counts);
$total_revenue  = $conn->query("SELECT SUM(total_price) as rev FROM orders WHERE status != 'cancelled'")->fetch_assoc()['rev'] ?? 0;
$pending_count  = $stat_counts['pending'] ?? 0;

$conn->close();

$pay_labels  = ['cod'=>'COD','bkash'=>'bKash','nagad'=>'Nagad','card'=>'Card'];
$status_cfg  = [
    'pending'    => ['⏳','#f59e0b','rgba(245,158,11,.15)'],
    'confirmed'  => ['✅','#3b82f6','rgba(59,130,246,.15)'],
    'processing' => ['⚙️','#8b5cf6','rgba(139,92,246,.15)'],
    'shipped'    => ['🚚','#06b6d4','rgba(6,182,212,.15)'],
    'delivered'  => ['📦','#10b981','rgba(16,185,129,.15)'],
    'cancelled'  => ['❌','#ef4444','rgba(239,68,68,.15)'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders — Admin</title>
    <style>
        :root{--bg:#0f172a;--card:#1e293b;--accent:#3b82f6;--success:#10b981;--danger:#ef4444;--warn:#f59e0b;--muted:#94a3b8;--text:#f8fafc;--border:#334155;--sidebar:#0b1120;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}
        .sidebar{width:220px;background:var(--sidebar);border-right:1px solid var(--border);padding:1.5rem 1rem;display:flex;flex-direction:column;gap:.3rem;flex-shrink:0;}
        .sb-logo{font-size:1.1rem;font-weight:bold;color:var(--accent);margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:1px solid var(--border);}
        .sb-logo span{color:var(--muted);font-size:.8rem;display:block;font-weight:normal;}
        .sb-link{display:flex;align-items:center;gap:.6rem;padding:.65rem .9rem;border-radius:8px;text-decoration:none;color:var(--muted);font-size:.88rem;transition:all .2s;}
        .sb-link:hover{background:var(--card);color:var(--text);}
        .sb-link.active{background:var(--accent);color:white;}
        .sb-divider{height:1px;background:var(--border);margin:.5rem 0;}
        .sb-bottom{margin-top:auto;}
        .main{flex:1;padding:2rem;overflow-y:auto;}
        .page-title{font-size:1.6rem;font-weight:bold;margin-bottom:.3rem;}
        .page-sub{color:var(--muted);font-size:.9rem;margin-bottom:1.5rem;}
        .alert{padding:.9rem 1.1rem;border-radius:8px;margin-bottom:1.2rem;font-size:.9rem;}
        .alert.success{background:rgba(16,185,129,.12);border:1px solid var(--success);color:#6ee7b7;}
        .alert.warn{background:rgba(245,158,11,.1);border:1px solid var(--warn);color:#fcd34d;}

        /* STAT CARDS */
        .stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;}
        .stat{background:var(--card);border-radius:12px;padding:1.1rem;border:1px solid var(--border);}
        .stat-val{font-size:1.8rem;font-weight:bold;margin-bottom:.2rem;}
        .stat-lbl{font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}

        /* TOOLBAR */
        .toolbar{display:flex;gap:.6rem;flex-wrap:wrap;background:var(--card);padding:.85rem 1rem;border-radius:10px;border:1px solid var(--border);margin-bottom:1.2rem;}
        .toolbar input,.toolbar select{padding:7px 11px;background:var(--sidebar);border:1px solid var(--border);border-radius:7px;color:var(--text);font-size:.83rem;outline:none;}
        .toolbar input:focus,.toolbar select:focus{border-color:var(--accent);}
        .btn{padding:7px 14px;border-radius:7px;border:none;font-size:.83rem;font-weight:bold;cursor:pointer;text-decoration:none;transition:all .2s;}
        .btn-accent{background:var(--accent);color:white;}
        .btn-sm{padding:4px 10px;font-size:.75rem;border-radius:5px;}
        .btn-cancel{background:rgba(239,68,68,.12);color:#fca5a5;border:1px solid var(--danger);}
        .btn-cancel:hover{background:var(--danger);color:white;}

        /* ORDER TABLE */
        .table-wrap{background:var(--card);border-radius:12px;border:1px solid var(--border);overflow:hidden;}
        table{width:100%;border-collapse:collapse;}
        th{background:var(--sidebar);text-align:left;padding:.65rem 1rem;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);}
        td{padding:.75rem 1rem;border-bottom:1px solid var(--border);font-size:.85rem;vertical-align:top;}
        tr:last-child td{border-bottom:none;}
        tr:hover td{background:rgba(255,255,255,.02);}
        .badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:bold;}

        /* EXPAND ROW */
        .expand-row{display:none;background:#0a1628;}
        .expand-row.open{display:table-row;}
        .expand-content{padding:1rem;}
        .items-list{display:flex;flex-direction:column;gap:.4rem;margin-bottom:.8rem;}
        .item-row{display:flex;justify-content:space-between;font-size:.82rem;padding:.3rem 0;border-bottom:1px solid var(--border);}
        .item-row:last-child{border-bottom:none;}
        .update-form{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;padding:.8rem;background:var(--sidebar);border-radius:8px;}
        .update-form select{padding:6px 10px;background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:.82rem;outline:none;}
        .update-form select:focus{border-color:var(--accent);}

        @media(max-width:900px){.sidebar{display:none;}}
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sb-logo">⚡ PC Builder <span>Admin Panel</span></div>
    <a href="admin_dashboard.php" class="sb-link">🏠 Dashboard</a>
    <a href="admin_components.php" class="sb-link">🖥️ Components</a>
    <a href="admin_add_component.php" class="sb-link">➕ Add Component</a>
    <a href="admin_users.php" class="sb-link">👥 Users</a>
    <a href="admin_orders.php" class="sb-link active">📦 Orders</a>
    <div class="sb-divider"></div>
    <div class="sb-bottom">
        <a href="../index.php" class="sb-link">🌐 View Site</a>
        <a href="../logout.php" class="sb-link" style="color:var(--danger);">🚪 Sign Out</a>
    </div>
</aside>

<main class="main">
    <div class="page-title">📦 Manage Orders</div>
    <div class="page-sub">View and update all customer orders.</div>

    <?php if ($msg): ?><div class="alert <?= $msg_type ?>"><?= $msg ?></div><?php endif; ?>

    <!-- STATS -->
    <div class="stats">
        <div class="stat"><div class="stat-val" style="color:var(--accent);"><?= $total_orders ?></div><div class="stat-lbl">Total Orders</div></div>
        <div class="stat"><div class="stat-val" style="color:var(--warn);"><?= $pending_count ?></div><div class="stat-lbl">Pending</div></div>
        <div class="stat"><div class="stat-val" style="color:#06b6d4;"><?= $stat_counts['shipped'] ?? 0 ?></div><div class="stat-lbl">Shipped</div></div>
        <div class="stat"><div class="stat-val" style="color:var(--success);"><?= $stat_counts['delivered'] ?? 0 ?></div><div class="stat-lbl">Delivered</div></div>
        <div class="stat"><div class="stat-val" style="color:var(--success);">৳<?= number_format($total_revenue) ?></div><div class="stat-lbl">Total Revenue</div></div>
    </div>

    <!-- TOOLBAR -->
    <form method="GET" action="admin_orders.php" class="toolbar">
        <input type="text" name="q" placeholder="🔍 Search name, phone, ID..." value="<?= htmlspecialchars($search_q) ?>">
        <select name="status" onchange="this.form.submit()">
            <option value="">All Status</option>
            <?php foreach (array_keys($status_cfg) as $st): ?>
            <option value="<?=$st?>" <?=$status_f===$st?'selected':''?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="pay" onchange="this.form.submit()">
            <option value="">All Payment</option>
            <option value="unpaid" <?=$pay_f==='unpaid'?'selected':''?>>💰 Unpaid</option>
            <option value="paid"   <?=$pay_f==='paid'?'selected':''?>>✅ Paid</option>
        </select>
        <button type="submit" class="btn btn-accent">Search</button>
        <a href="admin_orders.php" class="btn" style="background:var(--sidebar);color:var(--muted);">Reset</a>
    </form>

    <!-- TABLE -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Payment</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($orders && $orders->num_rows > 0): ?>
                <?php while ($o = $orders->fetch_assoc()):
                    $oid = $o['order_id'];
                    $sc  = $status_cfg[$o['status']] ?? ['❓','#888','rgba(128,128,128,.1)'];

                    // Fetch items for this order
                    $conn3 = get_db_connection();
                    $items = $conn3->query("SELECT * FROM order_items WHERE order_id=$oid");
                    $item_list = [];
                    while ($it = $items->fetch_assoc()) $item_list[] = $it;
                    $conn3->close();
                ?>
                <tr style="cursor:pointer;" onclick="toggleRow(<?= $oid ?>)">
                    <td><strong>#<?= str_pad($oid,6,'0',STR_PAD_LEFT) ?></strong></td>
                    <td>
                        <div style="font-weight:bold;font-size:.85rem;"><?= htmlspecialchars($o['full_name']) ?></div>
                        <div style="font-size:.75rem;color:var(--muted);"><?= htmlspecialchars($o['phone']) ?></div>
                        <?php if ($o['username']): ?><div style="font-size:.72rem;color:var(--accent);">@<?= htmlspecialchars($o['username']) ?></div><?php endif; ?>
                    </td>
                    <td><span style="font-size:.75rem;color:var(--muted);"><?= $o['order_type']==='build'?'🖥️ Build':'📦 Single' ?></span></td>
                    <td>
                        <div style="font-size:.8rem;"><?= $pay_labels[$o['payment_method']] ?? $o['payment_method'] ?></div>
                        <?php if ($o['payment_status']==='paid'): ?>
                            <span style="font-size:.7rem;color:var(--success);">✅ Paid</span>
                        <?php else: ?>
                            <span style="font-size:.7rem;color:var(--warn);">💰 Unpaid</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:bold;color:var(--success);">৳<?= number_format($o['total_price'],2) ?></td>
                    <td><span class="badge" style="color:<?=$sc[1]?>;background:<?=$sc[2]?>;border:1px solid <?=$sc[1]?>;"><?=$sc[0]?> <?= ucfirst($o['status']) ?></span></td>
                    <td style="font-size:.78rem;color:var(--muted);"><?= date('M d Y', strtotime($o['created_at'])) ?></td>
                    <td onclick="event.stopPropagation()">
                        <?php if ($o['status'] !== 'cancelled' && $o['status'] !== 'delivered'): ?>
                        <a href="admin_orders.php?cancel=<?= $oid ?>"
                           onclick="return confirm('Cancel order #<?= str_pad($oid,6,'0',STR_PAD_LEFT) ?> and restore stock?')"
                           class="btn btn-sm btn-cancel">✕ Cancel</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <!-- EXPAND ROW -->
                <tr class="expand-row" id="row-<?= $oid ?>">
                    <td colspan="8">
                        <div class="expand-content">
                            <!-- ITEMS -->
                            <div style="font-size:.78rem;color:var(--muted);margin-bottom:.5rem;">📋 ORDER ITEMS</div>
                            <div class="items-list">
                                <?php foreach ($item_list as $it): ?>
                                <div class="item-row">
                                    <span><?= htmlspecialchars($it['name']) ?> <span style="color:var(--muted);font-size:.75rem;">(<?= htmlspecialchars($it['brand']) ?>)</span></span>
                                    <span style="color:var(--success);">৳<?= number_format($it['price'],2) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- DELIVERY -->
                            <div style="font-size:.78rem;color:var(--muted);margin-bottom:.4rem;">📦 DELIVERY: <?= htmlspecialchars($o['address']) ?>, <?= htmlspecialchars($o['city']) ?></div>
                            <?php if ($o['txn_id']): ?><div style="font-size:.78rem;color:var(--success);margin-bottom:.4rem;">💳 TxnID: <?= htmlspecialchars($o['txn_id']) ?></div><?php endif; ?>
                            <?php if ($o['note']): ?><div style="font-size:.78rem;color:var(--muted);margin-bottom:.6rem;">📝 Note: <?= htmlspecialchars($o['note']) ?></div><?php endif; ?>

                            <!-- UPDATE STATUS FORM -->
                            <form method="POST" class="update-form">
                                <input type="hidden" name="order_id" value="<?= $oid ?>">
                                <span style="font-size:.8rem;color:var(--muted);">Update:</span>
                                <select name="status">
                                    <?php foreach (array_keys($status_cfg) as $st): ?>
                                    <option value="<?=$st?>" <?=$o['status']===$st?'selected':''?>><?= ucfirst($st) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="payment_status">
                                    <option value="unpaid" <?=$o['payment_status']==='unpaid'?'selected':''?>>💰 Unpaid</option>
                                    <option value="paid"   <?=$o['payment_status']==='paid'?'selected':''?>>✅ Paid</option>
                                    <option value="refunded" <?=$o['payment_status']==='refunded'?'selected':''?>>↩️ Refunded</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-accent btn-sm">💾 Save</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--muted);">No orders found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
function toggleRow(id) {
    document.getElementById('row-' + id).classList.toggle('open');
}
</script>
</body>
</html>
