// Chart Scripts for the stats page

$(function () {
	var curDecimals = 2;
	if(currency != 'USD'){
		curDecimals = 8;
	}
	 $.getJSON('json/stats.php?global=1&cur='+currency, function (data) {
		 if(data == null){
				$('#chart_GlobalDailyReturns').html('<br><h4>No Data</h4><p>There is currently no history data in your database.  Please make sure the hourly cron is running correctly, then wait at least one hour for historical data to load from bitfinex.<br><br><h3><a href="index.php?page=grabHistory&currencyType='+currency+'">To Load Historical Data directly from Bitfinex, check here.</h3>');
			}
		else{
			var dataLength = data.length,
				amtReturn = [],
				intReturn = [],
				balReturn = [],
				// set the allowed units for data grouping
				groupingUnits = [[
					'week',                         // unit name
					[1]                             // allowed multiples
				], [
					'month',
					[1, 2, 3, 4, 6]
				]],
				i = 0;
	
			for (i; i < dataLength; i += 1) {
				var s=data[i][0]/1e3;
				s=Math.floor(s)*1e3;
							
				amtReturn.push([
					s, // the date
					data[i][1] // return amount
					
				]);
				intReturn.push([
					s, // the date
					data[i][2] // the percent
				]);
				balReturn.push([
					s, // the date
					data[i][3] // the balance
				]);
			}
		
		
			$('#chart_GlobalDailyReturns').highcharts('StockChart',{
				chart: {
					// Edit chart spacing
					spacingLeft: 40,
					spacingRight: 50
				},
				rangeSelector: {
					selected: 1,
					buttons: [{
						type: 'day',
						count: 7,
						text: '7d'
					}, {
						type: 'day',
						count: 15,
						text: '15d'
					}, {
						type: 'month',
						count: 1,
						text: '1m'
					}, {
						type: 'month',
						count: 3,
						text: '3m'
					}, {
						type: 'month',
						count: 6,
						text: '6m'
					}, {
						type: 'ytd',
						text: 'YTD'
					}, {
						type: 'year',
						count: 1,
						text: '1y'
					}, {
						type: 'all',
						text: 'All'
					}]
				},
				title: {
					text: 'Global '+currency+' Daily Margin Returns'
				},
				yAxis: [{ // Primary yAxis
					title: {
						text: 'Daily Return '+currency,
						x: -22,
						y: 0,
						style: {
							color: '#960000'
						}
					},
					labels: {
						format: '{value} '+currency,
						align:'left',
						x: -25,
						y: 0,
						style: {
							color: '#960000'
						}
					},
					opposite:false,
					floor: 0
				}, { // Secondary yAxis
					title: {
						text: 'Daily Return %',
						x: 30,
						y: 0,
						style: {
							color: '#052487'
						}
					},
					labels: {
						format: '{value}%',
						align:'right',
						x: 31,
						y: -1,
						style: {
							color: '#052487'
						}
					},
					opposite: true,
					floor: 0
				
				}, { // Terciary yAxis
					title: {
						text: 'Balance',
						x: 20,
						y: 0,
						style: {
							color: '#009600'
						}
					},
					labels: {
						format: '{value} '+currency,
						align:'right',
						x: 5,
						y: 11,
						style: {
							color: '#009600'
						}
					},
					opposite: true,
					floor: 0
				}],
				tooltip: {
					shared: true
				},
				series: [
					{
					type: 'areaspline',
					name: currency+' Balance',
					data: balReturn,
					yAxis:2,
					tooltip: {
						valueDecimals: curDecimals
					},
					dataGrouping:{
								enabled:true,
								groupPixelWidth:2,
								units:groupingUnits
							},
					color: '#009600',
					fillColor : {
						linearGradient : {
							x1: 0,
							y1: 0,
							x2: 0,
							y2: 1
						},
						stops : [
							[0, '#cbe9cb'],
							[1, '#ecfeec']
						]
					}
				},{
					type: 'column',
					name: currency+' Return',
					data: amtReturn,
					tooltip: {
						valueDecimals: curDecimals
					},
					color: {
						linearGradient: { x1: 0, x2: 0, y1: 0, y2: 1 },
						stops: [
							[0, '#ca7f7f'],
							[1, '#e9cbcb']
						]
					},
					dataGrouping:{
								enabled:true,
								groupPixelWidth:2,
								units:groupingUnits
							}
					
				},
				{
					type: 'spline',
					name: 'Average Margin %',
					data: intReturn,
					yAxis:1,
					tooltip: {
						valueDecimals: 4
					},
					color: '#052487',
					dataGrouping:{
								enabled:true,
								groupPixelWidth:2,
								units:groupingUnits
							}
				}]
			});
		};
	});
});