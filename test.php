<?php
// Simple test page
header('Content-Type: text/plain');
echo "PHP Test Page Working!\n";
echo "Server time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP version: " . phpversion() . "\n";
echo "Server address: " . ($_SERVER['SERVER_ADDR'] ?? 'unknown') . "\n";
echo "Remote address: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
?>