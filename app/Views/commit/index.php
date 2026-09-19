<?php /** @var array<string,string> $items @var string[] $titles */
$errors = \App\Core\Session::get('_errors', []);
?>
<section class="reg">
  <div class="container pay">
    <div class="pay__card">
      <h1 class="pay__title">The Commitment</h1>
      <p class="commit__speaker"><span class="mono">Keynote speaker</span><strong>Pastor Rita Ijomah</strong><span>Head of Service, Loveworld Kingdom</span></p>
      <h2 class="pay__title commit__title2">Four things I will do.</h2>
      <p class="pay__lede">A producer is someone who makes, not only someone who consumes. At the close of this session you are asked to decide four things you will do with what you have heard, and to put your name to them.</p>
      <p class="commit__intro">Tick each commitment you are making and put your name to it. We will pray over every submission.</p>

      <?php if ($err = ($errors['items'] ?? '')): ?>
        <div class="pay__alert" role="alert"><span><?= e($err) ?></span></div>
      <?php endif; ?>

      <form class="pay__form commit__form" method="post" action="<?= url('/commitment') ?>">
        <?= csrf_field() ?>
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">

        <ol class="commit__list">
          <?php $n = 0; foreach ($items as $key => $text): $n++; ?>
            <li>
              <label class="check commit__item">
                <input type="checkbox" name="<?= $key ?>" value="1" <?= old_checked($key, '1') ?>>
                <span><span class="mono commit__num"><?= str_pad((string) $n, 2, '0', STR_PAD_LEFT) ?></span> <?= e($text) ?></span>
              </label>
            </li>
          <?php endforeach; ?>
        </ol>

        <div class="form__row form__row--title">
          <div class="field field--sm <?= error_for('title') ? 'has-error' : '' ?>">
            <label for="title">Title</label>
            <select id="title" name="title">
              <option value="">—</option>
              <?php foreach ($titles as $t): ?><option value="<?= $t ?>" <?= old('title') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field <?= error_for('first_name') ? 'has-error' : '' ?>">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" type="text" autocomplete="given-name" maxlength="80" required value="<?= e(old('first_name')) ?>">
            <?php if ($err = error_for('first_name')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>
          <div class="field <?= error_for('last_name') ? 'has-error' : '' ?>">
            <label for="last_name">Surname</label>
            <input id="last_name" name="last_name" type="text" autocomplete="family-name" maxlength="80" required value="<?= e(old('last_name')) ?>">
            <?php if ($err = error_for('last_name')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="form__row">
          <div class="field <?= error_for('email') ? 'has-error' : '' ?>">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="email" autocapitalize="off" required value="<?= e(old('email')) ?>">
            <?php if ($err = error_for('email')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
          </div>
          <div class="field">
            <label for="kingschat">KingsChat</label>
            <input id="kingschat" name="kingschat" type="text" autocapitalize="off" autocorrect="off" maxlength="80" placeholder="@username · optional" value="<?= e(old('kingschat')) ?>">
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
