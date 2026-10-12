<?php
require_once 'php/config.php';
if (is_logged()) { redirect(is_admin() ? 'admin/index.php' : 'dashboard.php'); }
$pageTitle = 'Login'; $active = '';
$next = safe_next($_GET['next'] ?? '', '');
include 'php/partials/header.php';
?>
<section class="auth-page">
  <div class="auth-card">
    <div class="auth-side"><h1>Welcome back</h1><p>Log in to see your saved properties, viewing requests and listings.</p><img src="images/h7.jpg" alt="" loading="lazy"></div>
    <div class="auth-form">
      <h2>Login</h2>
      <form action="php/do_login.php" method="post" class="stack">
        <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="input-box"><i class="fas fa-envelope"></i><input type="email" name="email" placeholder="Email address" value="<?= old('email') ?>" required autocomplete="email"></div>
        <div class="input-box"><i class="fas fa-lock"></i><input type="password" name="password" placeholder="Password" required autocomplete="current-password"></div>
        <button class="btn btn-block" type="submit" name="login">Login</button>
      </form>
      <p class="auth-alt">New to DIORWA? <a href="register.php">Create an account</a></p>
    </div>
  </div>
</section>
<?php clear_old(); include 'php/partials/footer.php'; ?>
