<?php
require_once 'php/config.php';
require_once 'php/payments.php';
$vid = (int)($_GET['viewing'] ?? 0);
$st = $pdo->prepare('SELECT v.*, p.title, p.city FROM viewings v JOIN properties p ON p.id=v.property_id WHERE v.id=?');
$st->execute([$vid]); $v = $st->fetch();
if (!$v || !can_access_viewing($v) || !fee_enabled()) { http_response_code(404); $pageTitle = 'Not found'; include 'php/partials/header.php'; echo '<div class="empty big"><i class="fas fa-receipt"></i><h2>Nothing to pay here</h2><p><a class="btn" href="properties.php">Browse properties</a></p></div>'; include 'php/partials/footer.php'; exit; }

$paid = viewing_paid($pdo, $vid);
$payId = (int)($_GET['pay'] ?? 0);
$pay = null;
if ($payId) { $q = $pdo->prepare('SELECT * FROM payments WHERE id=? AND viewing_id=?'); $q->execute([$payId, $vid]); $pay = $q->fetch() ?: null; }
$pageTitle = 'Viewing fee'; $active = '';
$demo = PAYMENT_MODE === 'demo';
$fee = VIEWING_FEE_RWF;
$isBank = $pay && $pay['provider'] === 'bank';
$ref = bank_reference($vid);

// Page script: switch fields by method, and (mobile money only) poll for the result.
$extraFoot = '<script>(function(){
var radios=document.querySelectorAll("input[name=provider]");
function sync(){var m=document.querySelector("input[name=provider]:checked");if(!m)return;var bank=m.value==="bank";
document.querySelectorAll(".mm-field").forEach(function(e){e.hidden=bank;var i=e.querySelector("input");if(i)i.required=!bank;});
document.querySelectorAll(".bank-field").forEach(function(e){e.hidden=!bank;var i=e.querySelector("input");if(i)i.required=bank;});
var b=document.getElementById("payBtn");if(b)b.textContent=bank?"I have made the transfer":"Pay RWF ' . number_format($fee) . '";}
radios.forEach(function(r){r.addEventListener("change",sync)});sync();})();</script>';
if ($pay && $pay['status'] === 'pending' && !$paid && !$isBank) {
    $extraFoot .= '<script>(function(){var id=' . (int)$pay['id'] . ',base=document.body.dataset.base||"",csrf=document.body.dataset.csrf;
function show(s){if(s==="paid"||s==="failed"){location.href="pay.php?viewing=' . $vid . '&pay="+id;}}
function poll(){fetch(base+"php/pay_status.php?id="+id,{credentials:"same-origin"}).then(function(r){return r.json()}).then(function(j){if(j.ok)show(j.status)}).catch(function(){});}
setInterval(poll,4000);
document.querySelectorAll("[data-demo]").forEach(function(b){b.addEventListener("click",function(){
fetch(base+"php/pay_status.php",{method:"POST",credentials:"same-origin",body:new URLSearchParams({id:id,action:b.dataset.demo,csrf:csrf})}).then(function(r){return r.json()}).then(function(j){if(j.ok)show(j.status)});});});})();</script>';
}
include 'php/partials/header.php';

/* The bank details box is shown in the form and again while a transfer is being confirmed. */
function bank_box(string $ref, int $fee, bool $demo): void { ?>
  <dl class="bank-box">
    <div><dt>Bank</dt><dd><?= e(BANK_NAME) ?><?= BANK_BRANCH ? ', ' . e(BANK_BRANCH) : '' ?></dd></div>
    <div><dt>Account name</dt><dd><?= e(BANK_ACCOUNT_NAME) ?></dd></div>
    <div><dt>Account number</dt><dd><?= e(BANK_ACCOUNT_NUMBER) ?></dd></div>
    <div><dt>Amount</dt><dd>RWF <?= number_format($fee) ?></dd></div>
    <div><dt>Write this as the payment reference</dt><dd><b><?= e($ref) ?></b></dd></div>
  </dl>
  <?php if ($demo): ?><p class="fine">Demo mode: these are sample bank details. Do not send money to them.</p><?php endif;
}
?>
<section class="form-wrap">
  <div class="form-card pay-card">
    <h1>Viewing fee</h1>
    <p class="fine">For <a href="property.php?id=<?= (int)$v['property_id'] ?>"><?= e($v['title']) ?></a>, <?= e($v['city']) ?> &middot; <?= e(date('D, j M Y', strtotime($v['preferred_date']))) ?>, <?= e($v['preferred_time']) ?></p>
    <div class="pay-amount"><strong>RWF <?= number_format($fee) ?></strong><span>about $<?= number_format($fee / USD_TO_RWF, 2) ?></span></div>

    <?php if ($paid || ($pay && $pay['status'] === 'paid')): ?>
      <div class="pay-state ok"><i class="fas fa-circle-check"></i><div><b>Payment received</b><p>Your slot is secured. We will confirm the time by phone.</p></div></div>
      <p><a class="btn" href="dashboard.php">Go to dashboard</a> <a class="link-btn" href="properties.php">Keep browsing</a></p>

    <?php elseif ($isBank && $pay['status'] === 'pending'): ?>
      <div class="pay-state wait"><i class="fas fa-building-columns"></i><div><b>Waiting for bank confirmation</b>
        <p>We received your transfer reference <b><?= e($pay['bank_ref']) ?></b>. Once the money shows in our account we mark it as paid and secure your slot. This usually takes one working day.</p></div></div>
      <?php bank_box($ref, $fee, $demo); ?>
      <p><a class="btn" href="dashboard.php#viewings">Go to dashboard</a></p>

    <?php elseif ($pay && $pay['status'] === 'pending'): ?>
      <div class="pay-state wait"><i class="fas fa-mobile-screen-button"></i><div><b>Check your phone</b>
        <p>We sent a request for RWF <?= number_format($fee) ?> to <?= e(substr($pay['phone'], 0, 6)) ?>***<?= e(substr($pay['phone'], -2)) ?>. Enter your <?= $pay['provider'] === 'mtn' ? 'MTN MoMo' : 'Airtel Money' ?> PIN to approve. This page updates by itself.</p></div></div>
      <?php if ($demo): ?>
        <div class="demo-box"><b>Demo mode: no real money moves.</b><p>Play the customer to see both outcomes.</p>
          <button type="button" class="btn btn-sm" data-demo="approve">Approve payment</button> <button type="button" class="btn btn-sm btn-dark" data-demo="decline">Decline</button></div>
      <?php endif; ?>

    <?php else: ?>
      <?php if ($pay && $pay['status'] === 'failed'): ?><div class="pay-state bad"><i class="fas fa-circle-xmark"></i><div><b>Payment was not completed</b><p>Nothing was charged. You can try again.</p></div></div><?php endif; ?>
      <form action="php/pay_action.php" method="post" class="stack">
        <?= csrf_field() ?><input type="hidden" name="viewing_id" value="<?= $vid ?>">
        <fieldset><legend>Choose how to pay</legend>
          <label class="pill"><input type="radio" name="provider" value="mtn" checked> MTN MoMo</label>
          <label class="pill"><input type="radio" name="provider" value="airtel"> Airtel Money</label>
          <?php if (bank_enabled()): ?><label class="pill"><input type="radio" name="provider" value="bank"> Bank transfer</label><?php endif; ?>
        </fieldset>
        <label class="mm-field">Mobile Money number<input type="tel" name="phone" value="<?= e($v['phone']) ?>" required placeholder="078 000 0000"></label>
        <?php if (bank_enabled()): ?>
        <div class="bank-field" hidden>
          <?php bank_box($ref, $fee, $demo); ?>
          <label>Your bank's transfer reference or receipt number<input type="text" name="bank_ref" maxlength="60" placeholder="e.g. the transaction ID on your receipt"></label>
        </div>
        <?php endif; ?>
        <button class="btn" type="submit" id="payBtn">Pay RWF <?= number_format($fee) ?></button>
        <?php if ($demo): ?><p class="fine">Demo mode is on. Switch it off in php/config.php to take real payments.</p><?php endif; ?>
      </form>
      <p class="fine">Paying is optional and secures your viewing slot. <a href="property.php?id=<?= (int)$v['property_id'] ?>">Skip for now</a></p>
    <?php endif; ?>
  </div>
</section>
<?php include 'php/partials/footer.php'; ?>
