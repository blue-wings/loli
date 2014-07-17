var Loli = Loli || {};

(function(){
	Loli.shoppingCart = function(options){
		this.options = $.extend({}, this.options, options);
		console.log(this.options);
		this.init();
		new Loli.shoppingCart({addProduct2CartUrl:"<{:u('shoppingCart/addProduct2Cart')}>",getShoppingCartDetailUrl:"<{:u('shoppingCart/detail')}>"});
		return this;
	}
	
	Loli.shoppingCart.prototype={
		options : {
			parentContainerId : "main-container",
			selfId : "shopping-cart",
			subscribeButtonClass : "subscribe-button",
			addProduct2CartUrl:null,
			getShoppingCartDetailUrl :null
		},
		
		init : function(){
			var me = this;
			if($("#"+me.options.selfId).length){
				return;
			}
			var shoppingCartDiv = $("<div>").attr("id",me.options.selfId);
			shoppingCartDiv.appendTo($("#"+me.options.parentContainerId));
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
		}
			
	}
	
})();


