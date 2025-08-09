<?php
if (!defined('ALLOWED_ACCESS')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}
?>
<link rel="stylesheet" href="assets/css/footer.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<footer>
  <div class="footer-container">
    <!-- Logo -->
    <div class="footer-logo">
      <a href=""><img src="img/logo.png" alt="Sahtout Server Logo" class="footer-logo-img"></a> 
    </div>

    <!-- Copyright -->
    <div class="footer-center">
      <p>© <?php echo date('Y'); ?> Sahtout Server by Blody. All rights reserved.</p>
    </div>

    <!-- Socials -->
    <div class="footer-socials">
      <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
      <a href="https://www.youtube.com/@Blodyone" target="_blank"><i class="fab fa-youtube"></i></a>
      <a href="https://discord.gg" target="_blank"><i class="fab fa-discord"></i></a>
      <a href="https://twitch.tv" target="_blank"><i class="fab fa-twitch"></i></a>
      <a href="https://kick.com" target="_blank">
        <img src="img/icons/kick-logo.svg" alt="Kick" class="kick-icon">
      </a>
      <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
      <a href="https://github.com" target="_blank"><i class="fab fa-github"></i></a>
      <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin-in"></i></a>
    </div>
  </div>

  <!-- Back to Top Button -->
  <button id="backToTop" title="Back to Top">
    <i class="fas fa-arrow-up"></i>
  </button>
</footer>

<!-- Back to Top Script -->
<script>
  const backToTop = document.getElementById("backToTop");

  window.addEventListener("scroll", () => {
    backToTop.style.opacity = window.scrollY > 300 ? "1" : "0";
    backToTop.style.pointerEvents = window.scrollY > 300 ? "auto" : "none";
    backToTop.style.transform = window.scrollY > 300 ? "translateY(0)" : "translateY(20px)";
  });

  backToTop.addEventListener("click", () => {
    backToTop.style.transform = "scale(0.9)";
    setTimeout(() => {
      backToTop.style.transform = "scale(1)";
    }, 100);
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
</script>