This is a PHP based Margin Lending Managment Bot for the Bitfinex [API](https://bitfinex.com/pages/api).

## Details
This bot is designed to manage 1 or more bitfinex accounts, doing its best to keep any money in the "depost" wallet lent out at the highest rate possible while avoiding long periods of pending loans (as often happens when using the Flash Return Rate, or some other arbitrary rate).  There are numerous options and setting to tailor the bot to your requirements.

### Install

[Download the most current version](https://github.com/HFenter/MarginBot/archive/master.zip), unzip to a folder on your server, then browse to that folder.  An install script will run you through the rest of the process.

## Requirements

A live webserver running
* PHP 5.1+
* MySQL
* Access to add a cronjob
* A Bitfinex Account with API Access [(Set Up Here)](https://www.bitfinex.com/account/api)

If you don't have a bitfinex account, please consider using my [affiliate code](https://www.bitfinex.com/?refcode=vsAnxuo5bM) when signing up.  By doing so, you'll save 10% on all fees for the first month, and it will help support further development of this code.

[https://www.bitfinex.com/?refcode=vsAnxuo5bM](https://www.bitfinex.com/?refcode=vsAnxuo5bM).