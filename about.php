<?php
require_once 'php/config.php';
$pageTitle = 'About Us'; $active = 'about.php';
include 'php/partials/header.php';
?>
<section class="page-hero" style="--hero:url('images/h10.jpg')"><div><h1>About DIORWA RealEstate</h1><p>Your trusted partner in finding homes and land across Rwanda.</p></div></section>
<section class="about-section">
  <div class="about-image"><img src="images/h10.jpg" alt="A DIORWA home" loading="lazy"></div>
  <div class="about-content">
    <h2>Who We Are</h2>
    <p>DIORWA RealEstate is a modern property platform that connects buyers, renters and property owners in one trusted place. Our goal is to make finding a home or land simple, secure and convenient across Rwanda.</p>
    <p>We believe everyone deserves access to quality housing and transparent property information. Through our platform, you can browse listings, compare options, check prices against the market and connect with property owners.</p>
    <a href="properties.php" class="btn">Explore Properties</a>
  </div>
</section>
<section class="mission">
  <div class="mission-card"><i class="fas fa-bullseye"></i><h3>Our Mission</h3><p>To simplify buying and renting property through practical digital tools.</p></div>
  <div class="mission-card"><i class="fas fa-eye"></i><h3>Our Vision</h3><p>To become Rwanda's most trusted online real estate marketplace.</p></div>
  <div class="mission-card"><i class="fas fa-handshake"></i><h3>Our Values</h3><p>Integrity, trust, innovation, professionalism and customer satisfaction.</p></div>
</section>
<section class="why-us">
  <h2>Why Choose DIORWA RealEstate?</h2>
  <div class="why-container">
    <div class="why-box"><i class="fas fa-home"></i><h3>Reviewed listings</h3><p>Every listing is reviewed by our team before it goes live.</p></div>
    <div class="why-box"><i class="fas fa-file-shield"></i><h3>Title checks</h3><p>Listings with a parcel UPI can be verified and receive a badge.</p></div>
    <div class="why-box"><i class="fas fa-headset"></i><h3>Fast support</h3><p>Reach us by form, phone or WhatsApp and we reply quickly.</p></div>
    <div class="why-box"><i class="fas fa-shield-alt"></i><h3>Secure platform</h3><p>Passwords are hashed and every form is protected against tampering.</p></div>
  </div>
</section>
<?php include 'php/partials/footer.php'; ?>
