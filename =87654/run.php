<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app.php';
date_default_timezone_set('Asia/Manila');
$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
    $checks++;
    echo "PASS: $message\n";
}
$state = [];
processAction($state, ['action' => 'new']);
processAction($state, ['action' => 'add', 'product' => 'notebook']);
check($state['cart'] === ['notebook' => 1] && cartTotal($state['cart']) === 4500, 'Add one product');
processAction($state, ['action' => 'add', 'product' => 'pen']);
check(count($state['cart']) === 2 && cartTotal($state['cart']) === 6000, 'Add multiple products and update total');
processAction($state, ['action' => 'increase', 'product' => 'notebook']);
check($state['cart']['notebook'] === 2 && cartTotal($state['cart']) === 10500, 'Increase quantity and subtotal');
processAction($state, ['action' => 'decrease', 'product' => 'notebook']);
check($state['cart']['notebook'] === 1 && cartTotal($state['cart']) === 6000, 'Decrease quantity and total');
processAction($state, ['action' => 'decrease', 'product' => 'pen']);
check(!isset($state['cart']['pen']), 'Decrease to zero removes item');
processAction($state, ['action' => 'add', 'product' => 'pencil']);
processAction($state, ['action' => 'remove', 'product' => 'pencil']);
check(!isset($state['cart']['pencil']) && cartTotal($state['cart']) === 4500, 'Remove item and update total');
foreach (['' => 'Enter the amount', 'abc' => 'valid number', '-10' => 'negative', '44.99' => 'Insufficient', '45.001' => 'two decimal', '1e5' => 'two decimal'] as $payment => $expected) {
    processAction($state, ['action' => 'pay', 'payment' => (string) $payment]);
    check(str_contains($state['error'], $expected) && !$state['receipt'], 'Reject payment: ' . var_export($payment, true));
}
processAction($state, ['action' => 'pay', 'payment' => '100.50']);
check($state['receipt']['total'] === 4500 && $state['receipt']['paid'] === 10050 && $state['receipt']['change'] === 5550, 'Sufficient payment and exact decimal change');
check(count($state['receipt']['items']) === 1 && $state['receipt']['items'][0]['quantity'] === 1 && $state['receipt']['reference'] !== '' && $state['receipt']['date'] !== '', 'Receipt contains products, quantities, reference, and date');
$receipt = $state['receipt'];
processAction($state, ['action' => 'add', 'product' => 'pen']);
processAction($state, ['action' => 'pay', 'payment' => '200']);
check($state['receipt'] === $receipt && count($state['cart']) === 1, 'Completed order cannot be changed or paid twice');
processAction($state, ['action' => 'new']);
check($state['cart'] === [] && $state['payment'] === '' && $state['error'] === '' && $state['receipt'] === null, 'New transaction clears cart, payment, errors, and receipt');
processAction($state, ['action' => 'pay', 'payment' => '100']);
check(str_contains($state['error'], 'Add at least') && !$state['receipt'], 'Reject payment for empty cart');
processAction($state, ['action' => 'add', 'product' => 'eraser']);
processAction($state, ['action' => 'pay', 'payment' => '8']);
check($state['receipt']['change'] === 0, 'Exact payment produces zero change');
echo "$checks checks passed.\n";
