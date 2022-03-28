<?php

/**
 * $KYAULabs: ftx.inc.php,v 1.0.2 2022/03/28 09:06:13 kyau Exp $
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

    class FTX extends Exchange
    {
        /**
         * @var string $url Base API URL.
         */
        protected $url = "https://ftx.us/api";

        /**
         * @return bool Return true if success.
         */
        public function __construct()
        {
            if (count(array_filter(array(FTX_KEY, FTX_SECRET))) == 1) {
                throw new \Exception('Please add API keys to settings.inc.php.');
                return 0;
            } else {
                $this->api_key = FTX_KEY;
                $this->api_secret = FTX_SECRET;
                return 1;
            }
        }

        /**
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
            $sign = ($request == null) ? $timestamp . "GET/api" . $endpoint : $timestamp . "GET/api" . $endpoint . "?" . $request;
            $signature = hash_hmac("sha256", $sign, $this->api_secret, false);
            // Combine the full URL
            $turl = $this->url . $endpoint . "?" . $request;
            // Send request with proper headers
            $headers[] = "FTXUS-KEY: " . $this->api_key;
            $headers[] = "FTXUS-TS: " . $timestamp;
            $headers[] = "FTXUS-SIGN: " . $signature;
            return $this->curlRequest($turl, $headers);
        }

        /**
         * Retrieve all tradable asset pairs.
         *
         * @return array $json API returned data converted from JSON.
         */
        public function getPairs()
        {
            return $this->apiLookup("/markets");
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
            return $this->apiLookup("/markets/" . $pair);
        }

        /**
         * Retrieve all wallet balances.
         *
         * @return array $json API returned data converted from JSON.
         */
        public function getBalances()
        {
            return $this->apiLookup("/wallet/balances");
        }
    }

}


/**
 * vim: ft=php sts=4 sw=4 ts=4 et:
 */
