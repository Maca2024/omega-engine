<?php
/**
 * Order Success Template.
 *
 * @var App\Domain\Order\DTOs\OrderDTO $order
 * @var callable $e Escape function
 */
?>
<div class="card">
    <div class="alert alert-success">
        <strong>Bestelling succesvol geplaatst!</strong>
        <p>Uw bestelnummer is: <strong>#<?= $order->id ?></strong></p>
    </div>

    <h2>Overzicht</h2>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Aantal</th>
                <th>Prijs</th>
                <th>Subtotaal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order->cart->items as $item): ?>
                <tr>
                    <td><?= $e($item->productName) ?></td>
                    <td><?= $item->quantity ?></td>
                    <td>&euro; <?= number_format($item->unitPrice, 2, ',', '.') ?></td>
                    <td>&euro; <?= number_format($item->subtotal(), 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Subtotaal</strong></td>
                <td>&euro; <?= number_format($order->subtotal, 2, ',', '.') ?></td>
            </tr>
            <?php if ($order->discountAmount > 0): ?>
                <tr>
                    <td colspan="3"><strong>Korting</strong></td>
                    <td>- &euro; <?= number_format($order->discountAmount, 2, ',', '.') ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td colspan="3"><strong>BTW</strong></td>
                <td>&euro; <?= number_format($order->vatTotal, 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td colspan="3"><strong>Totaal</strong></td>
                <td><strong>&euro; <?= number_format($order->grandTotal, 2, ',', '.') ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <a href="/" class="btn btn-primary">Terug naar winkel</a>
</div>
