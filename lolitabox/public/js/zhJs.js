(function(){

/**
   *高度一致
*/
var hx = $(".wp1020"),
    l_h = $('.side');
if(l_h){
  
  autoHeight(l_h,hx);
}
function autoHeight(l_h,hx){   
 var h = hx.height();
     l_h.css({"height":h+"px"});
} 


/**
  *复制功能
 */
var test_copy = $('#fx_link')[0];
if(test_copy){
	var copyCon = document.getElementById("fx_link").value;
	var flashvars = {
		content: encodeURIComponent(copyCon),
		uri: 'public/images/flash_copy_btn.png'
	};
	var params = {
		wmode: "transparent",
		allowScriptAccess: "always"
	};
	swfobject.embedSWF("public/js/clipboard.swf", "copy_btn", "96", "30", "9.0.0", null, flashvars, params);
	function copySuccess(){
		//flash回调
		alert("复制成功！");
	}
}


/**
  *编辑档案
*/
var testeidt = $('#edit_a')[0],edit_input,edit_select,that,testin,qlist,a,b;
if(testeidt){
	edit_input = $('.user_information').find('input:text');
	edit_select = $('.user_information').find('select');
	qlist = $('.q_list');
	$('#edit_a').click(function(){
		that = $(this);
		autoHeight(l_h,hx);
		that.toggleClass('edit_btn');
        testin = that.hasClass('edit_btn');
		if(testin){//可编辑状态
			$('a',this).text('确认编辑');
			$('.base_view').hide();
			$('.base').show();
			$('.q_list a').click(function(){
			   a = $(this).text() == "+" ? "-":"+";
			   $(this).parent('.q_list').next('.s_list').toggle();
			   $(this).parent('.q_list').find('input:button').toggle();
			   $(this).text(a);
			   autoHeight(l_h,hx);
			})
			$('.q_list input:button').click(function(){
                b = $(this).parent('.q_list').find('a').text() == "-" ? "+":"-";
				$(this).parent('.q_list').next('.s_list').hide();
				$(this).hide();
				$(this).parent('.q_list').find('a').text(b);
			})
		}else{//不可编辑状态
			$('a',this).text('编辑萝莉档案');
			$('.base_view').show();
			$('.base').hide();
			autoHeight(l_h,hx);
		}
        
	})
}


/**
  *自定义checkbox
*/
var testcheck = $('.check_bg');
var allcheck = $('.all_check');
var th_li = $('.th_li');
//执行
if(testcheck && !allcheck){
	testcheck.click(function(){
	    checkI($(this));
	})	
}
if(allcheck) {
	allcheck.click(function(){
	    checkAll($(this));
	})
	th_li.click(function(){
	    checkI($(this));
	})
}
//自定义复选框
function checkI(obj){
	var test = obj.find('i'),
	    testInput = obj.find('input');
	test.toggleClass('select');
	if(test.hasClass('select')){
		testInput.attr('checked',true);
        if(allcheck){
        	obj.addClass('select');
        }
	}else{
		testInput.attr('checked',false);
		if(allcheck){
        	obj.removeClass('select');
        }
	}
}
//全选
function checkAll(obj){
	var test = obj.find('i'),
	    th_input = $('.th_con').find('input'),
	    th_i = $('.th_li').find('i');
	test.toggleClass('select');
	if(test.hasClass('select')){
		th_input.attr('checked',true);
		th_i.addClass('select').parents('.th_li').addClass('select');
	}else{
		th_input.attr('checked',false);
		th_i.removeClass('select').parents('.th_li').removeClass('select');
	}
}

/**
  *自定义滚动条
*/
var autoMain = $('autoMain');
if(autoMain){
	$(".autoMain").mCustomScrollbar({
		theme:"dark-2" /*"dark-thin","light", "dark", "light-2", "dark-2", "light-thick", "dark-thick", "light-thin", "dark-thin"*/
	});
}

})();


