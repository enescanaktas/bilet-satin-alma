<?php
// Lightweight integration test: fetch CSRF from login, perform login via cookies, call quickBuy
// Run: php tests/integration/test_booking_flow.php

$base = 'http://localhost';
$cookie = sys_get_temp_dir() . '/test_cookies.txt';
@unlink($cookie);
function curl($url, $opts=[]) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    foreach ($opts as $k=>$v) curl_setopt($ch,$k,$v);
    $r = curl_exec($ch);
    if ($r === false) { throw new Exception('curl error '.curl_error($ch)); }
    return $r;
}
// get login page
$page = curl($base . '/login', [CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie]);
if (!preg_match('/name="csrf" value="([a-f0-9]+)"/i', $page, $m)) { echo "FAILED: no csrf\n"; exit(2); }
$csrf = $m[1];
// login as testuser/password (ensure test user exists)
$post = http_build_query(['csrf'=>$csrf,'username'=>'testuser','password'=>'password']);
$r = curl($base . '/login', [CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post, CURLOPT_HEADER => true]);
// allow redirect to / or /mybookings depending on app configuration
if (stripos($r,'Location: /') === false && stripos($r,'Location: /mybookings') === false) { echo "FAILED: login did not redirect to / or /mybookings\n"; exit(3); }
// try quickBuy on seats 1..40 until one succeeds (robust against occupied seats)
$ok = false; $lastResp = '';
for ($s = 1; $s <= 40; $s++) {
    $post2 = http_build_query(['csrf'=>$csrf,'trip_id'=>1,'seat_number'=>$s,'gender'=>'male']);
    $resp = curl($base . '/booking/quickbuy', [CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post2, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
    $j = json_decode($resp,true);
    $lastResp = $resp;
    if ($j && !empty($j['ok'])) { echo "OK quickBuy seat=".($j['seat'] ?? $s)." price=".($j['price'] ?? 'N/A')."\n"; $ok = true; break; }
}
if (!$ok) { echo "FAILED: quickBuy did not succeed on any seat. Last response: " . $lastResp . "\n"; exit(4); }
exit(0);
exit(0);
