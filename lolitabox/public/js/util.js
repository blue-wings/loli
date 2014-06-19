	function getTodayFormate(){
		var today = new Date();
		var yearStr = today.getFullYear();
		var monthStr = (today.getMonth())<9?"0"+(today.getMonth()+1):(today.getMonth()+1)+"";
		var dayStr = (today.getDate())<9?"0"+today.getDate():today.getDate()+"";
		return yearStr+"-"+monthStr+"-"+dayStr;
	}
