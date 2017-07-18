### v0.1
    * Initial Public Release

### v0.1.02
    * Fixed a bug in the Global Returns Chart (js/globabl_chart.js and crons/stats.php)
    * Fixed the stats always loading when there is no data
	* Changed Default "Minimum Lend Rate" to 0.0650%
	* Moved version info from inc/config.php to inc/version_info.php
	
### v0.1.03
	* Added a cron tracking database, to make sure crons are running and keep a history
	* Added a nag warning if the system detects your crons haven't run in a while
	
### v0.1.04
	* Added balance to the stats charts
	* Changed chart coloring and layout styles
	* Fixed a major bug in the install system introduced in 0.1.03
	
### v0.1.05
	* If only 1 user account is set up, stats page only shows 1 chart
		(instead of "Global" and that user, which would always be identical)
	* Lots of small fixes for WAMP servers
	* Added a pause feature to disable lending

### v0.1.06
	* Incorperated 5 decimal accuracy improvements (Thanks nwfella !)