<?php
namespace xjryanse\store\service\apply;

use xjryanse\store\service\StoreApplyDtlService;
use xjryanse\logic\Arrays;
use Exception;
/**
 * 分页复用列表
 */
trait TriggerTraits{

    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }
    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }
    public function extraPreDelete() {
        self::stopUse(__METHOD__);
    }

    /**
     * 20220627
     * @param type $data
     * @param type $uuid
     */
    public static function ramPreSave(&$data, $uuid) {

        $data['apply_time'] = Arrays::value($data, 'apply_time') ? : date('Y-m-d H:i:s');
        // 20240706:废弃原有逻辑
        $applyDtlsArr       = Arrays::value($data, 'applyDtls', []);
        if(!$applyDtlsArr){
            throw new Exception('请填写明细');
        }
        
        $feeList                    = [];
        foreach ($applyDtlsArr as $k => $v) {
            $tmpData                = $v;
            $tmpData['apply_id']    = $uuid;
            $tmpData['bus_id']      = Arrays::value($v, 'bus_id') ? : Arrays::value($data, 'bus_id');

            $feeList[]              = $tmpData;
        }
        StoreApplyDtlService::saveAllRam($feeList);
    }

    public static function ramAfterSave(&$data, $uuid) {
        
        // 20240707:默认是需要审批的，直接写
        self::getInstance($uuid)->approvalAdd();
    }

    public static function ramPreUpdate(&$data, $uuid) {

    }

    /**
     * 20220628
     * @param type $data
     * @param type $uuid
     */
    public static function ramAfterUpdate(&$data, $uuid) {

    }
    
    /**
     * 20220627:优化性能
     */
    public function ramPreDelete() {

    }
}
