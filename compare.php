<?php
require_once 'php/config.php';
$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')))));
$ids = array_slice($ids, 0, 3);
$rows = [];
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT * FROM properties WHERE status='approved' AND id IN ($in)");
    $st->execute($ids);
    $byId = array_column($st->fetchAll(), null, 'id');
    foreach ($ids as $i) { if (isset($byId[$i])) { $rows[] = $byId[$i]; } }
}
$pageTitle = 'Compare properties'; $active = 'properties.php';
include 'php/partials/header.php';
$lines = [
    'Price' => fn($p) => money((float)$p['price'], $p['listing_type']),
    'Location' => fn($p) => e($p['city']),
    'Type' => fn($p) => e(TYPES[$p['type']]),
    'Size' => fn($p) => e(area_label((int)$p['area_m2'], $p['type'])),
    'Price per m²' => fn($p) => '<span class="price" data-usd="' . round($p['price'] / max(1, $p['area_m2']), 2) . '">$' . number_format($p['price'] / max(1, $p['area_m2']), 2) . '</span>',
    'Bedrooms' => fn($p) => $p['bedrooms'] ?? '-',
    'Bathrooms' => fn($p) => $p['bathrooms'] ?? '-',
    'Title verified' => fn($p) => $p['title_verified'] ? '<span class="yes">Yes</span>' : 'Not yet',
    'Features' => fn($p) => e(str_replace(',', ', ', (string)$p['amenities'])) ?: '-',
];
?>
<section class="page-hero short" style="--hero:url('images/h4.jpg')"><div><h1>Compare properties</h1><p>Side by side, up to three at a time.</p></div></section>
<section class="properties">
<?php if (count($rows) < 2): ?>
  <div class="empty"><i class="fas fa-table-columns"></i><h3>Pick at least two properties</h3><p>Tick "Compare" on any listing, then open the compare bar at the bottom of the screen.</p><a class="btn" href="properties.php">Browse properties</a></div>
<?php else: ?>
  <div class="table-wrap"><table class="compare">
    <thead><tr><th></th><?php foreach ($rows as $p): ?><th><a href="property.php?id=<?= (int)$p['id'] ?>"><img src="<?= e(img_url($p['image'])) ?>" alt=""><span><?= e($p['title']) ?></span></a></th><?php endforeach; ?></tr></thead>
    <tbody><?php foreach ($lines as $label => $fn): ?><tr><th scope="row"><?= e($label) ?></th><?php foreach ($rows as $p): ?><td><?= $fn($p) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
  </table></div>
<?php endif; ?>
</section>
<?php include 'php/partials/footer.php'; ?>
