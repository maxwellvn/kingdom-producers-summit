<?php /** @var array $sponsorships @var int $sponsorTotal */ ?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Support</p>
      <h1 class="adm-page__title">Sponsorships <span class="adm-page__title-sub">£<?= number_format($sponsorTotal / 100, 2) ?> confirmed</span></h1>
    </div>
    <a class="adm-btn" href="<?= url('/sponsor') ?>" target="_blank" rel="noopener">Open the sponsor page <?= ph('arrow-square-out') ?></a>
  </header>
  <p class="adm-muted" style="margin:0 0 1.2rem">£<?= number_format($sponsorTotal / 100, 2) ?> confirmed. Card gifts confirm themselves; Espees and Revolut gifts wait here until you have seen the money.</p>
  <?php if (!$sponsorships): ?>
    <p class="adm-muted">No sponsorships yet. Share <a href="<?= url('/sponsor') ?>"><?= e(rtrim(site_url(), '/')) ?>/sponsor</a>.</p>
  <?php else: ?>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>When</th><th>Name</th><th>Email</th><th>Amount</th><th>Method</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($sponsorships as $s): ?>
        <tr>
          <td class="mono"><?= e(date('j M, H:i', strtotime($s['created_at']))) ?></td>
          <td><?= e($s['name']) ?></td>
          <td><a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></td>
          <td class="mono">£<?= number_format($s['amount_pence'] / 100, 2) ?></td>
          <td><?= e(ucfirst($s['method'])) ?></td>
          <td><span class="adm-pill adm-pill--<?= e($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span></td>
          <td>
            <?php if ($s['status'] !== 'paid'): ?>
              <form method="post" action="<?= url('/admin/sponsorships/confirm') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                <button type="submit" class="adm-btn adm-btn--dark" style="padding:.25rem .6rem;font-size:.75rem">Confirm received</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
