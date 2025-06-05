<?php
// Firebase configuration variables
// These would come from environment variables in production
// Output the config as a JavaScript object to be used in the frontend
// require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$firebaseConfig = [
  'apiKey' => $_ENV['FIREBASE_API_KEY'] ?? '',
  'authDomain' => $_ENV['FIREBASE_AUTH_DOMAIN'] ?? '',
  'projectId' => $_ENV['FIREBASE_PROJECT_ID'] ?? '',
  'appId' => $_ENV['FIREBASE_APP_ID'] ?? '',
];

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
