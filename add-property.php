<?php
require_once 'php/config.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$ex = null;
if ($id) {
    $st = $pdo->prepare('SELECT * FROM properties WHERE id=?'); $st->execute([$id]); $ex = $st->fetch();
    if (!$ex || ((int)$ex['user_id'] !== (int)$_SESSION['uid'] && !is_admin())) { http_response_code(403); exit('Not allowed.'); }
}
// Prefer what the person just typed (after an error), then the saved listing.
$v = fn($k, $d = '') => isset($_SESSION['old'][$k]) ? e($_SESSION['old'][$k]) : e($ex[$k] ?? $d);
$curAmen = array_map('trim', explode(',', (string)($ex['amenities'] ?? '')));
$pageTitle = $ex ? 'Edit listing' : 'List a property'; $active = '';
include 'php/partials/header.php';
?>
<section class="page-hero short" style="--hero:url('images/h5.jpg')"><div><h1><?= $ex ? 'Edit your listing' : 'List your property' ?></h1><p>Free to list. Our team reviews every listing before it goes live.</p></div></section>
<section class="form-wrap">
  <form action="php/property_save.php" method="post" enctype="multipart/form-data" class="stack form-card">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <label>Title<input type="text" name="title" required maxlength="160" value="<?= $v('title') ?>" placeholder="e.g. 3-bedroom family house with garden"></label>
    <div class="grid-2">
      <label>Property type<select name="type" id="ptype" required><?php foreach (TYPES as $k => $t): ?><option value="<?= $k ?>" <?= $v('type') === $k ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></label>
      <label>For<select name="listing_type" required><option value="sale" <?= $v('listing_type') === 'sale' ? 'selected' : '' ?>>Sale</option><option value="rent" <?= $v('listing_type') === 'rent' ? 'selected' : '' ?>>Rent (per month)</option></select></label>
      <label>Price in USD<input type="number" name="price" min="1" step="any" required value="<?= $v('price') ?>"></label>
      <label>Size in m² <small>(1 hectare = 10,000)</small><input type="number" name="area_m2" min="1" required value="<?= $v('area_m2') ?>"></label>
      <label>City<select name="city" required><option value="">Choose city</option><?php foreach (array_keys(CITY_COORDS) as $c): ?><option <?= $v('city') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
      <label>Area or street <small>(optional)</small><input type="text" name="address" maxlength="200" value="<?= $v('address') ?>" placeholder="e.g. Kimironko, near the market"></label>
      <label class="bb">Bedrooms<input type="number" name="bedrooms" min="0" max="30" value="<?= $v('bedrooms') ?>"></label>
      <label class="bb">Bathrooms<input type="number" name="bathrooms" min="0" max="30" value="<?= $v('bathrooms') ?>"></label>
    </div>
    <label>Description<textarea name="description" rows="5" placeholder="Condition, neighbourhood, nearby services, anything a buyer should know"><?= $v('description') ?></textarea></label>
    <fieldset><legend>Features</legend><?php foreach (AMENITY_OPTIONS as $a): ?><label class="pill"><input type="checkbox" name="amenities[]" value="<?= e($a) ?>" <?= in_array($a, $curAmen, true) ? 'checked' : '' ?>> <?= e($a) ?></label><?php endforeach; ?></fieldset>
    <label>Parcel UPI <small>(optional, adds a title check by our team)</small><input type="text" name="upi" maxlength="30" value="<?= $v('upi') ?>" placeholder="1/03/04/01/1234" pattern="[0-9/]{5,30}"></label>
    <label>Main photo <small>(JPG, PNG or WebP, up to 3 MB)</small><input type="file" name="image" accept="image/jpeg,image/png,image/webp" <?= $ex ? '' : 'required' ?>></label>
    <?php if ($ex): ?><img class="thumb" src="<?= e(img_url($ex['image'])) ?>" alt="Current photo"><?php endif; ?>
    <button class="btn" type="submit"><?= $ex ? 'Save changes' : 'Submit for review' ?></button>
  </form>
</section>
<?php clear_old(); include 'php/partials/footer.php'; ?>
