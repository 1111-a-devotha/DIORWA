<?php
require_once 'php/config.php';
$pageTitle = 'Contact'; $active = 'contact.php';
include 'php/partials/header.php';
?>
<section class="page-hero short" style="--hero:url('images/h9.jpg')"><div><h1>Contact us</h1><p>Questions about a property or about listing yours? We reply within one working day.</p></div></section>
<section class="contact-info">
  <div class="info-card"><i class="fas fa-location-dot"></i><h3>Visit</h3><p>Kigali, Rwanda</p></div>
  <div class="info-card"><i class="fas fa-phone"></i><h3>Call</h3><p><a href="tel:<?= e(str_replace(' ', '', CONTACT_PHONE)) ?>"><?= e(CONTACT_PHONE) ?></a></p></div>
  <div class="info-card"><i class="fas fa-envelope"></i><h3>Email</h3><p><a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a></p></div>
  <div class="info-card"><i class="fab fa-whatsapp"></i><h3>WhatsApp</h3><p><a href="https://wa.me/<?= e(WHATSAPP_NUMBER) ?>" target="_blank" rel="noopener">Chat with us</a></p></div>
</section>
<section class="contact-section">
  <form class="contact-form stack" action="php/do_contact.php" method="post">
    <h2>Send a message</h2>
    <?= csrf_field() ?>
    <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
    <input type="text" name="name" placeholder="Your name" value="<?= old('name') ?>" required>
    <input type="email" name="email" placeholder="Email" value="<?= old('email') ?>" required>
    <input type="text" name="subject" placeholder="Subject" value="<?= old('subject') ?>" required>
    <textarea name="message" rows="6" placeholder="Your message" required><?= old('message') ?></textarea>
    <button class="btn" type="submit" name="send">Send message</button>
  </form>
  <div class="contact-map">
    <h2>Find us</h2>
    <iframe title="Map of Kigali" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=29.98%2C-1.99%2C30.14%2C-1.90&amp;layer=mapnik"></iframe>
  </div>
</section>
<?php clear_old(); include 'php/partials/footer.php'; ?>
