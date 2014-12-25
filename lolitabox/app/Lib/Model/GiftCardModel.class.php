<?php
/**
 * 礼品卡模型
 * @author penglele
 */
class GiftCardModel extends Model {

	/**
	 * 激活礼品卡
	 * @author penglele
	 */
	public function activateGiftCard($giftCardId,$giftCardPwd,$userid){
		if(!$userid){
			//用户没有登录
			return 1000;
		}
		if(!$giftCardId || !$giftCardPwd){
			//礼品卡号或密码为空
			return 100;
		}


        M()->startTrans();
        try{
            D("DBLock")->getSingleUserLock($userid);
            $where["card_id"]=$giftCardId;
            $where["card_pwd"]=$giftCardPwd;
            $where["status"]=0;
            $giftCard = $this->where($where)->find();
            if(!$giftCard){
                //礼品卡号或密码错误
                return 100;
            }
            if($giftCard['status'] == C("GIFT_CARD_ACTIVATED")){
                //礼品卡已激活
                return 101;
            }
            $etime=strtotime($giftCard['indate']." 23:59:59",time());
            $ntime=time();
            if($ntime>$etime){
                //礼品卡已过期
                return 102;
            }
            $data['userid']=$userid;
            $data['activate_datetime']=date("Y-m-d H:i:s");
            $data['status']=C("GIFT_CARD_ACTIVATED");
            //保存用户的信息
            $this->where("card_id='".$giftCardId."'")->save($data);
            $sql= "UPDATE `users` SET balance=balance+".$giftCard["price"]." WHERE userid=".$userid;
            $this->db->execute ( $sql );
            M()->commit();
        }catch (Exception $e){
            M()->rollback();
            throw new Exception("数据库异常");
        }
        return 1;
	}
	
	
}
?>