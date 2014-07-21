var Loli = Loli || {};

(function(){
	Loli.shoppingCart = function(options){
		this.options = $.extend({}, this.options, options);
		console.log(this.options);
		this.init();
		//new Loli.shoppingCart({addProduct2CartUrl:"<{:u('shoppingCart/addProduct2Cart')}>",getShoppingCartDetailUrl:"<{:u('shoppingCart/detail')}>"});
		return this;
	}

	Loli.shoppingCart.prototype={
			options : {
				parentContainerId : "main-container",
				selfId : "ids",
				subscribeButtonClass : "subscribe-button",
				addProduct2CartUrl:null,
				getShoppingCartDetailUrl :null
			},

			init : function(){
				this.initHtml();
				this.bindEvent();
			},

			reloadShoppingCart : function(){
				$("."+me.options.subscribeButtonClass).click(function(){
					var pid = $(this).attr("pid");
					$.ajax({
						url:me.options.addProduct2CartUrl,
						type:"POST",
						datatype:"json",
						data:{"pid":pid,"pNum":1},
						cache:false,
						success:function(result){
							if(result.result){
								$("#"+me.options.selfId).load(me.options.getShoppingCartDetailUrl);
							}else{
								console.log(result.msg);
							}

						},
						error:function(result){
							console.log(result);		
						}
					});

				})
			},

			initHtml : function(){
				var html = "<div class='fm1025' id='ids'>"+
				"<div class='wp1020 chart' id='meg'>"+
				"<div class='hd'>&nbsp;</div>"+
				"<div class='mn'>"+
				"<div class='con'>"+
				"<!--chart展开-start-->"+
				"<div class='chart_list cfl' id='sider_wrap'>"+
				"<a href='javascript:;' class='a_arrow aleft agray l'><em class='gz_arrow_l'>&nbsp;</em></a>"+
				"<div class='chart_div'>"+
				"<ul class='chart_ul cfl'></ul>"+
				"</div>"+
				"<a href='javascript:;' class='a_arrow aright agray l'><em class='gz_arrow_r'>&nbsp;</em></a>"+
				"</div>"+
				"<!--chart展开-end-->"+
				"<div class='chart_info cfl'>"+
				"<p class='info l tr'>"+
				"<span>您选择了<i>3</i>件商品</span>"+
				"<span>重量：<i>1.182kg</i></span>"+
				"<span>订阅金额(不含运费）：<i class='gz_t_red'>￥ 271</i></span>"+
				"</p>"+
				"<p class='btn r'><a href='javascript:;'>订阅结算</a></p>"+
				"</div>"+
				"</div>"+
				"<p class='open_chart'>"+
				"<a href='javascript:;'>"+
				"<span class='gz_arrow_down'>&nbsp;</span>"+
				"<span>展开</span>"+
				"</a>"+
				"</p>"+
				"<p class='close_chart'>"+
				"<a href='javascript:;''>"+
				"<span class='gz_arrow_up'>&nbsp;</span>"+
				"<span>收起</span>"+
				"</a>"+
				"</p>"+
				"<!--未展开chart icon-->"+
				"<p class='chart_ico_i'><em class='gz_chart_i'>&nbsp;</em></p>"+
				"<!--未展开chart icon-->"+
				"<!--展开chart icon-->"+
				"<p class='chart_ico_m'><em class='gz_chart_m'>&nbsp;</em></p>"+
				"<!--展开chart icon-->"+
				"</div>"+
				"<div class='ft'>&nbsp;</div>"+
				"</div>"+
				"</div>";
				var parentContainer = $(this.options.parentContainerId);
				if(parentContainer.children().length){
					var firstChild = $(parentContainer.children()[0]);
					$(html).insertBefore(firstChild);
				}else{
					$(html).appendTo(parentContainer);
				}
			},

			bindEvent : function(){
				var meg = $('#meg');
				var ids =$("#ids");
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
				});
				//页面滚动时chart_list收缩
				$('.close_chart').click(function(){ 
					$('.chart_list').hide();
					$(this).hide();
					$('.chart_ico_i').show();
					$('.chart_ico_m').hide();
					$('.open_chart').show();
					$('.chart_info').find('.info').removeClass('tr');
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
					var oValue = btnobj.attr('mark');
					var imgsrc = btnobj.parents('.mn').find('.img img').attr('src'); 
					var proname = btnobj.parents('.mn').find('.name').text();
					var proprice = btnobj.prev().text();
					var max_num = btnobj.attr('max_num');
					if($.inArray(oValue,buyLiArray) == -1){
						numpro++;
						buyLiArray.push(oValue);
						var addHtml = '<li><dl><dt class="img"><img src="'+imgsrc+
						'" alt=""/></dt><dd class="name">'+proname+
						'</dd><dd class="price">'+proprice+
						'</dd><dd class="num_ctrl" id="id_'+oValue+
						'"><input type="button" class="num_down"/><input type="text" class="num" max_num="'+max_num+
						'" value="1"/><input type="button" class="num_up"/></dd></dl></li>'
						buy_box.find('ul').append(addHtml);
					}else{
						//alert("你已经添加过此产品！")
						var clickpro = $("#id_"+oValue).find('.num_up');
						numControl(clickpro,"+");//点击相同的产品，只是数量上增加
					}
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

			}
	}

})();


