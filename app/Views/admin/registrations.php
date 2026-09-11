<?php /** @var array $result @var string $participation @var string $search */
$pathLabel = ['onsite' => 'Onsite', 'online' => 'Online', 'initiative' => 'Initiative'];
$qs = static fn (array $extra) => url('/admin/registrations') . '?' . http_build_query(array_filter(array_merge(['type' => $participation, 'q' => $search], $extra), static fn ($v) => $v !== '' && $v !== null));
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>Registrations</p>
      <h1 class="adm-page__title"><?= number_format($result['total']) ?> <span class="adm-page__title-sub">record<?= $result['total'] === 1 ? '' : 's' ?></span></h1>
    </div>
    <a class="adm-btn" href="<?= url('/admin/export.csv') ?>">Export CSV ↓</a>
  </header>

  <?php $flash = (string) \App\Core\Session::get('admin_flash', ''); ?>
  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status" style="border-color: rgba(46,160,67,.5);margin-bottom:1.2rem"><span><?= e($flash) ?></span></div>
  <?php endif; ?>

  <form class="adm-filters" method="get" action="<?= url('/admin/registrations') ?>">
    <div class="adm-tabs" role="tablist">
      <?php foreach (['' => 'All', 'onsite' => 'A · Onsite', 'online' => 'B · Online', 'initiative' => 'C · Initiative'] as $val => $label): ?>
        <a class="adm-tab <?= $participation === $val ? 'is-active' : '' ?>" href="<?= $qs(['type' => $val, 'page' => null]) ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="type" value="<?= e($participation) ?>">
    <div class="adm-search">
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name, email, reference, country" aria-label="Search">
      <button type="submit" class="adm-btn adm-btn--ghost">Search</button>
    </div>
  </form>

  <?php if (!$result['rows']): ?>
    <p class="adm-empty mono">Nothing matches.</p>
  <?php else: ?>
    <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Reference</th><th>Name</th><th>Contact</th><th>KingsChat</th><th>Path</th><th>Field</th><th>Stage</th><th>Location</th><th>Payment</th><th>Attendance</th><th>Registered</th></tr></thead>
      <tbody>
        <?php foreach ($result['rows'] as $r): ?>
          <tr>
            <td class="mono"><?= e($r['reference']) ?></td>
            <td><?= e(trim(($r['title'] ?? '') . ' ' . $r['first_name'] . ' ' . $r['last_name'])) ?></td>
            <td><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?php if ($r['phone']): ?><br><span class="adm-muted mono"><?= e($r['phone']) ?></span><?php endif; ?></td>
            <td><?php if ($r['kingschat_username']): ?><a class="mono" href="https://kingschat.online/user/<?= e($r['kingschat_username']) ?>" target="_blank" rel="noopener">@<?= e($r['kingschat_username']) ?></a><?php else: ?><span class="adm-muted">—</span><?php endif; ?></td>
            <td><span class="adm-pill adm-pill--<?= e($r['participation']) ?>"><?= e($pathLabel[$r['participation']] ?? $r['participation']) ?></span>
              <?php if (!empty($r['issued_by'])): ?><br><span class="adm-muted mono" style="font-size:.7rem">issued by <?= e($r['issued_by']) ?></span><?php endif; ?></td>
            <td><?= e(field_label($r)) ?></td>
            <td><?= e(ucfirst((string) ($r['producer_stage'] ?? ''))) ?></td>
            <td><?= e(implode(', ', array_filter([$r['city'], $r['country']]))) ?><?php if ($r['zone']): ?><br><span class="adm-muted"><?= e(implode(' · ', array_filter([$r['zone'], $r['group_name'], $r['church_name']]))) ?></span><?php endif; ?></td>
            <td>
              <?php
                // Onsite and online both pay; the initiative does not.
                $paidPath = is_paid_path((string) $r['participation']);
                $payState = $paidPath ? ($r['payment_status'] ?? 'unpaid') : 'not_required';
              ?>
              <span class="adm-pill adm-pill--<?= $payState === 'paid' ? 'paid' : ($payState === 'claimed' ? 'claimed' : ($payState === 'not_required' ? 'initiative' : 'onsite')) ?> mono"><?= e(ucfirst($payState)) ?><?= in_array($payState, ['claimed', 'paid'], true) && !empty($r['payment_method']) ? ' · ' . e($r['payment_method']) . ($payState === 'paid' ? ' · ' . e(paid_amount($r)) : '') : '' ?></span>
              <?php if ($paidPath && in_array($payState, ['unpaid', 'claimed'], true)): ?>
                <form method="post" action="<?= url('/admin/registrations/confirm-payment') ?>" style="margin-top:.4rem">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <button type="submit" class="adm-btn adm-btn--dark" style="padding:.25rem .6rem;font-size:.75rem">Confirm <?= e(espees_price(price_pence((string) $r['participation']))) ?> received</button>
                </form>
              <?php endif; ?>
              <?php if ($paidPath && $payState === 'unpaid'): ?>
                <form method="post" action="<?= url('/admin/registrations/resend-payment') ?>" style="margin-top:.4rem">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <button type="submit" class="adm-btn" style="padding:.25rem .6rem;font-size:.75rem">Resend payment email</button>
                </form>
                <?php if (!empty($r['payment_email_resent_at'])): ?><span class="adm-muted mono" style="display:block;margin-top:.3rem;font-size:.68rem">resent <?= e(date('d M H:i', strtotime((string) $r['payment_email_resent_at']))) ?></span><?php endif; ?>
                <?php if (!empty($r['payment_reminder_sent_at'])): ?><span class="adm-muted mono" style="display:block;margin-top:.2rem;font-size:.68rem">24h reminder sent <?= e(date('d M H:i', strtotime((string) $r['payment_reminder_sent_at']))) ?></span><?php endif; ?>
              <?php endif; ?>
              <?php if (!empty($r['released_at'])): ?><span class="adm-muted mono" style="display:block;margin-top:.3rem;font-size:.68rem;color:#b4232b">place released <?= e(date('d M H:i', strtotime((string) $r['released_at']))) ?></span><?php endif; ?>
              <form method="post" action="<?= url('/admin/registrations/delete') ?>" style="margin-top:.4rem" onsubmit="return confirm('Permanently delete <?= e($r['reference']) ?>? This cannot be undone.')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button type="submit" class="adm-btn adm-btn--solid" style="padding:.25rem .6rem;font-size:.75rem;color:#b4232b">Delete</button>
              </form>
            </td>
            <td><?php if ($r['checked_in_at']): ?><span class="adm-checkin mono">Checked in<br><?= e(date('j M, H:i', strtotime($r['checked_in_at']))) ?></span><?php else: ?><span class="adm-muted mono">Not arrived</span><?php endif; ?></td>
            <td class="mono adm-muted"><?= e(date('j M Y', strtotime($r['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <?php if ($result['pages'] > 1): ?>
      <nav class="adm-pager mono" aria-label="Pagination">
        <?php if ($result['page'] > 1): ?><a href="<?= $qs(['page' => $result['page'] - 1]) ?>">← Prev</a><?php endif; ?>
        <span>Page <?= $result['page'] ?> of <?= $result['pages'] ?></span>
        <?php if ($result['page'] < $result['pages']): ?><a href="<?= $qs(['page' => $result['page'] + 1]) ?>">Next →</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>
