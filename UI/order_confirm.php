<?php
session_start();
require_once 'config.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$order_id) { header("Location: index.php"); exit(); }

$conn  = get_db_connection();
$order = null;
$items = [];

// Fetch order
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id=?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) $order = $res->fetch_assoc();
$stmt->close();

if (!$order) { header("Location: index.php"); exit(); }

// Fetch items
$ir = $conn->query("SELECT * FROM order_items WHERE order_id=$order_id");
while ($row = $ir->fetch_assoc()) $items[] = $row;

$conn->close();

$pay_labels = ['cod'=>'Cash on Delivery','bkash'=>'bKash','nagad'=>'Nagad','card'=>'Card / Bank Transfer'];
$status_colors = ['pending'=>'#f59e0b','confirmed'=>'#3b82f6','processing'=>'#8b5cf6','shipped'=>'#06b6d4','delivered'=>'#10b981','cancelled'=>'#ef4444'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed — NextGen PC Builder</title>
    <style>
        :root{--bg:#031c34;--card:#083c6c;--dark:#042a50;--accent:#0b609c;--badge:#72a0b8;--text:#b6cddc;--white:#fff;--success:#10b981;--warn:#f59e0b;--border:rgba(114,160,184,.22);}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--white);padding:2rem 1rem;min-height:100vh;}
        .container{max-width:680px;margin:0 auto;}

        /* SUCCESS BANNER */
        .success-banner{text-align:center;padding:2.5rem 1rem 2rem;background:var(--dark);border-radius:16px;border:1px solid rgba(16,185,129,.3);margin-bottom:1.5rem;}
        .success-icon{font-size:4rem;margin-bottom:.8rem;}
        .success-title{font-size:1.9rem;font-weight:bold;margin-bottom:.4rem;color:var(--white);}
        .success-sub{color:var(--badge);font-size:.95rem;}
        .order-num{display:inline-block;background:rgba(16,185,129,.12);border:1px solid var(--success);color:#6ee7b7;padding:6px 20px;border-radius:50px;font-size:.9rem;font-weight:bold;margin-top:1rem;}

        /* DETAIL CARD */
        .detail-card{background:var(--dark);border-radius:14px;border:1px solid var(--border);margin-bottom:1.2rem;overflow:hidden;}
        .card-title{padding:1rem 1.3rem;font-size:.75rem;font-weight:bold;text-transform:uppercase;letter-spacing:1.5px;color:var(--badge);border-bottom:1px solid var(--border);}
        .card-body{padding:1.2rem 1.3rem;}
        .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;}
        .info-item{}
        .info-lbl{font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--badge);margin-bottom:.2rem;}
        .info-val{font-size:.92rem;font-weight:500;}

        /* ORDER ITEMS */
        .order-item{display:flex;justify-content:space-between;align-items:flex-start;padding:.7rem 0;border-bottom:1px solid var(--border);}
        .order-item:last-child{border-bottom:none;}
        .oi-slot{font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:var(--accent);margin-bottom:.15rem;}
        .oi-name{font-size:.88rem;font-weight:bold;}
        .oi-brand{font-size:.75rem;color:var(--badge);}
        .oi-price{font-size:.92rem;font-weight:bold;color:var(--success);white-space:nowrap;}

        /* TOTAL */
        .total-row{display:flex;justify-content:space-between;margin-top:.8rem;padding-top:.8rem;border-top:1px solid var(--border);}
        .total-lbl{font-weight:bold;}
        .total-amt{font-size:1.4rem;font-weight:bold;color:var(--success);}

        /* STATUS BADGE */
        .status-badge{display:inline-block;padding:4px 14px;border-radius:20px;font-size:.78rem;font-weight:bold;}

        /* PAYMENT BADGE */
        .cod-note{background:rgba(245,158,11,.1);border:1px solid var(--warn);border-radius:10px;padding:1rem 1.2rem;margin-bottom:1.2rem;font-size:.88rem;color:#fcd34d;line-height:1.6;}

        /* ACTIONS */
        .actions{display:flex;gap:.8rem;margin-top:1.5rem;flex-wrap:wrap;}
        .btn{padding:11px 24px;border-radius:9px;text-decoration:none;font-weight:bold;font-size:.9rem;text-align:center;flex:1;min-width:140px;transition:all .2s;}
        .btn-accent{background:var(--accent);color:white;}
        .btn-accent:hover{background:#0d73ba;}
        .btn-outline{border:1px solid var(--badge);color:var(--badge);}
        .btn-outline:hover{border-color:var(--white);color:var(--white);}
    </style>
</head>
<body>
<div class="container">

    <!-- SUCCESS BANNER -->
    <div class="success-banner">
        <div class="success-icon">🎉</div>
        <div class="success-title">Order Placed Successfully!</div>
        <div class="success-sub">Thank you for your purchase. We'll contact you shortly.</div>
        <div class="order-num">Order #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></div>
    </div>

    <!-- COD NOTE -->
    <?php if ($order['payment_method'] === 'cod'): ?>
    <div class="cod-note">
        💵 <strong>Cash on Delivery:</strong> Please have <strong>৳<?= number_format($order['total_price'], 2) ?></strong> ready when our delivery person arrives. Delivery typically takes 2–5 business days within Dhaka, 3–7 days outside Dhaka.
    </div>
    <?php elseif ($order['payment_status'] === 'paid'): ?>
    <div class="cod-note" style="border-color:var(--success);background:rgba(16,185,129,.08);color:#6ee7b7;">
        ✅ <strong>Payment Received:</strong> Your <?= htmlspecialchars($pay_labels[$order['payment_method']] ?? '') ?> payment has been recorded. TxnID: <strong><?= htmlspecialchars($order['txn_id'] ?? 'N/A') ?></strong>
    </div>
    <?php endif; ?>

    <!-- ORDER DETAILS -->
    <div class="detail-card">
        <div class="card-title">📋 Order Details</div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-lbl">Order ID</div>
                    <div class="info-val">#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-lbl">Status</div>
                    <div class="info-val">
                        <span class="status-badge" style="background:rgba(245,158,11,.15);color:#fcd34d;border:1px solid var(--warn);">
                            ⏳ Pending
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-lbl">Payment</div>
                    <div class="info-val"><?= htmlspecialchars($pay_labels[$order['payment_method']] ?? $order['payment_method']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-lbl">Date</div>
                    <div class="info-val"><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-lbl">Deliver To</div>
                    <div class="info-val"><?= htmlspecialchars($order['full_name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-lbl">Phone</div>
                    <div class="info-val"><?= htmlspecialchars($order['phone']) ?></div>
                </div>
                <div class="info-item" style="grid-column:1/-1;">
                    <div class="info-lbl">Address</div>
                    <div class="info-val"><?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ORDER ITEMS -->
    <div class="detail-card">
        <div class="card-title">🛒 Items Ordered (<?= count($items) ?>)</div>
        <div class="card-body">
            <?php foreach ($items as $item): ?>
            <div class="order-item">
                <div>
                    <?php if ($item['slot_type']): ?><div class="oi-slot"><?= strtoupper(htmlspecialchars($item['slot_type'])) ?></div><?php endif; ?>
                    <div class="oi-name"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="oi-brand"><?= htmlspecialchars($item['brand']) ?></div>
                </div>
                <div class="oi-price">৳<?= number_format($item['price'], 2) ?></div>
            </div>
            <?php endforeach; ?>
            <div class="total-row">
                <span class="total-lbl">Total Paid</span>
                <span class="total-amt">৳<?= number_format($order['total_price'], 2) ?></span>
            </div>
        </div>
    </div>

    <!-- ACTIONS -->
    <div class="actions">
        <a href="index.php" class="btn btn-accent">🛒 Continue Shopping</a>
        <?php if (isset($_SESSION['user_id'])): ?>
        <a href="my_orders.php" class="btn btn-outline">📦 My Orders</a>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
