$(function(){
//全选
    $("#allSelect").click(function(){
        $ul = $('ul li :checkbox');
        if($(this).is(':checked')){
            $ul.each(function(){
                $(this).attr('checked',true);
            });    
        }else{
            $ul.each(function(){
                $(this).attr('checked',false);
            });        
        }            
    });
    //反选
    $("#InverSelect").click(function(){
        $ul = $('ul li :checkbox');
 
        $ul.each(function(){
            if($(this).is(':checked')){
                $(this).attr('checked',false);
            }else{
                $(this).attr('checked',true);    
            }
        });                
    });
 
    $('ul li :checkbox').click(function(){
        $ul = $('ul li :checkbox:not(:checked)');
        if($ul.length){
            $(":checkbox[value='全选']").attr('checked',false);
        }else{
            $(":checkbox[value='全选']").attr('checked',true);
        }
    });
	
    
    /* 鼠标移动变色 */
	$("#datatable  tr td:first-child").each(function(i){		
		var tr=$("td",$(this).parent());		
		var color=i%2 ? '#f5f5f5':'#dddddd';
		tr.css("background",color);
		tr.data("bgcolor",color);
	})
	
	$("#datatable tr td").mouseover(function () {
		var tr=$("td",$(this).parent());		
		if (tr.data("background")=='1') return;
		tr.css("background","#FFCC99");		
	}).mouseout(function () {
		var tr=$("td",$(this).parent());
		if (tr.data("background")=='1') return;		 
		tr.css("background",tr.data("bgcolor"));
	}).click(function(){
		var tr=$("td",$(this).parent());
		var val=tr.data("background")=='1' ? '0':'1';
		tr.data("background",val);
		tr.css("background","#ddCC66");
	});
	
});