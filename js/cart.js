document.addEventListener("DOMContentLoaded", function () {
  // Cart quantity update
  const quantityInputs = document.querySelectorAll(".item-quantity");

  quantityInputs.forEach((input) => {
    // Decrement button
    const decBtn = input.previousElementSibling;
    if (decBtn && decBtn.classList.contains("quantity-btn")) {
      decBtn.addEventListener("click", function () {
        let value = parseInt(input.value);
        if (value > 1) {
          input.value = value - 1;
          updateCartItem(input);
        }
      });
    }

    // Increment button
    const incBtn = input.nextElementSibling;
    if (incBtn && incBtn.classList.contains("quantity-btn")) {
      incBtn.addEventListener("click", function () {
        let value = parseInt(input.value);
        input.value = value + 1;
        updateCartItem(input);
      });
    }

    // Update on input change
    input.addEventListener("change", function () {
      let value = parseInt(this.value);
      if (isNaN(value) || value < 1) {
        this.value = 1;
        value = 1;
      }
      updateCartItem(this);
    });
  });

  // Function to update cart item quantity via AJAX
  function updateCartItem(input) {
    const productId = input.getAttribute("data-product-id");
    const quantity = parseInt(input.value);
    const cartRow = input.closest(".cart-item");

    fetch("/cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "update_quantity",
        product_id: productId,
        quantity: quantity,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Update item total
          const priceElement = cartRow.querySelector(".item-price");
          const itemTotalElement = cartRow.querySelector(".item-total");

          if (priceElement && itemTotalElement) {
            const price = parseFloat(priceElement.getAttribute("data-price"));
            const itemTotal = price * quantity;
            itemTotalElement.textContent = "$" + itemTotal.toFixed(2);
          }

          // Update cart total
          const cartTotalElement = document.getElementById("cart-total");
          if (cartTotalElement && data.cart_total) {
            cartTotalElement.textContent = "$" + data.cart_total.toFixed(2);
          }

          // Update cart count in navbar
          const cartCountElement = document.querySelector(".cart-count");
          if (cartCountElement && data.cart_count !== undefined) {
            cartCountElement.textContent = data.cart_count;
          }
        } else {
          showMessage("Error updating cart", "error");
          // Reset input to previous value if needed
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showMessage("Failed to update cart", "error");
      });
  }
  function UpdateCart() {
    // Update cart count in navbar
    const cartCountElement = document.querySelector(".cart-count");
    if (cartCountElement && data.cart_count !== undefined) {
      cartCountElement.textContent = data.cart_count;
    }
  }
  // Update cart count in navbar
  const cartCountElement = document.querySelector(".cart-count");
  if (cartCountElement && data.cart_count !== undefined) {
    cartCountElement.textContent = data.cart_count;
  }

  // Remove item from cart
  const removeButtons = document.querySelectorAll(".remove-item");

  removeButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();

      const productId = this.getAttribute("data-product-id");
      const cartItem = this.closest(".cart-item");

      if (
        confirm("Are you sure you want to remove this item from your cart?")
      ) {
        fetch("/cart.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            action: "remove_item",
            product_id: productId,
          }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              // Remove item from DOM
              cartItem.remove();

              // Update cart total
              const cartTotalElement = document.getElementById("cart-total");
              if (cartTotalElement && data.cart_total) {
                cartTotalElement.textContent = "$" + data.cart_total.toFixed(2);
              }

              // Update cart count in navbar
              const cartCountElement = document.querySelector(".cart-count");
              if (cartCountElement && data.cart_count !== undefined) {
                cartCountElement.textContent = data.cart_count;
              }

              // Show empty cart message if cart is empty
              if (data.cart_count === 0) {
                const cartItems = document.querySelector(".cart-items");
                const emptyCart = document.createElement("div");
                emptyCart.classList.add("empty-cart");
                emptyCart.innerHTML = `
                                <p>Your cart is empty.</p>
                                <a href="/products.php" class="btn btn-primary">Continue Shopping</a>
                            `;

                cartItems.innerHTML = "";
                cartItems.appendChild(emptyCart);

                // Hide checkout button
                const checkoutBtn = document.querySelector(".checkout-btn");
                if (checkoutBtn) {
                  checkoutBtn.style.display = "none";
                }
              }

              showMessage("Item removed from cart", "success");
            } else {
              showMessage("Error removing item from cart", "error");
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            showMessage("Failed to remove item", "error");
          });
      }
    });
  });

  // Add to cart functionality (for product pages)
  const addToCartForm = document.getElementById("add-to-cart-form");

  if (addToCartForm) {
    addToCartForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const productId = this.querySelector('input[name="product_id"]').value;
      const quantity = parseInt(
        this.querySelector('input[name="quantity"]').value
      );

      fetch("/cart.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "add_to_cart",
          product_id: productId,
          quantity: quantity,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Update cart count in navbar
            const cartCountElement = document.querySelector(".cart-count");
            if (cartCountElement && data.cart_count !== undefined) {
              cartCountElement.textContent = data.cart_count;
            }

            showMessage("Product added to cart", "success");
          } else {
            showMessage(data.message || "Error adding to cart", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showMessage("Failed to add item to cart", "error");
        });
    });
  }

  // Helper function to show messages
  function showMessage(message, type) {
    const messageContainer = document.getElementById("message-container");

    if (!messageContainer) {
      // Create message container if it doesn't exist
      const container = document.createElement("div");
      container.id = "message-container";
      document.body.appendChild(container);
    }

    const messageElement = document.createElement("div");
    messageElement.classList.add("message", type);
    messageElement.textContent = message;

    document.getElementById("message-container").appendChild(messageElement);

    // Auto-remove message after 3 seconds
    setTimeout(() => {
      messageElement.classList.add("fade-out");
      setTimeout(() => {
        messageElement.remove();
      }, 500);
    }, 3000);
  }

  // Apply coupon code
  const couponForm = document.getElementById("coupon-form");

  if (couponForm) {
    couponForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const couponCode = this.querySelector('input[name="coupon_code"]').value;

      fetch("/cart.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "apply_coupon",
          coupon_code: couponCode,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Update discount display
            const discountElement = document.getElementById("discount-amount");
            if (discountElement) {
              discountElement.textContent = "-$" + data.discount.toFixed(2);
              discountElement.closest(".summary-row").style.display = "flex";
            }

            // Update total
            const cartTotalElement = document.getElementById("cart-total");
            if (cartTotalElement && data.cart_total) {
              cartTotalElement.textContent = "$" + data.cart_total.toFixed(2);
            }

            showMessage(
              data.message || "Coupon applied successfully",
              "success"
            );
          } else {
            showMessage(data.message || "Invalid coupon code", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showMessage("Failed to apply coupon", "error");
        });
    });
  }
});
