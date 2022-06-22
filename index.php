<?php

require_once('Auth\OAuthConnection.php');
use Auth\OAuthConnection;

$authorize2_url = 'https://account.withings.com/oauth2_user/authorize2';
$callback_uri = 'http://localhost';
$access_token_url = 'https://wbsapi.withings.net/v2/oauth2';
$measure_api_url = 'https://wbsapi.withings.net/measure';

$client_id = '828c62a3088e34533f4708f99bb612c02c40d6487cc8d0588312b6057e8fb644';
$client_secret = '93000f961c69a6fa9021699980fb3a80eb6bd8eb6c2005036e68fff1a11b40f8';

$OAuthConnection = new OAuthConnection($authorize2_url, $callback_uri, $access_token_url, $measure_api_url, $client_id, $client_secret);

if ($_GET["code"]) {
    $access_token = $OAuthConnection->getAccessToken($_GET["code"]);
    $resource = $OAuthConnection->getWeightMeasure($access_token);
    echo $resource;
} else {
    $OAuthConnection->getAuthorizationCode();
}
