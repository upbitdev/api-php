<?php

require_once('./upbit-api.php');
// your api secret
$api_secret = '';
// your api key
$api_key    = '';

$UpbitAPI = new UpbitAPI($api_key,$api_secret);

echo(json_encode($UpbitAPI->getOrderBook('btc_usd')));

die;