(function(){
	var minuteSeconds = 60;
	var hourSeconds = 60 * minuteSeconds;
	var daySeconds = 24*hourSeconds;

	Loli.countDownTimer = function(callback){
		Loli.countDownTimerInner = function(){
			$(".countDown").each(function(){
				if(!$(this).attr("countDownSeconds")){
					return;
				}
				var countDownSeconds = parseInt($(this).attr("countDownSeconds"));
				var nowSeconds = Math.floor(new Date().getTime()/1000);
				var seconds = countDownSeconds - nowSeconds;
				if(seconds <= 0){
					console.log(callback);
					if(callback){
						callback($(this));
					}
					$(this).removeClass("countDown");
					return true;
				}
				var minutes = Math.floor(seconds / minuteSeconds);
				var hours = Math.floor(seconds / hourSeconds);
				var days = Math.floor(seconds/ daySeconds);
				
				var dayDisplay = days;
				var hourDisplay = hours % 24;
				var minuteDisplay = minutes % 60;
				var secondDisplay = seconds % 60;
				
				var text;
				var clock = $(this).attr("clock");
				$(this).empty();
				if(seconds > daySeconds){
					text = "<span class='"+clock+"'></span><span>"+dayDisplay+"天 "+hourDisplay+"小时 "+minuteDisplay+"分钟 "+secondDisplay+"秒</span>";	
				}else if(seconds > hourSeconds){
					text = "<span class='"+clock+"'></span><span>"+hourDisplay+"小时 "+minuteDisplay+"分钟 "+secondDisplay+"秒</span>";;
				}else {
					text = "<span>马上投递</span>";
				}
				$(text).appendTo($(this));	
			})
			if($(".countDown").length){
				setTimeout("Loli.countDownTimerInner()", 500);
			}
		};
		Loli.countDownTimerInner();
	}
})()