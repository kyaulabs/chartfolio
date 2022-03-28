<?php

/**
 * $KYAULabs: update.class.php,v 1.0.2 2022/03/28 09:30:50 kyau Exp $
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

namespace Chartfolio {

    class Update {
        /**
         * @var object $binance Binance API Class Instance.
         * @var object $bybit Bybit API Class Instance.
         * @var object $ftx FTX API Class Instance.
         * @var object $sql MySQL database object.
         */
        private $binance = null;
        private $bybit = null;
        private $ftx = null;
        private $sql = null;

        /**
         * @param object $binance Instance of \APIs\Binance class.
         * @param object $bybit Instance of \APIs\Bybit class.
         * @param object $ftx Instance of \APIs\FTX class.
         *
         * @return bool Return true if success.
         */
        public function __construct(object $binance = null, object $bybit = null, object $ftx = null, object $sql = null)
        {
            if (!($binance instanceof \APIs\Binance) or !($bybit instanceof \APIs\Bybit) or !($ftx instanceof \APIs\FTX) or !($sql instanceof \KYAULabs\SQLHandler)) {
                throw new \Exception('Included objects are not the proper classes.');
                return 0;
            } else {
                $this->binance = $binance;
                $this->bybit = $bybit;
                $this->ftx = $ftx;
                $this->sql = $sql;
                return 1;
            }
        }

        /**
         * Update Asset Pairs for Binance.
         *
         * @return bool Return true if success.
         */
        public function updateBinancePairs(bool $debug = false)
        {
            // BINANCE
            echo "Binance: Refreshing Asset Pairs...\n";
            $binancePairs = $this->binance->getPairs();
            foreach ($binancePairs['symbols'] as $i) {
                $pair = $this->binance->getPair($i['symbol']);
                if ($debug) {
                    echo $i['symbol'] . ": " . $pair['lastPrice'] . " " . $pair['priceChangePercent'] . "%\n";
                }
                $c = $this->sql->query("SELECT Count(*) as `total` FROM `binance_pairs` WHERE `pair` = :pair", array(':pair' => $i['symbol']));
                $count = $c->fetchObject();
                $vars = array(
                    ':pair' => $i['symbol'], ':ticker_base' => $i['baseAsset'], ':ticker_quote' => $i['quoteAsset'],
                    ':scale_base' => $i['baseAssetPrecision'], ':scale_quote' => $i['quoteAssetPrecision'],
                    ':fee_maker' => 0.1, ':fee_taker' => 0.1, ':mark_price' => $pair['lastPrice'],
                    ':price_24h_pcnt' => $pair['priceChangePercent'], ':volume_24h' => $pair['quoteVolume']
                );
                if ($count->total != 0) {
                    $this->sql->query("UPDATE `binance_pairs` SET `ticker_base` = :ticker_base, `ticker_quote` = :ticker_quote, `scale_base` = :scale_base, `scale_quote` = :scale_quote, `fee_maker` = :fee_maker, `fee_taker` = :fee_taker, `mark_price` = :mark_price, `price_24h_pcnt` = :price_24h_pcnt, `volume_24h` = :volume_24h WHERE `pair` = :pair", $vars);
                } else {
                    $this->sql->query("INSERT INTO `binance_pairs` (`id`, `pair`, `ticker_base`, `ticker_quote`, `scale_base`, `scale_quote`, `fee_maker`, `fee_taker`, `mark_price`, `price_24h_pcnt`, `volume_24h`) VALUES (NULL, :pair, :ticker_base, :ticker_quote, :scale_base, :scale_quote, :fee_maker, :fee_taker, :mark_price, :price_24h_pcnt, :volume_24h)", $vars);
                }
            }
            return 1;
        }

        /**
         * Update Asset Pairs for Bybit.
         *
         * @return bool Return true if success.
         */
        public function updateBybitPairs(bool $debug = false)
        {
            // BYBIT
            echo "Bybit: Refreshing Asset Pairs...\n";
            $bybitPairs = $this->bybit->getPairs();
            foreach ($bybitPairs['result'] as $i) {
                $pair = $this->bybit->getPair($i['name'])['result'][0];
                if ($debug) {
                    echo $i['name'] . ": " . $pair['last_price'] . " " . $pair['price_24h_pcnt'] . "%\n";
                }
                $c = $this->sql->query("SELECT Count(*) as `total` FROM `bybit_pairs` WHERE `pair` = :pair", array(':pair' => $i['name']));
                $count = $c->fetchObject();
                $vars = array(
                    ':pair' => $i['name'], ':ticker_base' => $i['base_currency'], ':ticker_quote' => $i['quote_currency'],
                    ':scale_quote' => $i['price_scale'], ':fee_maker' => $i['maker_fee'], ':fee_taker' => $i['taker_fee'],
                    ':leverage_min' => $i['leverage_filter']['min_leverage'], ':leverage_max' => $i['leverage_filter']['max_leverage'],
                    ':leverage_step' => $i['leverage_filter']['leverage_step'], ':funding_rate' => $pair['funding_rate'],
                    ':mark_price' => $pair['last_price'], ':price_24h_pcnt' => $pair['price_24h_pcnt'], ':volume_24h' => $pair['volume_24h']
                );
                if ($count->total != 0) {
                    $this->sql->query("UPDATE `bybit_pairs` SET `ticker_base` = :ticker_base, `ticker_quote` = :ticker_quote, `scale_quote` = :scale_quote, `fee_maker` = :fee_maker, `fee_taker` = :fee_taker, `leverage_min` = :leverage_min, `leverage_max` = :leverage_max, `leverage_step` = :leverage_step, `funding_rate` = :funding_rate, `mark_price` = :mark_price, `price_24h_pcnt` = :price_24h_pcnt, `volume_24h` = :volume_24h WHERE `pair` = :pair", $vars);
                } else {
                    $this->sql->query("INSERT INTO `bybit_pairs` (`id`, `pair`, `ticker_base`, `ticker_quote`, `scale_quote`, `fee_maker`, `fee_taker`, `leverage_min`, `leverage_max`, `leverage_step`, `funding_rate`, `mark_price`, `price_24h_pcnt`, `volume_24h`) VALUES (NULL, :pair, :ticker_base, :ticker_quote, :scale_quote, :fee_maker, :fee_taker, :leverage_min, :leverage_max, :leverage_step, :funding_rate, :mark_price, :price_24h_pcnt, :volume_24h)", $vars);
                }
            }
            return 1;
        }

        /**
         * Update Asset Pairs for FTX.
         *
         * @return bool Return true if success.
         */
        public function updateFTXPairs(bool $debug = false)
        {
            // FTX
            echo "FTX: Refreshing Asset Pairs...\n";
            $ftxPairs = $this->ftx->getPairs();
            foreach ($ftxPairs['result'] as $i) {
                if ($debug) {
                    echo $i['name'] . ": " . $i['last'] . " " . $i['change24h'] . "%\n";
                }
                $c = $this->sql->query("SELECT Count(*) as `total` FROM `ftx_pairs` WHERE `pair` = :pair", array(':pair' => $i['name']));
                $count = $c->fetchObject();
                $vars = array(
                    ':pair' => $i['name'], ':ticker_base' => $i['baseCurrency'], ':ticker_quote' => $i['quoteCurrency'],
                    ':scale_quote' => strlen(substr(strrchr($i['priceIncrement'], "."), 1)), ':fee_maker' => 0.1, ':fee_taker' => 0.4,
                    ':mark_price' => $i['last'], ':price_24h_pcnt' => $i['change24h'], ':volume_24h' => $i['volumeUsd24h']
                );
                if ($count->total != 0) {
                    $this->sql->query("UPDATE `ftx_pairs` SET `ticker_base` = :ticker_base, `ticker_quote` = :ticker_quote, `scale_quote` = :scale_quote, `fee_maker` = :fee_maker, `fee_taker` = :fee_taker, `mark_price` = :mark_price, `price_24h_pcnt` = :price_24h_pcnt, `volume_24h` = :volume_24h WHERE `pair` = :pair", $vars);
                } else {
                    $this->sql->query("INSERT INTO `ftx_pairs` (`id`, `pair`, `ticker_base`, `ticker_quote`, `scale_quote`, `fee_maker`, `fee_taker`, `mark_price`, `price_24h_pcnt`, `volume_24h`) VALUES (NULL, :pair, :ticker_base, :ticker_quote, :scale_quote, :fee_maker, :fee_taker, :mark_price, :price_24h_pcnt, :volume_24h)", $vars);
                }
            }
            return 1;
        }

        /**
         * Update Asset Pairs for all Exchanges.
         *
         * @return bool Return true if success.
         */
        public function updatePairs()
        {
            $a = $this->updateBinancePairs();
            $b = $this->updateBybitPairs();
            $c = $this->updateFTXPairs();
            $ret = $a * $b * $c;
            return $ret;
        }

        /**
         * Update Wallet Balances for Binance.
         *
         * @return bool Return true if success.
         */
        public function updateBinanceBalances(bool $debug = false)
        {
            echo "Binance: Refreshing Wallet Balances...\n";
            $binanceWallet = $this->binance->getBalances();
            foreach ($binanceWallet['balances'] as $i) {
                if ($debug) {
                    echo $i['asset'] . ": " . $i['free'] . "\n";
                }
                $c = $this->sql->query("SELECT Count(*) as `total` FROM `binance_wallet` WHERE `ticker` = :ticker", array(':ticker' => $i['asset']));
                $count = $c->fetchObject();
                $vars = array(
                    ':ticker' => $i['asset'], ':free' => $i['free'], ':locked' => $i['locked']
                );
                if ($count->total != 0) {
                    $this->sql->query("UPDATE `binance_wallet` SET `free` = :free, `locked` = :locked WHERE `ticker` = :ticker", $vars);
                } else {
                    $this->sql->query("INSERT INTO `binance_wallet` (`id`, `ticker`, `free`, `locked`) VALUES (NULL, :ticker, :free, :locked)", $vars);
                }
            }
            return 1;
        }

        /**
         * Update Wallet Balances for Bybit.
         *
         * @return bool Return true if success.
         */
        public function updateBybitBalances(bool $debug = false)
        {
            echo "Bybit: Refreshing Wallet Balances...\n";
            $bybitWallet = $this->bybit->getBalances();
            foreach ($bybitWallet['result'] as $k => $i) {
                if ($debug) {
                    echo $k . ": " . $i['equity'] . "\n";
                }
                $c = $this->sql->query("SELECT Count(*) as `total` FROM `bybit_wallet` WHERE `ticker` = :ticker", array(':ticker' => $k));
                $count = $c->fetchObject();
                $vars = array(
                    ':ticker' => $k, ':equity' => $i['equity'], ':available' => $i['available_balance'],
                    ':used_margin' => $i['used_margin'], ':order_margin' => $i['order_margin'],
                    ':position_margin' => $i['position_margin'], ':occ_closing_fee' => $i['occ_closing_fee'],
                    ':occ_funding_fee' => $i['occ_funding_fee'], ':wallet_balance' => $i['wallet_balance'],
                    ':realized_pnl' => $i['realised_pnl'], ':unrealized_pnl' => $i['unrealised_pnl'],
                    ':total_realized_pnl' => $i['cum_realised_pnl'], ':given_cash' => $i['given_cash'],
                    ':service_cash' => $i['service_cash']
                );
                if ($count->total != 0) {
                    $this->sql->query("UPDATE `bybit_wallet` SET `equity` = :equity, `available` = :available, `used_margin` = :used_margin, `order_margin` = :order_margin, `position_margin` = :position_margin, `occ_closing_fee` = :occ_closing_fee, `occ_funding_fee` = :occ_funding_fee, `wallet_balance` = :wallet_balance, `realized_pnl` = :realized_pnl, `unrealized_pnl` = :unrealized_pnl, `total_realized_pnl` = :total_realized_pnl, `given_cash` = :given_cash, `service_cash` = :service_cash WHERE `ticker` = :ticker", $vars);
                } else {
                    $this->sql->query("INSERT INTO `bybit_wallet` (`id`, `ticker`, `equity`, `available`, `used_margin`, `order_margin`, `position_margin`, `occ_closing_fee`, `occ_funding_fee`, `wallet_balance`, `realized_pnl`, `unrealized_pnl`, `total_realized_pnl`, `given_cash`, `service_cash`) VALUES (NULL, :ticker, :equity, :available, :used_margin, :order_margin, :position_margin, :occ_closing_fee, :occ_funding_fee, :wallet_balance, :realized_pnl, :unrealized_pnl, :total_realized_pnl, :given_cash, :service_cash)", $vars);
                }
            }
            return 1;
        }

        /**
         * Update Wallet Balances for FTX.
         *
         * @return bool Return true if success.
         */
        public function updateFTXBalances(bool $debug = false)
        {
            echo "FTX: Refreshing Wallet Balances...\n";
            $ftxWallet = $this->ftx->getBalances();
            foreach ($ftxWallet['result'] as $i) {
                if ($debug) {
                    echo $i['coin'] . ": " . $i['total'] . "\n";
                }
                $c = $this->sql->query("SELECT Count(*) as `total` FROM `ftx_wallet` WHERE `ticker` = :ticker", array(':ticker' => $i['coin']));
                $count = $c->fetchObject();
                $sb = 0;
                if (array_key_exists('spot_borrow', $i)) {
                    $sb = $i['spot_borrow'];
                }
                $vars = array(
                    ':ticker' => $i['coin'], ':free' => $i['free'], ':spot_borrow' => $sb,
                    ':total' => $i['total'], ':available' => $i['availableWithoutBorrow']
                );
                if ($count->total != 0) {
                    $this->sql->query("UPDATE `ftx_wallet` SET `free` = :free, `spot_borrow` = :spot_borrow, `total` = :total, `available` = :available WHERE `ticker` = :ticker", $vars);
                } else {
                    $this->sql->query("INSERT INTO `ftx_wallet` (`id`, `ticker`, `free`, `spot_borrow`, `total`, `available`) VALUES (NULL, :ticker, :free, :spot_borrow, :total, :available)", $vars);
                }
            }
        }

        /**
         * Update Wallet Balances for all Exchanges.
         *
         * @return bool Return true if success.
         */
        public function updateBalances()
        {
            $a = $this->updateBinanceBalances();
            $b = $this->updateBybitBalances();
            $c = $this->updateFTXBalances();
            $ret = $a * $b * $c;
            return $ret;
        }
    }

}


/**
 * vim: ft=php sts=4 sw=4 ts=4 et:
 */
