<?php

/**
 * $KYAULabs: timer.php,v 1.0.7 2022/03/30 23:13:38 kyau Exp $
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

define('SYSTIME', date('i'));

include_once('../aurora/sql.inc.php');
include_once('chartfolio.php');

$sql = new \KYAULabs\SQLHandler('chartfolio');
$binance = new \APIs\Binance();
$bybit = new \APIs\Bybit();
$ftx = new \APIs\FTX();
$timer = new \Chartfolio\Update($binance, $bybit, $ftx, $sql);

$timer->logo();
$timer->updatePairs();
$timer->updateBalances();
$timer->updateTrades();
$timer->updateDeposits();
$timer->updateWithdrawals();
exit;

switch (SYSTIME) {
    case 0:
        // every 5 minutes
        $timer->updateBalances();
        $timer->updateTrades();
        // every 15 minutes
        $timer->updateDeposits();
        $timer->updateWithdrawals();
        // hourly
        $timer->updatePairs();
        break;
    case 5:
    case 10:
    case 20:
    case 25:
    case 35:
    case 40:
    case 50:
    case 55:
        // every 5 minutes
        $timer->updateBalances();
        $timer->updateTrades();
        break;
    case 15:
    case 30:
    case 45:
        // every 15 minutes
        $timer->updateDeposits();
        $timer->updateWithdrawals();
        break;
    default:
        break;
}


/**
 * vim: ft=php sts=4 sw=4 ts=4 et:
 */
