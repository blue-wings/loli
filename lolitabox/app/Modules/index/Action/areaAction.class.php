<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 9/28/14
 * Time: 9:26 AM
 */
class areaAction extends commonAction {
    public function getProvincesJson(){
        $where["pid"]=C("AREA_PROVINCE_PID");
        $areas = M("Area")->where($where)->select();
        $this->ajaxReturn($areas, "JSON");
    }

    public function getCitiesJson(){
        $provinceId = $_GET["provinceId"];
        if(!$provinceId){
            return;
        }
        $where["pid"]=$provinceId;
        $areas = M("Area")->where($where)->select();
        $this->ajaxReturn($areas, "JSON");
    }

    public function getDistrictsJson(){
        $cityId = $_GET["cityId"];
        if(!$cityId){
            return;
        }
        $where["pid"]=$cityId;
        $areas = M("Area")->where($where)->select();
        $this->ajaxReturn($areas, "JSON");
    }
}