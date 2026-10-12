# StudentSuppliesOrderingSystem

A PHP and Bootstrap 5 school supplies checkout. No database, Composer, npm, or JavaScript required. Bootstrap CSS is bundled locally so it works offline.

## Run

Requires PHP 8.0 or newer with sessions enabled.

From this directory:

```sh
php -S 127.0.0.1:8080
```

Open http://127.0.0.1:8080. Alternatively, with Laragon's web server running, open http://localhost/exam/StudentSuppliesOrderingSystem/.

## Features

- Six hard-coded products with sample Philippine peso prices, editable in [app.php](app.php).
- Session-based cart; quantity controls, removal, and recalculated totals after every action.
- Cash validation for blank, nonnumeric, negative, insufficient, and overly precise amounts.
- Integer centavo calculations, with quantities limited to 999 per product.
- Receipt with a random transaction reference, Manila timestamp, item details, total, payment, and change.
- Paid transactions are locked until New Transaction clears the order, payment, errors, and receipt.
- Server-side validation, CSRF tokens, escaped output, and redirect after POST to avoid repeat submissions on refresh.

This is a classroom demonstration: receipts last only for the current browser session and are not stored in a database. It records cash received; it does not process online payments.

## Tests

```sh
php tests/run.php
```

The checks cover all ten requested transaction scenarios, invalid numeric input, exact payment, decimal accuracy, completed-order protection, and empty-cart payment.
