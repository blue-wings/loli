<?php
Class ImageReport{
	var $X;//图片大小X轴
	var $Y;//图片大小Y轴
	var $R;//背影色R值
	var $G;//...G.
	var $B;//...B.
	var $TRANSPARENT;//是否透明1或0
	var $IMAGE;//图片对像
	//-------------------
	var $ARRAYSPLIT;//指定用于分隔数值的符号
	var $ITEMARRAY;//数值
	var $REPORTTYPE;//图表类型,1为竖柱形2为横柱形3为折线形
	var $BORDER;//距离
	//-------------------
	var $FONTSIZE;//字体大小
	var $FONTCOLOR;//字体颜色
	var $XTAG=array();
	
	//--------参数设置函数
	function setImage($SizeX,$SizeY,$R,$G,$B,$Transparent){
		$this->X=$SizeX;
		$this->Y=$SizeY;
		$this->R=$R;
		$this->G=$G;
		$this->B=$B;
		$this->TRANSPARENT=$Transparent;
	}
	function setItem($ArraySplit,$ItemArray,$ReportType,$Border){
		$this->ARRAYSPLIT=$ArraySplit;
		$this->ITEMARRAY=$ItemArray;
		$this->REPORTTYPE=$ReportType;
		$this->BORDER=$Border;
	}
	function setFont($FontSize){
		$this->FONTSIZE=$FontSize;
	}
	//----------------主体
	function PrintReport($title=null,$xarray=array()){
		Header( "Content-type: image/gif");
		$this->XTAG=$xarray;
		//建立画布大小
		$this->IMAGE=ImageCreate($this->X,$this->Y+30);
		//设定画布背景色
		$background=ImageColorAllocate($this->IMAGE,$this->R,$this->G,$this->B);
		if($this->TRANSPARENT=="1"){
			//背影透明
			Imagecolortransparent($this->IMAGE,$background);
		}else{
			//如不要透明时可填充背景色
			ImageFilledRectangle($this->IMAGE,0,0,$this->X,$this->Y,$background);
		}
		//参数字体文小及颜色
		$this->FONTCOLOR=ImageColorAllocate($this->IMAGE,255-$this->R,255-$this->G,255-$this->B);
		
		if($title)
	    	imagettftext($this->IMAGE,16,0,$this->X/2-20,$this->Y+10,$this->FONTCOLOR,"./admin/Lib/ORG/Util/SIMLI.TTF",$title);
		
		Switch ($this->REPORTTYPE){
			case "0":
				break;
			case "1":
				$this->imageColumnS();
				break;
			case "2":
				$this->imageColumnH();
				break;
			case "3":
				$this->imageLine();
				break;
		}
		$this->printXY();
		$this->printAll();
	}
	//-----------打印XY坐标轴
	function printXY(){
		//画XY坐标轴*/
		$color=ImageColorAllocate($this->IMAGE,255-$this->R,255-$this->G,255-$this->B);
		$xx=$this->X/10;
		$yy=$this->Y-$this->Y/10;
		$this->imagelinethick($this->IMAGE,$this->BORDER,$this->BORDER,$this->BORDER,$this->Y-$this->BORDER,$color,1.2);//X轴
		$this->imagelinethick($this->IMAGE,$this->BORDER,$this->Y-$this->BORDER,$this->X-$this->BORDER,$this->Y-$this->BORDER,$color,1.2);//y轴
		//Y轴上刻度
		$rulerY=$this->Y-$this->BORDER;
		while($rulerY>$this->BORDER*2){
			$rulerY=$rulerY-$this->BORDER;
			ImageLine($this->IMAGE,$this->BORDER,$rulerY,$this->BORDER-2,$rulerY,$color);
		}
		//X轴上刻度
		$rulerX=$rulerX+$this->BORDER;
		$tag = empty($this->XTAG) ? 0: $this->XTAG[0];
		$len = strlen($tag)*2;
		ImageString($this->IMAGE,2,$rulerX-$len,$this->Y-$this->BORDER+5,$tag,$this->FONTCOLOR);
		$i=1;
		while($rulerX<($this->X-$this->BORDER*2)){
			$rulerX=$rulerX+$this->BORDER;
			//ImageLine($this->IMAGE,$this->BORDER,10,$this->BORDER+10,10,$color);
			ImageLine($this->IMAGE,$rulerX,$this->Y-$this->BORDER,$rulerX,$this->Y-$this->BORDER+5,$color);
            if($this->REPORTTYPE==3){
            	if(!empty($this->XTAG))
            		$temp = $this->XTAG[$i];
            	else
            		$temp = $i;
            	$len = strlen($temp)*2;
             	ImageString($this->IMAGE,2,$rulerX-$len,$this->Y-$this->BORDER+5,$temp,$this->FONTCOLOR);
            }
			$i++;
		}
	}

	//--------------竖柱形图
	function imageColumnS(){
		$item_array=Split($this->ARRAYSPLIT,$this->ITEMARRAY);
		$num=Count($item_array);
		$item_max=0;
		for ($i=0;$i<$num;$i++){
			$item_max=Max($item_max,$item_array[$i]);
		}
		$xx=$this->BORDER*2;
		//画柱形图
		for ($i=0;$i<$num;$i++){
			srand((double)microtime()*1000000);
			if($this->R!=255 && $this->G!=255 && $this->B!=255){
				$R=Rand($this->R,200);
				$G=Rand($this->G,200);
				$B=Rand($this->B,200);
			}else{
				$R=Rand(50,200);
				$G=Rand(50,200);
				$B=Rand(50,200);
			}
			$color=ImageColorAllocate($this->IMAGE,$R,$G,$B);
			//柱形高度
			$height=($this->Y-$this->BORDER)-($this->Y-$this->BORDER*2)*($item_array[$i]/$item_max);
			ImageFilledRectangle($this->IMAGE,$xx,$height,$xx+$this->BORDER,$this->Y-$this->BORDER,$color);
			ImageString($this->IMAGE,$this->FONTSIZE,$xx,$height-$this->BORDER,$item_array[$i],$this->FONTCOLOR);
			//用于间隔
			$xx=$xx+$this->BORDER*2;
		}
	}

	//-----------横柱形图
	function imageColumnH(){
		$item_array=Split($this->ARRAYSPLIT,$this->ITEMARRAY);
		$num=Count($item_array);
		$item_max=0;
		for ($i=0;$i<$num;$i++){
			$item_max=Max($item_max,$item_array[$i]);
		}
		$yy=$this->Y-$this->BORDER*2;
		//画柱形图
		for ($i=0;$i<$num;$i++){
			srand((double)microtime()*1000000);
			if($this->R!=255 && $this->G!=255 && $this->B!=255){
				$R=Rand($this->R,200);
				$G=Rand($this->G,200);
				$B=Rand($this->B,200);
			}else{
				$R=Rand(50,200);
				$G=Rand(50,200);
				$B=Rand(50,200);
			}
			$color=ImageColorAllocate($this->IMAGE,$R,$G,$B);
			//柱形长度
			$leight=($this->X-$this->BORDER*2)*($item_array[$i]/$item_max);
			ImageFilledRectangle($this->IMAGE,$this->BORDER,$yy-$this->BORDER,$leight,$yy,$color);
			ImageString($this->IMAGE,$this->FONTSIZE,$leight+2,$yy-$this->BORDER,$item_array[$i],$this->FONTCOLOR);
			//用于间隔
			$yy=$yy-$this->BORDER*2;
		}
	}

	//--------------折线图
	function imageLine(){
		$item_array=Split($this->ARRAYSPLIT,$this->ITEMARRAY);
		$num=Count($item_array);
		$item_max=0;
		for ($i=0;$i<$num;$i++){
			$item_max=Max($item_max,$item_array[$i]);
		}
		//$xx=$this->BORDER;
		//画柱形图
		for ($i=0;$i<$num;$i++){
			srand((double)microtime()*1000000);
			if($this->R!=255 && $this->G!=255 && $this->B!=255){
				$R=Rand($this->R,200);
				$G=Rand($this->G,200);
				$B=Rand($this->B,200);
			}else{
				$R=Rand(50,200);
				$G=Rand(50,200);
				$B=Rand(50,200);
			}
			$color=ImageColorAllocate($this->IMAGE,$R,$G,$B);
			//柱形高度
			$height_now=($this->Y-$this->BORDER)-($this->Y-$this->BORDER*2)*($item_array[$i]/$item_max);
			if($i!="0"){
				$this->imagelinethick($this->IMAGE,$xx,$height_next,$xx+$this->BORDER,$height_now,$color,2);
			}
			ImageString($this->IMAGE,$this->FONTSIZE,$xx+$this->BORDER,$height_now-$this->BORDER/2,$item_array[$i],$this->FONTCOLOR);
			$height_next=$height_now;
			//用于间隔
			$xx=$xx+$this->BORDER;
		}
	}

	//--------------完成打印图形http://knowsky.com
	function printAll(){
		ImageGIF($this->IMAGE);
		ImageDestroy($this->IMAGE);
	}
	//--------------调试
	function debug(){
		echo "X:".$this->X."
		Y:".$this->Y;
		echo "
		BORDER:".$this->BORDER;
		$item_array=split($this->ARRAYSPLIT,$this->ITEMARRAY);
		$num=Count($item_array);
		echo "
		数值个数:".$num."
		数值:";
		for ($i=0;$i<$num;$i++){
			echo "
			".$item_array[$i];
		}
	}
	
	/**
	 * 画粗线
	 * @param unknown_type $image
	 * @param unknown_type $x1
	 * @param unknown_type $y1
	 * @param unknown_type $x2
	 * @param unknown_type $y2
	 * @param unknown_type $color
	 * @param unknown_type $thick
	 * @author litingting
	 */
	function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
	{
		/* 下面两行只在线段直角相交时好使
		 imagesetthickness($image, $thick);
		return imageline($image, $x1, $y1, $x2, $y2, $color);
		*/
		if ($thick == 1) {
			return imageline($image, $x1, $y1, $x2, $y2, $color);
		}
		$t = $thick / 2 - 0.5;
		if ($x1 == $x2 || $y1 == $y2) {
			return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
		}
		$k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
		$a = $t / sqrt(1 + pow($k, 2));
		$points = array(
				round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
				round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
				round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
				round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
		);
		imagefilledpolygon($image, $points, 4, $color);
		return imagepolygon($image, $points, 4, $color);
	}
	
	
}
?>