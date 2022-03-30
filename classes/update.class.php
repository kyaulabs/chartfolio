<?php

/**
 * $KYAULabs: update.class.php,v 1.0.4 2022/03/28 16:36:17 kyau Exp $
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

        /**
         * Update Trades for Binance.
         *
         * @return bool Return true if success.
         */
        public function updateBinanceTrades(bool $debug = false)
        {
            echo "Refreshing Binance Trade History...\n";
            $ret = $this->sql->query("SELECT `pair` FROM `binance_pairs` WHERE `watch` = 1");
            $symbols = $ret->fetchAll();
            foreach ($symbols as $s) {
                $symbol = $s['pair'];
                if ($debug) {
                    echo $symbol . "\n";
                }
                $binanceTrades = $this->binance->getTrades($symbol);
                foreach ($binanceTrades as $i) {
                    $c = $this->sql->query("SELECT Count(*) as `total` FROM `binance_trade_history` WHERE `pair` = :pair AND `order_id` = :order_id", array(':pair' => $i['symbol'], ':order_id' => $i['orderId']));
                    $count = $c->fetchObject();
                    if ($count->total == 0) {
                        if ($debug) {
                            echo $i['symbol'] . ": " . $i['price'] . " * " . $i['qty'] . " (" . $i['orderId'] . ")\n";
                        }
                        $order = $this->binance->getOrder($symbol, $i['orderId']);
                        $isMaker = "TAKER";
                        if ($i['isMaker']) {
                            $isMaker = "MAKER";
                        }
                        $vars = array(
                            ':pair' => $i['symbol'], ':order_id' => $i['orderId'], 'price' => $i['price'],
                            ':quantity' => $order['executedQty'], ':type' => $order['type'], ':type_market' => $isMaker,
                            ':type_side' => $order['side'], ':stop_price' => $order['stopPrice'], ':datetime' => substr($order['updateTime'], 0, -3),
                            ':commission' => $i['commission'], ':commission_ticker' => $i['commissionAsset'], ':status' => $order['status']
                        );
                        $this->sql->query("INSERT INTO `binance_trade_history` (`id`, `pair`, `order_id`, `price`, `quantity`, `type`, `type_market`, `type_side`, `stop_price`, `datetime`, `commission`, `commission_ticker`, `status`) VALUES (NULL, :pair, :order_id, :price, :quantity, :type, :type_market, :type_side, :stop_price, FROM_UNIXTIME(:datetime), :commission, :commission_ticker, :status)", $vars);
                    }
                }
            }
            return 1;
        }

        /**
         * Update Trades for Bybit.
         *
         * @return bool Return true if success.
         */
        public function updateBybitTrades(bool $debug = false)
        {
            echo "Refreshing Bybit Trade History...\n";
            $ret = $this->sql->query("SELECT `pair` FROM `bybit_pairs` WHERE `watch` = 1");
            $symbols = $ret->fetchAll();
            foreach ($symbols as $s) {
                $symbol = $s['pair'];
                if ($debug) {
                    echo $symbol . "\n";
                }
                $bybitTrades = $this->bybit->getTrades($symbol);
                foreach ($bybitTrades['result']['data'] as $i) {
                    $c = $this->sql->query("SELECT Count(*) as `total` FROM `bybit_trade_history` WHERE `pair` = :pair AND `order_id` = UUID_TO_BIN(:order_id)", array(':pair' => $i['symbol'], ':order_id' => $i['order_id']));
                    $count = $c->fetchObject();
                    if ($count->total == 0) {
                        if ($debug) {
                            echo $i['symbol'] . ": " . $i['order_price'] . " * " . $i['qty'] . " (" . $i['order_id'] . ")\n";
                        }
                        $vars = array(
                            ':pair' => $i['symbol'], ':order_id' => $i['order_id'], 'price' => $i['order_price'],
                            ':quantity' => $i['qty'], ':type' => $i['order_type'], ':type_side' => $i['side'],
                            ':exec_type' => $i['exec_type'], ':closed_quantity' => $i['closed_size'],
                            ':avg_entry' => $i['avg_entry_price'], ':avg_exit' => $i['avg_exit_price'],
                            ':datetime' => substr($i['created_at'], 0, -3), ':leverage' => $i['leverage'],
                            ':closed_pnl' => $i['closed_pnl']
                        );
                        $this->sql->query("INSERT INTO `bybit_trade_history` (`id`, `pair`, `order_id`, `price`, `quantity`, `type`, `type_side`, `exec_type`, `closed_quantity`, `avg_entry`, `avg_exit`, `datetime`, `leverage`, `closed_pnl`) VALUES (NULL, :pair, UUID_TO_BIN(:order_id), :price, :quantity, :type, :type_side, :exec_type, :closed_quantity, :avg_entry, :avg_exit, FROM_UNIXTIME(:datetime), :leverage, :closed_pnl)", $vars);
                    }
                }
            }
            return 1;
        }

        /**
         * Update Trades for FTX.
         *
         * @return bool Return true if success.
         */
        public function updateFTXTrades(bool $debug = false)
        {
            echo "Refreshing FTX Trade History...\n";
            $ret = $this->sql->query("SELECT `pair` FROM `ftx_pairs` WHERE `watch` = 1");
            $symbols = $ret->fetchAll();
            foreach ($symbols as $s) {
                $symbol = $s['pair'];
                if ($debug) {
                    echo $symbol . "\n";
                }
                $ftxTrades = $this->ftx->getTrades($symbol);
                foreach ($ftxTrades['result'] as $i) {
                    $c = $this->sql->query("SELECT Count(*) as `total` FROM `ftx_trade_history` WHERE `pair` = :pair AND `order_id` = :order_id", array(':pair' => $i['market'], ':order_id' => $i['id']));
                    $count = $c->fetchObject();
                    if ($count->total == 0) {
                        if ($debug) {
                            echo $i['market'] . ": " . $i['price'] . " * " . $i['size'] . " (" . $i['id'] . ")\n";
                        }
                        $vars = array(
                            ':pair' => $i['market'], ':order_id' => $i['id'], ':price' => $i['price'],
                            ':quantity' => $i['size'], ':type' => strtoupper($i['type']), ':type_side' => strtoupper($i['side']),
                            ':datetime' => substr($i['createdAt'], 0, -13), ':status' => strtoupper($i['status'])
                        );
                        $this->sql->query("INSERT INTO `ftx_trade_history` (`id`, `pair`, `order_id`, `price`, `quantity`, `type`, `type_side`, `datetime`, `status`) VALUES(NULL, :pair, :order_id, :price, :quantity, :type, :type_side, :datetime, :status)", $vars);
                    }
                }
            }
            return 1;
        }

        /**
         * Update Trade History for all Exchanges.
         *
         * @return bool Return true if success.
         */
        public function updateTrades()
        {
            $a = $this->updateBinanceTrades();
            $b = $this->updateBybitTrades();
            $c = $this->updateFTXTrades();
            $ret = $a * $b * $c;
            return $ret;
        }

        /**
         * Update Deposits for Binance.
         *
         * @return bool Return true if success.
         */
        public function updateBinanceDeposits(bool $debug = false)
        {
            echo "Refreshing Binance Deposit History...\n";
            $ret = $this->sql->query("SELECT `ticker_base`, `ticker_quote` FROM `binance_pairs` WHERE `watch` = 1");
            $symbols = $ret->fetchAll();
            $tickers = array();
            foreach ($symbols as $s) {
                $ticka = $s['ticker_base'];
                $tickb = $s['ticker_quote'];
                if (! in_array($ticka, $tickers) ) {
                    array_push($tickers, $ticka);
                }
                if (! in_array($tickb, $tickers) ) {
                    array_push($tickers, $tickb);
                }
            }
            foreach ($tickers as $t) {
                $binanceDeposits = $this->binance->getDeposits($t);
                foreach ($binanceDeposits as $i) {
                    $c = $this->sql->query("SELECT Count(*) as `total` FROM `binance_deposits` WHERE `ticker` = :ticker AND `tx_id` = :tx_id", array(':ticker' => $i['coin'], ':tx_id' => $i['txId']));
                    $count = $c->fetchObject();
                    if ($count->total == 0) {
                        if ($debug) {
                            echo $i['coin'] . ": " . $i['amount'] . " (" . $i['network'] . " - " . $i['address'] . ")\n";
                        }
                        $vars = array(
                            ':ticker' => $i['coin'], ':quantity' => $i['amount'], ':address_recv' => $i['address'],
                            ':network' => $i['network'], ':tx_id' => $i['txId'], ':datetime' => substr($i['insertTime'], 0, -3),
                            ':confirmations' => $i['confirmTimes']
                        );
                        $this->sql->query("INSERT INTO `binance_deposits` (`id`, `ticker`, `quantity`, `address_recv`, `network`, `tx_id`, `datetime`, `confirmations`) VALUES (NULL, :ticker, :quantity, :address_recv, :network, :tx_id, FROM_UNIXTIME(:datetime), :confirmations)", $vars);
                    }
                }
            }
            return 1;
        }

        /**
         * Update Deposits for Bybit.
         *
         * @return bool Return true if success.
         */
        public function updateBybitDeposits(bool $debug = false)
        {
            echo "Refreshing Bybit Deposit History...\n";
            $bybitDeposits = $this->bybit->getDeposits();
            foreach ($bybitDeposits['result']['list'] as $i) {
                $c = $this->sql->query("SELECT Count(*) as `total` FROM `bybit_deposits` WHERE `ticker` = :ticker AND `tx_id` = :tx_id", array(':ticker' => $i['coin'], ':tx_id' => $i['transfer_id']));
                $count = $c->fetchObject();
                if ($count->total == 0) {
                    if ($debug) {
                        echo $i['coin'] . ": " . $i['amount'] . " (" . $i['transfer_id'] . ")\n";
                    }
                    $vars = array(
                        ':ticker' => $i['coin'], ':quantity' => $i['amount'], ':tx_id' => $i['transfer_id'],
                        ':datetime' => $i['timestamp']
                    );
                    $this->sql->query("INSERT INTO `bybit_deposits` (`id`, `ticker`, `quantity`, `tx_id`, `datetime`) VALUES(NULL, :ticker, :quantity, :tx_id, FROM_UNIXTIME(:datetime))", $vars);
                }
            }
            return 1;
        }

        /**
         * Update Deposits for FTX.
         *
         * @return bool Return true if success.
         */
        public function updateFTXDeposits(bool $debug = false)
        {
            echo "Refreshing FTX Deposit History...\n";
            $ftxDeposits = $this->ftx->getDeposits();
            foreach ($ftxDeposits['result'] as $i) {
                if (array_key_exists('fiat', $i)) {
                    $c = $this->sql->query("SELECT Count(*) as `total` FROM `ftx_deposits` WHERE `ticker` = :ticker AND `payment_id` = :payment_id", array(':ticker' => $i['coin'], ':payment_id' => $i['paymentId']));
                } else {
                    $c = $this->sql->query("SELECT Count(*) as `total` FROM `ftx_deposits` WHERE `ticker` = :ticker AND `tx_id` = :tx_id", array(':ticker' => $i['coin'], ':tx_id' => $i['txid']));
                }
                $count = $c->fetchObject();
                if ($count->total == 0) {
                    if ($debug) {
                        if (array_key_exists('fiat', $i)) {
                            echo $i['coin'] . ": " . $i['size'] . " (" . strtoupper($i['type']) . " - " . $i['paymentId'] . ")\n";
                        } else {
                            echo $i['coin'] . ": " . $i['size'] . " (" . strtoupper($i['address']['method']) . " - " . $i['txid'] . ")\n";
                        }
                    }
                    if (array_key_exists('fiat', $i)) {
                        $vars = array(
                            ':ticker' => $i['coin'], ':quantity' => $i['size'], 'fee' => $i['fee'],
                            ':network' => strtoupper($i['type']), ':payment_id' => $i['paymentId'],
                            ':datetime' => substr($i['creditedAt'], 0, -13), ':status' => strtoupper($i['status'])
                        );
                        $this->sql->query("INSERT INTO `ftx_deposits` (`id`, `ticker`, `quantity`, `fee`, `network`, `payment_id`, `datetime`, `status`) VALUES(NULL, :ticker, :quantity, :fee, :network, UUID_TO_BIN(:payment_id), :datetime, :status)", $vars);
                    } else {
                        $vars = array(
                            ':ticker' => $i['coin'], ':quantity' => $i['size'], 'fee' => $i['fee'],
                            ':network' => strtoupper($i['address']['method']), ':tx_id' => $i['txid'],
                            ':datetime' => substr($i['confirmedTime'], 0, -13), ':confirmations' => $i['confirmations'],
                            ':status' => strtoupper($i['status'])
                        );
                        $this->sql->query("INSERT INTO `ftx_deposits` (`id`, `ticker`, `quantity`, `fee`, `network`, `tx_id`, `datetime`, `confirmations`, `status`) VALUES(NULL, :ticker, :quantity, :fee, :network, :tx_id, :datetime, :confirmations, :status)", $vars);
                    }
                }
            }
            return 1;
        }

        /**
         * Update Deposit History for all Exchanges.
         *
         * @return bool Return true if success.
         */
        public function updateDeposits()
        {
            $a = $this->updateBinanceDeposits();
            $b = $this->updateBybitDeposits();
            $c = $this->updateFTXDeposits();
            $ret = $a * $b * $c;
            return $ret;
        }

        /**
         * Update Withdrawal History for all Exchanges.
         *
         * @return bool Return true if success.
         */
        public function updateWithdrawals()
        {
            $a = $this->updateBinanceWithdrawals();
            $b = $this->updateBybitWithdrawals();
            $c = $this->updateFTXWithdrawals();
            $ret = $a * $b * $c;
            return $ret;
        }
    }

}


/**
 * vim: ft=php sts=4 sw=4 ts=4 et:
 */
