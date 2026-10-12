<?php
require __DIR__ . '/../php/config.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('index.php'); }
csrf_check();
$id = (int)($_POST['id'] ?? 0);
$act = $_POST['action'] ?? '';
$tab = in_array($_POST['tab'] ?? '', ['overview', 'listings', 'messages', 'inquiries', 'viewings', 'payments', 'users'], true) ? $_POST['tab'] : 'overview';

$simple = [
    'approve'   => ["UPDATE properties SET status='approved' WHERE id=?", 'Listing approved and live.'],
    'reject'    => ["UPDATE properties SET status='rejected' WHERE id=?", 'Listing rejected.'],
    'verify'    => ["UPDATE properties SET title_verified=1 WHERE id=?", 'Title marked as verified.'],
    'unverify'  => ["UPDATE properties SET title_verified=0 WHERE id=?", 'Verification removed.'],
    'feature'   => ["UPDATE properties SET featured=1 WHERE id=?", 'Listing featured on the home page.'],
    'unfeature' => ["UPDATE properties SET featured=0 WHERE id=?", 'Listing no longer featured.'],
    'delete'    => ["DELETE FROM properties WHERE id=?", 'Listing deleted.'],
    'read'      => ["UPDATE contacts SET is_read=1 WHERE id=?", 'Marked as read.'],
    'replied'   => ["UPDATE inquiries SET status='replied' WHERE id=?", 'Marked as replied.'],
    'confirm'   => ["UPDATE viewings SET status='confirmed' WHERE id=?", 'Viewing confirmed.'],
    'pay_confirm' => ["UPDATE payments SET status='paid' WHERE id=? AND provider='bank' AND status='pending'", 'Bank transfer confirmed. The viewing fee is marked as paid.'],
    'pay_reject'  => ["UPDATE payments SET status='failed' WHERE id=? AND provider='bank' AND status='pending'", 'Bank transfer rejected.'],
    'cancel'    => ["UPDATE viewings SET status='cancelled' WHERE id=?", 'Viewing cancelled.'],
];
if (isset($simple[$act])) {
    if ($act === 'verify') {   // Only verify listings that carry a UPI, so the badge always means something was checked.
        $c = $pdo->prepare('SELECT upi FROM properties WHERE id=?'); $c->execute([$id]);
        if (!$c->fetchColumn()) { flash('error', 'Add the UPI to this listing before verifying its title.'); redirect('index.php?tab=' . $tab); }
    }
    $pdo->prepare($simple[$act][0])->execute([$id]);
    flash('success', $simple[$act][1]);
} elseif ($act === 'role') {
    $role = $_POST['role'] ?? '';
    if ($id === (int)$_SESSION['uid']) { flash('error', 'You cannot change your own role.'); }
    elseif (in_array($role, ['user', 'agent', 'admin'], true)) {
        $pdo->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role, $id]);
        flash('success', 'Role updated.');
    }
}
redirect('index.php?tab=' . $tab);
