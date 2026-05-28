<?php
require_once __DIR__ . '/../log_reg_inc/auth.inc.php';
require_once __DIR__ . '/../dbh.inc.php';
require_once __DIR__ . '/admin_paligs.php';

parbauditAutorizaciju('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Skats/admin.php#pasutit');
    exit;
}

function atjaunotPrecesDaudzumu(PDO $pdo, int $productId, int $quantity): void
{
    $stmt = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
    $stmt->execute([$quantity, $productId]);
}

function samazinatPrecesDaudzumu(PDO $pdo, int $productId, int $quantity): void
{
    $productStmt = $pdo->prepare('SELECT quantity FROM products WHERE id = ? FOR UPDATE');
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new RuntimeException('Izvēlētā prece nav atrasta.');
    }

    $availableQuantity = (int) ($product['quantity'] ?? 0);

    if ($quantity > $availableQuantity) {
        throw new RuntimeException('Noliktavā nav pietiekams preces daudzums. Pieejams: ' . $availableQuantity . '.');
    }

    $updateStmt = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
    $updateStmt->execute([$quantity, $productId]);
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Nav atrasts datubāzes pieslēgums.');
    }

    if ($orderId <= 0) {
        throw new RuntimeException('Nav atrasts pasūtījums.');
    }

    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare('SELECT id, product_id, quantity, status FROM orders WHERE id = ? FOR UPDATE');
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new RuntimeException('Pasūtījums nav atrasts.');
    }

    $productId = (int) $order['product_id'];
    $quantity = (int) $order['quantity'];
    $oldStatus = (string) ($order['status'] ?? '');

    if ($action === 'update_order_status') {
        $newStatus = trim((string) ($_POST['status'] ?? ''));
        $allowedStatuses = ['jauns', 'pieņemts', 'izpildīts', 'atcelts'];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new RuntimeException('Nederīgs pasūtījuma statuss.');
        }

        if ($oldStatus !== 'atcelts' && $newStatus === 'atcelts') {
            atjaunotPrecesDaudzumu($pdo, $productId, $quantity);
        } elseif ($oldStatus === 'atcelts' && $newStatus !== 'atcelts') {
            samazinatPrecesDaudzumu($pdo, $productId, $quantity);
        }

        $updateStatusStmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $updateStatusStmt->execute([$newStatus, $orderId]);

        $pdo->commit();
        adminNovirzitArZinu('pasutit', 'ok', 'Pasūtījuma statuss mainīts.');
    }

    if ($action === 'delete_order') {
        if ($oldStatus !== 'atcelts') {
            atjaunotPrecesDaudzumu($pdo, $productId, $quantity);
        }

        $deleteStmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
        $deleteStmt->execute([$orderId]);

        $pdo->commit();
        adminNovirzitArZinu('pasutit', 'ok', 'Pasūtījums izdzēsts.');
    }

    throw new RuntimeException('Nederīga darbība.');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    adminNovirzitArZinu('pasutit', 'kluda', $e->getMessage());
}
