<?php

/**
 * $KYAULabs: exchange.class.php,v 1.0.0 2022/03/26 17:20:03 kyau Exp $
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
         * @var string $api_key Exchange API Key.
         * @var string $api_secret Exchange API Secret Key.
         */
        protected $api_key = "";
        protected $api_secret = "";

        /**
         * @param array $headers Extra headers to send with request.
         * @param string $url Full URL to request.
         *
         * @return $array $json JSON Decoded array containing the results.
         */
        protected function curlRequest(string $url = null, array $headers = [])
        {
            if ($url == null) {
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
            curl_setopt($c, CURLOPT_HEADERFUNCTION, function ($c, $h) use (&$json_headers) {
                $len = strlen($h);
                $h = explode(':', $h, 2);
                if (count($h) < 2) {
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

}


/**
 * vim: ft=php sts=4 sw=4 ts=4 et:
 */
