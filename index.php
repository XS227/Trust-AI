<?php
if (!file_exists(__DIR__ . '/inc/config.local.php')) {
  header('Location: /install/index.php');
  exit;
}

readfile(__DIR__ . '/public/index.html');
