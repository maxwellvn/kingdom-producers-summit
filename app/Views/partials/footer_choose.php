<?php $summit = $summit ?? config('app.summit'); ?>
<footer class="choose-foot">
  <div class="container">
    <span class="mono">© <?= date('Y') ?> <?= e($summit['organiser']) ?>. All rights reserved.</span>
    <span><a href="<?= e(url('/privacy')) ?>">Privacy</a></span>
    <a class="mono" href="https://movortech.com" target="_blank" rel="noopener noreferrer">Made by Movor</a>
  </div>
</footer>
