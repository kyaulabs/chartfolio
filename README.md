![ANSI Logo](https://raw.githubusercontent.com/kyaulabs/chartfolio/master/chartfolio.ans.png "ANSI Logo")

[![](https://img.shields.io/badge/coded_in-vim-green.svg?logo=vim&logoColor=brightgreen&colorB=brightgreen&longCache=true&style=flat)](https://vim.org) &nbsp; [![](https://img.shields.io/badge/license-AGPL_v3-blue.svg?style=flat)](https://raw.githubusercontent.com/kyaulabs/chartfolio/master/LICENSE) &nbsp; [![](https://img.shields.io/badge/php-8.0+-C85000.svg?style=flat)](https://www.php.net/)

### About
Chartfolio is a cryptocurrency portfolio webapp that has been designed to
extract data from exchanges and display it in a centralized manner. This is
a highly custom piece of software that would require much adaptation to re-use.

Chartfolio utilizes [Aurora](https://github.com/kyaulabs/aurora) in order to
generate the HTML front-pages and access the MySQL database.

### Exchanges
Currently Chartfolio supports three exchanges:
* Binance/Binance.US
* Bybit
* FTX/FTX.US

Other exchanges will likely never be added.

### Features / TODO
- [x] Backend: API Helper
- [x] Backend: Database Helper
- [x] Timer Script: Asset Pairs
- [x] Timer Script: Wallet Balances
- [x] Timer Script: Trade History
- [x] Timer Script: Deposit History
- [ ] Timer Script: Withdrawal History
- [ ] Frontend: Wallet Balances
- [ ] Frontend: Trade History
- [ ] Frontend: Deposit History
- [ ] Frontend: Withdrawal History

Additional features may be added over time.

### Attribution
All of the research materials that were needed to make this possible are listed
here.
* [Binance.US API](https://docs.binance.us/)
* [Bybit API](https://bybit-exchange.github.io/docs/inverse/)
* [Coinglass API](https://coinglass.github.io/API-Reference/)
* [FTX.US API](https://docs.ftx.us/)
