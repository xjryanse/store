<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\approval\interfaces\ApprovalOutInterface;
use xjryanse\store\service\StoreChangeDtlService;
use xjryanse\logic\Debug;
use xjryanse\logic\Arrays2d;

/**
 * 仓库管理
 */
class StoreApplyService extends Base implements MainModelInterface, ApprovalOutInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;
    use \xjryanse\approval\traits\ApprovalOutTrait;
    
    use \xjryanse\store\service\apply\ApprovalTraits;
    use \xjryanse\store\service\apply\TriggerTraits;
    use \xjryanse\store\service\apply\MeTraits;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\StoreApply';
    //直接执行后续触发动作
    protected static $directAfter = true;
    

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    foreach($lists as &$v){
                        $v['isUserMe'] = $v['user_id'] == session(SESSION_USER_ID) ? 1 : 0;
                        // 仓库处理：0未发；1已发；2有缺货
                        //
                        // $v['storeDeal'] = 1;
                        $v['storeDeal'] = self::getInstance($v['id'])->calStoreDeal();
                    }
            
                    return $lists;
                }, true);
    }
    
    /**
     * 撤单
     */
    public function doCancel(){
        // 申请单撤销
        $this->doApplyCancel();
        // 审批单撤销
        $this->approvalCancel();
        return true;
    }
    
    private function doApplyCancel(){
        $data['is_cancel']      = 1;
        $data['cancel_time']    = date('Y-m-d H:i:s');

        return $this->doUpdateRam($data);
    }
    /**
     * 20240719：计算仓库处理状态
     */
    public function calStoreDeal(){
        $dtls = $this->objAttrsList('storeApplyDtl');
        // 申请数量
        $applyAmount = Arrays2d::sum($dtls, 'amount');

        $dtlIds = Arrays2d::uniqueColumn($dtls, 'id');
        
        $con = [['apply_dtl_id','in',$dtlIds]];
        $storeChangeAmount = StoreChangeDtlService::mainModel()->where($con)->sum('amount');
        if(!$storeChangeAmount){
            // 仓库未处理
            return 0;
        }
        if(abs($applyAmount) >= abs($storeChangeAmount) ){
            // 仓库部分处理，有缺货
            return 2;
        }
        // 仓库已处理
        return 1;
        //  return abs($applyAmount == ;
    }
    
}
