<?php
namespace xjryanse\store\service\inventory;

use xjryanse\store\service\StoreInventoryDtlService;

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
        // 20240706:废弃原有逻辑
        $inventoryDtls       = Arrays::value($data, 'inventoryDtls', []);
        if(!$inventoryDtls){
            throw new Exception('请填写盘点明细');
        }

        $feeList                    = [];
        foreach ($inventoryDtls as $k => $v) {
            $tmpData                    = $v;
            $tmpData['inventory_id']    = $uuid;

            $feeList[]              = $tmpData;
        }
        // 20240721:盘点明细
        StoreInventoryDtlService::saveAllRam($feeList);
    }

    public static function ramAfterSave(&$data, $uuid) {
        
        // 20240707:默认是需要审批的，直接写
        // self::getInstance($uuid)->approvalAdd();
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
