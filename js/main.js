document.addEventListener("DOMContentLoaded", function () {
  // Mobile navigation toggle
  const navToggle = document.getElementById("nav-toggle");
  const navMenu = document.getElementById("nav-menu");

  if (navToggle && navMenu) {
    navToggle.addEventListener("click", function () {
      navMenu.classList.toggle("active");
      navToggle.classList.toggle("active");
    });
  }

  // Dropdown menu toggle
  const dropdownToggles = document.querySelectorAll(".dropdown-toggle");

  dropdownToggles.forEach((toggle) => {
    toggle.addEventListener("click", function () {
      const dropdown = this.nextElementSibling;
      dropdown.classList.toggle("show");

      // Close other dropdowns
      dropdownToggles.forEach((otherToggle) => {
        if (otherToggle !== toggle) {
          otherToggle.nextElementSibling.classList.remove("show");
        }
      });
    });
  });

  // Close dropdowns when clicking outside
  document.addEventListener("click", function (event) {
    dropdownToggles.forEach((toggle) => {
      const dropdown = toggle.nextElementSibling;
      if (!toggle.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove("show");
      }
    });
  });

  // Product image gallery (if on product detail page)
  const mainImage = document.querySelector(".product-main-image img");
  const thumbnails = document.querySelectorAll(".product-thumbnails img");

  if (mainImage && thumbnails.length > 0) {
    thumbnails.forEach((thumbnail) => {
      thumbnail.addEventListener("click", function () {
        // Update main image src with thumbnail src
        mainImage.src = this.src;

        // Remove active class from all thumbnails
        thumbnails.forEach((t) => t.classList.remove("active"));

        // Add active class to clicked thumbnail
        this.classList.add("active");
      });
    });
  }

  // Initialize tooltips
  const tooltips = document.querySelectorAll("[data-tooltip]");

  tooltips.forEach((tooltip) => {
    tooltip.addEventListener("mouseenter", function () {
      const tooltipText = this.getAttribute("data-tooltip");
      const tooltipElement = document.createElement("div");
      tooltipElement.classList.add("tooltip");
      tooltipElement.textContent = tooltipText;

      document.body.appendChild(tooltipElement);

      const rect = this.getBoundingClientRect();
      tooltipElement.style.top =
        rect.top - tooltipElement.offsetHeight - 10 + "px";
      tooltipElement.style.left =
        rect.left + rect.width / 2 - tooltipElement.offsetWidth / 2 + "px";

      tooltipElement.classList.add("show");

      this.addEventListener("mouseleave", function () {
        tooltipElement.remove();
      });
    });
  });

  // Form validation
  const forms = document.querySelectorAll("form[data-validate]");

  forms.forEach((form) => {
    form.addEventListener("submit", function (event) {
      const inputs = this.querySelectorAll(
        'input:not([type="submit"]), select, textarea'
      );
      let isValid = true;

      inputs.forEach((input) => {
        // Required validation
        if (input.hasAttribute("required") && !input.value.trim()) {
          markInvalid(input, "This field is required");
          isValid = false;
        }
        // Email validation
        else if (
          input.type === "email" &&
          input.value.trim() &&
          !validateEmail(input.value)
        ) {
          markInvalid(input, "Please enter a valid email address");
          isValid = false;
        }
        // Password validation
        else if (
          input.type === "password" &&
          input.hasAttribute("data-min-length")
        ) {
          const minLength = parseInt(input.getAttribute("data-min-length"));
          if (input.value.length < minLength) {
            markInvalid(
              input,
              `Password must be at least ${minLength} characters`
            );
            isValid = false;
          }
        }
        // Password confirmation
        else if (input.hasAttribute("data-match")) {
          const matchId = input.getAttribute("data-match");
          const matchInput = document.getElementById(matchId);
          if (matchInput && input.value !== matchInput.value) {
            markInvalid(input, "Passwords do not match");
            isValid = false;
          }
        } else {
          clearInvalid(input);
        }
      });

      if (!isValid) {
        event.preventDefault();
      }
    });
  });

  // Helper functions for form validation
  function markInvalid(input, message) {
    input.classList.add("invalid");

    // Create or update error message
    let errorElement = input.nextElementSibling;
    if (!errorElement || !errorElement.classList.contains("error-message")) {
      errorElement = document.createElement("div");
      errorElement.classList.add("error-message");
      input.parentNode.insertBefore(errorElement, input.nextSibling);
    }

    errorElement.textContent = message;
  }

  function clearInvalid(input) {
    input.classList.remove("invalid");

    // Remove error message if exists
    const errorElement = input.nextElementSibling;
    if (errorElement && errorElement.classList.contains("error-message")) {
      errorElement.remove();
    }
  }

  function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  // Disable right-click on images
  const images = document.querySelectorAll("img");
  images.forEach((img) => {
    img.addEventListener("contextmenu", (e) => e.preventDefault());
  });
});
