// MarginBot Javascript //


	$(function() {
		// Toggle Tooltips and Popovers on
		$('[data-toggle="tooltip"]').tooltip();
  		$('[data-toggle="popover"]').popover({trigger:'click'});
		
		
		// pause / unpause lending on account //
		$(".doPauseAct").click(function(){
			var userId = $(this).attr("uid");
			//alert("Posting: " + userId);
			$.post("ajax/aj.php", { uid: userId, doPause: 1 }, function(data){
				if(data == '1'){
					$("#userRow_"+userId).toggleClass('danger');
					var curState = $("#doPauseAct_"+userId).html();
					if( curState == 'Unpause Lending'){
						$("#doPauseAct_"+userId).html('Pause Lending');
					}
					else{
						$("#doPauseAct_"+userId).html('Unpause Lending');
					}
				}
				
			});
		});

		// extract max button //
	
		$(".maxExtract").click(function () {
			event.preventDefault();
			var thisFund = $(this).val();
			$("#extractAmt_"+thisFund).val("MAX");
		});
		
		
		// jQuery formatCurrency plugin: http://plugins.jquery.com/project/formatCurrency

		// Format while typing & warn on decimals entered, 2 decimal places
		$('.autoCurrency').blur(function() {
			$(this).formatCurrency({ colorize: false, roundToDecimalPlace: 2, symbol:"" });
		})
		.keyup(function(e) {
			var e = window.event || e;
			var keyUnicode = e.charCode || e.keyCode;
			if (e !== undefined) {
				switch (keyUnicode) {
					case 16: break; // Shift
					case 17: break; // Ctrl
					case 18: break; // Alt
					case 27: this.value = ''; break; // Esc: clear entry
					case 35: break; // End
					case 36: break; // Home
					case 37: break; // cursor left
					case 38: break; // cursor up
					case 39: break; // cursor right
					case 40: break; // cursor down
					case 78: break; // N (Opera 9.63+ maps the "." from the number key section to the "N" key too!) (See: http://unixpapa.com/js/key.html search for ". Del")
					case 110: break; // . number block (Opera 9.63+ maps the "." from the number block to the "N" key (78) !!!)
					case 190: break; // .
					default: $(this).formatCurrency({ colorize: false, roundToDecimalPlace: -1, symbol:"" });
				}
			}
		});
		// Format while typing & warn on decimals entered, 2 decimal places
		$('.autoPercent').blur(function() {
			$(this).formatCurrency({ colorize: false, roundToDecimalPlace: 5, symbol:"" });
		})
		.keyup(function(e) {
			var e = window.event || e;
			var keyUnicode = e.charCode || e.keyCode;
			if (e !== undefined) {
				switch (keyUnicode) {
					case 16: break; // Shift
					case 17: break; // Ctrl
					case 18: break; // Alt
					case 27: this.value = ''; break; // Esc: clear entry
					case 35: break; // End
					case 36: break; // Home
					case 37: break; // cursor left
					case 38: break; // cursor up
					case 39: break; // cursor right
					case 40: break; // cursor down
					case 78: break; // N (Opera 9.63+ maps the "." from the number key section to the "N" key too!) (See: http://unixpapa.com/js/key.html search for ". Del")
					case 110: break; // . number block (Opera 9.63+ maps the "." from the number block to the "N" key (78) !!!)
					case 190: break; // .
					default: $(this).formatCurrency({ colorize: false, roundToDecimalPlace: -1, symbol:"" });
				}
			}
		});
	});