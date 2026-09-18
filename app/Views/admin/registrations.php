<?php /** @var array $result @var string $participation @var string $search */
$pathLabel = ['onsite' => 'Onsite', 'online' => 'Online', 'initiative' => 'Initiative'];
$support = $support ?? '';
$qs = static fn (array $extra) => url('/admin/registrations') . '?' . http_build_query(array_filter(array_merge(['type' => $participation, 'q' => $search, 'support' => $support], $extra), static fn ($v) => $v !== '' && $v !== null));
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
    <div class="adm-tabs adm-tabs--support" role="tablist" aria-label="Support">
      <?php foreach (['' => 'Everyone', 'contributed' => '★ Contributed', 'legacy' => '⚑ Paid before the change'] as $val => $label): ?>
        <a class="adm-tab <?= $support === $val ? 'is-active' : '' ?> <?= $val === 'legacy' ? 'adm-tab--legacy' : '' ?>" href="<?= $qs(['support' => $val, 'page' => null]) ?>"><?= $label ?></a>
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
      <thead><tr><th>Reference</th><th>Name</th><th>Contact</th><th>KingsChat</th><th>Path</th><th>Field</th><th>Stage</th><th>Location</th><th>Support</th><th>Attendance</th><th>Registered</th></tr></thead>
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
                // Onsite and online may contribute; the initiative has nothing to give.
                $paidPath = is_paid_path((string) $r['participation']);
                $payState = $paidPath ? ($r['payment_status'] ?? 'not_required') : 'none';
                $legacy = $paidPath && \App\Models\Registration::isLegacyPayment($r);
                $method = !empty($r['payment_method']) ? ' · ' . $r['payment_method'] : '';
                [$pill, $label] = match (true) {
                    $legacy && $payState === 'paid'    => ['legacy', '⚑ Paid before the change · ' . paid_amount($r) . $method],
                    $legacy && $payState === 'claimed' => ['legacy', '⚑ Claimed before the change' . $method],
                    $payState === 'paid'               => ['paid', '★ Contributed · ' . paid_amount($r) . $method],
                    $payState === 'claimed'            => ['claimed', '★ Contribution claimed' . $method],
                    $payState === 'none'               => ['initiative', '—'],
                    default                            => ['none', 'No contribution'],
                };
              ?>
              <span class="adm-pill adm-pill--<?= e($pill) ?> mono"><?= e($label) ?></span>
              <?php if ($paidPath && in_array($payState, ['not_required', 'claimed'], true)): ?>
                <form method="post" action="<?= url('/admin/registrations/confirm-payment') ?>" style="margin-top:.4rem">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <button type="submit" class="adm-btn adm-btn--dark" style="padding:.25rem .6rem;font-size:.75rem"><?= $payState === 'claimed' ? 'Confirm' : 'Record' ?> <?= e(espees_price(price_pence((string) $r['participation']))) ?> contribution</button>
                </form>
              <?php endif; ?>
              <form method="post" action="<?= url('/admin/registrations/send-stream') ?>" style="margin-top:.4rem;display:flex;gap:.3rem;align-items:center">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <input type="hidden" name="type" value="<?= e($participation) ?>"><input type="hidden" name="q" value="<?= e($search) ?>"><input type="hidden" name="page" value="<?= (int) $result['page'] ?>">
                <select name="channel" style="padding:.25rem .4rem;font-size:.75rem;border:1px solid rgba(0,0,0,.25);background:#fff">
                  <option value="email">Email</option>
                  <?php if (!empty($r['kingschat_username'])): ?><option value="kingschat">KingsChat</option><option value="both">Both</option><?php endif; ?>
                </select>
                <button type="submit" class="adm-btn" style="padding:.25rem .6rem;font-size:.75rem"><?= $r['participation'] === 'online' ? 'Send stream link' : 'Send directions' ?></button>
              </form>
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
