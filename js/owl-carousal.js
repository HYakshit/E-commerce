$(document).ready(function () {
  $(".owl-carousel").owlCarousel({
    loop: true,
    margin: 10,
    nav: false, // Disable navigation arrows
    autoplay: true, // Enable autoplay
    autoplayTimeout: 2000, // 5000 ms = 5 seconds per slide
    autoplayHoverPause: true, // Pause on hover
    responsive: {
      0: { items: 1 },
      600: { items: 2 },
      1000: { items: 5 },
    },
  });
});
