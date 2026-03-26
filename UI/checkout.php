<?php
session_start();
require_once 'config.php';

$conn = get_db_connection();

// ── DETERMINE WHAT WE'RE BUYING ──
// Mode: 'build' = buy full session build, 'single' = buy one component
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'build';
$single_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$items = [];       // ['component_id', 'slot_type', 'name', 'brand', 'price']
$total = 0;

if ($mode === 'single' && $single_id > 0) {
    $r = $conn->query("SELECT * FROM components WHERE component_id=$single_id");
    if ($r && $r->num_rows > 0) {
        $c = $r->fetch_assoc();
        $items[] = ['component_id' => $c['component_id'], 'slot_type' => strtolower(str_replace(' ','',$c['Type'])), 'name' => $c['Name'], 'brand' => $c['Brand'], 'price' => (float)$c['Price']];
        $total   = (float)$c['Price'];
    }
} else {
    // Build mode — load from session
    if (!empty($_SESSION['build'])) {
        foreach ($_SESSION['build'] as $slot => $cid) {
            $r = $conn->query("SELECT * FROM components WHERE component_id=" . (int)$cid);
            if ($r && $r->num_rows > 0) {
                $c = $r->fetch_assoc();
                $items[] = ['component_id' => $c['component_id'], 'slot_type' => $slot, 'name' => $c['Name'], 'brand' => $c['Brand'], 'price' => (float)$c['Price']];
                $total  += (float)$c['Price'];
            }
        }
    }
}

if (empty($items)) { header("Location: index.php"); exit(); }

// ── PLACE ORDER ──
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $full_name  = trim($_POST['full_name']  ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $address    = trim($_POST['address']    ?? '');
    $city       = trim($_POST['city']       ?? '');
    $note       = trim($_POST['note']       ?? '');
    $pay_method = $_POST['payment_method']  ?? 'cod';
    $txn_id     = trim($_POST['txn_id']     ?? '');

    if (empty($full_name)) $errors[] = "Full name is required.";
    if (empty($phone))     $errors[] = "Phone number is required.";
    if (empty($address))   $errors[] = "Delivery address is required.";
    if (empty($city))      $errors[] = "City is required.";
    if (in_array($pay_method, ['bkash','nagad','card']) && empty($txn_id))
        $errors[] = "Transaction ID is required for {$pay_method} payment.";

    if (empty($errors)) {
        $uid         = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $order_type  = $mode === 'single' ? 'single' : 'build';
        $pay_status  = $pay_method === 'cod' ? 'unpaid' : 'paid';

        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (user_id,order_type,payment_method,payment_status,txn_id,total_price,full_name,phone,address,city,note) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("issssdsssss", $uid, $order_type, $pay_method, $pay_status, $txn_id, $total, $full_name, $phone, $address, $city, $note);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        // Insert order items + reduce stock
        foreach ($items as $item) {
            $s = $conn->prepare("INSERT INTO order_items (order_id,component_id,slot_type,name,brand,price) VALUES (?,?,?,?,?,?)");
            $s->bind_param("iisssd", $order_id, $item['component_id'], $item['slot_type'], $item['name'], $item['brand'], $item['price']);
            $s->execute(); $s->close();
            // Reduce stock (minimum 0)
            $conn->query("UPDATE components SET stock_quantity = GREATEST(0, stock_quantity - 1) WHERE component_id=" . (int)$item['component_id']);
        }

        // Clear build from session if buying build
        if ($mode === 'build') $_SESSION['build'] = [];

        $_SESSION['last_order_id'] = $order_id;
        header("Location: order_confirm.php?id=$order_id");
        exit();
    }
}

$conn->close();

// Pre-fill from session if logged in
$prefill_name  = '';
$prefill_phone = '';
if (isset($_SESSION['username'])) $prefill_name = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — NextGen PC Builder</title>
    <style>
        :root{--bg:#031c34;--card:#083c6c;--accent:#0b609c;--badge:#72a0b8;--text:#b6cddc;--white:#fff;--danger:#ef4444;--success:#10b981;--warn:#f59e0b;--border:rgba(114,160,184,.22);--dark:#042a50;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--white);padding:2rem 1rem;min-height:100vh;}
        .container{max-width:1000px;margin:0 auto;}
        .back-link{display:inline-block;color:var(--accent);text-decoration:none;font-weight:bold;margin-bottom:1.5rem;font-size:.9rem;}
        .back-link:hover{text-decoration:underline;}
        h1{font-size:1.9rem;margin-bottom:.3rem;}
        .subtitle{color:var(--badge);font-size:.9rem;margin-bottom:2rem;}

        /* LAYOUT */
        .checkout-grid{display:grid;grid-template-columns:1fr 380px;gap:1.5rem;align-items:start;}
        @media(max-width:820px){.checkout-grid{grid-template-columns:1fr;}}

        /* LEFT FORM */
        .form-card{background:var(--dark);border-radius:14px;border:1px solid var(--border);overflow:hidden;}
        .form-section{padding:1.5rem;}
        .form-section+.form-section{border-top:1px solid var(--border);}
        .section-title{font-size:.78rem;font-weight:bold;text-transform:uppercase;letter-spacing:1.5px;color:var(--badge);margin-bottom:1.2rem;display:flex;align-items:center;gap:.5rem;}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:.9rem;}
        .form-group{display:flex;flex-direction:column;gap:.35rem;}
        .form-group.full{grid-column:1/-1;}
        label{font-size:.8rem;color:var(--badge);font-weight:bold;}
        input,select,textarea{width:100%;padding:10px 13px;background:#031c34;border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:.9rem;outline:none;transition:border .2s;font-family:inherit;}
        input:focus,select:focus,textarea:focus{border-color:var(--accent);}
        textarea{resize:vertical;min-height:70px;}

        /* PAYMENT SELECTOR */
        .pay-options{display:grid;grid-template-columns:1fr 1fr;gap:.7rem;}
        .pay-opt{position:relative;}
        .pay-opt input[type=radio]{position:absolute;opacity:0;width:0;height:0;}
        .pay-label{display:flex;align-items:center;gap:.7rem;padding:.9rem 1rem;background:#031c34;border:2px solid var(--border);border-radius:10px;cursor:pointer;transition:all .2s;font-size:.88rem;}
        .pay-label:hover{border-color:var(--accent);}
        .pay-opt input[type=radio]:checked + .pay-label{border-color:var(--accent);background:rgba(11,96,156,.2);}
        .pay-icon{font-size:1.4rem;flex-shrink:0;}
        .pay-name{font-weight:bold;font-size:.88rem;}
        .pay-sub{font-size:.72rem;color:var(--badge);}

        /* TXN ID SECTION */
        .txn-section{display:none;margin-top:1rem;padding:1rem;background:#031c34;border-radius:9px;border:1px solid var(--border);}
        .txn-section.show{display:block;}
        .pay-instruction{font-size:.82rem;color:var(--badge);margin-bottom:.8rem;line-height:1.6;}
        .pay-instruction strong{color:var(--white);}
        .pay-number{font-size:1.2rem;font-weight:bold;color:var(--accent);letter-spacing:1px;margin-bottom:.5rem;}

        /* ERRORS */
        .errors{background:rgba(239,68,68,.1);border:1px solid var(--danger);border-radius:8px;padding:1rem 1.2rem;margin-bottom:1.2rem;}
        .errors p{color:#fca5a5;font-size:.85rem;margin:.2rem 0;}
        .errors p::before{content:"• ";}

        /* RIGHT — ORDER SUMMARY */
        .summary-card{background:var(--dark);border-radius:14px;border:1px solid var(--border);padding:1.5rem;position:sticky;top:1rem;}
        .summary-title{font-size:.78rem;font-weight:bold;text-transform:uppercase;letter-spacing:1.5px;color:var(--badge);margin-bottom:1.2rem;}
        .summary-items{display:flex;flex-direction:column;gap:.7rem;margin-bottom:1.2rem;}
        .summary-item{display:flex;justify-content:space-between;gap:.8rem;align-items:flex-start;}
        .item-info{flex:1;min-width:0;}
        .item-name{font-size:.85rem;font-weight:bold;line-height:1.3;}
        .item-brand{font-size:.72rem;color:var(--badge);}
        .item-slot{font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:var(--accent);margin-bottom:.15rem;}
        .item-price{font-size:.9rem;font-weight:bold;color:var(--success);white-space:nowrap;flex-shrink:0;}
        .summary-divider{height:1px;background:var(--border);margin:.8rem 0;}
        .summary-row{display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:.4rem;color:var(--badge);}
        .summary-total{display:flex;justify-content:space-between;font-size:1.5rem;font-weight:bold;margin-top:.8rem;}
        .summary-total .amt{color:var(--success);}

        /* PLACE ORDER BTN */
        .btn-order{width:100%;padding:14px;background:var(--success);color:#000;border:none;border-radius:10px;font-size:1.05rem;font-weight:bold;cursor:pointer;margin-top:1.2rem;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:.5rem;}
        .btn-order:hover{background:#34d399;}

        /* BADGES */
        .slot-badge{display:inline-block;background:rgba(11,96,156,.3);color:var(--text);padding:1px 7px;border-radius:20px;font-size:.65rem;text-transform:uppercase;letter-spacing:.5px;}
    </style>
</head>
<body>
<div class="container">
    <a href="<?= $mode==='single' ? 'index.php' : 'build.php' ?>" class="back-link">← Back</a>
    <h1>🛒 Checkout</h1>
    <p class="subtitle"><?= $mode==='build' ? 'Purchasing your full custom PC build' : 'Purchasing ' . htmlspecialchars($items[0]['name'] ?? 'component') ?></p>

    <?php if (!empty($errors)): ?>
    <div class="errors"><?php foreach($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="POST">
    <div class="checkout-grid">

        <!-- LEFT: FORM -->
        <div>
        <div class="form-card">

            <!-- DELIVERY INFO -->
            <div class="form-section">
                <div class="section-title">📦 Delivery Information</div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" placeholder="Your full name" required value="<?= htmlspecialchars($_POST['full_name'] ?? $prefill_name) ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" placeholder="e.g. 01XXXXXXXXX" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>City *</label>
                        <select name="city" required>
                            <option value="">— Select City —</option>
                            <?php
                            $cities = ['Dhaka','Chittagong','Rajshahi','Khulna','Barishal','Sylhet','Rangpur','Mymensingh','Gazipur','Narayanganj','Comilla','Narsingdi'];
                            foreach ($cities as $ct) {
                                $sel = (($_POST['city'] ?? '') === $ct) ? 'selected' : '';
                                echo "<option value='$ct' $sel>$ct</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Full Delivery Address *</label>
                        <textarea name="address" placeholder="House no, Road no, Area, Thana..." required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Order Note (Optional)</label>
                        <textarea name="note" placeholder="Any special instructions..."><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- PAYMENT METHOD -->
            <div class="form-section">
                <div class="section-title">💳 Payment Method</div>
                <div class="pay-options">
                    <div class="pay-opt">
                        <input type="radio" name="payment_method" id="pay_cod" value="cod" <?= ($_POST['payment_method'] ?? 'cod')==='cod'?'checked':'' ?> onchange="showTxn(this.value)">
                        <label for="pay_cod" class="pay-label">
                            <span class="pay-icon">💵</span>
                            <div><div class="pay-name">Cash on Delivery</div><div class="pay-sub">Pay when you receive</div></div>
                        </label>
                    </div>
                    <div class="pay-opt">
                        <input type="radio" name="payment_method" id="pay_bkash" value="bkash" <?= ($_POST['payment_method'] ?? '')==='bkash'?'checked':'' ?> onchange="showTxn(this.value)">
                        <label for="pay_bkash" class="pay-label">
                            <span class="pay-icon">📱</span>
                            <div><div class="pay-name">bKash</div><div class="pay-sub">Mobile banking</div></div>
                        </label>
                    </div>
                    <div class="pay-opt">
                        <input type="radio" name="payment_method" id="pay_nagad" value="nagad" <?= ($_POST['payment_method'] ?? '')==='nagad'?'checked':'' ?> onchange="showTxn(this.value)">
                        <label for="pay_nagad" class="pay-label">
                            <span class="pay-icon">💳</span>
                            <div><div class="pay-name">Nagad</div><div class="pay-sub">Mobile banking</div></div>
                        </label>
                    </div>
                    <div class="pay-opt">
                        <input type="radio" name="payment_method" id="pay_card" value="card" <?= ($_POST['payment_method'] ?? '')==='card'?'checked':'' ?> onchange="showTxn(this.value)">
                        <label for="pay_card" class="pay-label">
                            <span class="pay-icon">🏦</span>
                            <div><div class="pay-name">Card / Bank</div><div class="pay-sub">Transfer to account</div></div>
                        </label>
                    </div>
                </div>

                <!-- TRANSACTION ID SECTION -->
                <div class="txn-section" id="txnSection">
                    <div class="pay-instruction" id="payInstruction"></div>
                    <div class="form-group">
                        <label>Transaction ID *</label>
                        <input type="text" name="txn_id" id="txnInput" placeholder="Enter your transaction ID" value="<?= htmlspecialchars($_POST['txn_id'] ?? '') ?>">
                    </div>
                </div>
            </div>

        </div><!-- form-card -->
        </div>

        <!-- RIGHT: ORDER SUMMARY -->
        <div class="summary-card">
            <div class="summary-title">📋 Order Summary</div>
            <div class="summary-items">
                <?php foreach ($items as $item): ?>
                <div class="summary-item">
                    <div class="item-info">
                        <?php if ($item['slot_type']): ?>
                            <div class="item-slot"><?= htmlspecialchars(strtoupper($item['slot_type'])) ?></div>
                        <?php endif; ?>
                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="item-brand"><?= htmlspecialchars($item['brand']) ?></div>
                    </div>
                    <div class="item-price">৳<?= number_format($item['price'], 2) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-divider"></div>
            <div class="summary-row"><span>Subtotal</span><span>৳<?= number_format($total, 2) ?></span></div>
            <div class="summary-row"><span>Shipping</span><span style="color:var(--success);">FREE</span></div>
            <div class="summary-row"><span>Items</span><span><?= count($items) ?></span></div>
            <div class="summary-divider"></div>
            <div class="summary-total">
                <span>Total</span>
                <span class="amt">৳<?= number_format($total, 2) ?></span>
            </div>

            <button type="submit" name="place_order" class="btn-order">
                ✅ Place Order
            </button>

            <?php if (!isset($_SESSION['user_id'])): ?>
            <p style="font-size:.75rem;color:var(--badge);margin-top:.8rem;text-align:center;">
                💡 <a href="login.php" style="color:var(--accent);">Sign in</a> to track your order history
            </p>
            <?php endif; ?>
        </div>

    </div>
    </form>
</div>

<script>
const payInfo = {
    bkash: { num: '01844921508 (bKash Merchant)', inst: 'Send <strong>৳<?= number_format($total, 2) ?></strong> to our bKash merchant number:<br><div class="pay-number">01844921508</div>Use "Send Money", enter your order amount, then paste the TxnID below.' },
    nagad:  { num: '01844921508 (Nagad Merchant)', inst: 'Send <strong>৳<?= number_format($total, 2) ?></strong> to our Nagad merchant number:<br><div class="pay-number">01844921508</div>Use "Send Money", enter the amount, then paste the TxnID below.' },
    card:   { num: 'Bank Account', inst: 'Transfer <strong>৳<?= number_format($total, 2) ?></strong> to:<br><div class="pay-number">Bank: Islami Bank PLC | AC: 20501120207149417</div>Use your bank app or internet banking, then paste the Transaction ID below.' },
};

function showTxn(val) {
    const sec  = document.getElementById('txnSection');
    const inst = document.getElementById('payInstruction');
    if (val === 'cod') {
        sec.classList.remove('show');
    } else {
        sec.classList.add('show');
        inst.innerHTML = payInfo[val]?.inst || '';
    }
}

// On load — show txn if already selected
document.addEventListener('DOMContentLoaded', () => {
    const checked = document.querySelector('input[name="payment_method"]:checked');
    if (checked && checked.value !== 'cod') showTxn(checked.value);
});
</script>
</body>
</html>
