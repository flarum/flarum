<h2>Hold Up!</h2>

<p>These errors must be resolved before you can continue the installation. If you're having trouble, get help on the <a href="http://flarum.org/docs/installation" target="_blank">Flarum website</a>.</p>

<div class="Errors">
  <?php foreach ($errors as $error): ?>
    <div class="Error">
      <h3 class="Error-message"><?php echo $error['message']; ?></h3>
      <?php if (! empty($error['detail'])): ?>
        <p class="Error-detail"><?php echo $error['detail']; ?></p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
