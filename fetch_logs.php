<?php
$ch = curl_init('https://mototrack.online/debug-logs-temp-2026');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($httpcode == 404) {
    echo "404 Not Found (Auto-deploy hasn't finished)";
} else {
    echo $res;
}
