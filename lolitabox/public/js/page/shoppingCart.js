var Loli = Loli || {};

(function(){
	Loli.shoppingCart = function(options){
		this.options = $.extend({}, this.options, options);
		this.init();
		this.open=true;
		return this;
	}

	Loli.shoppingCart.prototype={
			options : {
				parentContainerId : "content",
				selfId : "ids",
				subscribeButtonClass : "subscribe-button",
				addProduct2CartUrl:null,
				getShoppingCartDetailUrl :null,
			},

			init : function(){
				this.initHtml();
			},

			initHtml : function(){
				var html = "<div class='fm1025' id='ids' style='margin-bottom:-16px'>"+
				"</div>";
				var parentContainer = $("#"+this.options.parentContainerId);
				if(parentContainer.children().length){
					var firstChild = $(parentContainer.children()[0]);
					$(html).insertBefore(firstChild);
				}else{
					$(html).appendTo(parentContainer);
				}
				var me = this;
				$("#"+this.options.selfId).load(this.options.getShoppingCartDetailUrl, function(){
					me.bindEvent();
				});
			},

			bindEvent : function(){
				var meg = $('#meg');
				var ids =$("#ids");
				var me = this;
				if(meg && ids){
					function findDimensions(){           
						var isIE6 = !-[1,] && !window.XMLHttpRequest;
						var scrolltop_top =  ids.offset().top;
						var scrolltop_left =  ids.offset().left;    
						$(window).scroll(function(){//定位导航栏
							var H = $(this).scrollTop();
							if( H >= scrolltop_top){
								if(isIE6){
									meg.css({ "position": "absolute","top":H-82+"px"});

								}else{
									meg.css({"position":"fixed","top":"-4px"});
								}  
							}else{
								meg.css({"position":"relative","z-index":"10"}); 
							}
						});
					}
					findDimensions();
					window.onresize=findDimensions;
				}

				//chart_list展开
				$('.open_chart').click(function(){
					$('.chart_list').show();
					$(this).hide();
					$('.chart_ico_i').hide();
					$('.chart_ico_m').show();
					$('.close_chart').show();
					$('.chart_info').find('.info').removeClass('tr');
					me.open=true;
				});
				//页面滚动时chart_list收缩
				$('.close_chart').click(function(){ 
					$('.chart_list').hide();
					$(this).hide();
					$('.chart_ico_i').show();
					$('.chart_ico_m').hide();
					$('.open_chart').show();
					$('.chart_info').find('.info').removeClass('tr');
					me.open=false;
				})

				var buy_box = $('#sider_wrap');
				var all_box = $('#all_box');
				var pro_btn = all_box.find("input:button");
				var buyLiArray = new Array();
				var numpro = 0,that,numli=0;
				//mark标记
				if(all_box){
					for(var i = 0; i < pro_btn.length; i++){
						pro_btn.eq(i).attr('mark','pro_'+i);
					}
				}

				pro_btn.click(function(){
					proBtnClick($(this));
					slider();
				});
				//点击按钮加入产品开始
				function proBtnClick(btnobj){
					var pid = btnobj.attr("pid");
					$.ajax({
						url:me.options.addProduct2CartUrl,
						type:"POST",
						datatype:"json",
						data:{"pid":pid,"pNum":1},
						cache:false,
						success:function(result){
							if(result.result){
								$("#"+me.options.selfId).load(me.options.getShoppingCartDetailUrl, function(){
									me.bindEvent();
									if(me.open){
										$(".open_chart").trigger("click");
									}else{
										$(".close_chart").trigger("click");
									}
								});
							}else{
								alert(result.msg);
							}

						},
						error:function(result){
							alert("添加到购物车失败!");		
						}
					});

					
				}

				//订阅内容滚动
				function slider(){
					var count = $("#sider_wrap li").length - 6;  
					var interval = $("#sider_wrap li:first").outerWidth(true); 
					var curIndex = 0;
					var maxIndex = -count*interval+"px";
					if(count > 0){
						$('.aright').removeClass('agray');
					}else{
						$('.aright').addClass('agray');
					}
					$('.a_arrow').click(function(){
						if ($(this).hasClass('aleft')) {
							--curIndex;
						}
						if($(this).hasClass('aright')){
							++curIndex;
						}
						$("#sider_wrap ul").stop().animate({"left" : -curIndex*interval + "px"},300,function(){
							var leftul = $("#sider_wrap ul").css("left");
							if(leftul == "0px"){
								$('.aleft').addClass('agray');
							}else if(leftul == maxIndex){
								$('.aright').addClass('agray');
							}else{
								$('.aleft').removeClass('agray');
								$('.aright').removeClass('agray');
							}
						});     
					});

				}

				//实现数量的控制
				$(".num_down").live("click",function(){    
					numControl($(this),"-");
				})
				$(".num_up").live("click",function(){
					numControl($(this),"+");
				})

				//关于点击时数量变化方法
				function numControl(proId,state){
					var maxNum = parseInt(proId.parent().find('.num').attr('max_num')); 
					var num = parseInt(proId.parent().find('.num').val());
					if(state == '-'){
						if(num == 1){
							proId.attr('disabled',true);
						}else{
							var num_d = proId.parent().find('.num').val(num-1).stop().val();
							if(num_d <= 1){
								proId.attr('disabled',true);
							}else if(num_d > 1 && num_d < maxNum){
								proId.attr('disabled',false);
								proId.parent().find('.num_up').attr('disabled',false);
							}
						}

					}
					if(state == '+'){
						var num_u = proId.parent().find('.num').val(num+1).stop().val();
						if(num_u >= maxNum){
							proId.attr('disabled',true);
						}else if(num_u >= 1 && num_u < maxNum){
							proId.attr('disabled',false);
							proId.parent().find('.num_down').attr('disabled',false);
						}

					}
				}


				//直接修改数量时对输入字符的控制
				$(".num").live("change",function(){
					check($(this),$(this).val());
				})

				//直接修改数量,对输入的字符的控制
				function check(proId,num){
					var m_num = proId.attr('max_num');
					var c = Number(num);
					if(!c || c < 1 || c > m_num){
						alert("请输入1~"+m_num+"的整数");
						proId.val(1);
					}   
				}
				//chartlist可滚动显示

			},
			
			hide : function(){
				$("#"+this.options.selfId).hide();
				
			},
			
			show : function(){
				$("#"+this.options.selfId).show();
			}
	}

})();


