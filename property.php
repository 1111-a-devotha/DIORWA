<?php
require_once 'php/config.php';
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT p.*, u.fullname AS owner FROM properties p LEFT JOIN users u ON u.id=p.user_id WHERE p.id=?');
$st->execute([$id]);
$p = $st->fetch();
$canSee = $p && ($p['status'] === 'approved' || (is_logged() && ((int)$p['user_id'] === (int)$_SESSION['uid'] || is_admin())));
if (!$canSee) { http_response_code(404); $pageTitle = 'Not found'; include 'php/partials/header.php'; echo '<div class="empty big"><i class="fas fa-house-circle-xmark"></i><h2>This property is not available</h2><p><a class="btn" href="properties.php">Browse properties</a></p></div>'; include 'php/partials/footer.php'; exit; }

if ($p['status'] === 'approved') { $pdo->prepare('UPDATE properties SET views=views+1 WHERE id=?')->execute([$id]); }
$deal = deal_info($pdo, $p);
$favs = user_favorites($pdo);
$isFav = in_array($id, $favs, true);
$amen = array_filter(array_map('trim', explode(',', (string)$p['amenities'])));
$sim = $pdo->prepare("SELECT * FROM properties WHERE status='approved' AND id<>? AND (city=? OR type=?) AND listing_type=? ORDER BY (city=?) DESC, ABS(price-?) ASC LIMIT 3");
$sim->execute([$id, $p['city'], $p['type'], $p['listing_type'], $p['city'], $p['price']]);
$similar = $sim->fetchAll();

$pageTitle = $p['title']; $active = 'properties.php';
$metaDesc = $p['title'] . ' in ' . $p['city'] . ' - ' . mb_substr((string)$p['description'], 0, 120);
$hasMap = $p['lat'] !== null && $p['lng'] !== null;
$extraHead = $hasMap ? '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">' : '';
$extraFoot = $hasMap ? '<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script><script>
(function(){var m=L.map("map",{scrollWheelZoom:false}).setView([' . (float)$p['lat'] . ',' . (float)$p['lng'] . '],13);
L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png",{maxZoom:19,attribution:"&copy; OpenStreetMap contributors"}).addTo(m);
L.circle([' . (float)$p['lat'] . ',' . (float)$p['lng'] . '],{radius:900,color:"#10284D",fillColor:"#D4AF37",fillOpacity:.25}).addTo(m);})();
</script>' : '';
include 'php/partials/header.php';
$waText = rawurlencode('Hello DIORWA, I am interested in "' . $p['title'] . '" in ' . $p['city'] . ' (ref #' . $id . ').');
$u = $_SESSION['uid'] ?? null; $me = ['name' => $_SESSION['name'] ?? '', 'email' => '', 'phone' => ''];
if ($u) { $q = $pdo->prepare('SELECT email,phone FROM users WHERE id=?'); $q->execute([$u]); $me = array_merge($me, $q->fetch() ?: []); }
?>
<?php if ($p['status'] !== 'approved'): ?><div class="flash info">Only you and admins can see this listing. Status: <?= e($p['status']) ?>.</div><?php endif; ?>
<section class="detail">
  <div class="detail-main">
    <div class="detail-photo">
      <span class="badge <?= $p['listing_type'] === 'rent' ? 'rent' : 'sale' ?>"><?= $p['listing_type'] === 'rent' ? 'For Rent' : 'For Sale' ?></span>
      <img src="<?= e(img_url($p['image'])) ?>" alt="<?= e($p['title']) ?>">
    </div>
    <div class="detail-head">
      <div>
        <h1><?= e($p['title']) ?></h1>
        <p class="location"><i class="fas fa-location-dot"></i> <?= e($p['city']) ?><?= $p['address'] ? ', ' . e($p['address']) : '' ?></p>
      </div>
      <div class="detail-price"><?= money((float)$p['price'], $p['listing_type']) ?></div>
    </div>
    <ul class="spec-row">
      <?php if ($p['bedrooms'] !== null): ?><li><i class="fas fa-bed"></i><b><?= (int)$p['bedrooms'] ?></b> Bedrooms</li><?php endif; ?>
      <?php if ($p['bathrooms'] !== null): ?><li><i class="fas fa-shower"></i><b><?= (int)$p['bathrooms'] ?></b> Bathrooms</li><?php endif; ?>
      <li><i class="fas fa-ruler-combined"></i><b><?= e(area_label((int)$p['area_m2'], $p['type'])) ?></b> Size</li>
      <li><i class="fas fa-tag"></i><b><?= e(TYPES[$p['type']]) ?></b> Type</li>
      <li><i class="fas fa-eye"></i><b><?= (int)$p['views'] ?></b> Views</li>
    </ul>

    <h2>About this property</h2>
    <p class="prose"><?= nl2br(e($p['description'] ?: 'No description yet.')) ?></p>
    <?php if ($amen): ?>
      <h2>Features</h2>
      <ul class="chips"><?php foreach ($amen as $a): ?><li><i class="fas fa-check"></i> <?= e($a) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>

    <h2>Trust check</h2>
    <div class="trust">
      <div class="trust-item <?= $p['title_verified'] ? 'ok' : '' ?>">
        <i class="fas <?= $p['title_verified'] ? 'fa-circle-check' : 'fa-circle-question' ?>"></i>
        <div><b><?= $p['title_verified'] ? 'Title verified by DIORWA' : 'Title not yet verified' ?></b>
        <p><?= $p['upi'] ? 'Parcel UPI: ' . e($p['upi']) . '. ' : 'No parcel UPI provided. ' ?>Always confirm ownership with the Rwanda Land Authority and a notary before you pay anything.</p></div>
      </div>
      <?php if ($deal): ?>
      <div class="trust-item <?= $deal['class'] === 'good' ? 'ok' : '' ?>">
        <i class="fas fa-scale-balanced"></i>
        <div><b><?= e($deal['label']) ?>: <?= $deal['diff'] > 0 ? '+' : '' ?><?= $deal['diff'] ?>% per m²</b>
        <p>Compared with <?= $deal['n'] ?> similar <?= e($p['type']) ?> listings <?= e($deal['scope']) ?> on DIORWA. It is a guide, not a valuation.</p></div>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($p['listing_type'] === 'sale'): ?>
    <h2>Mortgage calculator</h2>
    <div class="calc" id="mortgage" data-price="<?= (float)$p['price'] ?>">
      <label>Deposit <output id="depOut">20</output>%<input type="range" id="dep" min="0" max="80" value="20"></label>
      <label>Interest rate <output id="rateOut">12</output>% a year<input type="range" id="rate" min="4" max="20" step="0.5" value="12"></label>
      <label>Term <output id="yrOut">20</output> years<input type="range" id="yrs" min="5" max="25" value="20"></label>
      <div class="calc-result"><span>Estimated monthly payment</span><strong id="monthly">-</strong><small id="loanNote"></small></div>
      <p class="fine">Illustration only. Real rates, fees and eligibility come from your bank.</p>
    </div>
    <?php endif; ?>

    <?php if ($hasMap): ?><h2>Area</h2><div id="map" class="map" role="img" aria-label="Map of the area"></div><p class="fine">Circle shows the approximate area, not the exact address.</p><?php endif; ?>
  </div>

  <aside class="detail-side">
    <div class="side-card">
      <div class="side-actions">
        <button type="button" class="fav-btn inline <?= $isFav ? 'on' : '' ?>" data-id="<?= $id ?>" aria-pressed="<?= $isFav ? 'true' : 'false' ?>"><i class="fas fa-heart"></i> <span><?= $isFav ? 'Saved' : 'Save' ?></span></button>
        <button type="button" class="link-btn" id="shareBtn"><i class="fas fa-share-nodes"></i> Share</button>
      </div>
      <a class="btn btn-wa" target="_blank" rel="noopener" href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>?text=<?= $waText ?>"><i class="fab fa-whatsapp"></i> Ask on WhatsApp</a>

      <form action="php/inquiry.php" method="post" id="contact" class="stack">
        <h3>Send an inquiry</h3>
        <?= csrf_field() ?><input type="hidden" name="property_id" value="<?= $id ?>">
        <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="text" name="name" placeholder="Your name" value="<?= e($me['name']) ?>" required>
        <input type="email" name="email" placeholder="Email" value="<?= e($me['email']) ?>" required>
        <input type="tel" name="phone" placeholder="Phone (optional)" value="<?= e($me['phone']) ?>">
        <textarea name="message" rows="3" required>I am interested in "<?= e($p['title']) ?>". Is it still available?</textarea>
        <button class="btn" type="submit">Send inquiry</button>
      </form>

      <form action="php/viewing.php" method="post" id="viewing" class="stack">
        <h3>Book a viewing</h3>
        <?= csrf_field() ?><input type="hidden" name="property_id" value="<?= $id ?>">
        <input type="text" name="name" placeholder="Your name" value="<?= e($me['name']) ?>" required>
        <input type="tel" name="phone" placeholder="Phone" value="<?= e($me['phone']) ?>" required>
        <input type="date" name="preferred_date" min="<?= date('Y-m-d') ?>" required aria-label="Preferred date">
        <select name="preferred_time" required aria-label="Time slot"><option>Morning (8-12)</option><option>Afternoon (12-17)</option><option>Evening (17-19)</option></select>
        <button class="btn btn-dark" type="submit">Request viewing</button>
      </form>
      <?php if ($p['owner']): ?><p class="fine">Listed by <?= e($p['owner']) ?></p><?php endif; ?>
    </div>
  </aside>
</section>

<?php if ($similar): ?>
<section class="properties">
  <h2 class="center">Similar properties</h2>
  <div class="property-container"><?php foreach ($similar as $p) { include 'php/partials/card.php'; } ?></div>
</section>
<?php endif; ?>
<?php include 'php/partials/footer.php'; ?>
