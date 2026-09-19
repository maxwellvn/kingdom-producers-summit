<?php /** @var array<string,string> $items */
$errors = \App\Core\Session::get('_errors', []);
$old = \App\Core\Session::get('_old', []);
$fresh = $old === [];
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <p class="mono pay__kicker"><span class="pay__dot" aria-hidden="true"></span> The Commitment</p>
      <h1 class="pay__title">Four things I will do.</h1>
      <p class="pay__lede">Read them, tick the ones you mean, put your name to it. We pray over every card after the session.</p>

      <?php if ($err = ($errors['items'] ?? '')): ?>
        <div class="pay__alert" role="alert"><span><?= e($err) ?></span></div>
      <?php endif; ?>

      <form class="pay__form commit__form" method="post" action="<?= url('/commit') ?>">
        <?= csrf_field() ?>
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">

        <ol class="commit__list">
          <?php $n = 0; foreach ($items as $key => $text): $n++; ?>
            <li>
              <label class="check commit__item">
                <input type="checkbox" name="<?= $key ?>" value="1" <?= ($fresh || old_checked($key, '1')) ? 'checked' : '' ?>>
                <span><span class="mono commit__num"><?= str_pad((string) $n, 2, '0', STR_PAD_LEFT) ?></span> <?= e($text) ?></span>
              </label>
            </li>
          <?php endforeach; ?>
        </ol>

        <div class="field <?= error_for('name') ? 'has-error' : '' ?>">
          <label for="name">Your name</label>
          <input id="name" name="name" type="text" autocomplete="name" maxlength="160" required value="<?= e(old('name')) ?>">
          <?php if ($err = error_for('name')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="what">What I will produce <span class="field__opt">optional</span></label>
          <input id="what" name="what" type="text" maxlength="255" placeholder="A product, a service, a book…" value="<?= e(old('what')) ?>">
        </div>
        <div class="form__row">
          <div class="field <?= error_for('email') ? 'has-error' : '' ?>">
            <label for="email">Email <span class="field__opt">optional</span></label>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="email" autocapitalize="off" value="<?= e(old('email')) ?>">
            <?php if ($err = error_for('email')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="kingschat">KingsChat <span class="field__opt">optional</span></label>
            <input id="kingschat" name="kingschat" type="text" autocapitalize="off" autocorrect="off" maxlength="80" placeholder="@username" value="<?= e(old('kingschat')) ?>">
          </div>
        </div>

        <button type="submit" class="btn btn--stamp btn--lg">
          <span class="btn__label">I commit</span>
          <span class="btn__arrow" aria-hidden="true"><?= icon_arrow() ?></span>
        </button>
      </form>
    </div>
  </div>
</section>
