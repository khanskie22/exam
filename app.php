<?php
declare(strict_types=1);

// Prices are stored in centavos so money calculations remain exact.
function products(): array
{
    return [
        'notebook' => ['name' => 'Notebook', 'price' => 4500, 'icon' => '▤', 'description' => '80 leaves · ruled pages', 'color' => 'purple'],
        'pen' => ['name' => 'Ballpoint Pen', 'price' => 1500, 'icon' => '✒', 'description' => 'Black ink · smooth writing', 'color' => 'blue'],
        'pencil' => ['name' => 'Pencil', 'price' => 1000, 'icon' => '✎', 'description' => 'No. 2 · everyday essential', 'color' => 'yellow'],
        'eraser' => ['name' => 'Eraser', 'price' => 800, 'icon' => '▱', 'description' => 'Soft rubber · clean corrections', 'color' => 'pink'],
        'ruler' => ['name' => 'Ruler', 'price' => 2000, 'icon' => '▥', 'description' => '30 cm · clear measurements', 'color' => 'green'],
        'folder' => ['name' => 'Folder', 'price' => 1200, 'icon' => '▰', 'description' => 'Long size · keep things together', 'color' => 'orange'],
    ];
}

function money(int $centavos): string
{
    return '₱' . number_format($centavos / 100, 2);
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function cartItems(array $cart): array
{
    $items = [];
    foreach ($cart as $id => $quantity) {
        $product = products()[$id];
        $items[] = $product + ['id' => $id, 'quantity' => $quantity, 'subtotal' => $product['price'] * $quantity];
    }
    return $items;
}

function cartTotal(array $cart): int
{
    return array_sum(array_column(cartItems($cart), 'subtotal'));
}

function paymentCentavos(string $input): int
{
    $input = trim($input);
    if ($input === '') {
        throw new InvalidArgumentException('Enter the amount paid.');
    }
    if (!is_numeric($input)) {
        throw new InvalidArgumentException('Enter a valid number for the payment amount.');
    }
    if ((float) $input < 0) {
        throw new InvalidArgumentException('Payment cannot be negative.');
    }
    if (!preg_match('/^\d{1,7}(?:\.\d{1,2})?$/D', $input)) {
        throw new InvalidArgumentException('Use up to two decimal places, with a maximum payment of ₱9,999,999.99.');
    }
    $parts = explode('.', $input);
    return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
}

function processAction(array &$state, array $input): void
{
    $action = $input['action'] ?? '';
    $state['error'] = '';
    $state['notice'] = '';
    if ($action === 'new') {
        $state = ['cart' => [], 'payment' => '', 'error' => '', 'notice' => 'Ready for a new transaction.', 'receipt' => null];
        return;
    }
    if (!empty($state['receipt'])) {
        $state['error'] = 'This transaction is complete. Start a new transaction to place another order.';
        return;
    }
    if (in_array($action, ['add', 'increase', 'decrease', 'remove'], true)) {
        $id = $input['product'] ?? '';
        if (!is_string($id) || !isset(products()[$id])) {
            $state['error'] = 'Choose a valid product.';
            return;
        }
        $quantity = $state['cart'][$id] ?? 0;
        if ($action === 'remove' || ($action === 'decrease' && $quantity <= 1)) {
            unset($state['cart'][$id]);
        } elseif ($action === 'decrease') {
            $state['cart'][$id]--;
        } elseif ($quantity < 999) {
            $state['cart'][$id] = $quantity + 1;
        } else {
            $state['error'] = 'A product can have a maximum quantity of 999.';
            return;
        }
        $state['payment'] = '';
        $state['notice'] = 'Cart updated.';
        return;
    }
    if ($action === 'pay') {
        $state['payment'] = is_string($input['payment'] ?? null) ? $input['payment'] : '';
        if (!$state['cart']) {
            $state['error'] = 'Add at least one product before making a payment.';
            return;
        }
        try {
            $paid = paymentCentavos($state['payment']);
            $total = cartTotal($state['cart']);
            if ($paid < $total) {
                throw new InvalidArgumentException('Insufficient payment. Please pay at least ' . money($total) . '.');
            }
            $state['receipt'] = [
                'reference' => 'SS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))),
                'date' => date('M d, Y · h:i A') . ' (Asia/Manila)',
                'items' => cartItems($state['cart']),
                'total' => $total,
                'paid' => $paid,
                'change' => $paid - $total,
            ];
            $state['notice'] = 'Payment confirmed. Your receipt is ready.';
        } catch (InvalidArgumentException $error) {
            $state['error'] = $error->getMessage();
        }
    }
}
