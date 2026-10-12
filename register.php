<?php
require_once 'php/config.php';
if (is_logged()) { redirect('dashboard.php'); }
$pageTitle = 'Register'; $active = '';
include 'php/partials/header.php';
?>
<section class="auth-page">
  <div class="auth-card">
    <div class="auth-side"><h1>Join DIORWA RealEstate</h1><p>Create an account to save properties, book viewings and list your own homes or land.</p><img src="images/h7.jpg" alt="" loading="lazy"></div>
    <div class="auth-form">
      <h2>Create account</h2>
      <form action="php/do_register.php" method="post" class="stack" id="registerForm">
        <?= csrf_field() ?>
        <div class="input-box"><i class="fas fa-user"></i><input type="text" name="fullname" placeholder="Full name" value="<?= old('fullname') ?>" required autocomplete="name"></div>
        <div class="input-box"><i class="fas fa-envelope"></i><input type="email" name="email" placeholder="Email address" value="<?= old('email') ?>" required autocomplete="email"></div>
        <div class="input-box"><i class="fas fa-phone"></i><input type="tel" name="phone" placeholder="Phone, e.g. +250 788 000 000" value="<?= old('phone') ?>" required autocomplete="tel"></div>
        <div class="input-box"><i class="fas fa-lock"></i><input type="password" name="password" id="pw" placeholder="Password (8+ characters, letters and numbers)" required minlength="8" autocomplete="new-password"></div>
        <div class="input-box"><i class="fas fa-lock"></i><input type="password" name="confirmPassword" id="pw2" placeholder="Confirm password" required autocomplete="new-password"></div>
        <p class="pw-hint" id="pwHint" aria-live="polite"></p>
        <fieldset class="inline-choice"><legend>I am joining as</legend>
          <label class="pill"><input type="radio" name="account_type" value="user" <?= old('account_type') !== 'agent' ? 'checked' : '' ?>> Buyer or renter</label>
          <label class="pill"><input type="radio" name="account_type" value="agent" <?= old('account_type') === 'agent' ? 'checked' : '' ?>> Owner or agent</label>
        </fieldset>
        <button class="btn btn-block" type="submit" name="register">Create account</button>
      </form>
      <p class="auth-alt">Already have an account? <a href="login.php">Login</a></p>
    </div>
  </div>
</section>
<?php clear_old(); include 'php/partials/footer.php'; ?>
