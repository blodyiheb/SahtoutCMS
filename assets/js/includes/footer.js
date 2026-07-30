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