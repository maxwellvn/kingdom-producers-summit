<?php /** @var array $admins @var string $rootEmail @var string $flash */
use App\Core\Session;
$errors = \App\Core\Session::get('_errors', []);
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Access</p>
      <h1 class="adm-page__title">Admin <span class="adm-page__title-sub">users</span></h1>
    </div>
  </header>

  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status" style="border-color: rgba(46,160,67,.5)">
      <span><?= e($flash) ?></span>
    </div>
  <?php endif; ?>

  <form class="adm-filters" method="post" action="<?= url('/admin/admins') ?>" style="align-items:flex-start">
    <?= csrf_field() ?>
    <div class="adm-search" style="flex:1">
      <input type="email" name="email" value="<?= old('email') ?>" placeholder="New admin email" autocomplete="email" required>
      <?php if (!empty($errors['email'])): ?><p class="form__error"><?= e($errors['email']) ?></p><?php endif; ?>
    </div>
    <div class="adm-search" style="flex:1">
      <input type="password" name="password" placeholder="Password (min 10 characters)" autocomplete="new-password" required>
      <?php if (!empty($errors['password'])): ?><p class="form__error"><?= e($errors['password']) ?></p><?php endif; ?>
    </div>
    <button type="submit" class="adm-btn adm-btn--dark">Add admin</button>
  </form>

  <table class="adm-table">
    <thead>
      <tr><th>Email</th><th>Added</th><th></th></tr>
    </thead>
    <tbody>
      <tr>
        <td><strong><?= e($rootEmail) ?></strong> <span class="mono" style="opacity:.6">root · env</span></td>
        <td class="mono">—</td>
        <td></td>
      </tr>
      <?php foreach ($admins as $admin): ?>
        <tr>
          <td><strong><?= e($admin['email']) ?></strong></td>
          <td class="mono"><?= e(date('j M Y', (int) strtotime((string) $admin['created_at']))) ?></td>
          <td style="text-align:right">
            <?php if (mb_strtolower((string) $admin['email']) === mb_strtolower((string) Session::get('admin_email'))): ?>
              <span class="mono" style="opacity:.6">you</span>
            <?php else: ?>
              <form method="post" action="<?= url('/admin/admins/delete') ?>" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $admin['id'] ?>">
                <button type="submit" class="adm-btn adm-btn--solid" style="padding:.25rem .6rem;font-size:.75rem;color:#b4232b">Remove</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$admins): ?>
        <tr><td colspan="3" class="adm-empty mono">No extra admins yet — the root login above is the only access.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
