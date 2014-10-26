<?php

/**
 * Class ArchiveModel
 */
class ArchiveModel extends Model {

    /**
     * 得到主页需要显示的属性
     */
    public function getIndexProperty($userId){
        $archiveProperty = D("ArchiveProperty");
        $list = $archiveProperty->field("archive_property.name as property_name,archive_property.type as property_type,archive_value.*")
            ->join("archive_value on archive_value.property_id=archive_property.id")
            ->order('sort desc,archive_property.id asc')->select();
        $userProperty = D("ArchiveUser");
        $myProperties = $userProperty->where(array('userid'=>$userId))->select();
        $pvMap = array();
        if($myProperties){
            foreach($myProperties as $row){
                $pvMap[$row['property_id'].'-'.$row['value_id']] = 1;
            }
        }

        $properties = array();
        foreach($list as $row){
            if(!isset($properties[$row['property_id']])){
                $properties[$row['property_id']] = array("property_id"=>$row['property_id'],
                    "property_name"=>$row['property_name'],
                    "checked_values" => '',
                    "type"=>$row['property_type'],"values"=>array());
            }

            if(isset($pvMap[$row['property_id'].'-'.$row['id']])){
                $checkedValues = $properties[$row['property_id']]['checked_values'];
                if(empty($checkedValues)){
                    $properties[$row['property_id']]['checked_values'] = $row['name'];
                }else{
                    $properties[$row['property_id']]['checked_values'] = $checkedValues.'、'.$row['name'];
                }

                array_push($properties[$row['property_id']]['values'],array('value_id'=>$row['id'],'value_name'=>$row['name'],'checked'=>true));
            }else{
                array_push($properties[$row['property_id']]['values'],array('value_id'=>$row['id'],'value_name'=>$row['name'],'checked'=>false));
            }

        }
        return array_values($properties);
    }
    public function updateUserArchive($userId,$propertyId,$valueIds){
        $valueNames = '';
        try{
            M()->startTrans();
            $archiveUser = D("ArchiveUser");
            $archiveValue = M("ArchiveValue");
            $archiveUser->where(array('userid'=>$userId,'property_id'=>$propertyId))->delete();
            if(!empty($valueIds)){
                foreach ($valueIds as $valueId) {
                    $ok = $archiveUser->add(array(
                        'userid'=>$userId,
                        'property_id'=>$propertyId,
                        'value_id'=>$valueId
                    ));
                    //todo 写入索引

                    $name = $archiveValue->field('name')->where(array('id'=>$valueId))->find();
                    if(empty($valueNames)){
                        $valueNames = $name['name'];
                    }else{
                        $valueNames = $valueNames.'、'.$name['name'];
                    }
                }
            }
            M()->commit();
        }catch (Exception $e){
            M()->rollback();
            return false;
        }
        return $valueNames;

    }
}