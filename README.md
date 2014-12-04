This is a PHP based Margin Lending Management Bot for the Bitfinex [API](https://bitfinex.com/pages/api).

## Details
This bot is designed to manage 1 or more bitfinex accounts, doing its best to keep any money in the "depost" wallet lent out at the highest rate possible while avoiding long periods of pending loans (as often happens when using the Flash Return Rate, or some other arbitrary rate).  There are numerous options and setting to tailor the bot to your requirements.

### Install

[Download the most current version](https://github.com/HFenter/MarginBot/archive/master.zip), unzip to a folder on your server, then browse to that folder.  An install script will run you through the rest of the process.


**Note: *If you get the following error during install:***

	PHP Parse error:  syntax error, unexpected ''America/Los_Angeles\\');' (T_CONSTANT_ENCAPSED_STRING), expecting identifier (T_STRING) in /var/www/html/web/install.php on line 92

*you most likely have your PHP configured without short_open_tag = on.  Make sure to set*

	short_open_tag = on

### Update from an older Version

**Important**  - Make sure to backup your existing inc/config.php file **FIRST**

To update, **make a backup of your inc/config.php file**, then [download the most current version](https://github.com/HFenter/MarginBot/archive/master.zip).  Unzip the files and overwrite your existing install.  

In your inc/config.php file, update the following lines, copying over from your previous backup:

	$config['db']['host'] = '';
	$config['db']['dbname'] = '';
	$config['db']['dbuser'] = '';
	$config['db']['dbpass'] = '';
	
	$config['db']['prefix'] = '';
	
	$config['admin_email'] = 'support@fuckedgox.com';

(In future versions, backing up the config file then overwriting it during an update should be easier, but I didn't plan correctly in the first version... oops)


## Requirements

A live webserver running
* PHP 5.1+
* MySQL
* Access to add a cronjob
* A Bitfinex Account with API Access [(Set Up Here)](https://www.bitfinex.com/account/api)
* At least $50 in your Bitfinex "Deposit" wallet.  Preferably $100 or more. ( *Note: This is a bitfinex requirement, not a bot requirement.  Bitfinex doesn't allow Margin Loans of less than $50.* ) 

If you don't have a bitfinex account, please consider using my [affiliate code](https://www.bitfinex.com/?refcode=vsAnxuo5bM) when signing up.  By doing so, you'll save 10% on all fees for the first month, and it will help support further development of this code.

[https://www.bitfinex.com/?refcode=vsAnxuo5bM](https://www.bitfinex.com/?refcode=vsAnxuo5bM).

## Donations
Developing this software, and testing the various strategies for lending that led to its development have taken significant time and effort.  If you find this software useful, please send a small donation our way.  All donations support the continued development of this software, and help to cover my distribution and support costs.

You can send donations to:

Bitcoin: 1A3y1xDXtyZySmPZySbpz7PPog4Vsyqig1
