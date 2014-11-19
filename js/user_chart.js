// Chart Scripts for the stats page

$(function () {
	$.each(userIds, function( index, value ) {
  		//alert( index + ": " + value + ' - ' + userNames[index]);
		var thisId = value;
		var thisName = userNames[index];
		 $.getJSON('json/stats.php?userid='+thisId, function (data) {
			var dataLength = data.length,
				amtReturn = [],
				intReturn = [],
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
			}
		
		
			$('#chart_UserDailyReturns_'+thisId).highcharts('StockChart',{
	
				rangeSelector: {
					selected: 1
				},
				title: {
					text: 'Daily Margin Returns for '+thisName
				},
				yAxis: [{ // Primary yAxis
					title: {
						text: 'Daily Return $USD',
						style: {
							color: Highcharts.getOptions().colors[0]
						}
					},
					labels: {
						format: '${value}',
						align:'left',
						style: {
							color: Highcharts.getOptions().colors[0]
						}
					},
					opposite:false
				}, { // Secondary yAxis
					title: {
						text: 'Daily Return %',
						style: {
							color: Highcharts.getOptions().colors[1]
						}
					},
					labels: {
						format: '{value}% / Day',
						align:'right',
						style: {
							color: Highcharts.getOptions().colors[1]
						}
					},
					opposite: true
				}],
				tooltip: {
					shared: true
				},
				series: [{
					type: 'column',
					name: '$USD Return',
					data: amtReturn,
					tooltip: {
						valueDecimals: 2
					},
					dataGrouping:{
								enabled:true,
								groupPixelWidth:2,
								units:groupingUnits
							}
					
				},
				{
					type: 'line',
					name: 'Average Margin %',
					data: intReturn,
					yAxis:1,
					tooltip: {
						valueDecimals: 4
					},
					dataGrouping:{
								enabled:true,
								groupPixelWidth:2,
								units:groupingUnits
							}
				}]
			});
		});
	});
});