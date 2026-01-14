<?php
/**
 * Error Template.
 *
 * @var string $message
 * @var callable $e Escape function
 */
?>
<div class="card">
    <div class="alert alert-error">
        <strong>Er is een fout opgetreden:</strong>
        <p><?= $e($message) ?></p>
    </div>

    <a href="javascript:history.back()" class="btn btn-primary">
        &larr; Terug
    </a>
</div>
