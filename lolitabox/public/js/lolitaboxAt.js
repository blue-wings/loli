(function($) {
//判断是否为子元素　
	function isParent(obj,pobj){
　　　　while (obj != undefined && obj != null && obj.tagName.toUpperCase() != 'BODY'){
		　　　if (obj == pobj){
					return true;
		　　　}
				 obj = obj.parentNode;
　　　　}　　　
			return false;
		}

    $.fn.showAtUsers = function() {
    	//alert($("#at_textarea").length);return false;
        if($("#at_textarea").length == 0){
            $("body").append("<pre id='at_textarea'></pre><div id='at_userslist'></div>");   
            //首先模拟一个输入框和显示用户列表的div，有人说pre会把有回车的内容，跟原先一样的展示，但测试后，算出@的位置还是不对，难道pre一说是传说吗。。。
        }
      //  alert(222);return false;
        return this.each(function(){
        	//alert(1235);
            var t = null,li_Index=1,
            $at_textarea=$("#at_textarea"),
            $at_userslist=$("#at_userslist");
            $(this).on("keydown click", function(e) {
            	//必须用keydown，否则上下键使光标会移动
                var _key=e.keyCode,
                Textarea = this;
            	//alert(_key);return false;
                if($at_userslist.is(":visible") && (_key==40 || _key==38 || _key==13)){
                    TipLiSelect(Textarea,_key);
                    return false;
                }else{
                    if(t !==null){
                        clearTimeout(t);
                    }
                    t = setTimeout(function(){
                        drawTextarea(Textarea);
                        getAt(Textarea);
                    },300);
                }
            });
            //ok------------------------------------------
            $("body").on("click",function(e){
                if(!isParent(e.target,$at_userslist[0])){
                    hiddenTip();
                }
            });
            
            
            //ok------------------------------------------
            var getCursor = function(textarea) {
            	//alert("mouse");return false;
                var  rangeData={
                        start: 0,   
                        end: 0,   
                        text: ""
                    };
                    
                textarea.focus();
                if (textarea.setSelectionRange) { // W3C
                	//alert(111);
                    rangeData.start= textarea.selectionStart;
                    rangeData.end = textarea.selectionEnd;
                    rangeData.text = textarea.value.substring(0, rangeData.end);
                } else if (document.selection) { // IE
                    var i,
                        oS = document.selection.createRange(),
                        oR = document.body.createTextRange();
                    oR.moveToElementText(textarea);
                    rangeData.text = oS.text;
                    rangeData.bookmark = oS.getBookmark();
                    for (i = 0; oR.compareEndPoints('StartToStart', oS) < 0 && oS.moveStart("character", -1) !== 0; i ++) {
                        if (textarea.value.charAt(i) == '\n') {
                            i ++;
                        }
                    }
                    rangeData.start = i;
                    rangeData.end = rangeData.text.length + rangeData.start;
                    rangeData.text = textarea.value.substring(0,i);   
                }
                return rangeData;
            },
            
            //设置光标   
            setCursor = function(textarea, rangeData) {
                textarea.focus();
                if (textarea.setSelectionRange) { // W3C
                    textarea.setSelectionRange(rangeData.start, rangeData.end);
                } else if (textarea.createTextRange) { // IE
                    var oR = textarea.createTextRange();
                    if(textarea.value.length === rangeData.start) {
                        oR.collapse(false);
                        oR.select();
                    } else {
                        oR.moveToBookmark(rangeData.bookmark);
                        oR.select();
                    }
                }
            },
            
          //插入选择的用户名称
            add = function(txtData,Object) {
                var oValue,atPos,nStart,nEnd,nValue,st,sR,
                textarea = Object.textarea;
                
                setCursor(textarea, Object.rangeData);
                oValue = textarea.value;
                //@符所在的位置
                atPos = parseInt(Object.pos) - parseInt(Object.len);
                nValue = oValue.substring(0,atPos) + "@" +txtData + " " + oValue.substring(Object.rangeData.end);
                nStart = nEnd = atPos + txtData.length +2;
                
                st = textarea.scrollTop;
                textarea.value = nValue;
                // 赋值后，scrollTop会变回0，重新设置scrollTop
                if(textarea.scrollTop != st) {
                    textarea.scrollTop = st;
                }
                if (textarea.setSelectionRange) { // W3C
                    textarea.setSelectionRange(nStart, nEnd);
                } else if (textarea.createTextRange) { 　　　　　　　　

// IE下测试坑爹，又要插入，又要光标插入到姓名+空格后面，不是最末尾哦，我对TextRange不熟悉，所以写成下面这样，还好测试OK。　　　　　　　　　　　　　
                //如果高人有更好的方法，望诚心讨教，回复我下，先谢谢了。
                    oValue = oValue.substring(Object.rangeData.end);//光标后面的字符
                    st = oValue.replace(/\n/g,'').length;//替换掉换行
                    sR = document.selection.createRange();
                    sR.moveEnd("character", -st);
                    sR.select();
                }
            },
            
            //匹配@符
            getAt = function(textarea) {
                var _rangeData=getCursor(textarea);   
                var k=_value=_rangeData.text;
                var _reg=/@[^@\s]{0,20}$/g;//匹配@符后面0至20个字符
                if(_value.indexOf("@")>= 0 && _value.match(_reg)) {
                    var _postion=_rangeData.start;
                    var _oValue=_value.match(_reg)[0];//找到value中最后匹配的数据
                    var _AT={};//存储输入内容被截取后的字段信息
                    if(_oValue==="@"){
                        _AT['m'] = "lolitabox_@";//自己改喜欢的标记字符，说明此次输入@后面没有字，我因为是给娜米汇做网站，所以这样写了。
                        _AT['l'] = _value.slice(0, -1).replace(/\n/g,'<br>'); //@前面的文字,把回车转为br   
                        _AT['r'] = '';//@后面的文字   
                        _AT['pos']=_postion;//光标位置   
                        _AT['len']=1;//光标位置至@的长度
                        _AT['rangeData']=_rangeData;
                        _AT['textarea']=textarea;
                        showTip(_AT);
                    }else if(/^@[a-zA-Z0-9\u4e00-\u9fa5_-]+$/.test(_oValue) && !/\s/.test(_oValue)) {   
                        _AT['m'] = _oValue.slice(1);//用户输入的字符  如@娜米汇，即"娜米汇"
                        _AT['l'] = _value.slice(0, -_oValue.length).replace(/\n/g,'<br>'); //@前面的文字   
                        _AT['r'] = k.slice(_postion - _oValue.length+1, k.length);//@后面的文字   
                        _AT['pos']=_postion;//光标位置   
                        _AT['len']=_oValue.length;//光标位置至@的长度
                        _AT['rangeData']=_rangeData;
                        _AT['textarea']=textarea;
                        showTip(_AT);
                    } else {
                        hiddenTip();
                    }   
                } else {
                    hiddenTip();
                }   
            },  
            
            drawTextarea= function(textarea){
                var _left=$(textarea).offset().left + parseInt($(textarea).css("padding-left"))+ "px",   
                _top=$(textarea).offset().top + parseInt($(textarea).css("padding-top")) +"px",
                //_width=$(textarea).width() +"px",
                _width=$(textarea).width()-14+'px',
                _lineHeight=$(textarea).css("line-height"), 
                _fontSize=$(textarea).css("font-size"), 
                _Height=$(textarea).height()-12+'px', 
                Tstyle="height:"+_Height+";font-size:"+_fontSize+";line-height:"+_lineHeight+";width:"+_width+";left:"+_left+";top:"+_top;
                $at_textarea.attr("style",Tstyle);
               //alert(_fontSize)
            }
            	
           
            
            showTip = function(obj){
            	//alert(obj.r);return false;
            	if(obj.m=="lolitabox_@"){
            		obj.m="";
            	}
            	//alert(obj.m);return false;
             //  alert(window.location.host);return false;
            var url="/common/get_user_at_list.html";
            	//var url ="__APP__/get_user_at_list";//url你们改成后台拉数据的地址就行，格式在此文章的后面，我有说明
/*                if(obj.m == "namihui"){//这里写另外个地址是为了演示@最近联系人与根据输入后台返回匹配的数据，2种不同情况写的死数据
                    url = 'json.html';
                }*/
                $.ajax({
                    url: url,
                    type:'post',
                    dataType:"json",
					async:false,
                    data:{"key":obj.m},
                    success: function(result){
                    //	alert(result.info);return false;
                    	if(result.info!=""){
                    		buidTip(result.info,obj);
                    	}
                    }
                })
            },
            
            //创建tip，设置tip的位置  
            buidTip = function(html,obj) {
            	//alert(478);
                var _left, _top, Ttop, citeOfs,
              _string="<span>"+obj['l']+"</span>"+"<cite>@</cite>"+"<span>"+obj['r']+"</span>";
                //alert(_string);return false;
                $at_textarea.html(_string);
                citeOfs=$at_textarea.find("cite").offset();
                $at_textarea.css({'overflow':'hidden'})
               // $at_textarea.css({'white-space':'pre-wrap','white-space':'-moz-pre-wrap','white-space':'-pre-wrap' , 'white-space':'-o-pre-wrap' , 'word-wrap':'break-word'})
                	 
                
                
                _left=citeOfs.left;
                _top=citeOfs.top+parseInt($at_textarea.css("line-height"));
                Ttop = parseInt($at_textarea.offset().top+$(obj.textarea).height());
                if(_top > Ttop) {
                    _top = Ttop;
                }
               // console.log(html);return false;
                if(html!="" && html!=undefined && html!="undefined"){
                	var html_info="<li>选择昵称或轻敲空格完成输入</li>";
                	$.each(html,function(index,arr){
                		html_info+="<li>"+arr.nickname+"</li>";
                	})
                }else{
                	html_info="";
                }
                $at_userslist.css({
                    "left":_left,   
                    "top":_top,
                    "display":"block"
                }).html('<ul>'+html_info+'</ul>').find("li").eq(1).addClass("on");
                TipLiEvent(obj);
                $(obj.textarea).data("at",obj);
            },
            hiddenTip = function() {   
                $at_userslist.hide().find("li").off();
            },
            
            //键盘选择列表操作
            TipLiSelect = function(textarea,key){
                var li = $at_userslist.find("li"),
                    _len=li.length;
                switch(key) {
                    case 40:   
                        //向下键选择
                        li_Index++;
                        if(li_Index>_len-1) {   
                            li_Index=1;
                        }   
                        li.removeClass("on").eq(li_Index).addClass("on");
                        break;   
                    case 38:   
                        //向上键选择   
                        li_Index--;   
                        if(li_Index>1) {   
                            li_Index=_len-1;
                        }   
                        li.removeClass("on").eq(li_Index).addClass("on");
                        break;   
                    case 13:   
                        //enter键   
                        var txtData=li.filter(".on").text(),
                        obj = $(textarea).data("at");
                        add(txtData,obj);
                        hiddenTip();
                        break;
                    default:   
                };   
            },
            
            //添加列表绑定事件
            TipLiEvent = function(obj) {
                $at_userslist.find("li").on("click", function() {
                    if($(this).index() == 0) {
                        obj.textarea.focus();
                    } else {   
                        var txtData=$(this).text();
                        add(txtData,obj);
                    }
                    hiddenTip();
                    return false;   
                }).not(":first").hover( function() {   
                    li_Index=$(this).index();   
                    $(this).addClass("on").siblings().removeClass("on");
                    return false;
                }, function() {   
                    return false;
                });
            };
        });
       // alert(456);
    }
    
})(jQuery);