<?php
$ch = curl_init('https://mototrack.online/search-parts?model=gar');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "STATUS: " . $httpcode . "\n";
echo "BODY: " . $res . "\n";
