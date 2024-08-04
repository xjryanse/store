<?php
namespace xjryanse\store\service\change;

use xjryanse\store\service\StoreChangeDtlService;
use xjryanse\logic\Arrays;
use Exception;
/**
 * 分页复用列表
 */
trait TriggerTraits{

    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 20220627
     * @param type $data
     * @param type $uuid
     */
    public static function ramPreSave(&$data, $uuid) {
        
        // 20240726:默认是已结，除非特殊指定为未结
        $data['has_settle'] = isset($data['has_settle']) ? Arrays::value($data, 'has_settle') : 1;
        
        if (Arrays::value($data, 'has_settle')) {
            $data['change_time'] = Arrays::value($data, 'change_time') ?: date('Y-m-d H:i:s');
        }
        // 20240706:废弃原有逻辑
        $changeDtlsArr = Arrays::value($data, 'changeDtls', []);
        if ($changeDtlsArr) {
            $feeList                    = [];
            foreach ($changeDtlsArr as $k => $v) {
                $tmpData                = $v;
                $tmpData['change_id']   = $uuid;
                $tmpData['change_type'] = Arrays::value($data, 'change_type') ? : Arrays::value($v, 'change_type') ;

                $feeList[]              = $tmpData;
            }
            StoreChangeDtlService::saveAllRam($feeList);
        }
    }

    public static function ramAfterSave(&$data, $uuid) {
        //【关联已有对账单明细】
        /*
         * 20240706优化废弃
        if (isset($data['storeChangeDtlIds'])) {
            //更新对账单订单的账单id
            foreach ($data['storeChangeDtlIds'] as $value) {
                //财务账单-订单；
                StoreChangeDtlService::getInstance($value)->setChangeIdRam($uuid);
            }
        }
         * 
         */
    }

    public static function ramPreUpdate(&$data, $uuid) {
        $info = self::getInstance($uuid)->get();
        $hasSettleRaw = Arrays::value($info, 'has_settle');
        $data['hasSettleChange'] = $hasSettleRaw != Arrays::value($data, 'has_settle');
        //20220716:增加时间
        if ($data['hasSettleChange']) {
            if (Arrays::value($data, 'has_settle')) {
                $data['change_time'] = Arrays::value($data, 'change_time') ?: date('Y-m-d H:i:s');
            }
        }
    }

    /**
     * 20220628
     * @param type $data
     * @param type $uuid
     */
    public static function ramAfterUpdate(&$data, $uuid) {
        if (isset($data['has_settle']) && $data['hasSettleChange']) {
            if ($data['has_settle']) {
                //$accountLogId   = Arrays::value($data, 'account_log_id');
                self::getInstance($uuid)->settleRam();
            } else {
                self::getInstance($uuid)->cancelSettleRam();
            }
        }
        //20220629：关联更新
        /* 
         * 20240706: 进行优化，和更新
        $dataDetail = Arrays::getByKeys($data, ['user_id', 'customer_user_id', 'store_user_id', 'bus_id', 'is_noticed', 'has_settle']);
        $lists = self::getInstance($uuid)->objAttrsList('storeChangeDtl');
        foreach ($lists as $v) {
            StoreChangeDtlService::getInstance($v['id'])->doUpdateRam($dataDetail);
        }
         * 
         */
    }

    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 不删明细
     */
    public function extraPreDelete() {
        //20220627
        self::stopUse(__METHOD__);
    }

    /**
     * 20220627:优化性能
     */
    public function ramPreDelete() {
        $info = $this->get();
        if (Arrays::value($info, 'has_settle')) {
            throw new Exception('出入库单已结，不可删');
        }

        $lists = $this->objAttrsList('storeChangeDtl');
        StoreChangeDtlService::changeIdSetNullRam($this->uuid);
        foreach ($lists as $v) {
            StoreChangeDtlService::getInstance($v['id'])->deleteRam();
        }
    }
}
