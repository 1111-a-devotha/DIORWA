<?php
require_once 'php/config.php';
$pageTitle = 'Find My Match'; $active = 'match.php';
$metaDesc = 'Tell us your budget, city and priorities and DIORWA ranks the best matching properties for you.';
$PRIOS = ['verified' => 'Verified title', 'below_market' => 'Good price for the area', 'security' => 'Security', 'parking' => 'Parking', 'garden' => 'Garden or outdoor space', 'big_space' => 'More space than usual', 'power_water' => 'Backup power or water'];
$ran = isset($_GET['go']);
$in = [
    'purpose' => ($_GET['purpose'] ?? 'sale') === 'rent' ? 'rent' : 'sale',
    'type' => isset(TYPES[$_GET['type'] ?? '']) ? $_GET['type'] : '',
    'city' => isset(CITY_COORDS[$_GET['city'] ?? '']) ? $_GET['city'] : '',
    'budget' => max(0, (float)($_GET['budget'] ?? 0)),
    'beds' => max(0, (int)($_GET['beds'] ?? 0)),
    'prio' => array_values(array_intersect((array)($_GET['prio'] ?? []), array_keys($PRIOS))),
];
$results = [];
if ($ran && $in['budget'] > 0) {
    $st = $pdo->prepare("SELECT * FROM properties WHERE status='approved' AND listing_type=? AND price<=?");
    $st->execute([$in['purpose'], $in['budget'] * 1.3]);
    $cands = $st->fetchAll();
    $areaSum = []; foreach ($cands as $c) { $areaSum[$c['type']][] = $c['area_m2']; }
    foreach ($cands as $p) {
        $score = 0; $why = [];
        // Budget (35): full marks inside budget, partial up to 10% over, small credit up to 30% over.
        if ($p['price'] <= $in['budget']) { $score += 35; $why[] = 'Within your budget'; }
        elseif ($p['price'] <= $in['budget'] * 1.1) { $score += 22; $why[] = 'Up to 10% over budget'; }
        else { $score += 8; $why[] = 'Stretches your budget'; }
        // Location (20), type (15), bedrooms (10)
        if ($in['city'] === '' || $p['city'] === $in['city']) { $score += 20; if ($in['city']) { $why[] = 'In ' . $p['city']; } }
        if ($in['type'] === '' || $p['type'] === $in['type']) { $score += 15; if ($in['type']) { $why[] = 'Matches the property type'; } }
        if ($p['bedrooms'] === null || $p['bedrooms'] >= $in['beds']) { $score += 10; if ($in['beds'] && $p['bedrooms'] !== null) { $why[] = $p['bedrooms'] . ' bedrooms'; } }
        // Priorities (20 shared equally)
        if ($in['prio']) {
            $share = 20 / count($in['prio']);
            $am = array_map('strtolower', array_map('trim', explode(',', (string)$p['amenities'])));
            $deal = deal_info($pdo, $p);
            $avg = $areaSum[$p['type']] ? array_sum($areaSum[$p['type']]) / count($areaSum[$p['type']]) : 0;
            $hit = [
                'verified' => (bool)$p['title_verified'], 'below_market' => $deal && $deal['class'] === 'good',
                'security' => in_array('security', $am, true), 'parking' => in_array('parking', $am, true),
                'garden' => in_array('garden', $am, true) || $p['type'] === 'land',
                'big_space' => $avg && $p['area_m2'] > $avg, 'power_water' => in_array('backup power', $am, true) || in_array('water tank', $am, true),
            ];
            foreach ($in['prio'] as $k) { if ($hit[$k]) { $score += $share; $why[] = $PRIOS[$k]; } }
        }
        $p['score'] = (int)round($score); $p['why'] = $why; $results[] = $p;
    }
    usort($results, fn($a, $b) => $b['score'] <=> $a['score'] ?: $a['price'] <=> $b['price']);
    $results = array_slice($results, 0, 6);
}
$favs = user_favorites($pdo);
include 'php/partials/header.php';
?>
<section class="page-hero short" style="--hero:url('images/h7.jpg')"><div><h1>Find my match</h1><p>Tell us what matters. We rank listings against your answers and show why each one fits.</p></div></section>
<section class="match-wrap">
  <form method="get" class="match-form">
    <input type="hidden" name="go" value="1">
    <fieldset><legend>I want to</legend>
      <label class="pill"><input type="radio" name="purpose" value="sale" <?= $in['purpose'] === 'sale' ? 'checked' : '' ?>> Buy</label>
      <label class="pill"><input type="radio" name="purpose" value="rent" <?= $in['purpose'] === 'rent' ? 'checked' : '' ?>> Rent</label>
    </fieldset>
    <div class="grid-2">
      <label>Maximum budget (USD<?= $in['purpose'] === 'rent' ? ' per month' : '' ?>)<input type="number" name="budget" min="1" required value="<?= $in['budget'] ?: '' ?>" placeholder="e.g. 150000 or 800 for rent"></label>
      <label>City<select name="city"><option value="">Anywhere</option><?php foreach (array_keys(CITY_COORDS) as $c): ?><option <?= $in['city'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
      <label>Property type<select name="type"><option value="">Any</option><?php foreach (TYPES as $k => $v): ?><option value="<?= $k ?>" <?= $in['type'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
      <label>Bedrooms (minimum)<select name="beds"><?php foreach ([0 => 'Any', 1 => '1+', 2 => '2+', 3 => '3+', 4 => '4+'] as $k => $v): ?><option value="<?= $k ?>" <?= $in['beds'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
    </div>
    <fieldset><legend>What matters most? Pick any</legend>
      <?php foreach ($PRIOS as $k => $v): ?><label class="pill"><input type="checkbox" name="prio[]" value="<?= $k ?>" <?= in_array($k, $in['prio'], true) ? 'checked' : '' ?>> <?= e($v) ?></label><?php endforeach; ?>
    </fieldset>
    <button class="btn" type="submit"><i class="fas fa-wand-magic-sparkles"></i> Show my matches</button>
  </form>

  <?php if ($ran): ?>
    <h2 class="center">Your matches</h2>
    <?php if (!$results): ?>
      <div class="empty"><i class="fas fa-magnifying-glass"></i><h3>Nothing within 30% of that budget</h3><p>Raise the budget or choose "Anywhere" and "Any" to see more.</p></div>
    <?php else: ?>
      <div class="match-list">
        <?php foreach ($results as $p): ?>
          <div class="match-item">
            <div class="match-score" style="--s:<?= $p['score'] ?>"><b><?= $p['score'] ?>%</b><span>match</span></div>
            <div class="match-card"><?php $noDeal = true; include 'php/partials/card.php'; ?>
              <ul class="chips small"><?php foreach ($p['why'] as $w): ?><li><i class="fas fa-check"></i> <?= e($w) ?></li><?php endforeach; ?></ul>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="fine center">Scores use simple, visible rules: budget 35, location 20, type 15, bedrooms 10 and your priorities 20 points.</p>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php include 'php/partials/footer.php'; ?>
