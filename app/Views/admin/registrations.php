<?php /** @var array $result @var string $participation @var string $search */
$pathLabel = ['onsite' => 'Onsite', 'online' => 'Online', 'initiative' => 'Initiative'];
$support = $support ?? '';
$base = $base ?? '/admin/registrations';
$isInitiative = $participation === 'initiative';
$qs = static fn (array $extra) => url($base) . '?' . http_build_query(array_filter(array_merge(['type' => $participation, 'q' => $search, 'support' => $support], $extra), static fn ($v) => $v !== '' && $v !== null));
?>
<section class="adm-page">
  <header class="adm-page__head">
    <div>
      <p class="eyebrow"><span class="eyebrow__dot"></span>People</p>
      <h1 class="adm-page__title"><?= $isInitiative ? 'Initiative' : 'Registrations' ?> <span class="adm-page__title-sub"><?= number_format($result['total']) ?> record<?= $result['total'] === 1 ? '' : 's' ?></span></h1>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;justify-content:flex-end">
      <form method="get" action="<?= url('/admin/export.csv') ?>" style="display:flex;gap:.4rem;align-items:center">
        <select name="type" aria-label="Who to export" style="padding:.45rem .5rem;font-size:.75rem;border:1px solid rgba(0,0,0,.25);background:#fff">
          <option value="" <?= $participation === '' ? 'selected' : '' ?>>Everyone, summit and Initiative</option>
          <option value="onsite" <?= $participation === 'onsite' ? 'selected' : '' ?>>Onsite only</option>
          <option value="online" <?= $participation === 'online' ? 'selected' : '' ?>>Online only</option>
          <option value="initiative" <?= $participation === 'initiative' ? 'selected' : '' ?>>Initiative only</option>
        </select>
        <button type="submit" class="adm-btn"><?= ph('download-simple') ?> Export CSV</button>
      </form>
    </div>
  </header>

  <?php $flash = (string) \App\Core\Session::get('admin_flash', ''); ?>
  <?php if ($flash !== ''): ?>
    <div class="form__alert" role="status" style="border-color: rgba(46,160,67,.5);margin-bottom:1.2rem"><span><?= e($flash) ?></span></div>
  <?php endif; ?>


  <form class="adm-filters" method="get" action="<?= url($base) ?>">
    <?php if (!$isInitiative): ?>
    <div class="adm-tabs" role="tablist">
      <?php foreach (['' => 'All summit places', 'onsite' => 'Onsite', 'online' => 'Online'] as $val => $label): ?>
        <a class="adm-tab <?= $participation === $val ? 'is-active' : '' ?>" href="<?= $qs(['type' => $val, 'page' => null]) ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
    <div class="adm-tabs adm-tabs--support" role="tablist" aria-label="Support">
      <?php foreach (['' => 'Everyone', 'contributed' => ph('star') . ' Contributed', 'legacy' => ph('flag') . ' Paid before the change'] as $val => $label): ?>
        <a class="adm-tab <?= $support === $val ? 'is-active' : '' ?> <?= $val === 'legacy' ? 'adm-tab--legacy' : '' ?>" href="<?= $qs(['support' => $val, 'page' => null]) ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="type" value="<?= e($participation) ?>">
    <?php endif; ?>
    <div class="adm-search">
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name, email, reference, country" aria-label="Search">
      <button type="submit" class="adm-btn adm-btn--ghost">Search</button>
    </div>
  </form>

  <?php if (!$result['rows']): ?>
    <?php if ($search !== ''): ?>
      <div class="adm-empty"><?= ph('magnifying-glass') ?><strong>Nothing matches</strong>Try a shorter search<?= $isInitiative ? '' : ' or another tab' ?>.</div>
    <?php else: ?>
      <div class="adm-empty"><?= ph($isInitiative ? 'plant' : 'users-three') ?><strong><?= $isInitiative ? 'No Initiative members yet' : 'No registrations yet' ?></strong><?= $isInitiative ? 'People who join the Initiative show here, whichever edition they joined in.' : 'New sign-ups show here the moment they register.' ?></div>
    <?php endif; ?>
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
                    $legacy && $payState === 'paid'    => ['legacy', 'Paid before the change · ' . paid_amount($r) . $method],
                    $legacy && $payState === 'claimed' => ['legacy', 'Claimed before the change' . $method],
                    $payState === 'paid'               => ['paid', 'Contributed · ' . paid_amount($r) . $method],
                    $payState === 'claimed'            => ['claimed', 'Contribution claimed' . $method],
                    $payState === 'none'               => ['initiative', '—'],
                    default                            => ['none', 'No contribution'],
                };
              ?>
              <span class="adm-pill adm-pill--<?= e($pill) ?>"><?= ['legacy' => ph('flag'), 'paid' => ph('star'), 'claimed' => ph('star')][$pill] ?? '' ?> <?= e($label) ?></span>
              <?php if ($paidPath && in_array($payState, ['not_required', 'claimed'], true)): ?>
                <form method="post" action="<?= url('/admin/registrations/confirm-payment') ?>" style="margin-top:.4rem">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <button type="submit" class="adm-btn adm-btn--dark" style="padding:.25rem .6rem;font-size:.75rem"><?= $payState === 'claimed' ? 'Confirm' : 'Record' ?> <?= e(espees_price(price_pence((string) $r['participation']))) ?> contribution</button>
                </form>
              <?php endif; ?>
              <?php if ($r['status'] === 'confirmed'): ?>
              <form method="post" action="<?= url('/admin/registrations/resend') ?>" class="resend" style="margin-top:.4rem">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <input type="hidden" name="type" value="<?= e($participation) ?>"><input type="hidden" name="q" value="<?= e($search) ?>"><input type="hidden" name="page" value="<?= (int) $result['page'] ?>">
                <select name="what" aria-label="What to send" style="padding:.25rem .4rem;font-size:.75rem;border:1px solid rgba(0,0,0,.25);background:#fff">
                  <?php if ($r['participation'] === 'onsite'): ?><option value="pass">QR pass</option><?php endif; ?>
                  <option value="live">Live link</option>
                </select>
                <select name="channel" aria-label="How to send it" style="padding:.25rem .4rem;font-size:.75rem;border:1px solid rgba(0,0,0,.25);background:#fff">
                  <?php if (!empty($r['kingschat_username'])): ?><option value="both">Email + KingsChat</option><option value="kingschat">KingsChat</option><?php endif; ?>
                  <option value="email">Email</option>
                </select>
                <button type="submit" class="adm-btn" style="padding:.25rem .6rem;font-size:.75rem">Send</button>
              </form>
              <?php endif; ?>
              <form method="post" action="<?= url('/admin/registrations/delete') ?>" style="margin-top:.4rem" onsubmit="return confirm('Permanently delete <?= e($r['reference']) ?>? This cannot be undone.')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button type="submit" class="adm-btn adm-btn--solid" style="padding:.25rem .6rem;font-size:.75rem;color:#b4232b">Delete</button>
              </form>
            </td>
            <td><?php if ($r['checked_in_at']): ?><span class="adm-checkin mono">Checked in<br><?= e(date('j M, H:i', strtotime($r['checked_in_at']))) ?></span><?php else: ?>
              <form method="post" action="<?= url('/admin/check-in') ?>" style="margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($r['reference']) ?>">
                <input type="hidden" name="back" value="<?= e($base . '?' . http_build_query(array_filter(['type' => $participation, 'q' => $search, 'support' => $support, 'page' => $result['page'] ?? null]))) ?>">
                <button type="submit" class="adm-btn adm-btn--dark" style="padding:.25rem .6rem;font-size:.75rem" onclick="return confirm('Check in <?= e(addslashes($r['first_name'] . ' ' . $r['last_name'])) ?> now?')">Check in</button>
              </form>
            <?php endif; ?></td>
            <td class="mono adm-muted"><?= e(date('j M Y', strtotime($r['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <?php if ($result['pages'] > 1): ?>
      <nav class="adm-pager mono" aria-label="Pagination">
        <?php if ($result['page'] > 1): ?><a href="<?= $qs(['page' => $result['page'] - 1]) ?>"><?= ph('caret-left') ?> Prev</a><?php endif; ?>
        <span>Page <?= $result['page'] ?> of <?= $result['pages'] ?></span>
        <?php if ($result['page'] < $result['pages']): ?><a href="<?= $qs(['page' => $result['page'] + 1]) ?>">Next <?= ph('caret-right') ?></a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>


