
function creste_or_code(title,img,url,parentid){
	var root="https://open.weixin.qq.com/qr/set/?a=1"
    $.ajax({
    	url:root,
    	type:"get",
    	data:{title:title,img,img,url:url},
    	dataType:"jsonp",
    	success:function(){
    		
    	}
    })   
    function showWxBox(id){
		var html="<img src='https://open.weixin.qq.com/qr/get/"+id+"/'">;
		$("#"+parentid).html(html);
	}
}