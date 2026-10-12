<?php
require_once 'php/config.php';
require_login();
$uid = (int)$_SESSION['uid'];
$pageTitle = 'My dashboard'; $active = '';
$me = $pdo->prepare('SELECT * FROM users WHERE id=?'); $me->execute([$uid]); $me = $me->fetch();

$saved = $pdo->prepare("SELECT p.* FROM favorites f JOIN properties p ON p.id=f.property_id WHERE f.user_id=? AND p.status<>'rejected' ORDER BY f.created_at DESC");
$saved->execute([$uid]); $saved = $saved->fetchAll();
$mine = $pdo->prepare('SELECT * FROM properties WHERE user_id=? ORDER BY created_at DESC'); $mine->execute([$uid]); $mine = $mine->fetchAll();
$myView = $pdo->prepare('SELECT v.*, p.title FROM viewings v JOIN properties p ON p.id=v.property_id WHERE v.user_id=? ORDER BY v.preferred_date DESC'); $myView->execute([$uid]); $myView = $myView->fetchAll();
require_once 'php/payments.php';
$inq = $pdo->prepare('SELECT i.*, p.title FROM inquiries i JOIN properties p ON p.id=i.property_id WHERE p.user_id=? ORDER BY i.created_at DESC LIMIT 30'); $inq->execute([$uid]); $inq = $inq->fetchAll();
$vReq = $pdo->prepare('SELECT v.*, p.title FROM viewings v JOIN properties p ON p.id=v.property_id WHERE p.user_id=? ORDER BY v.preferred_date ASC LIMIT 30'); $vReq->execute([$uid]); $vReq = $vReq->fetchAll();
$favs = array_column($saved, 'id');
include 'php/partials/header.php';
?>
<section class="dash">
  <div class="dash-head">
    <div><h1>Hello, <?= e(explode(' ', $me['fullname'])[0]) ?></h1><p><?= e($me['email']) ?> &middot; <?= e(ucfirst($me['role'])) ?></p></div>
    <div class="dash-actions"><a class="btn" href="add-property.php"><i class="fas fa-plus"></i> List a property</a><?php if (is_admin()): ?><a class="btn btn-dark" href="admin/index.php">Admin panel</a><?php endif; ?></div>
  </div>

  <h2 id="saved">Saved properties (<?= count($saved) ?>)</h2>
  <?php if (!$saved): ?><div class="empty small"><p>Tap the heart on any listing to keep it here. <a href="properties.php">Browse properties</a></p></div>
  <?php else: ?><div class="property-container"><?php foreach ($saved as $p) { include 'php/partials/card.php'; } ?></div><?php endif; ?>

  <h2 id="viewings">My viewing requests</h2>
  <?php if (!$myView): ?><div class="empty small"><p>No viewing requests yet.</p></div><?php else: ?>
  <div class="table-wrap"><table class="data"><thead><tr><th>Property</th><th>Date</th><th>Time</th><th>Status</th><th>Fee</th></tr></thead><tbody>
    <?php foreach ($myView as $v): ?><tr><td><a href="property.php?id=<?= (int)$v['property_id'] ?>"><?= e($v['title']) ?></a></td><td><?= e(date('D, j M Y', strtotime($v['preferred_date']))) ?></td><td><?= e($v['preferred_time']) ?></td><td><span class="status <?= e($v['status']) ?>"><?= e($v['status']) ?></span></td><td><?php if (!fee_enabled()) { echo '-'; } elseif (viewing_paid($pdo, (int)$v['id'])) { echo '<span class="status confirmed">paid</span>'; } elseif (viewing_pending($pdo, (int)$v['id'])) { echo '<span class="status pending">awaiting bank confirmation</span>'; } else { echo '<a class="link-btn" href="pay.php?viewing=' . (int)$v['id'] . '">Pay viewing fee</a>'; } ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>

  <h2 id="listings">My listings (<?= count($mine) ?>)</h2>
  <?php if (!$mine): ?><div class="empty small"><p>You have not listed anything yet. <a href="add-property.php">Add your first property</a>.</p></div><?php else: ?>
  <div class="table-wrap"><table class="data"><thead><tr><th>Property</th><th>Price</th><th>Status</th><th>Views</th><th></th></tr></thead><tbody>
    <?php foreach ($mine as $p): ?><tr>
      <td><a href="property.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a><br><small><?= e($p['city']) ?></small></td>
      <td><?= money((float)$p['price'], $p['listing_type']) ?></td>
      <td><span class="status <?= e($p['status']) ?>"><?= e($p['status']) ?></span><?= $p['title_verified'] ? ' <i class="fas fa-circle-check ok" title="Title verified"></i>' : '' ?></td>
      <td><?= (int)$p['views'] ?></td>
      <td class="row-actions">
        <a class="link-btn" href="add-property.php?id=<?= (int)$p['id'] ?>">Edit</a>
        <?php foreach (['sold' => 'Mark sold', 'delete' => 'Delete'] as $act => $label): if ($act === 'sold' && $p['status'] === 'sold') { continue; } ?>
          <form action="php/property_save.php" method="post" class="inline" <?= $act === 'delete' ? 'data-confirm="Delete this listing permanently?"' : '' ?>><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="<?= $act ?>"><button class="link-btn <?= $act === 'delete' ? 'danger' : '' ?>" type="submit"><?= $label ?></button></form>
        <?php endforeach; ?>
      </td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>

  <?php if ($mine): ?>
  <h2 id="leads">Inquiries and viewing requests on my listings</h2>
  <?php if (!$inq && !$vReq): ?><div class="empty small"><p>Nothing yet. Leads from your listings appear here.</p></div><?php endif; ?>
  <?php if ($inq): ?><div class="table-wrap"><table class="data"><thead><tr><th>Property</th><th>From</th><th>Message</th><th>Received</th></tr></thead><tbody>
    <?php foreach ($inq as $i): ?><tr><td><?= e($i['title']) ?></td><td><?= e($i['name']) ?><br><small><a href="mailto:<?= e($i['email']) ?>"><?= e($i['email']) ?></a><?= $i['phone'] ? ' &middot; ' . e($i['phone']) : '' ?></small></td><td><?= e(mb_strimwidth($i['message'], 0, 140, '...')) ?></td><td><?= e(date('j M, H:i', strtotime($i['created_at']))) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
  <?php if ($vReq): ?><div class="table-wrap"><table class="data"><thead><tr><th>Property</th><th>Visitor</th><th>Wants to visit</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($vReq as $v): ?><tr><td><?= e($v['title']) ?></td><td><?= e($v['name']) ?><br><small><?= e($v['phone']) ?></small></td><td><?= e(date('D, j M', strtotime($v['preferred_date']))) ?>, <?= e($v['preferred_time']) ?></td><td><span class="status <?= e($v['status']) ?>"><?= e($v['status']) ?></span></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
  <?php endif; ?>

  <h2 id="settings">Account settings</h2>
  <div class="grid-2">
    <form action="php/account.php" method="post" class="stack side-card">
      <h3>Profile</h3><?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <input type="text" name="fullname" value="<?= e($me['fullname']) ?>" required aria-label="Full name">
      <input type="tel" name="phone" value="<?= e($me['phone']) ?>" required aria-label="Phone">
      <button class="btn" type="submit">Save profile</button>
    </form>
    <form action="php/account.php" method="post" class="stack side-card">
      <h3>Change password</h3><?= csrf_field() ?><input type="hidden" name="action" value="password">
      <input type="password" name="current_password" placeholder="Current password" required autocomplete="current-password">
      <input type="password" name="new_password" placeholder="New password" required minlength="8" autocomplete="new-password">
      <input type="password" name="confirm_password" placeholder="Confirm new password" required autocomplete="new-password">
      <button class="btn btn-dark" type="submit">Change password</button>
    </form>
  </div>
</section>
<?php include 'php/partials/footer.php'; ?>
