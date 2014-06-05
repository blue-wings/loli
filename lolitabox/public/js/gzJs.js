(function(){

//高度一致
var r_h = $(".gz"),
    l_h = $('.side');
if(r_h && l_h){
  l_h.css({"height":r_h.height()-8+"px"}) ;
}
})();