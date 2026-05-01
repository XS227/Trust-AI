<?php
return [
  'db' => [
    'host' => '',
    'name' => '',
    'user' => '',
    'pass' => '',
  ],
  'vipps' => [
    'env' => 'test', // test|prod
    'merchantSerialNumber' => '',
    'client_id' => '',
    'client_secret' => '',
    'subscription_key_primary' => '',
    'issuer' => '',
    'well_known' => null, // settes dynamisk i start.php
    'authorization_endpoint' => '',
    'token_endpoint' => '',
    'userinfo_endpoint' => '',
    'scope' => 'openid name phone',
    'redirect_uri' => 'https://trustai.no/auth/vipps/callback.php',
    'post_login_redirect' => '/?logged=1',
  ],
  'google' => [
    'client_id' => '',
    'client_secret' => '',
    'redirect_uri' => 'https://trustai.no/api/auth/google.php?action=callback',
  ],
  'installed_at' => null,
];
