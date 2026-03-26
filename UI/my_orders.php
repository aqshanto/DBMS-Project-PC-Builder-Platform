<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php?msg=required"); exit(); }

$conn = get_db_connection();
$uid  = (int)$_SESSION['user_id'];

$orders = $conn->query("SELECT * FROM orders WHERE user_id=$uid ORDER BY created_at DESC");
$conn->close();

$pay_labels   = ['cod'=>'Cash on Delivery','bkash'=>'bKash','nagad'=>'Nagad','card'=>'Card/Bank'];
$status_cfg   = [
    'pending'    => ['⏳','#f59e0b','rgba(245,158,11,.15)'],
    'confirmed'  => ['✅','#3b82f6','rgba(59,130,246,.15)'],
    'processing' => ['⚙️','#8b5cf6','rgba(139,92,246,.15)'],
    'shipped'    => ['🚚','#06b6d4','rgba(6,182,212,.15)'],
    'delivered'  => ['📦','#10b981','rgba(16,185,129,.15)'],
    'cancelled'  => ['❌','#ef4444','rgba(239,68,68,.15)'],
];
$pay_status_cfg = [
    'unpaid'   => ['💰','#f59e0b','rgba(245,158,11,.12)'],
    'paid'     => ['✅','#10b981','rgba(16,185,129,.12)'],
    'refunded' => ['↩️','#8b5cf6','rgba(139,92,246,.12)'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — NextGen PC Builder</title>
    <style>
        :root{--bg:#031c34;--card:#083c6c;--dark:#042a50;--accent:#0b609c;--badge:#72a0b8;--text:#b6cddc;--white:#fff;--success:#10b981;--warn:#f59e0b;--danger:#ef4444;--border:rgba(114,160,184,.22);}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--white);padding:2rem 1rem;min-height:100vh;}
        .container{max-width:820px;margin:0 auto;}
        .top-nav{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.8rem;flex-wrap:wrap;gap:.8rem;}
        .nav-links{display:flex;gap:.8rem;}
        .back-link{color:var(--accent);text-decoration:none;font-weight:bold;font-size:.9rem;}
        .back-link:hover{text-decoration:underline;}
        h1{font-size:1.7rem;font-weight:bold;margin-bottom:.2rem;}
        .subtitle{color:var(--badge);font-size:.88rem;margin-bottom:1.5rem;}

        /* ORDER CARD */
        .order-card{background:var(--dark);border-radius:14px;border:1px solid var(--border);margin-bottom:1.2rem;overflow:hidden;transition:border-color .2s;}
        .order-card:hover{border-color:var(--badge);}

        .order-header{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.3rem;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:.6rem;cursor:pointer;}
        .order-header-left{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;}
        .order-num{font-weight:bold;font-size:1rem;color:var(--white);}
        .order-date{font-size:.78rem;color:var(--badge);}
        .order-type{font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;background:rgba(11,96,156,.3);color:var(--text);padding:2px 8px;border-radius:20px;}

        .status-badge{display:inline-block;padding:3px 12px;border-radius:20px;font-size:.75rem;font-weight:bold;}
        .pay-badge{display:inline-block;padding:3px 12px;border-radius:20px;font-size:.75rem;font-weight:bold;margin-left:.4rem;}

        .order-meta{display:flex;gap:1rem;align-items:center;flex-wrap:wrap;}
        .order-total{font-size:1.1rem;font-weight:bold;color:var(--success);}

        /* ITEMS LIST */
        .order-items{padding:0 1.3rem;display:none;}
        .order-items.open{display:block;}
        .order-item{display:flex;justify-content:space-between;align-items:flex-start;padding:.65rem 0;border-bottom:1px solid var(--border);}
        .order-item:last-child{border-bottom:none;}
        .oi-slot{font-size:.67rem;text-transform:uppercase;letter-spacing:.5px;color:var(--accent);margin-bottom:.12rem;}
        .oi-name{font-size:.85rem;font-weight:bold;}
        .oi-brand{font-size:.73rem;color:var(--badge);}
        .oi-price{font-size:.88rem;font-weight:bold;color:var(--success);white-space:nowrap;}

        /* DELIVERY INFO */
        .delivery-info{padding:.9rem 1.3rem;border-top:1px solid var(--border);display:none;background:rgba(0,0,0,.15);}
        .delivery-info.open{display:block;}
        .di-grid{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;}
        .di-lbl{font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--badge);margin-bottom:.1rem;}
        .di-val{font-size:.83rem;}

        /* TOGGLE BTN */
        .toggle-btn{font-size:.78rem;color:var(--accent);cursor:pointer;user-select:none;display:flex;align-items:center;gap:.3rem;padding:.5rem 1.3rem;border-top:1px solid var(--border);}
        .toggle-btn:hover{color:var(--white);}

        /* EMPTY */
        .empty{text-align:center;padding:3rem 1rem;color:var(--text);}
        .empty h2{font-size:1.4rem;margin-bottom:.5rem;}
        .btn{display:inline-block;padding:10px 22px;border-radius:9px;text-decoration:none;font-weight:bold;font-size:.9rem;margin-top:1rem;}
        .btn-accent{background:var(--accent);color:white;}
    </style>
</head>
<body>
<div class="container">

    <div class="top-nav">
        <div class="nav-links">
            <a href="index.php" class="back-link">← Home</a>
            <a href="build.php" class="back-link">🛒 My Build</a>
        </div>
        <span style="color:var(--badge);font-size:.88rem;">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
    </div>

    <h1>📦 My Orders</h1>
    <p class="subtitle">Track all your purchases and order statuses.</p>

    <?php if ($orders && $orders->num_rows > 0): ?>
        <?php while ($o = $orders->fetch_assoc()):
            $sc  = $status_cfg[$o['status']]   ?? ['❓','#888','rgba(128,128,128,.1)'];
            $pc  = $pay_status_cfg[$o['payment_status']] ?? ['❓','#888','rgba(128,128,128,.1)'];
            $oid = $o['order_id'];

            // Fetch items
            $conn2 = get_db_connection();
            $items = $conn2->query("SELECT * FROM order_items WHERE order_id=$oid");
            $item_list = [];
            while ($it = $items->fetch_assoc()) $item_list[] = $it;
            $conn2->close();
        ?>
        <div class="order-card">
            <!-- HEADER -->
            <div class="order-header" onclick="toggleOrder(<?= $oid ?>)">
                <div class="order-header-left">
                    <div>
                        <div class="order-num">Order #<?= str_pad($oid, 6, '0', STR_PAD_LEFT) ?></div>
                        <div class="order-date">📅 <?= date('M d, Y  h:i A', strtotime($o['created_at'])) ?></div>
                    </div>
                    <span class="order-type"><?= $o['order_type'] === 'build' ? '🖥️ Full Build' : '📦 Single Item' ?></span>
                </div>
                <div class="order-meta">
                    <span class="status-badge" style="color:<?= $sc[1] ?>;background:<?= $sc[2] ?>;border:1px solid <?= $sc[1] ?>;"><?= $sc[0] ?> <?= ucfirst($o['status']) ?></span>
                    <span class="pay-badge" style="color:<?= $pc[1] ?>;background:<?= $pc[2] ?>;border:1px solid <?= $pc[1] ?>;"><?= $pc[0] ?> <?= ucfirst($o['payment_status']) ?></span>
                    <span class="order-total">৳<?= number_format($o['total_price'], 2) ?></span>
                    <span style="color:var(--badge);font-size:.85rem;" id="arrow-<?= $oid ?>">▼</span>
                </div>
            </div>

            <!-- ITEMS -->
            <div class="order-items" id="items-<?= $oid ?>">
                <?php foreach ($item_list as $it): ?>
                <div class="order-item">
                    <div>
                        <?php if ($it['slot_type']): ?><div class="oi-slot"><?= strtoupper(htmlspecialchars($it['slot_type'])) ?></div><?php endif; ?>
                        <div class="oi-name"><?= htmlspecialchars($it['name']) ?></div>
                        <div class="oi-brand"><?= htmlspecialchars($it['brand']) ?></div>
                    </div>
                    <div class="oi-price">৳<?= number_format($it['price'], 2) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- DELIVERY INFO -->
            <div class="delivery-info" id="delivery-<?= $oid ?>">
                <div class="di-grid">
                    <div><div class="di-lbl">Name</div><div class="di-val"><?= htmlspecialchars($o['full_name']) ?></div></div>
                    <div><div class="di-lbl">Phone</div><div class="di-val"><?= htmlspecialchars($o['phone']) ?></div></div>
                    <div><div class="di-lbl">City</div><div class="di-val"><?= htmlspecialchars($o['city']) ?></div></div>
                    <div><div class="di-lbl">Payment</div><div class="di-val"><?= $pay_labels[$o['payment_method']] ?? $o['payment_method'] ?></div></div>
                    <div style="grid-column:1/-1"><div class="di-lbl">Address</div><div class="di-val"><?= htmlspecialchars($o['address']) ?></div></div>
                    <?php if ($o['txn_id']): ?><div style="grid-column:1/-1"><div class="di-lbl">TxnID</div><div class="di-val" style="color:var(--success);"><?= htmlspecialchars($o['txn_id']) ?></div></div><?php endif; ?>
                    <?php if ($o['note']): ?><div style="grid-column:1/-1"><div class="di-lbl">Note</div><div class="di-val" style="color:var(--badge);"><?= htmlspecialchars($o['note']) ?></div></div><?php endif; ?>
                </div>
            </div>

            <!-- TOGGLE -->
            <div class="toggle-btn" id="tbtn-<?= $oid ?>" onclick="toggleOrder(<?= $oid ?>)">▼ Show details</div>
        </div>
        <?php endwhile; ?>

    <?php else: ?>
        <div class="empty">
            <h2>📭 No orders yet</h2>
            <p style="color:var(--badge);">You haven't placed any orders yet.</p>
            <a href="index.php" class="btn btn-accent">Start Shopping →</a>
        </div>
    <?php endif; ?>

</div>
<script>
function toggleOrder(id) {
    const items = document.getElementById('items-' + id);
    const del   = document.getElementById('delivery-' + id);
    const btn   = document.getElementById('tbtn-' + id);
    const arrow = document.getElementById('arrow-' + id);
    const open  = items.classList.toggle('open');
    del.classList.toggle('open', open);
    btn.textContent  = open ? '▲ Hide details' : '▼ Show details';
    arrow.textContent = open ? '▲' : '▼';
}
</script>
</body>
</html>
