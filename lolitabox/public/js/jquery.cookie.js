	//js 获取cookie
	jQuery.cookie = function(name, value, options) {
	    if (typeof value != 'undefined') { // name and value given, set cookie
	        options = options || {};
	        if (value === null) {
	            value = '';
	            options.expires = -1;
	        }
	        var expires = '';
	        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
	            var date;
	            if (typeof options.expires == 'number') {
	                date = new Date();
	                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
	            } else {
	                date = options.expires;
	            }
	            expires = '; expires=' + date.toUTCString(); // use expires
	        }
	        var path = options.path ? '; path=' + options.path : '';
	        var domain = options.domain ? '; domain=' + options.domain : '';
	        var secure = options.secure ? '; secure' : '';
	        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
	    } else { // only name given, get cookie
	        var cookieValue = null;
	        if (document.cookie && document.cookie != '') {
	            var cookies = document.cookie.split(';');
	            for (var i = 0; i < cookies.length; i++) {
	                var cookie = jQuery.trim(cookies[i]);
	                // Does this cookie string begin with the name we want?
	                if (cookie.substring(0, name.length + 1) == (name + '=')) {
	                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
	                    break;
	                }
	            }
	        }
	        return cookieValue;
	    }
	};
	
//	 function findDimensions(topPeak,wrapPeak,btnPeak){
//		var isIE6 = !-[1,] && !window.XMLHttpRequest;
//		var meg = $(wrapPeak);
//		var ids =$(topPeak);
//		var ids_offset = $(topPeak).offset();
//		
//		var scrolltop_top =  ids_offset.top;
//		var scrolltop_left =  ids_offset.left ;
//		var footer_top = $(btnPeak).offset().top-(meg.height()+21);
//
//		$(window).scroll(function(){
//			var H = $(this).scrollTop();
//			var fl_left = scrolltop_left+620+ "px";
//			 if( H >= scrolltop_top){
//				 if(isIE6){
//					meg.css({position: "absolute","top":H+"px","left":fl_left});
//				 }else if(H >= footer_top){
//					meg.css({position: "absolute","top":footer_top+"px"});
//				 }else{
//					meg.css({"position":"fixed","top":"68px","left":fl_left});
//				}
//			}
//			else{
//				meg.css({"position":"static"});
//			}
//		});
//	}
