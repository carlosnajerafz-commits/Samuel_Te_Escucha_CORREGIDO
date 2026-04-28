document.addEventListener("DOMContentLoaded", () => {
  initCarousel();
});

function initCarousel() {
  const slides = document.querySelectorAll(".carousel__slide");
  const dotsWrap = document.getElementById("carouselDots");
  const prevBtn = document.getElementById("prevSlide");
  const nextBtn = document.getElementById("nextSlide");

  if (!slides.length) return;

  let current = 0;
  let timer = null;
  const interval = 5000;

  function renderDots() {
    if (!dotsWrap) return;
    dotsWrap.innerHTML = "";

    slides.forEach((_, index) => {
      const dot = document.createElement("button");
      dot.className = "carousel__dot" + (index === 0 ? " active" : "");
      dot.addEventListener("click", () => {
        goTo(index);
        restart();
      });
      dotsWrap.appendChild(dot);
    });
  }

  function update() {
    slides.forEach((slide, index) => {
      slide.classList.toggle("active", index === current);
    });

    if (dotsWrap) {
      [...dotsWrap.children].forEach((dot, index) => {
        dot.classList.toggle("active", index === current);
      });
    }
  }

  function goTo(index) {
    current = (index + slides.length) % slides.length;
    update();
  }

  function next() {
    goTo(current + 1);
  }

  function prev() {
    goTo(current - 1);
  }

  function start() {
    stop();
    timer = setInterval(next, interval);
  }

  function stop() {
    if (timer) clearInterval(timer);
  }

  function restart() {
    start();
  }

  if (prevBtn) prevBtn.addEventListener("click", () => { prev(); restart(); });
  if (nextBtn) nextBtn.addEventListener("click", () => { next(); restart(); });

  renderDots();
  update();
  start();
}