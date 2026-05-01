<?php
// /auth/vipps/http.php

function vipps_curl_json(string $url): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
      'Accept: application/json'
    ],
  ]);

  $body = curl_exec($ch);
  $err  = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($body === false) {
    return ['ok'=>false, 'error'=>"cURL error: $err", 'http'=>0, 'body'=>null];
  }
  if ($code < 200 || $code >= 300) {
    return ['ok'=>false, 'error'=>"HTTP $code", 'http'=>$code, 'body'=>$body];
  }

  $json = json_decode($body, true);
  if (!$json) {
    return ['ok'=>false, 'error'=>"Invalid JSON from Vipps", 'http'=>$code, 'body'=>$body];
  }

  return ['ok'=>true, 'json'=>$json, 'http'=>$code];
}
