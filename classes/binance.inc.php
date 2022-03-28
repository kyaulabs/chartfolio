<?php

/**
 * $KYAULabs: binance.inc.php,v 1.0.3 2022/03/28 10:51:34 kyau Exp $
 * ▄▄▄▄ ▄▄▄▄ ▄▄▄▄▄▄▄▄▄ ▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄
 * █ ▄▄ ▄ ▄▄ ▄ ▄▄▄▄ ▄▄ ▄    ▄▄   ▄▄▄▄ ▄▄▄▄  ▄▄▄ ▀
 * █ ██ █ ██ █ ██ █ ██ █    ██   ██ █ ██ █ ██▀  █
 * ■ ██▄▀ ██▄█ ██▄█ ██ █ ▀▀ ██   ██▄█ ██▄▀ ▀██▄ ■
 * █ ██ █ ▄▄ █ ██ █ ██ █    ██▄▄ ██ █ ██ █  ▄██ █
 * ▄ ▀▀ ▀ ▀▀▀▀ ▀▀ ▀ ▀▀▀▀    ▀▀▀▀ ▀▀ ▀ ▀▀▀▀ ▀▀▀  █
 * ▀▀▀▀▀▀▀▀▀▀▀▀▀▀ ▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀ ▀▀▀▀▀▀▀▀▀▀▀▀▀
 *
 * Chartfolio
 * Copyright (C) 2022 KYAU Labs (https://kyaulabs.com)
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

    class Binance extends Exchange
    {
        /**
         * @var string $url Base API URL.
         */
        protected $url = "https://api.binance.us";

        /**
         * @param string $key Binance API Key.
         * @param string $secret Binance API Secret Key.
         *
         * @return bool Return true if success.
         */
        public function __construct()
        {
            if (count(array_filter(array(BINANCE_KEY, BINANCE_SECRET))) == 1) {
                throw new \Exception('Please add API keys to settings.inc.php.');
                return 0;
            } else {
                $this->api_key = BINANCE_KEY;
                $this->api_secret = BINANCE_SECRET;
                return 1;
            }
        }

        /**
         * Lookup data from an endpoint that requires authentication.
         *
         * @param string $endpoint API Endpoint to get data from.
         * @param string $request API Request parameters.
         *
         * @return array $json API returned data converted from JSON.
         */
        private function apiLookup(string $endpoint = null, string $request = null)
        {
            if ($endpoint == null) {
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
            return $this->curlRequest($turl, $headers);
        }

        /**
         * Lookup data from a public API endpoint.
         *
         * @param string $endpoint API Endpoint to get data from.
         *
         * @return array $json API returned data converted from JSON.
         */
        private function apiPublic(string $endpoint = null)
        {
            if ($endpoint == null) {
                throw new \Exception('Required parameter is null.');
                return 0;
            }
            return $this->curlRequest($this->url . $endpoint);
        }

        /**
         * Retrieve all tradable asset pairs.
         *
         * @return array $json API returned data converted from JSON.
         */
        public function getPairs()
        {
            return $this->apiPublic("/api/v3/exchangeInfo");
        }

        /**
         * Retrieve a specific assets market information.
         *
         * @return array $json API returned data converted from JSON.
         */
        public function getPair(string $pair = null)
        {
            if ($pair == null) {
                throw new \Exception('Required parameter is null.');
                return 0;
            }
            return $this->apiPublic("/api/v3/ticker/24hr?symbol=" . $pair);
        }

        /**
         * Retrieve all wallet balances.
         *
         * @return array $json API returned data converted from JSON.
         */
        public function getBalances()
        {
            return $this->apiLookup("/api/v3/account");
        }

        /**
         * Retrieve all previous orders based on watched pairs.
         *
         * @return array $json API returned data converted from JSON.
         */
        public function getTrades(string $pair = null)
        {
            if ($pair == null) {
                throw new \Exception('Required parameter is null.');
                return 0;
            }
            return $this->apiLookup("/api/v3/myTrades", "symbol=" . $pair);
        }

        /**
         * Retrieve a specific order from the account history.
         *
         * @return array $json API returned data converted from JSON.
         */
        public function getOrder(string $pair = null, int $orderId = null)
        {
            if (count(array_filter(array($pair, $orderId))) == 1) {
                throw new \Exception('Required parameter is null.');
                return 0;
            }
            return $this->apiLookup("/api/v3/order", "symbol=" . $pair . "&orderId=" . $orderId);
        }
    }

}


/**
 * vim: ft=php sts=4 sw=4 ts=4 et:
 */
