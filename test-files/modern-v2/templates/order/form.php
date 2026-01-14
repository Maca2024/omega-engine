<?php
/**
 * Order Form Template - Modern, secure, accessible.
 *
 * REPLACES TOXIC LEGACY CODE:
 * ```php
 * <body bgcolor="#CCCCCC">
 * <center>
 *     <font face="Verdana" size="2">
 *     <h1>Hallo <? echo $this->klant_data['naam'];?></h1>  // XSS!
 *     <form action="?actie=save" method="POST">
 *         <table border="1" cellpadding="5">
 *             <? while ($row = mysql_fetch_object($res)) { // DB query in view!
 *                 $kleur = ($row->voorraad < 5)? "red" : "black";
 *                 echo "<td><font color='$kleur'>". $row->naam. "</font></td>"; // XSS!
 *             } ?>
 * ```
 *
 * NOW: Proper escaping, semantic HTML, accessibility.
 *
 * @var App\Domain\Customer\DTOs\CustomerDTO $customer
 * @var list<App\Domain\Catalog\DTOs\ProductDTO> $products
 * @var string $csrf_token
 * @var callable $e Escape function
 */
?>
<div class="card">
    <h1>Welkom, <?= $e($customer->name) ?></h1>

    <form action="?action=save" method="POST">
        <?= $csrf_token ?>
        <input type="hidden" name="customer_id" value="<?= $customer->id ?>">

        <table>
            <thead>
                <tr>
                    <th scope="col">Product</th>
                    <th scope="col">Prijs</th>
                    <th scope="col">BTW</th>
                    <th scope="col">Voorraad</th>
                    <th scope="col">Aantal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <strong><?= $e($product->name) ?></strong>
                            <?php if ($product->description): ?>
                                <br>
                                <small><?= $e($product->description) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>&euro; <?= number_format($product->price, 2, ',', '.') ?></td>
                        <td><?= $product->vatCategory->percentage() ?>%</td>
                        <td class="<?= $product->isLowStock() ? 'stock-low' : 'stock-ok' ?>">
                            <?= $product->stock ?>
                            <?php if ($product->isLowStock()): ?>
                                <span aria-label="Lage voorraad">(!)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <label class="visually-hidden" for="qty-<?= $product->id ?>">
                                Aantal <?= $e($product->name) ?>
                            </label>
                            <input
                                type="number"
                                id="qty-<?= $product->id ?>"
                                name="mandje[<?= $product->id ?>]"
                                value="0"
                                min="0"
                                max="<?= $product->stock ?>"
                                aria-describedby="stock-<?= $product->id ?>"
                            >
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn-primary">
            Bestelling Plaatsen
        </button>
    </form>
</div>

<style>
    .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
</style>
