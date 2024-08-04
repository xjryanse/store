<?php
namespace xjryanse\store\service\changeDtl;

use xjryanse\goods\service\GoodsService;
use xjryanse\store\service\StoreChangeService;
use xjryanse\logic\Arrays;
use xjryanse\logic\DataCheck;
use Exception;
/**
 * 分页复用列表
 */
trait TriggerTraits{

    /**
     * 钩子-保存前
     */
    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 钩子-更新前
     */
    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 钩子-删除前
     */
    public function extraPreDelete() {
        self::stopUse(__METHOD__);
    }
    
    /**
     * 钩子-保存前
     */
    public static function ramPreSave(&$data, $uuid) {

        DataCheck::must($data, ['change_type', 'amount']);
        $changeType = Arrays::value($data, 'change_type');

        $goodsId = $data['goods_id'];
        $amount = Arrays::value($data, 'amount');
        if (!$changeType) {
            $data['change_type'] = $amount >= 0 ? 1 : 2;
        }
        // 20240705:入库和调账不用，其他要
        if( session(SESSION_COMPANY_ID) == 6 && !in_array($data['change_type'],[1,5,6]) && !Arrays::value($data, 'raw_dtl_id')){
            throw new Exception('无关联的前向记录编号，请联系开发');
        }

        //20220525入库；退库，正值
        if (in_array(Arrays::value($data, 'change_type'), [1, 3])) {
            $data['amount'] = abs($amount); //入账，正值
        }
        if (Arrays::value($data, 'change_type') == 2) {
            //20220524增加出库库存校验；没有库存的不允许出库；
            $stock = self::getStockByGoodsId($data['goods_id']);
            if ($stock < abs($amount)) {
                $goodsName = GoodsService::getInstance($data['goods_id'])->fGoodsName();
                // 20240718：todo:发现bug，需探索新的模式:增加仓库属性
                // throw new Exception($goodsName . ' 库存不足无法出库,当前库存' . $stock);
            }
            $data['amount'] = -1 * abs($amount); //出库，负值
        }
        // 2022-12-07:增加单据时间（兼容补单）
        if (!Arrays::value($data, 'bill_time')) {
            $data['bill_time'] = date('Y-m-d H:i:s');
        }
        // 2022-12-09
        if (!Arrays::value($data, 'unit')) {
            $data['unit'] = GoodsService::getInstance($goodsId)->fUnit();
        }
        // 20240726
        $data = self::changeTypeCheckDealData($data['change_type'], $data);

        // 20240624：无单号，则匹配一个
        if (!Arrays::value($data, 'change_id')){
            $sD['has_settle'] = isset($data['has_settle']) ? Arrays::value($data, 'has_settle') : 1;
            $sD['user_id']    = Arrays::value($data, 'user_id');
            $data['change_id'] = StoreChangeService::generateNewGetId($data['store_id'],$data['change_type'], $sD);
        }
        
        // 20240706
        self::redunFields($data, $uuid);
    }
    
    public static function ramAfterSave(&$data, $uuid) {
        //20240717:校验库位 
        self::getInstance($uuid)->checkSetPlace();

        self::getInstance($uuid)->updateGoodsStockRam();
        // 20220628：明细直接生成单据？？
        if (Arrays::value($data, 'directBill')) {
            $storeData['storeChangeDtlIds'] = [$uuid];
            $res = StoreChangeService::saveRam($storeData);
            //20220629
            $data['change_id'] = $res['id'];
        }
        if (Arrays::value($data, 'change_id')) {
            $changeId = Arrays::value($data, 'change_id');
            StoreChangeService::getInstance($changeId)->objAttrsPush('storeChangeDtl', $data);
            StoreChangeService::getInstance($changeId)->dataSyncRam();
        }
        //20220630
        if (Arrays::value($data, 'raw_dtl_id')) {
            // 更新退库数量
            self::getInstance($data['raw_dtl_id'])->updateRefAmount();
            // 2022-12-08:更新出库数量
            self::getInstance($data['raw_dtl_id'])->updateOutAmount();
        }
    }
    /**
     * 20240717:校验库位
     * @return bool
     */
    public function checkSetPlace(){
        if($this->calNeedPlace() && !$this->fSetPlace()){
            throw new Exception('放置位置(库位)未填写');
        }
    }
    
    
    /**
     * 钩子-更新前
     */
    public static function ramPreUpdate(&$data, $uuid) {

    }


    public static function ramAfterUpdate(&$data, $uuid) {
        self::getInstance($uuid)->updateGoodsStockRam();
        $info = self::getInstance($uuid)->get(0);
        $changeId = Arrays::value($info, 'change_id');
        if ($changeId) {
            // 2022-12-09为了兼容一种情况，当原先没有change_id,后来更新了change_id,需把全部数据更新给属性
            $infoArr = is_array($info) ? $info : $info->toArray();
            $sData = array_merge($infoArr, $data);
            StoreChangeService::getInstance($changeId)->objAttrsUpdate('storeChangeDtl', $uuid, $sData);
            StoreChangeService::getInstance($changeId)->dataSyncRam();
        }
    }

    public function ramPreDelete() {
        // 2022-12-08：有关联记录，则不可删
        $conRaw[] = ['raw_dtl_id', '=', $this->uuid];
        $rawCount = self::where($conRaw)->count();
        if ($rawCount) {
            throw new Exception('有后向关联数据，不可删除');
        }

        $info = $this->get();
        $changeId = Arrays::value($info, 'change_id');
        if ($changeId) {
            $con[] = ['change_id', '=', $changeId];
            $count = self::count($con);
            if ($count == 1) {
                // throw new Exception('不可删除，带多笔明细的出入库单');
                StoreChangeService::getInstance($changeId)->doDeleteRam();
                StoreChangeService::getInstance($changeId)->objAttrsUnSet('storeChangeDtl', $this->uuid);
                StoreChangeService::getInstance($changeId)->dataSyncRam();
            }
        }
        if ($info['goods_id']) {
            //商品信息更新库存
            GoodsService::getInstance($info['goods_id'])->updateStockRam();
        }
    }

    /**
     * 钩子-删除后
     */
    public function ramAfterDelete($data) {
        if (Arrays::value($data, 'raw_dtl_id')) {
            // 更新退库数量
            self::getInstance($data['raw_dtl_id'])->updateRefAmount();
            // 2022-12-08:更新出库数量
            self::getInstance($data['raw_dtl_id'])->updateOutAmount();
        }
    }

    protected static function redunFields(&$data, $uuid){

        if(Arrays::value($data, 'change_id')){
            $data['store_id'] = StoreChangeService::getInstance($data['change_id'])->fStoreId();
        }

        if (Arrays::value($data, 'raw_dtl_id')) {
            $rawDtlId           = Arrays::value($data, 'raw_dtl_id');
            $rawDtlInfo         = self::getInstance($rawDtlId)->get();
            $data['unit_prize'] = $rawDtlInfo['unit_prize'];
            $data['unit']       = $rawDtlInfo['unit'];
        }

        return $data;
    }
    
}
