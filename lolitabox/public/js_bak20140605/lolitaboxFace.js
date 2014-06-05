// QQ表情插件
(function($){  
	$.fn.qqFace = function(options){
		var defaults = {
			id : 'facebox',
			path : 'public/lolitabox/smilies/',
			assign : 'content'
		};
		var option = $.extend(defaults, options);
		var assign = $('#'+option.assign);
		var id = option.id;
		var path = option.path;
		if(assign.length<=0){
			alert('缺少表情赋值对象。');
			return false;
		}
		$(this).click(function(e){
			var strFace, labFace;
			var smile_name = new Array(["bizui","闭嘴"],["bye","拜拜"],["eat","吃货"],["haixiu","害羞"],["han","汗"],["hehe","呵呵"],["huaxin","花心"],["hufen","互粉"],["jidong","激动"],["jiong","囧"],["ku","哭"],["laugh","大笑"],["ma","咒骂"],["meiyan","抛媚眼"],["qin","亲亲"],["shuai","衰"],["wunai","无奈"],["yes","点头"],["yun","晕"],["zan","赞"]); 
			
			if($('#'+id).length<=0){
				strFace='<div class="tc_face_wrap tc_info"  id="'+id+'">';
				strFace+='<div class="login_tit"><h2>添加表情</h2></div>';
				strFace+='<div class="layer_faces"><div class="detail"><ul class="faces_list cfl">';
				for(var i=0; i<20; i++){
					labFace = '['+smile_name[i][1]+']';
					strFace +=	'<li><img src="'+path+smile_name[i][0]+'.gif" onclick="$(\'#'+option.assign+'\').setCaret();$(\'#'+option.assign+'\').insertAtCaret(\'' + labFace + '\');" title="'+smile_name[i][1]+'" /></li>';
				}
				strFace += "</ul></div><div class=\"arrow arrow_t\"></div></div></div>";
			}
			$(this).parent().append(strFace);
			var offset = $(this).position();
			var top = offset.top + $(this).outerHeight();
			$('#'+id).css('position','absolute');
			$('#'+id).css('top',top);
			$('#'+id).css('left',offset.left);
			$('#'+id).show();
			e.stopPropagation();
		});

		$(document).click(function(){
			$('#'+id).hide();
			$('#'+id).remove();
		});
	};

})(jQuery);

jQuery.extend({ 
unselectContents: function(){ 
	if(window.getSelection) 
		window.getSelection().removeAllRanges(); 
	else if(document.selection) 
		document.selection.empty(); 
	} 
}); 
jQuery.fn.extend({ 
	selectContents: function(){ 
		$(this).each(function(i){ 
			var node = this; 
			var selection, range, doc, win; 
			if ((doc = node.ownerDocument) && (win = doc.defaultView) && typeof win.getSelection != 'undefined' && typeof doc.createRange != 'undefined' && (selection = window.getSelection()) && typeof selection.removeAllRanges != 'undefined'){ 
				range = doc.createRange(); 
				range.selectNode(node); 
				if(i == 0){ 
					selection.removeAllRanges(); 
				} 
				selection.addRange(range); 
			} else if (document.body && typeof document.body.createTextRange != 'undefined' && (range = document.body.createTextRange())){ 
				range.moveToElementText(node); 
				range.select(); 
			} 
		}); 
	}, 

	setCaret: function(){ 
		if(!$.browser.msie) return; 
		var initSetCaret = function(){ 
			var textObj = $(this).get(0); 
			textObj.caretPos = document.selection.createRange().duplicate(); 
		}; 
		$(this).click(initSetCaret).select(initSetCaret).keyup(initSetCaret); 
	}, 

	insertAtCaret: function(textFeildValue){ 
		var textObj = $(this).get(0); 
		if(textObj.value=="如果是分享关于产品/品牌的心得，记得@产品/品牌的名称哦~" || textObj.value=="顺便评论一下呗"){
			textObj.value="";
		}
		if(document.all && textObj.createTextRange && textObj.caretPos){ 
			var caretPos=textObj.caretPos; 
			caretPos.text = caretPos.text.charAt(caretPos.text.length-1) == '' ? 
			textFeildValue+'' : textFeildValue; 
		} else if(textObj.setSelectionRange){ 
			var rangeStart=textObj.selectionStart; 
			var rangeEnd=textObj.selectionEnd; 
			var tempStr1=textObj.value.substring(0,rangeStart); 
			var tempStr2=textObj.value.substring(rangeEnd); 
			textObj.value=tempStr1+textFeildValue+tempStr2; 
			textObj.focus(); 
			var len=textFeildValue.length; 
			textObj.setSelectionRange(rangeStart+len,rangeStart+len); 
			textObj.blur(); 
		}else{ 
			textObj.value+=textFeildValue; 
			var len = textObj.value.length;
			var sel = textObj.createTextRange();
		    sel.moveStart('character',len);
		    sel.collapse();
		    sel.select();
		} 
		textObj.focus(); 
		
		if($(this).attr("onpropertychange")){    //评论框
			var callback = $(this).attr("onpropertychange");
			eval(callback);
		}
	} 
});