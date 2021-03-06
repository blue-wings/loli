var Loli = Loli || {};

(function(){
	Loli.shoppingCart = function(options){
		this.options = $.extend({}, this.options, options);
		this.init();
		this.open=false;
        this.updating = 0;
		return this;
	}

	Loli.shoppingCart.prototype={
			options : {
				parentContainerId : "content",
				selfId : "ids",
				subscribeButtonClass : "subscribe-button",
				addProduct2CartUrl:null,
				getShoppingCartDetailUrl :null,
                deleteUrl : null,
                deleteButtonClassName : "chart-del",
				submitButtonId:"submit"
			},

			init : function(){
				this.initHtml();
			},

			initHtml : function(){
				var html = "<div class='fm1025' id='ids' style='height: 94px;'>"+
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
                    me.bindSubscribeButtonEvent();
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

				//实现数量的控制
				$(".num_down").click("click",function(){
					numControl($(this),"-");
					me.updateOneShoppingCart($(this).parent().find('.num'));
				})
				$(".num_up").click("click",function(){
					numControl($(this),"+");
					me.updateOneShoppingCart($(this).parent().find('.num'));
				})

				//关于点击时数量变化方法
				function numControl(proId,state){
					var maxNum = parseInt(proId.parent().find('.num').attr('max_num')); 
					var num = parseInt(proId.parent().find('.num').val());
					if(state == '-'){
						if(num == 1){
							proId.attr('disabled',true);
							noty({'text':"已到达最小值!",'layout':'topLeft','type':'error'});
						}else{
							if((num-1)<maxNum){
								proId.parent().find('.num').val(num-1);
								proId.parent().find('.num_up').attr('disabled',false);
							}else{
								proId.parent().find('.num').val(maxNum);
								noty({'text':"最多可添加"+maxNum+"件!",'layout':'topLeft','type':'error'});
							}
						}

					}
					if(state == '+'){
						if(num >=maxNum ){
							proId.attr('disabled',true);
							proId.parent().find('.num').val(maxNum);
							noty({'text':"最多可添加"+maxNum+"件!",'layout':'topLeft','type':'error'});
						}else{
							proId.parent().find('.num').val(num+1);
							proId.parent().find('.num_down').attr('disabled',false);
						}
					}
				}


				//直接修改数量时对输入字符的控制
				$(".num").bind("change",function(){
					check($(this),$(this).val());
					me.updateOneShoppingCart($(this));
				})

				//直接修改数量,对输入的字符的控制
				function check(proId,num){
					var m_num = proId.attr('max_num');
					var c = Number(num);
					if( c > m_num){
						proId.parent().find('.num_up').attr('disabled',true);
						proId.val(m_num);
						noty({'text':"最多可添加"+m_num+"件!",'layout':'topLeft','type':'error'});
					}else if (c < 1){
						proId.parent().find('.num_up').attr('disabled',true);
						proId.val(1);
						noty({'text':"已到达最小值!",'layout':'topLeft','type':'error'});
					}else{
						proId.parent().find('.num_up').attr('disabled',false);
						proId.parent().find('.num_up').attr('disabled',false);
					}
				}
				
				$("#"+me.options.submitButtonId).click(function(){
                        if(this.updating != 0){
                            noty({'text':"稍等片刻...",'layout':'topLeft','type':'alter'});
                        }
					    me.updateAllShoppingCartsAndcreateOrder();
				});

                $("."+me.options.deleteButtonClassName).click(function(){
                    var productName = $(this).attr("product-name");
                    var chartId = $(this).attr("chart-id");
                    if(confirm("真的要删除"+productName+"吗？")){
                        me.updating++;
                        $.ajax({
                            url:me.options.deleteUrl,
                            type:"POST",
                            datatype:"json",
                            data:{"shoppingCartId":chartId},
                            cache:false,
                            success:function(result){
                                if(result.result){
                                    me._reload(function(){
                                        me.updating--;
                                        noty({'text':"删除成功!",'layout':'topLeft','type':'success'});
                                    })
                                }
                            }
                        })
                    }
                })

			},

            bindSubscribeButtonEvent : function(){
                var me= this;
                var all_box = $('#all_box');
                var pro_btn = all_box.find(".subscribe-button");

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


                pro_btn.die("click").live("click",function(){
                    proBtnClick($(this));
                    slider();
                });
                //点击按钮加入产品开始
                function proBtnClick(btnobj){
                    var pid = btnobj.attr("pid");
                    me.updating ++;
                    $.ajax({
                        url:me.options.addProduct2CartUrl,
                        type:"POST",
                        datatype:"json",
                        data:{"pid":pid,"pNum":1},
                        cache:false,
                        success:function(result){
                            if(result.result){
                                me._reload(function(){
                                    noty({'text':"更新购物车成功",'layout':'topLeft','type':'success'});
                                })
                            }else{
                                noty({'text':result.msg,'layout':'topLeft','type':'error'});
                            }
                            me.updating --;
                        },
                        error:function(result){
                            noty({'text':"添加到购物车失败!",'layout':'topLeft','type':'error'});
                        }
                    });


                }
            },
			
			hide : function(){
				$("#"+this.options.selfId).hide();
				
			},
			
			show : function(){
                this.bindSubscribeButtonEvent();
				$("#"+this.options.selfId).show();
			},
			
			updateOneShoppingCart : function(productNumEle){
				var chartId = productNumEle.attr("id");
				var productNum = productNumEle.val();
				var productOldNum = productNumEle.attr("old-value");
				if(productNum != productOldNum){
					var chartIds = new Array();
					var chartProductNums = new Array();
					chartIds.push(chartId);
					chartProductNums.push(productNum);
					this._update(chartIds, chartProductNums, function(data){
						productNumEle.attr("old-value",productNum);
						noty({'text':"更新购物车成功",'layout':'topLeft','type':'success'});	
					});
				}
			},
			
			updateAllShoppingCartsAndcreateOrder : function(){
                var me = this;
                if(this.updating != 0){
                    setTimeout(function(){
                        me.updateAllShoppingCartsAndcreateOrder();
                    }, 500);
                    return;
                }
				var chartItems = $(".chart_item");
				var chartIds = new Array();
				var chartProductNums = new Array();
				var hasError = false;
				$.each(chartItems, function(){
					var chartId = $(this).find(".chart_id");
					var chartProductNum = $(this).find(".chart_product_num");
					if(chartId && chartId.length && chartProductNum && chartProductNum.length){
						var oldValue = $(chartProductNum[0]).attr("old-value");
						var value = $(chartProductNum[0]).val();
						chartIds.push($(chartId[0]).val());
						if(oldValue != value){
							chartProductNums.push($(chartProductNum[0]).val());
						}
					}else{
						hasError = true;
						return false;
					}
				})
				if(!chartIds.length){
					noty({'text':"请先添加商品",'layout':'topLeft','type':'error'});
                    return;
				}
				if(!hasError){
					if(chartProductNums.length){
						this._update(chartIds, chartProductNums, function(data){
							$("#shopping-cart-ids").val(data.shoppingCartIds);
							$("#create-order-form").submit();	
						})
					}else{
						$("#shopping-cart-ids").val(chartIds.join(","));
						$("#create-order-form").submit();
					}
						
				}
			},
			
			_update : function(chartIds, chartProductNums, func) {
				$("#shoppingCartIds").val(chartIds.join(","));
				$("#productNums").val(chartProductNums.join(","));
                var me = this;
                me.updating++;
				$("#chart_form").ajaxSubmit({
					type:"post",
					datatype:"json",
					success:function(data){
						if(data.result){
							$("#productNum").text(data.productTotalNum);
							$("#productWeight").text(data.weight+"kg");
							$("#totalCost").text("￥"+data.totalCost);
							if(func){
								func(data);
							}
						}else{
							noty({'text':data.msg,'layout':'topLeft','type':'error'});
						}
                        me.updating--;
					}
				});
			},

            _reload : function(callback){
                var me = this;
                $("#"+me.options.selfId).load(me.options.getShoppingCartDetailUrl, function(){
                    me.bindEvent();
                    if(me.open){
                        $(".open_chart").trigger("click");
                    }else{
                        $(".close_chart").trigger("click");
                    }
                    if(callback){
                        callback();
                    }
                });
            }
	}

})();


