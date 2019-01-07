<?php 
namespace Wouerner\MercadoBitcoin;

class Api
{
    private  $id;
    private  $segredo;
    private  $apiPrivada;
    private  $apiPublica;
    private  $baseUrl;

    public function __construct($id, $segredo)
      {
        $this->id = $id;
        $this->secredo = $segredo;

        $this->baseUrl = "https://www.mercadobitcoin.net/";
        $this->version = "v1";
        $this->apiPrivada = Array("getInfo", "OrderList", "Trade", "CancelOrder");
        $this->apiPublica = Array("ticker", "orderbook", "trades", "ticker_litecoin", "orderbook_litecoin", "trades_litecoin");
      }

    private function nonce()
    {
        list($usec, $sec) = explode(" ", microtime());
        return (int)((float)$usec + (float)$sec);
    }

    private function signMessage($message)
    {
        $signedMessage = hash_hmac('sha512', $message, $this->secredo);

        return $signedMessage;
    }

    private function callApi($api, $params = Array())
    {
        foreach($this->apiPrivada as $value)
        {
            if($api == $value)
            {
                $this->nonce = $this->nonce();
                $header = [];

                $header[] = "TAPI-ID: " . $this->id;
                $header[] = "TAPI-MAC: " . $this->signMessage($message);

                $params["tapi_method"] = 'get_account_info';
                $params["tapi_nonce"] = '2';

                return $this->doRequest("POST", $params, $header);
            }
        }

        foreach($this->apiPublica as $value)
        {
            if($api == $value)
            {
                return $this->doRequest("GET", Array("method" => $value));
            }
        }
        return false;
    }

    private function doRequest($metodo, $params, $header = Array())
    {
        foreach(array_keys($params) as $key)
        {
            $params[$key] = urlencode($params[$key]);
        }

        $postFields = http_build_query($params);
        $ch = curl_init();
        $options = Array(
            CURLOPT_HEADER         => false,
            /* CURLOPT_USERAGENT      => urlencode('Módulo de API Mercado Bitcoin em PHP ' . $this->version), */
            CURLOPT_RETURNTRANSFER => true //,
        );

        if($metodo == "POST")
        {
            $options[CURLOPT_URL]  = $this->baseUrl . "tapi/v3/";
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_HTTPHEADER] = $header;
            $options[CURLOPT_POSTFIELDS] = $postFields;
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);


        /* var_dump($response);die; */

        if ($status == 200)
        {
            return json_decode($response, true);
        }
        else
        {
            /* Debug */
            var_dump($response);
            var_dump($status);
            echo "\nHTTP Status: " . $status . "\n";
            exit(0);
        }
        return false;
    }

    public function getAccountInfo() {
        $nonce = $this->nonce();

        $message = '/tapi/v3/?tapi_method=get_account_info&tapi_nonce=' . $nonce;

        $header = [];

        $header[] = "TAPI-ID: " . $this->id;
        $header[] = "TAPI-MAC: " . $this->signMessage($message);

        $params["tapi_method"] = 'get_account_info';
        $params["tapi_nonce"] = $nonce;

        return $this->doRequest("POST", $params, $header);
    }
}