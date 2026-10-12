<?php
require_once __DIR__ . '/../php/config.php';
require_admin();
$tabs = ['overview' => 'Overview', 'listings' => 'Listings', 'messages' => 'Messages', 'inquiries' => 'Inquiries', 'viewings' => 'Viewings', 'payments' => 'Payments', 'users' => 'Users'];
$tab = array_key_exists($_GET['tab'] ?? '', $tabs) ? $_GET['tab'] : 'overview';
$pageTitle = 'Admin'; $active = '';
$count = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
$badge = ['listings' => $count("SELECT COUNT(*) FROM properties WHERE status='pending'"), 'messages' => $count('SELECT COUNT(*) FROM contacts WHERE is_read=0'), 'inquiries' => $count("SELECT COUNT(*) FROM inquiries WHERE status='new'"), 'viewings' => $count("SELECT COUNT(*) FROM viewings WHERE status='pending'"), 'payments' => $count("SELECT COUNT(*) FROM payments WHERE provider='bank' AND status='pending'")];
require_once __DIR__ . '/../php/payments.php';
include __DIR__ . '/../php/partials/header.php';

/* A small form button that posts to actions.php */
function act(string $label, string $action, int $id, string $tab, string $cls = 'link-btn', string $confirm = ''): string {
    return '<form action="actions.php" method="post" class="inline"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field()
        . '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="action" value="' . $action . '"><input type="hidden" name="tab" value="' . $tab . '">'
        . '<button class="' . $cls . '" type="submit">' . e($label) . '</button></form>';
}
?>
<section class="dash">
  <div class="dash-head"><div><h1>Admin panel</h1><p>Review listings, answer messages and manage people.</p></div></div>
  <nav class="tabs" aria-label="Admin sections">
    <?php foreach ($tabs as $k => $label): ?><a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= e($label) ?><?php if (!empty($badge[$k])): ?><span class="count"><?= $badge[$k] ?></span><?php endif; ?></a><?php endforeach; ?>
  </nav>

<?php if ($tab === 'overview'):
    $s = $pdo->query("SELECT (SELECT COUNT(*) FROM properties WHERE status='approved') live, (SELECT COUNT(*) FROM properties WHERE status='pending') pending, (SELECT COUNT(*) FROM users) users, (SELECT COUNT(*) FROM inquiries) inq, (SELECT COUNT(*) FROM viewings) views_n, (SELECT COALESCE(SUM(views),0) FROM properties) page_views")->fetch();
    $top = $pdo->query("SELECT id,title,city,views FROM properties WHERE status='approved' ORDER BY views DESC LIMIT 5")->fetchAll(); ?>
  <div class="stat-cards">
    <div><strong><?= (int)$s['live'] ?></strong><span>Live listings</span></div><div><strong><?= (int)$s['pending'] ?></strong><span>Awaiting review</span></div>
    <div><strong><?= (int)$s['users'] ?></strong><span>Users</span></div><div><strong><?= (int)$s['inq'] ?></strong><span>Inquiries</span></div>
    <div><strong><?= (int)$s['views_n'] ?></strong><span>Viewing requests</span></div><div><strong><?= (int)$s['page_views'] ?></strong><span>Listing views</span></div>
  </div>
  <h2>Most viewed listings</h2>
  <div class="table-wrap"><table class="data"><thead><tr><th>Property</th><th>City</th><th>Views</th></tr></thead><tbody>
    <?php foreach ($top as $t): ?><tr><td><a href="../property.php?id=<?= (int)$t['id'] ?>"><?= e($t['title']) ?></a></td><td><?= e($t['city']) ?></td><td><?= (int)$t['views'] ?></td></tr><?php endforeach; ?>
  </tbody></table></div>

<?php elseif ($tab === 'listings'):
    $rows = $pdo->query("SELECT p.*, u.fullname owner FROM properties p LEFT JOIN users u ON u.id=p.user_id ORDER BY FIELD(p.status,'pending','approved','sold','rejected'), p.created_at DESC")->fetchAll(); ?>
  <div class="table-wrap"><table class="data"><thead><tr><th>Property</th><th>Owner</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($rows as $p): $i = (int)$p['id']; ?><tr>
    <td><a href="../property.php?id=<?= $i ?>"><?= e($p['title']) ?></a><br><small><?= e($p['city']) ?> &middot; <?= e($p['upi'] ?: 'no UPI') ?></small></td>
    <td><?= e($p['owner'] ?: 'DIORWA') ?></td><td><?= money((float)$p['price'], $p['listing_type']) ?></td>
    <td><span class="status <?= e($p['status']) ?>"><?= e($p['status']) ?></span><?= $p['title_verified'] ? ' <i class="fas fa-circle-check ok"></i>' : '' ?><?= $p['featured'] ? ' <i class="fas fa-star gold" title="Featured"></i>' : '' ?></td>
    <td class="row-actions">
      <?php if ($p['status'] !== 'approved') { echo act('Approve', 'approve', $i, 'listings'); } ?>
      <?php if ($p['status'] === 'pending') { echo act('Reject', 'reject', $i, 'listings', 'link-btn danger'); } ?>
      <?= $p['title_verified'] ? act('Unverify', 'unverify', $i, 'listings') : act('Verify title', 'verify', $i, 'listings') ?>
      <?= $p['featured'] ? act('Unfeature', 'unfeature', $i, 'listings') : act('Feature', 'feature', $i, 'listings') ?>
      <a class="link-btn" href="../add-property.php?id=<?= $i ?>">Edit</a>
      <?= act('Delete', 'delete', $i, 'listings', 'link-btn danger', 'Delete this listing permanently?') ?>
    </td></tr><?php endforeach; ?>
  </tbody></table></div>

<?php elseif ($tab === 'messages'):
    $rows = $pdo->query('SELECT * FROM contacts ORDER BY is_read ASC, created_at DESC LIMIT 100')->fetchAll(); ?>
  <?php if (!$rows): ?><div class="empty small"><p>No messages yet.</p></div><?php endif; ?>
  <?php foreach ($rows as $m): ?><article class="msg <?= $m['is_read'] ? '' : 'unread' ?>">
    <header><b><?= e($m['subject']) ?></b><span><?= e(date('j M Y, H:i', strtotime($m['created_at']))) ?></span></header>
    <p class="from"><?= e($m['name']) ?> &middot; <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></p>
    <p><?= nl2br(e($m['message'])) ?></p>
    <?php if (!$m['is_read']) { echo act('Mark as read', 'read', (int)$m['id'], 'messages'); } ?>
  </article><?php endforeach; ?>

<?php elseif ($tab === 'inquiries'):
    $rows = $pdo->query('SELECT i.*, p.title FROM inquiries i JOIN properties p ON p.id=i.property_id ORDER BY i.status ASC, i.created_at DESC LIMIT 100')->fetchAll(); ?>
  <?php if (!$rows): ?><div class="empty small"><p>No inquiries yet.</p></div><?php else: ?>
  <div class="table-wrap"><table class="data"><thead><tr><th>Property</th><th>From</th><th>Message</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?= e($r['title']) ?></td><td><?= e($r['name']) ?><br><small><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?= $r['phone'] ? ' &middot; ' . e($r['phone']) : '' ?></small></td><td><?= e(mb_strimwidth($r['message'], 0, 160, '...')) ?></td><td><span class="status <?= $r['status'] === 'new' ? 'pending' : 'confirmed' ?>"><?= e($r['status']) ?></span></td><td><?= $r['status'] === 'new' ? act('Mark replied', 'replied', (int)$r['id'], 'inquiries') : '' ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>

<?php elseif ($tab === 'viewings'):
    $rows = $pdo->query('SELECT v.*, p.title FROM viewings v JOIN properties p ON p.id=v.property_id ORDER BY FIELD(v.status,"pending","confirmed","cancelled"), v.preferred_date ASC LIMIT 100')->fetchAll(); ?>
  <?php if (!$rows): ?><div class="empty small"><p>No viewing requests yet.</p></div><?php else: ?>
  <div class="table-wrap"><table class="data"><thead><tr><th>Property</th><th>Visitor</th><th>When</th><th>Status</th><th>Fee</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $v): ?><tr><td><?= e($v['title']) ?></td><td><?= e($v['name']) ?><br><small><a href="tel:<?= e($v['phone']) ?>"><?= e($v['phone']) ?></a></small></td><td><?= e(date('D, j M Y', strtotime($v['preferred_date']))) ?><br><small><?= e($v['preferred_time']) ?></small></td><td><span class="status <?= e($v['status']) ?>"><?= e($v['status']) ?></span></td>
    <td><?= !fee_enabled() ? '-' : (viewing_paid($pdo, (int)$v['id']) ? '<span class="status confirmed">paid</span>' : '<span class="status pending">unpaid</span>') ?></td>
    <td class="row-actions"><?php if ($v['status'] === 'pending') { echo act('Confirm', 'confirm', (int)$v['id'], 'viewings'); } if ($v['status'] !== 'cancelled') { echo act('Cancel', 'cancel', (int)$v['id'], 'viewings', 'link-btn danger'); } ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>

<?php elseif ($tab === 'payments'):
    $rows = $pdo->query('SELECT pay.*, p.title FROM payments pay JOIN properties p ON p.id=pay.property_id ORDER BY pay.created_at DESC LIMIT 100')->fetchAll();
    $total = (int)$pdo->query("SELECT COALESCE(SUM(amount_rwf),0) FROM payments WHERE status='paid'")->fetchColumn(); ?>
  <div class="stat-cards"><div><strong>RWF <?= number_format($total) ?></strong><span>Collected (mode: <?= e(PAYMENT_MODE) ?>)</span></div></div>
  <?php if (!$rows): ?><div class="empty small"><p>No payments yet.</p></div><?php else: ?>
  <div class="table-wrap"><table class="data"><thead><tr><th>Property</th><th>Method</th><th>Number / reference</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?= e($r['title']) ?></td><td><?= $r['provider'] === 'bank' ? 'Bank transfer' : e(strtoupper($r['provider'])) ?></td><td><?= $r['provider'] === 'bank' ? e($r['bank_ref']) . '<br><small>Ref: ' . e(bank_reference((int)$r['viewing_id'])) . '</small>' : e($r['phone']) ?></td><td>RWF <?= number_format((int)$r['amount_rwf']) ?></td><td><span class="status <?= $r['status'] === 'paid' ? 'confirmed' : ($r['status'] === 'failed' ? 'cancelled' : 'pending') ?>"><?= e($r['status']) ?></span></td><td><?= e(date('j M, H:i', strtotime($r['created_at']))) ?></td><td class="row-actions"><?php if ($r['provider'] === 'bank' && $r['status'] === 'pending') { echo act('Confirm received', 'pay_confirm', (int)$r['id'], 'payments') . act('Reject', 'pay_reject', (int)$r['id'], 'payments', 'link-btn danger'); } ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>

<?php elseif ($tab === 'users'):
    $rows = $pdo->query('SELECT id, fullname, email, phone, role, created_at FROM users ORDER BY created_at DESC LIMIT 200')->fetchAll(); ?>
  <div class="table-wrap"><table class="data"><thead><tr><th>Name</th><th>Contact</th><th>Role</th><th>Joined</th></tr></thead><tbody>
  <?php foreach ($rows as $u): ?><tr><td><?= e($u['fullname']) ?></td><td><?= e($u['email']) ?><br><small><?= e($u['phone']) ?></small></td>
    <td><?php if ((int)$u['id'] === (int)$_SESSION['uid']): ?><?= e($u['role']) ?> (you)<?php else: ?>
      <form action="actions.php" method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="action" value="role"><input type="hidden" name="tab" value="users">
      <select name="role" data-autosubmit aria-label="Role for <?= e($u['fullname']) ?>"><?php foreach (['user', 'agent', 'admin'] as $r): ?><option <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select></form><?php endif; ?></td>
    <td><?= e(date('j M Y', strtotime($u['created_at']))) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
<?php endif; ?>
</section>
<?php include __DIR__ . '/../php/partials/footer.php'; ?>
