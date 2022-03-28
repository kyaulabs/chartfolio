-- $KYAULabs: chartfolio.sql,v 1.1.2 2022/03/28 13:45:54 kyau Exp $
-- ▄▄▄▄ ▄▄▄▄ ▄▄▄▄▄▄▄▄▄ ▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄
-- █ ▄▄ ▄ ▄▄ ▄ ▄▄▄▄ ▄▄ ▄    ▄▄   ▄▄▄▄ ▄▄▄▄  ▄▄▄ ▀
-- █ ██ █ ██ █ ██ █ ██ █    ██   ██ █ ██ █ ██▀  █
-- ■ ██▄▀ ██▄█ ██▄█ ██ █ ▀▀ ██   ██▄█ ██▄▀ ▀██▄ ■
-- █ ██ █ ▄▄ █ ██ █ ██ █    ██▄▄ ██ █ ██ █  ▄██ █
-- ▄ ▀▀ ▀ ▀▀▀▀ ▀▀ ▀ ▀▀▀▀    ▀▀▀▀ ▀▀ ▀ ▀▀▀▀ ▀▀▀  █
-- ▀▀▀▀▀▀▀▀▀▀▀▀▀▀ ▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀ ▀▀▀▀▀▀▀▀▀▀▀▀▀
--
-- Chartfolio SQL Database
-- Copyright (C) 2022 KYAU Labs (https://kyaulabs.com)
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU Affero General Public License as
-- published by the Free Software Foundation, either version 3 of the
-- License, or (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU Affero General Public License for more details.
--
-- You should have received a copy of the GNU Affero General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.

--
-- Set timezone to UTC
--
SET time_zone = "+00:00";

--
-- Database: `chartfolio`
--
CREATE DATABASE IF NOT EXISTS `chartfolio` DEFAULT CHARACTER SET ascii COLLATE ascii_general_ci;
USE `chartfolio`;

-- --------------------------------------------------------

--
-- Functions
--
DELIMITER //

CREATE FUNCTION BIN_TO_UUID(b BINARY(16))
RETURNS CHAR(36)
BEGIN
   DECLARE hexStr CHAR(32);
   SET hexStr = HEX(b);
   RETURN LOWER(CONCAT(
        SUBSTR(hexStr, 1, 8), '-',
        SUBSTR(hexStr, 9, 4), '-',
        SUBSTR(hexStr, 13, 4), '-',
        SUBSTR(hexStr, 17, 4), '-',
        SUBSTR(hexStr, 21)
    ));
END//

CREATE FUNCTION UUID_TO_BIN(uuid CHAR(36))
RETURNS BINARY(16)
BEGIN
    RETURN UNHEX(REPLACE(uuid, '-', ''));
END//

DELIMITER ;


-- --------------------------------------------------------

--
-- Data input structure for table `binance_pairs`
--
/*
:/api/v3/account
{
  "makerCommission": 10,
  "takerCommission": 10,
  "buyerCommission": 0,
  "sellerCommission": 0,
  "canTrade": true,
  "canWithdraw": true,
  "canDeposit": true,
  "updateTime": 1646528386842,
  "accountType": "SPOT",
  ...
}

:curl "https://api.binance.us/api/v3/exchangeInfo"
{
  "timezone": "UTC",
  "serverTime": 1646610937394,
  "rateLimits": [ ... ],
  "exchangeFilters": [],
  "symbols": [
    {
      "symbol": "BTCUSD",
      "status": "TRADING",
      "baseAsset": "BTC",
      "baseAssetPrecision": 8,
      "quoteAsset": "USD",
      "quotePrecision": 4,
      "quoteAssetPrecision": 4,
      "baseCommissionPrecision": 8,
      "quoteCommissionPrecision": 2,
      "orderTypes": [
        "LIMIT",
        "LIMIT_MAKER",
        "MARKET",
        "STOP_LOSS_LIMIT",
        "TAKE_PROFIT_LIMIT"
      ],
      "icebergAllowed": true,
      "ocoAllowed": true,
      "quoteOrderQtyMarketAllowed": true,
      "allowTrailingStop": false,
      "isSpotTradingAllowed": true,
      "isMarginTradingAllowed": false,
      "filters": [
        {
          "filterType": "PRICE_FILTER",
          "minPrice": "0.0100",
          "maxPrice": "100000.0000",
          "tickSize": "0.0100"
        },
        {
          "filterType": "PERCENT_PRICE",
          "multiplierUp": "5",
          "multiplierDown": "0.2",
          "avgPriceMins": 5
        },
        {
          "filterType": "LOT_SIZE",
          "minQty": "0.00000100",
          "maxQty": "9000.00000000",
          "stepSize": "0.00000100"
        },
        {
          "filterType": "MIN_NOTIONAL",
          "minNotional": "10.0000",
          "applyToMarket": true,
          "avgPriceMins": 5
        },
        {
          "filterType": "ICEBERG_PARTS",
          "limit": 10
        },
        {
          "filterType": "MARKET_LOT_SIZE",
          "minQty": "0.00000000",
          "maxQty": "60.25597724",
          "stepSize": "0.00000000"
        },
        {
          "filterType": "MAX_NUM_ORDERS",
          "maxNumOrders": 200
        },
        {
          "filterType": "MAX_NUM_ALGO_ORDERS",
          "maxNumAlgoOrders": 5
        }
      ],
      "permissions": [
        "SPOT"
      ]
    },
    ...
  ]
}

:curl -X "GET" "https://api.binance.us/api/v3/ticker/price?symbol=BTCUSD"
{
  "symbol": "BTCUSD",
  "price": "38308.8600"
}

:curl -X "GET" "https://api.binance.us/api/v3/ticker/24hr?symbol=BTCUSD"
{
  "symbol": "BTCUSD",
  "priceChange": "49.3800",
  "priceChangePercent": "0.129",
  "weightedAvgPrice": "38439.2505",
  "prevClosePrice": "38242.3500",
  "lastPrice": "38303.4300",
  "lastQty": "0.00026400",
  "bidPrice": "38301.5000",
  "bidQty": "0.00199300",
  "askPrice": "38303.4300",
  "askQty": "0.00128500",
  "openPrice": "38254.0500",
  "highPrice": "39364.7900",
  "lowPrice": "37577.6200",
  "volume": "620.95864900",
  "quoteVolume": "23869185.0357",
  "openTime": 1646567036518,
  "closeTime": 1646653436518,
  "firstId": 30726700,
  "lastId": 30765923,
  "count": 39224
}
*/

--
-- Table structure for table `binance_pairs`
--
CREATE TABLE `binance_pairs` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Pair ID',
	`pair` varchar(16) NOT NULL COMMENT 'Pair Symbol',
	`watch` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Pair is Traded, Lookup Orders',
	`ticker_base` varchar(16) NOT NULL COMMENT 'Pair Base Ticker',
	`ticker_quote` varchar(16) NOT NULL COMMENT 'Pair Quote Ticker',
	`scale_base` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Base Ticker Max Decimal Places',
	`scale_quote` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Quote Ticker Max Decimal Places',
	`fee_maker` decimal(8,5) signed NOT NULL DEFAULT 0 COMMENT 'Maker Fee',
    -- IF BNB > 0 (makerCommission / 1000) - (makerCommission * 0.25)
	-- ELSE (makerCommission / 1000)
	`fee_taker` decimal(8,5) signed NOT NULL DEFAULT 0 COMMENT 'Taker Fee',
    -- IF BNB > 0 (takerCommission / 1000) - (takerCommission * 0.25)
	-- ELSE (takerCommission / 1000)
	`mark_price` decimal(19,4) signed NOT NULL DEFAULT 0 COMMENT 'Current Market Price',
	`price_24h_pcnt` decimal(10,6) signed NOT NULL DEFAULT 0 COMMENT 'Percentage Change of Market Price Relative to 24h',
	`volume_24h` int(16) unsigned NOT NULL DEFAULT 0 COMMENT 'Trading Volume Relative to 24h',
	PRIMARY KEY (`id`),
	UNIQUE KEY `pair` (`pair`),
	KEY `mark_price` (`mark_price`),
	KEY `watch` (`watch`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;

--
-- Dumping data for table `binance_pairs`
--

--LOCK TABLES `binance_pairs` WRITE;
--INSERT INTO `binance_pairs` VALUES (NULL,'BTCUSD','BTC','USD',8,4,0.01,0.01,38308.8600,0.129,23869185.0357);
--UNLOCK TABLES;


--
-- Data input structure for table `bybit_pairs`
--
/*
:curl https://api-testnet.bybit.com/v2/public/symbols
{
  "ret_code": 0,
  "ret_msg": "OK",
  "ext_code": "",
  "ext_info": "",
  "result": [
    {
      "name": "BTCUSD",
      "alias": "BTCUSD",
      "status": "Trading",
      "base_currency": "BTC",
      "quote_currency": "USD",
      "price_scale": 2,
      "taker_fee": "0.00075",
      "maker_fee": "-0.00025",
      "funding_interval": 480,
      "leverage_filter": {
        "min_leverage": 1,
        "max_leverage": 100,
        "leverage_step": "0.01"
      },
      "price_filter": {
        "min_price": "0.5",
        "max_price": "999999",
        "tick_size": "0.5"
      },
      "lot_size_filter": {
        "max_trading_qty": 1000000,
        "min_trading_qty": 1,
        "qty_step": 1
      }
    },
    ...
  ]
}

:/v2/public/tickers
{
  "ret_code": 0,
  "ret_msg": "OK",
  "ext_code": "",
  "ext_info": "",
  "result": [
    {
      "symbol": "BTCUSD",
      "bid_price": "38622.5",
      "ask_price": "38623",
      "last_price": "38622.50",
      "last_tick_direction": "ZeroMinusTick",
      "prev_price_24h": "37866.00",
      "price_24h_pcnt": "0.019978",
      "high_price_24h": "39520.00",
      "low_price_24h": "37151.50",
      "prev_price_1h": "38679.00",
      "price_1h_pcnt": "-0.00146",
      "mark_price": "38637.79",
      "index_price": "38637.31",
      "open_interest": 616586920,
      "open_value": "14616.80",
      "total_turnover": "102255566.23",
      "turnover_24h": "46631.45",
      "total_volume": 2614625970389,
      "volume_24h": 1785419476,
      "funding_rate": "0.000024",
      "predicted_funding_rate": "0.000069",
      "next_funding_time": "2022-03-08T08:00:00Z",
      "countdown_hour": 5,
      "delivery_fee_rate": "0",
      "predicted_delivery_price": "",
      "delivery_time": ""
    }
  ],
  "time_now": "1646711534.019723"
}
*/

--
-- Table structure for table `bybit_pairs`
--
CREATE TABLE `bybit_pairs` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Pair ID',
	`pair` varchar(16) NOT NULL COMMENT 'Pair Symbol',
	`watch` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Pair is Traded, Lookup Orders',
	`ticker_base` varchar(16) NOT NULL COMMENT 'Pair Base Ticker',
	`ticker_quote` varchar(16) NOT NULL COMMENT 'Pair Quote Ticker',
	`scale_quote` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Quote Ticker Max Decimal Places',
	`fee_maker` decimal(8,5) signed NOT NULL DEFAULT 0 COMMENT 'Maker Fee',
	`fee_taker` decimal(8,5) signed NOT NULL DEFAULT 0 COMMENT 'Taker Fee',
	`leverage_min` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Minimum Leverage',
	`leverage_max` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Maximum Leverage',
	`leverage_step` decimal(5,2) unsigned NOT NULL DEFAULT 0 COMMENT 'Leverage Step',
	`funding_rate` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Amount Locked in Trades',
	`mark_price` decimal(19,4) signed NOT NULL DEFAULT 0 COMMENT 'Current Market Price',
	`price_24h_pcnt` decimal(10,6) signed NOT NULL DEFAULT 0 COMMENT 'Percentage Change of Market Price Relative to 24h',
	`volume_24h` int(16) unsigned NOT NULL DEFAULT 0 COMMENT 'Trading Volume Relative to 24h',
	PRIMARY KEY (`id`),
	UNIQUE KEY `pair` (`pair`),
	KEY `mark_price` (`mark_price`),
	KEY `watch` (`watch`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;

--
-- Dumping data for table `bybit_pairs`
--

--LOCK TABLES `bybit_pairs` WRITE;
--INSERT INTO `bybit_pairs` VALUES (NULL,'BTCUSD','BTC','USD',2,-0.00025,0.00075,0,100,0.01,480,38637.79,0.019978,1785419476);
--UNLOCK TABLES;


--
-- Data input structure for table `ftx_pairs`
--
/*
:/markets
{
  "success": true,
  "result": [
    {
      "name": "AAVE/USD",
      "enabled": true,
      "postOnly": false,
      "priceIncrement": 0.01,
      "sizeIncrement": 0.01,
      "minProvideSize": 0.01,
      "last": 119.39,
      "bid": 119.38,
      "ask": 119.64,
      "price": 119.39,
      "type": "spot",
      "baseCurrency": "AAVE",
      "quoteCurrency": "USD",
      "underlying": null,
      "restricted": false,
      "highLeverageFeeExempt": true,
      "largeOrderThreshold": 2000,
      "change1h": 0.008106054209237525,
      "change24h": 0.04007317710601969,
      "changeBod": 0.020689065572368985,
      "quoteVolume24h": 86564.1952,
      "volumeUsd24h": 86564.1952
    },
    ...
  ]
}
*/

--
-- Table structure for table `ftx_pairs`
--
CREATE TABLE `ftx_pairs` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Pair ID',
	`pair` varchar(16) NOT NULL COMMENT 'Pair Symbol',
	`watch` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Pair is Traded, Lookup Orders',
	`ticker_base` varchar(16) NOT NULL COMMENT 'Pair Base Ticker',
	`ticker_quote` varchar(16) NOT NULL COMMENT 'Pair Quote Ticker',
	`scale_quote` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Quote Ticker Max Decimal Places',
	`fee_maker` decimal(8,5) signed NOT NULL DEFAULT 0 COMMENT 'Maker Fee',
	`fee_taker` decimal(8,5) signed NOT NULL DEFAULT 0 COMMENT 'Taker Fee',
	`mark_price` decimal(19,4) signed NOT NULL DEFAULT 0 COMMENT 'Current Market Price',
	`price_24h_pcnt` decimal(10,6) signed NOT NULL DEFAULT 0 COMMENT 'Percentage Change of Market Price Relative to 24h',
	`volume_24h` int(16) unsigned NOT NULL DEFAULT 0 COMMENT 'Trading Volume Relative to 24h',
	PRIMARY KEY (`id`),
	UNIQUE KEY `pair` (`pair`),
	KEY `mark_price` (`mark_price`),
	KEY `watch` (`watch`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;

--
-- Dumping data for table `ftx_pairs`
--

--LOCK TABLES `ftx_pairs` WRITE;
--INSERT INTO `ftx_pairs` VALUES (NULL,'BTCUSDT','BTC','USDT',2,0,0,39000,0.04007317710601969,86564);
--UNLOCK TABLES;


-- --------------------------------------------------------

--
-- Data input structure for table `binance_wallet`
--
/*
:/api/v3/account
{
  "balances": [
    {
      "asset": "BTC",
      "free": "0.00000082",
      "locked": "0.01500000"
    },
    ...
  ]
}
*/

--
-- Table structure for table `binance_wallet`
--
CREATE TABLE `binance_wallet` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Asset ID',
	`ticker` varchar(16) NOT NULL COMMENT 'Asset Ticker',
	`free` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Amount Available',
	`locked` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Amount Locked in Trades',
	PRIMARY KEY (`id`),
	UNIQUE KEY `ticker` (`ticker`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;

--
-- Dumping data for table `binance_wallet`
--

--LOCK TABLES `binance_wallet` WRITE;
--INSERT INTO `binance_wallet` VALUES (NULL,'USD',0,0,0);
--UNLOCK TABLES;


--
-- Data input structure for table `bybit_wallet`
--
/*
:/v2/private/wallet/balance
{
  "result": {
	"USDT": {
	  "equity": 494.16599116,
	  "available_balance": 494.16599116,
	  "used_margin": 0,
	  "order_margin": 0,
	  "position_margin": 0,
	  "occ_closing_fee": 0,
	  "occ_funding_fee": 0,
	  "wallet_balance": 494.16599116,
	  "realised_pnl": 0,
	  "unrealised_pnl": 0,
	  "cum_realised_pnl": 0,
	  "given_cash": 0,
	  "service_cash": 0
	},
	...
  }
}
*/

--
-- Table structure for table `bybit_wallet`
--
CREATE TABLE `bybit_wallet` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Asset ID',
	`ticker` varchar(16) NOT NULL COMMENT 'Asset Ticker',
	`equity` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'equity = wallet_balance + unrealized_pnl',
	`available` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'available_balance',
	`used_margin` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'used_margin = wallet_balance - available_balance',
	`order_margin` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Used margin by order',
	`position_margin` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Position margin',
	`occ_closing_fee` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Position closing fee',
	`occ_funding_fee` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Funding fee',
	`wallet_balance` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Wallet balance (When in Cross Margin mod, the number minus your unclosed loss is your real wallet balance)',
	`realized_pnl` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Daily realized profit and loss',
	`unrealized_pnl` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Unrealized profit and loss',
		-- when side is sell:
		--		unrealized_pnl = size * (1.0 / mark_price - 1.0 / entry_price)
		-- when side is buy:
		--		unrealized_pnl = size * (1.0 / entry_price - 1.0 / mark_price)
	`total_realized_pnl` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Total realized profit and loss',
	`given_cash` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Experience gold',
	`service_cash` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Service cash is used for user\'s service charge',
	PRIMARY KEY (`id`),
	UNIQUE KEY `ticker` (`ticker`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;

--
-- Dumping data for table `bybit_wallet`
--

--LOCK TABLES `bybit_wallet` WRITE;
--INSERT INTO `bybit_wallet` VALUES (NULL,'USDT',0,0,0,0,0,0,0,0,0,0,0,0,0,0);
--UNLOCK TABLES;


--
-- Data input structure for table `ftx_wallet`
--
/*
:/api/wallet/balances
{
  "success": true,
  "result": {
    {
      "coin": "ETH",
      "total": 0.01201069,
      "free": 1.069e-05,
      "availableWithoutBorrow": 1.069e-05,
      "usdValue": 31.48123414257851,
      "spotBorrow": 0
    },
    ...
  }
}
*/

--
-- Table structure for table `ftx_wallet`
--
CREATE TABLE `ftx_wallet` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Asset ID',
	`ticker` varchar(16) NOT NULL COMMENT 'Asset Ticker',
	`free` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Free amount',
	`spot_borrow` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Amount borrowed using spot margin',
	`total` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Total amount',
	`available` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Amount available without borrowing',
	PRIMARY KEY (`id`),
	UNIQUE KEY `ticker` (`ticker`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;

--
-- Dumping data for table `ftx_wallet`
--

--LOCK TABLES `ftx_wallet` WRITE;
--INSERT INTO `ftx_wallet` VALUES (NULL,'USD',0,0,0,0,0);
--UNLOCK TABLES;


-- --------------------------------------------------------

--
-- Data input structure for table `binance_trade_history`
--
/*
:/api/v3/myTrades
[
  {
    "symbol": "BTCUSD",
    "id": 30711794,
	"orderId": 891597075,
    "orderListId": -1,
	"price": "39675.0000",
    "qty": "0.00100000",
    "quoteQty": "39.6750",
    "commission": "0.00007751",
    "commissionAsset": "BNB",
    "time": 1646528386842,
    "isBuyer": false,
    "isMaker": true,
    "isBestMatch": true
  },
  ...
]

:/api/v3/order
{
  "symbol": "BTCUSD",
  "orderId": 891597075,
  "orderListId": -1,
  "clientOrderId": "web_edabc3b2a7eb4fcf9e946c9ba16c7915",
  "price": "39675.0000",
  "origQty": "0.00100000",
  "executedQty": "0.00100000",
  "cummulativeQuoteQty": "39.6750",
  "status": "FILLED",
  "timeInForce": "GTC",
  "type": "LIMIT",
  "side": "SELL",
  "stopPrice": "0.0000",
  "icebergQty": "0.00000000",
  "time": 1646519874978,
  "updateTime": 1646528386842,
  "isWorking": true,
  "origQuoteOrderQty": "0.0000"
}
*/

--
-- Table structure for table `binance_trade_history`
--
CREATE TABLE `binance_trade_history` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Trade ID',
	`pair` varchar(16) NOT NULL COMMENT 'Asset Pair',
	`order_id` int(16) unsigned NOT NULL DEFAULT 0 COMMENT 'Binance Order ID',
	`price` decimal(19,4) signed NOT NULL DEFAULT 0 COMMENT 'Base Asset Price in USD',
	`quantity` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Base Asset Amount',
	`type` varchar(18) NOT NULL COMMENT 'Binance Order Type',
		-- LIMIT, MARKET, STOP_LOSS, STOP_LOSS_LIMIT, TAKE_PROFIT, TAKE_PROFIT_LIMIT, LIMIT_MAKER
	`type_market` varchar(5) NOT NULL DEFAULT 'TAKER' COMMENT 'Binance Order Side',
		-- MAKER, TAKER
	`type_side` varchar(4) NOT NULL COMMENT 'Binance Order Type Side',
		-- BUY, SELL
	`stop_price` decimal(19,4) signed NOT NULL DEFAULT 0 COMMENT 'Stop Price (SL/TP) for Base Asset in USD',
	`datetime` datetime NOT NULL COMMENT 'Binance Order Date & Time',
		-- FROM_UNIXTIME(), UNIX_TIMESTAMP()
	`commission` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Commission Fee Amount',
	`commission_ticker` varchar(12) NOT NULL DEFAULT 'USD' COMMENT 'Commission Ticker Used',
	`status` varchar(16) NOT NULL COMMENT 'Binance Order Status',
		-- NEW, PARTIALLY_FILLED, FILLED, CANCELED, PENDING_CANCEL, REJECTED, EXPIRED
	PRIMARY KEY (`id`),
	UNIQUE KEY `order_id` (`order_id`),
	KEY `pair` (`pair`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;

--
-- Dumping data for table `binance_trade_history`
--

--LOCK TABLES `binance_trade_history` WRITE;
--INSERT INTO `binance_trade_history` VALUES (NULL,'BTCUSD',891597075,39675.0000,0.00100000,"LIMIT","MAKER","SELL",0.0000,FROM_UNIXTIME(1646528386842),0.00007751,"BNB");
--UNLOCK TABLES;


--
-- Data input structure for table `bybit_trade_history`
--
/*
:/private/linear/trade/closed-pnl/list
{
  "ret_code": 0,
  "ret_msg": "OK",
  "ext_code": "",
  "ext_info": "",
  "result": {
    "current_page": 1,
    "data": [
      {
        "id": 41197961,
        "user_id": 19940200,
        "symbol": "BTCUSDT",
        "order_id": "89e635de-1be4-499f-b130-76f2bb04dd6a",
        "side": "Sell",
        "qty": 0.001,
        "order_price": 37051.5,
        "order_type": "Market",
        "exec_type": "Trade",
        "closed_size": 0.001,
        "cum_entry_value": 38.595,
        "avg_entry_price": 38595,
        "cum_exit_value": 38.323,
        "avg_exit_price": 38323,
        "closed_pnl": -0.2910935,
        "fill_count": 1,
        "leverage": 25,
        "created_at": 1647031543
      },
	  ...
    ]
  },
  "time_now": "1647122957.075896",
  "rate_limit_status": 119,
  "rate_limit_reset_ms": 1647122957057,
  "rate_limit": 120
}
*/

--
-- Table structure for table `bybit_trade_history`
--
CREATE TABLE `bybit_trade_history` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Trade ID',
	`pair` varchar(16) NOT NULL COMMENT 'Asset Pair',
	`order_id` binary(16) NOT NULL COMMENT 'Bybit Order ID',
	`price` decimal(19,4) signed NOT NULL DEFAULT 0 COMMENT 'Base Asset Price in USD',
	`quantity` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Base Asset Amount',
	`type` varchar(18) NOT NULL COMMENT 'Bybit Order Type',
	`type_side` varchar(5) NOT NULL DEFAULT 'LONG' COMMENT 'Bybit Order Type Side',
	`exec_type` varchar(9) NOT NULL DEFAULT 'Trade' COMMENT 'Transaction Type',
	`closed_quantity` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Closed Asset Amount',
	`avg_entry` decimal(19,4) signed NOT NULL DEFAULT 0 COMMENT 'Average Entry Price',
	`avg_exit` decimal(19,4) signed NOT NULL DEFAULT 0 COMMENT 'Average Exit Price',
	`datetime` datetime NOT NULL COMMENT 'Bybit Order Date & Time',
	`leverage` tinyint(3) signed NOT NULL DEFAULT 0 COMMENT 'Leverage',
	`closed_pnl` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Closed Profit and Loss',
	PRIMARY KEY (`id`),
	UNIQUE KEY `order_id` (`order_id`),
	KEY `pair` (`pair`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;
--		`order_id`			UUID_TO_BIN(), BIN_TO_UUID()
--		`type`				LIMIT, MARKET
--		`type_side`			Buy (Long), Sell (Short)
--		`exec_type`			Trade, AdlTrade, Funding, BustTrade
--		`exec_quantity_left`	leaves_qty
--		`datetime`			FROM_UNIXTIME(), UNIX_TIMESTAMP()
--		`liquidity_type`	AddedLiquidity (MAKER), RemovedLiquidity (TAKER), LiquidityIndNA (NULL)

--
-- Dumping data for table `bybit_trade_history`
--

--LOCK TABLES `bybit_trade_history` WRITE;
--INSERT INTO `bybit_trade_history` VALUES (NULL,'BTCUSDT',UUID_TO_BIN(89e635de-1be4-499f-b130-76f2bb04dd6a),37051.5,0.001,"MARKET","SHORT","TRADE",38323,0.001,0,0.00075,0.02874225,FROM_UNIXTIME(1647032087),25,'RemovedLiquidity',-0.2910935);

--UNLOCK TABLES;


--
-- Data input structure for table `ftx_trade_history`
--
/*
:/api/orders/history
{
  "success": true,
  "result": [
    {
      "id": 3751625576,
      "clientId": null,
      "market": "BTC/USD",
      "type": "limit",
      "side": "buy",
      "price": 38015,
      "size": 0.0028,
      "status": "closed",
      "filledSize": 0.0028,
      "remainingSize": 0,
      "reduceOnly": false,
      "liquidation": false,
      "avgFillPrice": 38015,
      "postOnly": false,
      "ioc": false,
      "createdAt": "2022-02-23T04:50:37.268333+00:00",
      "future": null
    },
    ...
  ],
  "hasMoreData": false
}
*/

--
-- Table structure for table `ftx_trade_history`
--
CREATE TABLE `ftx_trade_history` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Trade ID',
	`pair` varchar(16) NOT NULL COMMENT 'Asset Pair',
	`order_id` int(16) unsigned NOT NULL DEFAULT 0 COMMENT 'FTX Order ID',
	`price` decimal(19,4) signed NOT NULL DEFAULT 0 COMMENT 'Base Asset Price in USD',
	`quantity` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Base Asset Amount',
	`type` varchar(18) NOT NULL COMMENT 'FTX Order Type',
	`type_side` varchar(4) NOT NULL COMMENT 'FTX Order Type Side',
	`datetime` datetime NOT NULL COMMENT 'FTX Order Date & Time',
	`status` varchar(16) NOT NULL COMMENT 'Binance Order Status',
	PRIMARY KEY (`id`),
	UNIQUE KEY `order_id` (`order_id`),
	KEY `pair` (`pair`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;
--		`type`			LIMIT, MARKET
--		`type_side`		BUY, SELL
--		`datetime`		FROM_UNIXTIME(), UNIX_TIMESTAMP()
--		`status`		NEW, OPEN, CLOSED

--
-- Dumping data for table `ftx_trade_history`
--

--LOCK TABLES `ftx_trade_history` WRITE;
--INSERT INTO `ftx_trade_history` VALUES (NULL,'BTCUSD',3751625576,38015,0.0028,"LIMIT","BUY","2022-02-23T04:50:37.268333+00:00","CLOSED");
--UNLOCK TABLES;


-- --------------------------------------------------------

--
-- Data input structure for table `binance_deposits`
--

/*
:/sapi/v1/capital/deposit/hisrec
[
  {
    "amount": "0.00446287",
    "coin": "BTC",
    "network": "BTC",
    "status": 1,
    "address": "135GqV23yNjpyVHbaYJ2Pa6LWsfdAhAngF",
    "addressTag": "",
    "txId": "30542162ed5f6fc6c943af946a58b840f418d90de1e1ce3c659374f14610220b",
    "insertTime": 1645592900960,
    "transferType": 0,
    "confirmTimes": "1/1"
  },
*/

--
-- Table structure for table `binance_deposits`
--
CREATE TABLE `binance_deposits` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Deposit ID',
	`ticker` varchar(16) NOT NULL COMMENT 'Asset Ticker',
	`quantity` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Deposit Amount',
	`address_recv` varchar(255) NOT NULL COMMENT 'Received Deposit Address',
	`network` varchar(16) NOT NULL COMMENT 'Asset Network',
	`tx_id` varchar(255) NOT NULL COMMENT 'Transaction ID',
	`datetime` datetime NOT NULL COMMENT 'Deposit Date & Time',
	`confirmations` varchar(16) NOT NULL COMMENT 'Deposit Confirmations',
	PRIMARY KEY (`id`),
	UNIQUE KEY `tx_id` (`tx_id`),
	KEY `ticker` (`ticker`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;
--		`datetime`		FROM_UNIXTIME(), UNIX_TIMESTAMP()

--
-- Dumping data for table `binance_deposits`
--

--LOCK TABLES `binance_deposits` WRITE;
--INSERT INTO `binance_deposits` VALUES (NULL,'BTC',0.00446287,'135GqV23yNjpyVHbaYJ2Pa6LWsfdAhAngF','BTC','30542162ed5f6fc6c943af946a58b840f418d90de1e1ce3c659374f14610220b',FROM_UNIXTIME(1645592900960),'1/1');
--UNLOCK TABLES;


--
-- Data input structure for table `bybit_deposits`
--

/*
:/v2/private/wallet/fund/records
{
  "ret_code": 0,
  "ret_msg": "OK",
  "ext_code": "",
  "ext_info": "",
  "result": {
    "data": [
      {
        "id": 11560076,
        "user_id": 19940200,
        "coin": "USDT",
        "wallet_id": 3340051,
        "type": "AccountTransfer",
        "amount": "9.96599116",
        "tx_id": "71107d0d-10bb-4b28-b114-a9106d20676d",
        "address": "",
        "wallet_balance": "9.96599116",
        "exec_time": "2022-03-04T02:16:34Z",
        "cross_seq": 0
      }
    ]
  },
  "time_now": "1647237685.450557",
  "rate_limit_status": 119,
  "rate_limit_reset_ms": 1647237685431,
  "rate_limit": 120
}
*/

--
-- Table structure for table `bybit_deposits`
--
CREATE TABLE `bybit_deposits` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Deposit ID',
	`ticker` varchar(16) NOT NULL COMMENT 'Asset Ticker',
	`quantity` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Deposit Amount',
	`tx_id` varchar(255) NOT NULL COMMENT 'Transaction ID',
	`datetime` datetime NOT NULL COMMENT 'Deposit Date & Time',
	PRIMARY KEY (`id`),
	UNIQUE KEY `tx_id` (`tx_id`),
	KEY `ticker` (`ticker`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;
--		`datetime`		FROM_UNIXTIME(), UNIX_TIMESTAMP()

--
-- Dumping data for table `bybit_deposits`
--

--LOCK TABLES `bybit_deposits` WRITE;
--INSERT INTO `bybit_deposits` VALUES (NULL,'USDT',9.96599116,'71107d0d-10bb-4b28-b114-a9106d20676d','2022-03-04T02:16:34Z');
--UNLOCK TABLES;


--
-- Data input structure for table `ftx_deposits`
--

/*
:
{
  "success": true,
  "result": [
    {
      "id": 542445,
      "coin": "ETH",
      "txid": "0xa9349c2091df894d853fa11ccd7ad1b3837127ef1a5092e64ccc872b04612810",
      "address": {
        "address": "0xB7924315412Cc82a2A61c68923589506E60dA295",
        "tag": null,
        "method": "eth",
        "coin": null
      },
      "size": 0.01201069,
      "fee": 0,
      "status": "confirmed",
      "time": "2022-03-04T11:26:41.263748+00:00",
      "sentTime": "2022-03-04T11:26:20+00:00",
      "confirmedTime": "2022-03-04T11:28:12.740391+00:00",
      "confirmations": 10,
      "method": "eth"
    },
    {
      "id": 866783,
      "achAccountId": 240655,
      "coin": "USD",
      "time": "2021-12-14T12:36:38.528249+00:00",
      "status": "complete",
      "fee": 0,
      "requestedSize": 85,
      "size": 85,
      "ach": true,
      "type": "ach",
      "fiat": true,
      "creditedAt": "2021-12-27T14:11:01.038834+00:00",
      "credited": true,
      "wasEarlyCredited": true,
      "earlyCredited": false,
      "errorCode": null,
      "redirectUrl3ds": null,
      "paymentId": "1c8a02d8-9fd6-4755-97a2-7c8d79d561d6"
    }
  ]
}
*/

--
-- Table structure for table `ftx_deposits`
--
CREATE TABLE `ftx_deposits` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Deposit ID',
	`ticker` varchar(16) NOT NULL COMMENT 'Asset Ticker',
	`quantity` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Deposit Amount',
	`fee` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Fee Amount',
	`network` varchar(16) NOT NULL DEFAULT 'ACH' COMMENT 'Asset Network',
	`tx_id` varchar(255) DEFAULT NULL COMMENT 'Transaction ID (Crypto)',
	`payment_id` binary(16) DEFAULT NULL COMMENT 'Payment UUID (Fiat)',
	`datetime` datetime NOT NULL COMMENT 'Deposit Date & Time',
	`confirmations` tinyint(3) NOT NULL DEFAULT 0 COMMENT 'Transaction Confirmations',
	`status` varchar(12) NOT NULL COMMENT 'Deposit Status',
	PRIMARY KEY (`id`),
	KEY `tx_id` (`tx_id`),
	KEY `payment_id` (`payment_id`),
	KEY `ticker` (`ticker`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;
--		`network`		method
--		`payment_id`	UUID_TO_BIN(), BIN_TO_UUID()
--		`datetime`		FROM_UNIXTIME(), UNIX_TIMESTAMP()
--		`status`		COMPLETE, CONFIRMED, UNCONFIRMED, CANCELLED

--
-- Dumping data for table `ftx_deposits`
--

--LOCK TABLES `ftx_deposits` WRITE;
--INSERT INTO `ftx_deposits` VALUES (NULL,'ETH',0.01201069,0,'ERC20','0xa9349c2091df894d853fa11ccd7ad1b3837127ef1a5092e64ccc872b04612810',NULL,'2022-03-04T11:28:12.740391+00:00',10,'CONFIRMED');
--INSERT INTO `ftx_deposits` VALUES (NULL,'USD',85,0,NULL,NULL,UUID_TO_BIN('1c8a02d8-9fd6-4755-97a2-7c8d79d561d6'),'2021-12-27T14:11:01.038834+00:00',0,'COMPLETE');
--UNLOCK TABLES;


-- --------------------------------------------------------

--
-- Data input structure for table `binance_withdrawals`
--

/*
:/sapi/v1/capital/withdraw/history
[
  {
    "id": "5a8b7605feb0453eb9a7ddd9257a65d8",
    "amount": "33.102064",
    "transactionFee": "0.8",
    "coin": "USDT",
    "status": 6,
    "address": "0xe3d0d0ad46d28624b339a38114dee2c066e4048e",
    "txId": "0x4d3831544f88f2e82e184fd02e8e2522fea435e39ee381dd9acb215e6883f194",
    "applyTime": "2022-01-11 06:08:28",
    "network": "BSC",
    "transferType": 0
  }
]
*/

--
-- Table structure for table `binance_withdrawals`
--
CREATE TABLE `binance_withdrawals` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Withdrawal ID',
	`ticker` varchar(16) NOT NULL COMMENT 'Asset Ticker',
	`quantity` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Withdrawal Amount',
	`tx_id` varchar(255) NOT NULL COMMENT 'Transaction ID',
	`tx_addr` varchar(255) NOT NULL COMMENT 'Asset Withdrawn to this Address',
	`tx_fee` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Transaction Fee',
	`tx_network` varchar(16) NOT NULL COMMENT 'Transaction Network',
	`datetime` datetime NOT NULL COMMENT 'Withdrawal Date & Time',
	`status` varchar(17) NOT NULL COMMENT 'Withdrawal Status',
	PRIMARY KEY (`id`),
	UNIQUE KEY `tx_id` (`tx_id`),
	KEY `ticker` (`ticker`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;
--		`datetime`		FROM_UNIXTIME(), UNIX_TIMESTAMP()
--		`status`		0:EMAIL_SENT, 1:CANCELLED, 2:AWAITING_APPROVAL, 3:REJECTED,
--						4:PROCESSING, 5:FAILURE, 6:COMPLETED

--
-- Dumping data for table `binance_withdrawals`
--

--LOCK TABLES `binance_withdrawals` WRITE;
--INSERT INTO `binance_withdrawals` VALUES (NULL,'USDT',33.102064,'0x4d3831544f88f2e82e184fd02e8e2522fea435e39ee381dd9acb215e6883f194','0xe3d0d0ad46d28624b339a38114dee2c066e4048e',0.8,'BSC','2022-01-11 06:08:28','COMPLETED');
--UNLOCK TABLES;


--
-- Data input structure for table `bybit_withdrawals`
--

/*
:/v2/private/wallet/withdraw/list
{
  "ret_code": 0,
  "ret_msg": "ok",
  "ext_code": "",
  "result": {
      "data": [{
          "id": 137,
          "user_id": 1,
          "coin": "XRP",
          "status": "Pending"
          "amount": "20.00000000",
          "fee": "0.25000000",
          "address": "rH7H595XYEVTEHU2FySYsWnmfACBnZS9zM",
          "tx_id": "",
          "submited_at": "2019-06-11T02:20:24.000Z",
          "updated_at": "2019-06-11T02:20:24.000Z"
      }]
      "current_page": 1,
      "last_page": 1
  },
  "ext_info": null,
  "time_now": "1577482295.125488",
  "rate_limit_status": 119,
  "rate_limit_reset_ms": 1577482295132,
  "rate_limit": 120
}
*/

--
-- Table structure for table `bybit_withdrawals`
--
CREATE TABLE `bybit_withdrawals` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Withdrawal ID',
	`ticker` varchar(16) NOT NULL COMMENT 'Asset Ticker',
	`quantity` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Withdrawal Amount',
	`tx_id` varchar(255) NOT NULL COMMENT 'Transaction ID',
	`tx_addr` varchar(255) NOT NULL COMMENT 'Asset Withdrawn to this Address',
	`tx_fee` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Transaction Fee',
	`datetime` datetime NOT NULL COMMENT 'Withdrawal Date & Time',
	`status` varchar(17) NOT NULL COMMENT 'Withdrawal Status',
	PRIMARY KEY (`id`),
	UNIQUE KEY `tx_id` (`tx_id`),
	KEY `ticker` (`ticker`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;
--		`datetime`		FROM_UNIXTIME(), UNIX_TIMESTAMP()
--		`status`		TOBECONFIRMED, UNDERREVIEW, PENDING, SUCCESS,
--						CANCELBYUSER, REJECT, EXPIRE

--
-- Dumping data for table `bybit_withdrawals`
--

--LOCK TABLES `bybit_withdrawals` WRITE;
--INSERT INTO `bybit_withdrawals` VALUES (NULL,'XRP',20.00000000,'','rH7H595XYEVTEHU2FySYsWnmfACBnZS9zM',0.25000000,'2019-06-11T02:20:24.000Z','PENDING');
--UNLOCK TABLES;

--
-- Data input structure for table `ftx_withdrawals`
--

/*
:
{
  "success": true,
  "result": [
    {
      "id": 706292,
      "coin": "BTC",
      "address": "135GqV23yNjpyVHbaYJ2Pa6LWsfdAhAngF",
      "tag": null,
      "method": "btc",
      "txid": "30542162ed5f6fc6c943af946a58b840f418d90de1e1ce3c659374f14610220b",
      "size": 0.00446287,
      "fee": 0,
      "status": "complete",
      "time": "2022-02-23T04:54:39.481258+00:00",
      "notes": null,
      "destinationName": "BINANCE-BTC"
    },
  ]
}
*/

--
-- Table structure for table `ftx_withdrawals`
--
CREATE TABLE `ftx_withdrawals` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Withdrawal ID',
	`ticker` varchar(16) NOT NULL COMMENT 'Asset Ticker',
	`quantity` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Withdrawal Amount',
	`tx_id` varchar(255) NOT NULL COMMENT 'Transaction ID',
	`tx_addr` varchar(255) NOT NULL COMMENT 'Asset Withdrawn to this Address',
	`tx_name` varchar(64) NOT NULL COMMENT 'Transaction Destination Name',
	`tx_fee` decimal(24,8) signed NOT NULL DEFAULT 0 COMMENT 'Transaction Fee',
	`tx_network` varchar(16) NOT NULL COMMENT 'Transaction Network',
	`datetime` datetime NOT NULL COMMENT 'Withdrawal Date & Time',
	`status` varchar(17) NOT NULL COMMENT 'Withdrawal Status',
	PRIMARY KEY (`id`),
	UNIQUE KEY `tx_id` (`tx_id`),
	KEY `ticker` (`ticker`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;
--		`datetime`		FROM_UNIXTIME(), UNIX_TIMESTAMP()
--		`status`		REQUESTED, PROCESSING, COMPLETE, CANCELLED

--
-- Dumping data for table `ftx_withdrawals`
--

--LOCK TABLES `ftx_withdrawals` WRITE;
--INSERT INTO `ftx_withdrawals` VALUES (NULL,'BTC',0.00446287,'30542162ed5f6fc6c943af946a58b840f418d90de1e1ce3c659374f14610220b','135GqV23yNjpyVHbaYJ2Pa6LWsfdAhAngF','BINANCE-BTC',0,'BTC','2022-02-23T04:54:39.481258+00:00','COMPLETED');
--UNLOCK TABLES;



-- --------------------------------------------------------


-- vim: ft=sql sts=4 sw=4 ts=4 noet:
