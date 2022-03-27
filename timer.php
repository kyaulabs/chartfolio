<?php

/**
 * $KYAULabs: timer.php,v 1.0.4 2022/03/27 00:35:19 kyau Exp $
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

ini_set('default_charset', 'UTF-8');
date_default_timezone_set('UTC');

include_once('../aurora/sql.inc.php');
include_once('chartfolio.php');

$sql = new \KYAULabs\SQLHandler('chartfolio');
$binance = new \APIs\Binance();
$bybit = new \APIs\Bybit();
$ftx = new \APIs\FTX();


/**
 * Update Wallets
 */
$binanceWallet = $binance->getBalances();
foreach ($binanceWallet['balances'] as $i) {
    //echo $i['asset'] . ": " . $i['free'] . "\n";
    $c = $sql->query("SELECT Count(*) as `total` FROM `binance_wallet` WHERE `ticker` = :ticker", array(':ticker' => $i['asset']));
    $count = $c->fetchObject();
    $vars = array(
        ':ticker' => $i['asset'], ':free' => $i['free'], ':locked' => $i['locked']
    );
    if ($count->total != 0) {
        $sql->query("UPDATE `binance_wallet` SET `free` = :free, `locked` = :locked WHERE `ticker` = :ticker", $vars);
    } else {
        $sql->query("INSERT INTO `binance_wallet` (`id`, `ticker`, `free`, `locked`) VALUES (NULL, :ticker, :free, :locked)", $vars);
    }
}
$bybitWallet = $bybit->getBalances();
foreach ($bybitWallet['result'] as $k => $i) {
    //echo $k . ": " . $i['equity'] . "\n";
    $c = $sql->query("SELECT Count(*) as `total` FROM `bybit_wallet` WHERE `ticker` = :ticker", array(':ticker' => $k));
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
        $sql->query("UPDATE `bybit_wallet` SET `equity` = :equity, `available` = :available, `used_margin` = :used_margin, `order_margin` = :order_margin, `position_margin` = :position_margin, `occ_closing_fee` = :occ_closing_fee, `occ_funding_fee` = :occ_funding_fee, `wallet_balance` = :wallet_balance, `realized_pnl` = :realized_pnl, `unrealized_pnl` = :unrealized_pnl, `total_realized_pnl` = :total_realized_pnl, `given_cash` = :given_cash, `service_cash` = :service_cash WHERE `ticker` = :ticker", $vars);
    } else {
        $sql->query("INSERT INTO `bybit_wallet` (`id`, `ticker`, `equity`, `available`, `used_margin`, `order_margin`, `position_margin`, `occ_closing_fee`, `occ_funding_fee`, `wallet_balance`, `realized_pnl`, `unrealized_pnl`, `total_realized_pnl`, `given_cash`, `service_cash`) VALUES (NULL, :ticker, :equity, :available, :used_margin, :order_margin, :position_margin, :occ_closing_fee, :occ_funding_fee, :wallet_balance, :realized_pnl, :unrealized_pnl, :total_realized_pnl, :given_cash, :service_cash)", $vars);
    }
}

$ftxWallet = $ftx->getBalances();
foreach ($ftxWallet['result'] as $i) {
    //echo $i['coin'] . ": " . $i['total'] . "\n";
    $c = $sql->query("SELECT Count(*) as `total` FROM `ftx_wallet` WHERE `ticker` = :ticker", array(':ticker' => $i['coin']));
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
        $sql->query("UPDATE `ftx_wallet` SET `free` = :free, `spot_borrow` = :spot_borrow, `total` = :total, `available` = :available WHERE `ticker` = :ticker", $vars);
    } else {
        $sql->query("INSERT INTO `ftx_wallet` (`id`, `ticker`, `free`, `spot_borrow`, `total`, `available`) VALUES (NULL, :ticker, :free, :spot_borrow, :total, :available)", $vars);
    }
}


/**
 * vim: ft=php sts=4 sw=4 ts=4 et:
 */
