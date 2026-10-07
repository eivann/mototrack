<?php
$ch = curl_init('https://mototrack.online/search-parts?model=gar');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
file_put_contents('live_error2.html', $res);
echo "Fetched error html size: " . strlen($res);
