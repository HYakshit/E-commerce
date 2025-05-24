<?php
// Firebase configuration variables
// These would come from environment variables in production
$firebaseConfig = [
    'apiKey' => getenv('FIREBASE_API_KEY') ?: 'AIzaSyA2vABdu-vQPPwET38TXFowRYvods_GCoI',
    'authDomain' => getenv('FIREBASE_PROJECT_ID') ? getenv('FIREBASE_PROJECT_ID') . '.firebaseapp.com' : 'authentication-10a27.firebaseapp.com',
    'projectId' => getenv('FIREBASE_PROJECT_ID') ?: 'authentication-10a27',
    'appId' => getenv('FIREBASE_APP_ID') ?: '1:454245723353:web:05c31324c6c7faaf9022c8'
];

// Output the config as a JavaScript object to be used in the frontend
function outputFirebaseConfig()
{
    global $firebaseConfig;
    echo "
<script>
  window.firebaseConfig = {
    apiKey: '" . htmlspecialchars($firebaseConfig['apiKey'], ENT_QUOTES, 'UTF-8') . "',
    authDomain: '" . htmlspecialchars($firebaseConfig['authDomain'], ENT_QUOTES, 'UTF-8') . "',
    projectId: '" . htmlspecialchars($firebaseConfig['projectId'], ENT_QUOTES, 'UTF-8') . "',
    storageBucket: '" . htmlspecialchars($firebaseConfig['projectId'], ENT_QUOTES, 'UTF-8') . ".appspot.com',
    appId: '" . htmlspecialchars($firebaseConfig['appId'], ENT_QUOTES, 'UTF-8') . "'
  };
</script>";
}