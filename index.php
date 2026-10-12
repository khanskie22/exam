<?php
declare(strict_types=1);
require __DIR__ . '/app.php';
date_default_timezone_set('Asia/Manila');
session_start();
header('Cache-Control: no-store');
$_SESSION['token'] ??= bin2hex(random_bytes(32));
$_SESSION['order'] ??= ['cart' => [], 'payment' => '', 'error' => '', 'notice' => '', 'receipt' => null];
$state = &$_SESSION['order'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['token'] ?? null) || !hash_equals($_SESSION['token'], $_POST['token'])) {
        $state['error'] = 'Your session changed. Please try again.';
    } else {
        processAction($state, $_POST);
    }
    header('Location: index.php', true, 303);
    exit;
}
$items = cartItems($state['cart']);
$total = cartTotal($state['cart']);
$count = array_sum($state['cart']);
$receipt = $state['receipt'];
$error = $state['error'];
$notice = $state['notice'];
$state['error'] = $state['notice'] = '';
function tokenField(): void { echo '<input type="hidden" name="token" value="' . escape($_SESSION['token']) . '">'; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Supplies Ordering System</title>
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <div class="container d-flex justify-content-between align-items-center gap-3">
        <a href="index.php" class="brand"><span class="brand-mark" aria-hidden="true">s.</span> student supplies<span class="brand-dot">.</span></a>
        <span class="store-tag">YOUR CAMPUS ESSENTIALS</span>
    </div>
</header>
<main class="container py-4 py-lg-5">
    <div class="intro d-flex justify-content-between align-items-end gap-3 mb-4">
        <div><div class="eyebrow mb-2">A LITTLE PREP. A GREAT SCHOOL DAY.</div><h1>Good things start here.</h1><p class="text-secondary mb-0">Pick your supplies, check your cart, and you’re ready for class.</p></div>
        <span class="cash-badge">● Cash payments</span>
    </div>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= escape($error) ?></div><?php endif; ?>
    <?php if ($notice): ?><div class="alert alert-success" role="status"><?= escape($notice) ?></div><?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <section aria-labelledby="products-title" class="mb-4">
                <div class="section-heading"><h2 id="products-title">Product List</h2><span>6 everyday essentials</span></div>
                <div class="row g-3">
                    <?php foreach (products() as $id => $product): ?>
                    <div class="col-sm-6 col-xl-4">
                        <article class="product-card h-100">
                            <div class="product-art <?= $product['color'] ?>" aria-hidden="true"><span><?= $product['icon'] ?></span><small>STUDENT ESSENTIAL</small></div>
                            <div class="p-3"><h3><?= escape($product['name']) ?></h3><p class="product-description"><?= escape($product['description']) ?></p>
                                <div class="d-flex justify-content-between align-items-center gap-2"><strong class="product-price"><?= money($product['price']) ?></strong>
                                    <form method="post"><?php tokenField(); ?><input type="hidden" name="action" value="add"><input type="hidden" name="product" value="<?= $id ?>"><button class="btn btn-sm btn-add" <?= $receipt ? 'disabled' : '' ?> aria-label="Add <?= escape($product['name']) ?> to cart">+ Add to Cart</button></form>
                                </div>
                            </div>
                        </article>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="panel" aria-labelledby="cart-title">
                <div class="section-heading"><h2 id="cart-title">Shopping Cart <span class="count-badge"><?= $count ?></span></h2><span><?= $count ?> item<?= $count === 1 ? '' : 's' ?></span></div>
                <?php if (!$items): ?>
                    <div class="empty-state"><span class="empty-icon" aria-hidden="true">＋</span><h3>Your next great idea starts here.</h3><p>Add a few essentials to your cart to get started.</p></div>
                <?php else: ?>
                    <div class="table-responsive"><table class="table align-middle cart-table"><thead><tr><th>Product</th><th>Unit price</th><th>Quantity</th><th class="text-end">Subtotal</th><th><span class="visually-hidden">Remove</span></th></tr></thead><tbody>
                    <?php foreach ($items as $item): ?>
                        <tr><th scope="row"><?= escape($item['name']) ?></th><td><?= money($item['price']) ?></td><td>
                            <form method="post" class="quantity-control"><?php tokenField(); ?><input type="hidden" name="product" value="<?= $item['id'] ?>"><button name="action" value="decrease" aria-label="Decrease <?= escape($item['name']) ?> quantity" <?= $receipt ? 'disabled' : '' ?>>−</button><span aria-label="Quantity"><?= $item['quantity'] ?></span><button name="action" value="increase" aria-label="Increase <?= escape($item['name']) ?> quantity" <?= $receipt ? 'disabled' : '' ?>>+</button></form>
                        </td><td class="text-end fw-semibold"><?= money($item['subtotal']) ?></td><td><form method="post"><?php tokenField(); ?><input type="hidden" name="product" value="<?= $item['id'] ?>"><button class="remove-button" name="action" value="remove" aria-label="Remove <?= escape($item['name']) ?>" <?= $receipt ? 'disabled' : '' ?>>×</button></form></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                <?php endif; ?>
            </section>
        </div>
        <div class="col-lg-4">
            <div class="checkout-stack">
                <section class="panel summary-panel mb-3" aria-labelledby="summary-title">
                    <div class="eyebrow mb-2">YOUR ORDER, ALL TOGETHER</div><h2 id="summary-title">Order Summary</h2>
                    <div class="d-flex justify-content-between summary-line"><span>Items (<?= $count ?>)</span><span><?= money($total) ?></span></div>
                    <div class="total-line d-flex justify-content-between"><span>Total amount</span><strong id="order-total"><?= money($total) ?></strong></div>
                    <p class="small text-secondary mt-2 mb-0">All prices are in Philippine pesos (PHP).</p>
                </section>
                <section class="panel mb-3" aria-labelledby="payment-title">
                    <div class="section-heading"><h2 id="payment-title">Payment Section</h2><span class="step-label">CASH</span></div>
                    <form method="post" novalidate>
                        <?php tokenField(); ?><input type="hidden" name="action" value="pay">
                        <label for="payment" class="form-label">Amount paid</label>
                        <div class="input-group mb-2"><span class="input-group-text">₱</span><input class="form-control" id="payment" name="payment" type="number" inputmode="decimal" step="0.01" min="0" max="9999999.99" placeholder="0.00" value="<?= escape($state['payment']) ?>" aria-describedby="payment-help" <?= $receipt ? 'disabled' : '' ?>></div>
                        <p id="payment-help" class="small text-secondary mb-3">Enter the cash received. We’ll calculate your change.</p>
                        <button type="submit" class="btn btn-primary w-100" <?= $receipt ? 'disabled' : '' ?>><?= $receipt ? '✓ Payment confirmed' : 'Confirm Payment →' ?></button>
                    </form>
                </section>
                <section class="panel receipt-panel" aria-labelledby="receipt-title">
                    <div class="section-heading"><h2 id="receipt-title">Receipt Section</h2><span class="step-label"><?= $receipt ? 'PAID' : 'PENDING' ?></span></div>
                    <?php if (!$receipt): ?>
                        <p class="text-secondary small mb-0">Your digital receipt will appear here after a successful payment.</p>
                    <?php else: ?>
                        <div id="receipt"><div class="receipt-brand">student supplies.</div><p class="text-success fw-semibold text-center">✓ Payment confirmed</p>
                        <dl class="receipt-meta"><dt>Transaction reference</dt><dd><?= escape($receipt['reference']) ?></dd><dt>Date &amp; time</dt><dd><?= escape($receipt['date']) ?></dd></dl>
                        <div class="table-responsive"><table class="table table-sm receipt-table"><thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th class="text-end">Subtotal</th></tr></thead><tbody>
                        <?php foreach ($receipt['items'] as $item): ?><tr><td><?= escape($item['name']) ?></td><td><?= $item['quantity'] ?></td><td><?= money($item['price']) ?></td><td class="text-end"><?= money($item['subtotal']) ?></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                        <div class="receipt-line fw-bold"><span>Total amount due</span><span><?= money($receipt['total']) ?></span></div><div class="receipt-line"><span>Amount paid</span><span><?= money($receipt['paid']) ?></span></div><div class="receipt-line change-line"><span>Change</span><strong id="receipt-change"><?= money($receipt['change']) ?></strong></div><p class="small text-center text-secondary mt-3 mb-0">Thank you. Make it a great school day!</p></div>
                    <?php endif; ?>
                </section>
                <form method="post" class="mt-3"><?php tokenField(); ?><button name="action" value="new" class="btn btn-outline-secondary w-100">New Transaction</button></form>
            </div>
        </div>
    </div>
    <footer class="d-flex flex-wrap justify-content-between gap-2 mt-5 pt-3"><span>StudentSuppliesOrderingSystem</span><span>Small essentials. Big possibilities.</span></footer>
</main>
</body>
</html>
