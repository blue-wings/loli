
$(function(){
	bindHoverCard();
});
bindHoverCard=function () {
    var isHover = false;
    var showHoverCard,removeHoverCard,CurrentCard;
	var selector=$(".bind_hover_card");//要绑定的对象
	
    selector.die("mouseover").live("mouseover", function () {
        if (CurrentCard) CurrentCard.remove();
        if (removeHoverCard) clearTimeout(removeHoverCard);
        if (showHoverCard) clearTimeout(showHoverCard);
        var obj = $(this);
		//显示名片
        showHoverCard = setTimeout(function(){hoverCardBuilder(obj)}, 500);
    });
    selector.die("mouseout").live("mouseout", function () {
        if (!isHover) {
            clearTimeout(showHoverCard);
        } else if(CurrentCard) {
			removeCard();
			CurrentCard.hover(function () {
				clearTimeout(removeHoverCard);
			}, function () {
				removeCard();
			});
        }
        isHover = false;
    });
	//删除名片
	removeCard=function(timer){
		removeHoverCard = setTimeout(function () { CurrentCard.remove() }, 600);
	}
	//构建名片DOM
	hoverCardBuilder=function (hoverObject) {
		var scrolltops =  document.body.scrollTop+document.documentElement.scrollTop;
		var scrolllefts = document.body.clientWidth - hoverObject.offset().left-hoverObject.width();
		if (!isHover) {
			isHover = true;
			if((hoverObject.offset().top - scrolltops)<=190){
				 var bmHoverCard = $("<div>").addClass("bm_hover_card").css({ 
											top: hoverObject.offset().top+hoverObject.height()-2,
											left: hoverObject.offset().left+ hoverObject.width()/2});
			}else{
				 var bmHoverCard = $("<div>").addClass("bm_hover_card").css({ 
											top: hoverObject.offset().top -187,
											left: hoverObject.offset().left+ hoverObject.width()/2});	
			}
			
			if(scrolllefts<=285){
				var bmHoverCard = $("<div>").addClass("bm_hover_card").css({ 
											top: hoverObject.offset().top -187,
											left: hoverObject.offset().left-255});
			}
			
			var bmHoverCardArrow = $("<div>").addClass("bm_hover_card_arrow");
			var bmHoverCardBorder = $("<div>").addClass("bm_hover_card_border");
		//	var bmLoading = $("<img>").attr({ "border": "0", "src": "public/images/loading.gif" }).addClass("loading")
			var bmHoverCardBefore = $("<div>").addClass("bm_hover_card_before");
			var bmHoverCardContainer = $("<div>").addClass("bm_hover_card_container").html(bmHoverCardBefore);
			bmHoverCard.append(bmHoverCardArrow).append(bmHoverCardBorder).append(bmHoverCardContainer);				
			/**插入DOM**/
			$("body").prepend(bmHoverCard);
			CurrentCard=$(".bm_hover_card");
			/**获取数据
			*bm_id为用户id，用于查询用户信息
			**/
			if (hoverObject.attr("bm_id")) {
				
			//	bmHoverCardContainer.html(strHtml);
				/*ajax动态获取用户信息*/
				$.ajax({
					url:"/common/getfaceinfo",
					type:"post",
					data:{id:hoverObject.attr("bm_id")+"_"+hoverObject.attr("bm_type")},
					dataType:"html",
					timeout:8000,
					/*beforeSend:function(){
						bmHoverCardBefore.html(bmLoading);
					},*/
					success:function(data){
						bmHoverCardContainer.html(data);
						var maxWidth = CurrentCard.find('.name').width();
		                 $('.signature').css({'width':(maxWidth+70)+'px'});
						
						if((hoverObject.offset().top - scrolltops)<=190){
							CurrentCard.find('.W_layer').css({'margin-top':'13px', 'margin-bottom':'0px'});
							CurrentCard.find('.arrow').removeClass('arrow_l').addClass('arrow_t')
						}
						
						if(scrolllefts<=285){
							CurrentCard.find('.arrow').removeClass('arrow_l').addClass('arrow_r')
						}
						
	                    if(CurrentCard.find(".bm_hover_card_container").height() <=170){
							if(CurrentCard.find(".arrow").hasClass("arrow_l")){
								$(".bm_hover_card").css({ 
									top: hoverObject.offset().top-150});
								CurrentCard.css({'height':'150px'})
							}
						}
	                    
	                    if((hoverObject.offset().top - scrolltops)<=190 && scrolllefts<=285){
							$(".bm_hover_card").css({ 
								top: hoverObject.offset().top+hoverObject.height()-2,
								left: hoverObject.offset().left-255});
								CurrentCard.find('.arrow').removeClass('arrow_t').removeClass('arrow_r').addClass('arrow_tr');
	                    }
	                    
	                    
	                    
						CurrentCard.find(".close").click(removeCard);
					},
					error:function(){
						bmHoverCardBefore.html("读取错误");
					}
				});
			} else {
			
				bmHoverCardBefore.html("缺少查询参数");
			}
		}
	}
};
