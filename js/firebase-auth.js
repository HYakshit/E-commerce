// Initialize Firebase
firebase.initializeApp(window.firebaseConfig);

// Function to handle login with Google
function loginWithGoogle() {
  const provider = new firebase.auth.GoogleAuthProvider();

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
      showError("Authentication failed. Please try again.");
    });
}

// Function to handle login with email and password
function loginWithEmail(email, password) {
  firebase
    .auth()
    .signInWithEmailAndPassword(email, password)
    .then((userCredential) => {
      // User is signed in
      const user = userCredential.user;

      // Send the user data to the server
      fetch("/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "firebase_login",
          uid: user.uid,
          email: user.email,
          name: user.displayName || email.split("@")[0],
          photo: user.photoURL,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
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

// Function to handle user registration
function registerWithEmail(email, password, name) {
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
          fetch("/register.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              action: "firebase_register",
              uid: user.uid,
              email: user.email,
              name: name,
              photo: user.photoURL,
            }),
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                window.location.href = data.redirect || "/index.php";
              } else {
                showError(data.message || "Registration failed");
              }
            })
            .catch((error) => {
              showError("Server error. Please try again later.");
              console.error("Server error:", error);
            });
        });
    })
    .catch((error) => {
      // Handle errors
      console.error("Firebase auth error:", error);

      const errorMessage = getAuthErrorMessage(error.code);
      showError(errorMessage);
    });
}

// Function to handle user logout
function logoutUser() {
  firebase
    .auth()
    .signOut()
    .then(() => {
      // Sign-out successful, notify the server
      fetch("/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          action: "logout",
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          window.location.href = "/login.php";
        })
        .catch((error) => {
          console.error("Server error:", error);
          window.location.href = "/login.php";
        });
    })
    .catch((error) => {
      console.error("Firebase signout error:", error);
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
        },
        body: JSON.stringify({
          action: "check_session",
          uid: user.uid,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          // If server session is not valid, sign out from Firebase
          if (!data.logged_in) {
            firebase.auth().signOut();
          }
        })
        .catch((error) => {
          console.error("Server error:", error);
        });
    }
  });
}

// Helper function to get human-readable error messages
function getAuthErrorMessage(errorCode) {
  switch (errorCode) {
    case "auth/email-already-in-use":
      return "This email is already registered. Please login instead.";
    case "auth/invalid-email":
      return "Please enter a valid email address.";
    case "auth/weak-password":
      return "Password should be at least 6 characters.";
    case "auth/user-not-found":
    case "auth/wrong-password":
      return "Invalid email or password.";
    default:
      return "An error occurred. Please try again.";
  }
}

// Helper function to show error messages
function showError(message) {
  const errorElement = document.getElementById("error-message");
  if (errorElement) {
    errorElement.textContent = message;
    errorElement.style.display = "block";
  } else {
    alert(message);
  }
}

// Event listeners
document.addEventListener("DOMContentLoaded", function () {
  // Check auth state
  checkAuthState();

  // Logout button listener
  const logoutBtn = document.getElementById("logout-btn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault();
      logoutUser();
    });
  }

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
