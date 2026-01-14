<?php
/**
 * Order Form Template.
 *
 * @var \App\Domain\Order\DTOs\UserDTO $user
 * @var array<array{id: int, name: string, price: float, stock: int, type: int}> $products
 */

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Formulier - AetherLink.AI Tech</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: #f3f4f6;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 24px;
        }
        h1 {
            color: #2563eb;
            margin-bottom: 8px;
        }
        .welcome {
            color: #6b7280;
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        th {
            text-align: left;
            padding: 12px;
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        input[type="number"] {
            width: 80px;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
        }
        input[type="number"]:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .price {
            font-weight: 500;
            color: #059669;
        }
        .stock {
            font-size: 12px;
            color: #6b7280;
        }
        .btn {
            background: #2563eb;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Order Formulier</h1>
            <p class="welcome">Welkom <?= htmlspecialchars($user->firstName, ENT_QUOTES, 'UTF-8') ?>!</p>

            <form method="POST" action="?action=save" id="orderForm">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Prijs</th>
                            <th>Aantal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
                                <div class="stock">Voorraad: <?= (int) $product['stock'] ?></div>
                            </td>
                            <td class="price">
                                &euro; <?= number_format($product['price'], 2, ',', '.') ?>
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="items[<?= (int) $product['id'] ?>][qty]"
                                    min="0"
                                    max="<?= (int) $product['stock'] ?>"
                                    value="0"
                                    aria-label="Aantal voor <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                                <input type="hidden" name="items[<?= (int) $product['id'] ?>][id]" value="<?= (int) $product['id'] ?>">
                                <input type="hidden" name="items[<?= (int) $product['id'] ?>][price]" value="<?= (float) $product['price'] ?>">
                                <input type="hidden" name="items[<?= (int) $product['id'] ?>][type]" value="<?= (int) $product['type'] ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button type="submit" class="btn">Bestelling plaatsen</button>
            </form>

            <div class="footer">
                &copy; <?= date('Y') ?> AetherLink.AI Tech - Powered by PHP 8.4
            </div>
        </div>
    </div>
</body>
</html>
