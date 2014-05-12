var order = null;
var by = null;

function getQueryStringArgs(qy){
	
	//取得查询字符串并去掉开头的问号	
	var qs = (location.search.length > 0)?location.search.substring(1):"";
	
	//保存数据的对象
	var args = {};
	
	//取得每一项
	var items = qs.split("&");
	var item  = null
		
	for(var i =0;i<items.length;i++){
		item = items[i].split("=");
		if(decodeURIComponent(item[0]) == qy){
			return decodeURIComponent(item[1]);
		}
	}
}

function pregpattern (obj){

	order = getQueryStringArgs('order');
	by = getQueryStringArgs('by');

	$.each(obj,function(idx,dom){
				
		//alert(idx+'=>'+dom);
		var  str=''; 
		if(idx == order){
			str = "<a  title='排序' style='color:#FF8080' href='javascript:void(0)' onclick='skip(\""+idx+"\")' class='xxxx'>"+dom+"</a>";
		}else{
			str = "<a  title='排序' href='javascript:void(0)' style='color:#0080FF' onclick='skip(\""+idx+"\")' class='xxxx'>"+dom+"</a>";
		}
		
		$("#awarp").find("td:contains("+dom+")").attr("align","center").html(str);
	})	
}

//跳转
//2为正序
//1为倒序
function skip(v1){
	//return false;
	if(v1 == order){
		var m= (by == 1)?2:1;
		$("#myform").prepend("<input type='hidden' name='order' value='"+v1+"'><input type='hidden' name='by' value='"+m+"'>");
	}else{
		$("#myform").prepend("<input type='hidden' name='order' value='"+v1+"'><input type='hidden' name='by' value='1'>");
	}

	$("#myform").submit();
}





