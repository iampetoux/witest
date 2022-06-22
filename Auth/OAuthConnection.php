<?php

namespace Auth;

class OAuthConnection
{
    private string $authorize2_url;
    private string $callback_uri;
    private string $access_token_url;
    private string $measure_api_url;

    private string $client_id;
    private string $client_secret;

    private string $state;

    /*
     * Class constructor
    */
    public function __construct(string $authorize2_url, string $callback_uri, string $access_token_url, string $measure_api_url, string $client_id, string $client_secret)
    {
        $this->authorize2_url = $authorize2_url;
        $this->callback_uri = $callback_uri;
        $this->access_token_url = $access_token_url;
        $this->measure_api_url = $measure_api_url;
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->state = $this->generateRandomString();
    }

    /*
     * Create the connection string and redirect the client to the Withings login page
    */
    public function getAuthorizationCode()
    {
        $authorization_redirect_url = "{$this->authorize2_url}?response_type=code&client_id={$this->client_id}&state={$this->state}&redirect_uri={$this->callback_uri}&scope=user.metrics";
        // Redirect the user to the login page to obtain his authorization code
        header("Location: " . $authorization_redirect_url);
    }

    /*
     * Ask the API for an Access Token passing the authorization code we just got
    */
    public function getAccessToken($authorization_code)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->access_token_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'action' => 'requesttoken',
            'grant_type' => 'authorization_code',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $authorization_code,
            'redirect_uri' => $this->callback_uri
        ]));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response)->body->access_token;
    }

    /*
     * Ask the API for the user's last measured weight.
    */
    public function getWeightMeasure($access_token)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->measure_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$access_token}"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'action' => 'getmeas',
            'meastype' => 1
        ]));

        $response = curl_exec($ch);
        curl_close($ch);

        return json_encode(json_decode($response)->body->measuregrps);
    }

    // Generate a random string that we'll use for the state to confirm that the redirect wasn't spoofed.
    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
