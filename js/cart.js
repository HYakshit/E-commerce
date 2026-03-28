document.addEventListener("DOMContentLoaded", function () {
  function formatPrice(amount) {
    return "$" + Number(amount || 0).toFixed(2);
  }

  function updateCartCount(count) {
    const cartCountElement = document.querySelector(".cart-count");
    if (cartCountElement && count !== undefined) {
      cartCountElement.textContent = count;
    }
  }

  function refreshOrderSummary(data) {
    const summaryListElement = document.getElementById("summary-product-list");
    const subtotalElement = document.getElementById("cart-subtotal");
    const cartTotalElement = document.getElementById("cart-total");
    const discountRowElement = document.getElementById("discount-row");
    const discountLabelElement = document.getElementById("discount-label");
    const discountAmountElement = document.getElementById("discount-amount");
    const couponActionsElement = document.getElementById("coupon-actions");
    const couponFormWrapperElement =
      document.getElementById("coupon-form-wrapper");

    if (summaryListElement && data.summary_items_html !== undefined) {
      summaryListElement.innerHTML = data.summary_items_html;
    }

    if (subtotalElement && data.subtotal !== undefined) {
      subtotalElement.textContent = formatPrice(data.subtotal);
    }

    if (cartTotalElement && data.cart_total !== undefined) {
      cartTotalElement.textContent = formatPrice(data.cart_total);
    }

    if (discountRowElement && discountLabelElement && discountAmountElement) {
      if (data.has_coupon) {
        discountRowElement.style.display = "flex";
        discountLabelElement.textContent = data.coupon_code
          ? `Discount (${data.coupon_code})`
          : "Discount";
        discountAmountElement.textContent = "-" + formatPrice(data.discount);
      } else {
        discountRowElement.style.display = "none";
        discountLabelElement.textContent = "Discount";
        discountAmountElement.textContent = "-" + formatPrice(0);
      }
    }

    if (couponActionsElement) {
      couponActionsElement.style.display = data.has_coupon ? "block" : "none";
    }

    if (couponFormWrapperElement) {
      couponFormWrapperElement.style.display = data.has_coupon
        ? "none"
        : "block";
    }
  }

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
          handleQuantityChange(input);
        }
      });
    }

    // Increment button
    const incBtn = input.nextElementSibling;
    if (incBtn && incBtn.classList.contains("quantity-btn")) {
      incBtn.addEventListener("click", function () {
        let value = parseInt(input.value);
        const max = parseInt(input.getAttribute("max"));

        if (!isNaN(max) && value >= max) {
          input.value = max;
        } else {
          input.value = value + 1;
        }

        handleQuantityChange(input);
      });
    }

    // Update on input change
    input.addEventListener("change", function () {
      let value = parseInt(this.value);
      const max = parseInt(this.getAttribute("max"));

      if (isNaN(value) || value < 1) {
        this.value = 1;
        value = 1;
      }

      if (!isNaN(max) && value > max) {
        this.value = max;
      }

      handleQuantityChange(this);
    });
  });

  function handleQuantityChange(input) {
    const productId = input.getAttribute("data-product-id");
    const cartRow = input.closest(".cart-item");

    // Product details pages reuse the same quantity UI, but they are not
    // cart rows and should not trigger cart update AJAX calls.
    if (!productId || !cartRow) {
      return;
    }

    updateCartItem(input, cartRow, productId);
  }

  // Function to update cart item quantity via AJAX
  function updateCartItem(input, cartRow, productId) {
    const quantity = parseInt(input.value);

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
            itemTotalElement.textContent = formatPrice(itemTotal);
          }

          refreshOrderSummary(data);
          updateCartCount(data.cart_count);
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

              refreshOrderSummary(data);
              updateCartCount(data.cart_count);

              // Show empty cart message if cart is empty
              if (data.cart_count === 0) {
                const cartItems = document.querySelector(".cart-items");
                const cartSummary = document.querySelector(".cart-summary");
                const emptyCart = document.createElement("div");
                emptyCart.classList.add("empty-cart");
                emptyCart.innerHTML = `
                                <p>Your cart is empty.</p>
                                <a href="/products.php" class="btn btn-primary">Continue Shopping</a>
                            `;

                cartItems.innerHTML = "";
                cartItems.appendChild(emptyCart);

                if (cartSummary) {
                  cartSummary.innerHTML = `
                    <h3 class="summary-title">Order Summary</h3>
                    <p class="summary-empty">No products in your cart.</p>
                  `;
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
  const removeCouponBtn = document.getElementById("remove-coupon-btn");

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
            refreshOrderSummary(data);

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

  if (removeCouponBtn) {
    removeCouponBtn.addEventListener("click", function () {
      fetch("/cart.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "remove_coupon",
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            refreshOrderSummary(data);

            const couponInput = document.getElementById("coupon_code");
            if (couponInput) {
              couponInput.value = "";
            }

            showMessage(
              data.message || "Coupon removed successfully",
              "success"
            );
          } else {
            showMessage(data.message || "Failed to remove coupon", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showMessage("Failed to remove coupon", "error");
        });
    });
  }
});
