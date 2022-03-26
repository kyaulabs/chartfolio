<?php

/**
 * $KYAULabs: apis.inc.php,v 1.0.1 2022/03/24 22:22:08 kyau Exp $
 * ▄▄▄▄ ▄▄▄▄ ▄▄▄▄▄▄▄▄▄ ▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄
 * █ ▄▄ ▄ ▄▄ ▄ ▄▄▄▄ ▄▄ ▄    ▄▄   ▄▄▄▄ ▄▄▄▄  ▄▄▄ ▀
 * █ ██ █ ██ █ ██ █ ██ █    ██   ██ █ ██ █ ██▀  █
 * ■ ██▄▀ ██▄█ ██▄█ ██ █ ▀▀ ██   ██▄█ ██▄▀ ▀██▄ ■
 * █ ██ █ ▄▄ █ ██ █ ██ █    ██▄▄ ██ █ ██ █  ▄██ █
 * ▄ ▀▀ ▀ ▀▀▀▀ ▀▀ ▀ ▀▀▀▀    ▀▀▀▀ ▀▀ ▀ ▀▀▀▀ ▀▀▀  █
 * ▀▀▀▀▀▀▀▀▀▀▀▀▀▀ ▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀ ▀▀▀▀▀▀▀▀▀▀▀▀▀
 *
 * Crypto Exchange APIs
 * Copyright (C) 2021 KYAU Labs (https://kyaulabs.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace APIs
{
    /**
     * Aurora Crypto APIs
     *
     * This is the addon for the Aurora Template Engine that adds support for
     * the Binance, Bybit and FTX Exchange APIs. All of which will require
     * personal API keys.
     */
    class Exchange
    {
        /**
         * @param array $headers Extra headers to send with request.
         * @param string $url Full URL to request.
         *
         * @return $array $json JSON Decoded array containing the results.
         */
        protected function curl_get(string $url = null, array $headers = [])
        {
            if ($url == null)
            {
                throw new \Exception('Required parameter is null.');
                return 0;
            }
            $c = curl_init();
            $headers[] = "Accept: application/json";
            $headers[] = "Accept-Language: en";
            $headers[] = "Cache-Control: no-cache";

            curl_setopt($c, CURLOPT_URL, $url);
            curl_setopt($c, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($c, CURLOPT_HTTPGET, true);
            curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($c, CURLOPT_TCP_FASTOPEN, true);
            curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($c, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2TLS);
            curl_setopt($c, CURLOPT_HEADERFUNCTION, function ($c, $h) use (&$json_headers)
            {
                $len = strlen($h);
                $h = explode(':', $h, 2);
                if (count($h) < 2)
                {
                    // ignore invalid headers
                    return $len;
                }
                $json_headers[strtolower(trim($h[0]))][] = trim($h[1]);
                return $len;
            });

            $hs = curl_getinfo($c, CURLINFO_HEADER_SIZE);
            $data = curl_exec($c);
            $code = curl_getinfo($c, CURLINFO_RESPONSE_CODE);
            $json = json_decode(substr($data, $hs), true);
            curl_close($c);
            return $json;
        }
    }

    class Binance extends Exchange
    {
        /**
         * @var string $url Base API URL.
         */
        protected $url = "https://api.binance.us";

        /**
         * @var string $api_key Binance API Key.
         * @var string $api_secret Binance API Secret Key.
         */
        private $api_key = "";
        private $api_secret = "";

        /**
         * @param string $key Binance API Key.
         * @param string $secret Binance API Secret Key.
         *
         * @return bool Return true if success.
         */
        public function __construct(string $key = null, string $secret = null)
        {
            if (count(array_filter(array($key, $secret))) == 1)
            {
                throw new \Exception('Required parameter is null.');
                return 0;
            } else
            {
                $this->api_key = $key;
                $this->api_secret = $secret;
                return 1;
            }
        }

        /**
         * @param string $endpoint API Endpoint to get data from.
         * @param string $request API Request parameters.
         *
         * @return array $json API returned data converted from JSON.
         */
        private function api_get(string $endpoint = null, string $request = null)
        {
            if ($endpoint == null)
            {
                throw new \Exception('Required parameter is null.');
                return 0;
            }
            // Get timestamp to send with API request
            $timestamp = time() . "000";
            // Signature to hash
            $request = ($request == null) ? "timestamp=" . $timestamp : $request . "&timestamp=" . $timestamp;
            $signature = hash_hmac("sha256", $request, $this->api_secret, false);
            // Combine the full URL
            $turl = $this->url . $endpoint . "?" . $request . "&signature=" . $signature;
            // Send request with proper headers
            $headers[] = "X-MBX-APIKEY: " . $this->api_key;
            return $this->curl_get($turl, $headers);
        }

        /**
         * @return array $json API returned data converted from JSON.
         */
        public function get_balances()
        {
            return $this->api_get("/api/v3/account");
        }
    }

    class Bybit extends Exchange
    {
    }

    class FTX extends Exchange
    {
    }

}


/**
 * vim: ft=php sts=4 sw=4 ts=4 et:
 */
