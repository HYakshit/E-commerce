// Initialize Firebase
firebase.initializeApp(window.firebaseConfig);

// Function to handle login with Google
function loginWithGoogle() {
  const provider = new firebase.auth.GoogleAuthProvider();
  clearError(); // Clear any existing errors

  firebase
    .auth()
    .signInWithPopup(provider)
    .then((result) => {
      // User is signed in, get the Google user info
      const user = result.user;

      // Send the user data to the server to create or update the user in the database
      fetch("/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          action: "firebase_login",
          uid: user.uid,
          email: user.email,
          name: user.displayName,
          photo: user.photoURL,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Redirect user to the intended page or home page
            window.location.href = data.redirect || "/index.php";
          } else {
            showError(data.message || "Authentication failed");
          }
        })
        .catch((error) => {
          showError("Server error. Please try again later.");
          console.error("Server error:", error);
        });
    })
    .catch((error) => {
      // Handle errors
      console.error("Firebase auth error:", error);
      const errorMessage = getAuthErrorMessage(error.code);
      showError(errorMessage);
    });
}

// Function to handle login with email and password
function loginWithEmail(email, password) {
  clearError(); // Clear any existing errors

  // Try Firebase authentication first
  firebase
    .auth()
    .signInWithEmailAndPassword(email, password)
    .then((userCredential) => {
      // User is signed in
      const user = userCredential.user;
      console.log('Firebase auth successful:', user.email);

      // Send the user data to the server
      return fetch("/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          action: "firebase_login",
          uid: user.uid,
          email: user.email,
          name: user.displayName || email.split("@")[0],
          photo: user.photoURL
        })
      });
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`Server responded with status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log('Server response:', data);
      if (data.success) {
        window.location.href = data.redirect || "/index.php";
      } else {
        throw new Error(data.message || "Authentication failed");
      }
    })
    .catch((error) => {
      console.error("Authentication error:", error);
      
      // Get appropriate error message
      let errorMessage;
      if (error.code) {
        // Firebase Auth Error
        errorMessage = getAuthErrorMessage(error.code);
      } else {
        // Server or other error
        errorMessage = error.message || "Authentication failed";
      }
      
      showError(errorMessage);
    });
}

// Function to handle user registration
function registerWithEmail(email, password, name) {
  clearError(); // Clear any existing errors

  firebase
    .auth()
    .createUserWithEmailAndPassword(email, password)
    .then((userCredential) => {
      // User is created and signed in
      const user = userCredential.user;

      // Update profile with name
      return user
        .updateProfile({
          displayName: name,
        })
        .then(() => {
          // Send the user data to the server
          return fetch("/register.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({
              action: "firebase_register",
              uid: user.uid,
              email: user.email,
              name: name,
              photo: user.photoURL,
              password: password
            }),
          })
            .then(async (response) => {
              const responseText = await response.text();
              console.log('Server response from register:', responseText);

              try {
                // Try to parse the response as JSON
                const data = JSON.parse(responseText);
                if (data.success) {
                  window.location.href = data.redirect || "/index.php";
                } else {
                  showError(data.message || "Registration failed");
                }
              } catch (e) {
                console.error('Failed to parse server response:', e);
                showError("Server returned invalid response. Please contact support.");
              }
            })
            .catch((error) => {
              showError("Server error. Please try again later.");
              console.error("Server error in registration:", error);
            });
        });
    })
    .catch((error) => {
      // Handle Firebase Auth errors
      let errorMessage = "Registration failed";
      switch (error.code) {
        case 'auth/email-already-in-use':
          errorMessage = "This email is already registered";
          break;
        case 'auth/invalid-email':
          errorMessage = "Invalid email address";
          break;
        case 'auth/operation-not-allowed':
          errorMessage = "Email/password accounts are not enabled";
          break;
        case 'auth/weak-password':
          errorMessage = "Password is too weak";
          break;
        default:
          errorMessage = error.message;
      }
      showError(errorMessage);
    });
}

// Function to handle user logout
function logoutUser() {
  console.log('Logout function called');
  
  firebase
    .auth()
    .signOut()
    .then(() => {
      console.log('Firebase signout successful');
      return fetch("/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          action: "logout"
        }),
      });
    })
    .then(response => response.json())
    .then((data) => {
      if (data.success) {
        window.location.href = "/login.php";
      } else {
        throw new Error('Logout failed: ' + (data.message || 'Unknown error'));
      }
    })
    .catch((error) => {
      console.error("Logout error:", error);
      // Redirect to login page even if there's an error
      window.location.href = "/login.php";
    });
}

// Check authentication state on page load
function checkAuthState() {
  firebase.auth().onAuthStateChanged((user) => {
    if (user) {
      // User is signed in, check with the server
      fetch("/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          action: "check_session",
          uid: user.uid,
        }),
      })
        .then(async (response) => {
          const responseText = await response.text();
          console.log('Server response from check_session:', responseText);

          try {
            // Try to parse the response as JSON
            const data = JSON.parse(responseText);
            // If server session is not valid, sign out from Firebase
            if (!data.logged_in) {
              firebase.auth().signOut();
            }
          } catch (e) {
            console.error('Failed to parse server response:', e);
            // Don't sign out if we can't parse the response
            // as this might be a temporary server error
          }
        })
        .catch((error) => {
          console.error("Server error in checkAuthState:", error);
        });
    }
  });
}

// Helper function to get human-readable error messages
function getAuthErrorMessage(errorCode) {
  console.log("Getting error message for code:", errorCode); // Add error code logging

  switch (errorCode) {
    case "auth/email-already-in-use":
      return "This email is already registered. Please login instead.";
    case "auth/invalid-email":
      return "Please enter a valid email address.";
    case "auth/weak-password":
      return "Password should be at least 6 characters.";
    case "auth/user-not-found":
      return "No account found with this email. Please check your email or register.";
    case "auth/wrong-password":
      return "Incorrect password. Please try again.";
    case "auth/too-many-requests":
      return "Too many failed login attempts. Please try again later.";
    case "auth/network-request-failed":
      return "Network error. Please check your internet connection.";
    case "auth/popup-closed-by-user":
      return "Login popup was closed. Please try again.";
    case "auth/cancelled-popup-request":
      return "Another login popup is already open.";
    case "auth/operation-not-allowed":
      return "This login method is not enabled. Please try another method.";
    case "auth/invalid-login-credentials":
      return "Invalid email or password. Please check your credentials and try again.";
    case "auth/missing-password":
      return "Please enter your password.";
    case "auth/internal-error":
      return "An internal error occurred. Please try again later.";
    case "auth/unknown":
      return "An unknown error occurred. Please try again.";
    default:
      console.log("Unhandled error code:", errorCode); // Log unhandled error codes
      return `Authentication error (${errorCode}). Please try again.`;
  }
}

// Helper function to show error messages
function showError(message) {
  const errorElement = document.getElementById("error-message");
  if (errorElement) {
    errorElement.textContent = message;
    errorElement.style.display = "block";
    errorElement.classList.remove("hide");

    // Scroll error into view if it's not visible
    const rect = errorElement.getBoundingClientRect();
    const isVisible = (rect.top >= 0) && (rect.bottom <= window.innerHeight);
    if (!isVisible) {
      errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  } else {
    alert(message);
  }
}

// Helper function to clear error messages
function clearError() {
  const errorElement = document.getElementById("error-message");
  if (errorElement) {
    errorElement.classList.add("hide");
    setTimeout(() => {
      errorElement.style.display = "none";
      errorElement.textContent = "";
    }, 300);
  }
}

// Event listeners
document.addEventListener("DOMContentLoaded", function () {
  console.log('DOM Content Loaded');
  
  // Check auth state
  checkAuthState();

  // Logout button listener
  const logoutButtons = document.querySelectorAll('.logout-btn');
  console.log('Logout buttons found:', logoutButtons.length);
  
  logoutButtons.forEach(button => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      console.log('Logout button clicked');
      logoutUser();
    });
  });

  // Google login button
  const googleLoginBtn = document.getElementById("google-login-btn");
  if (googleLoginBtn) {
    googleLoginBtn.addEventListener("click", function (e) {
      e.preventDefault();
      loginWithGoogle();
    });
  }

  // Login form
  const loginForm = document.getElementById("login-form");
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const email = document.getElementById("email").value;
      const password = document.getElementById("password").value;
      loginWithEmail(email, password);
    });
  }

  // Registration form
  const registerForm = document.getElementById("register-form");
  if (registerForm) {
    registerForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const name = document.getElementById("name").value;
      const email = document.getElementById("email").value;
      const password = document.getElementById("password").value;
      const confirmPassword = document.getElementById("confirm-password").value;

      // Simple validation
      if (password !== confirmPassword) {
        showError("Passwords do not match");
        return;
      }

      registerWithEmail(email, password, name);
    });
  }
});
