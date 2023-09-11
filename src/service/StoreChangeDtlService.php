<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\goods\service\GoodsService;
use xjryanse\logic\Arrays;
use xjryanse\logic\DataCheck;
use xjryanse\logic\Sql;
use Exception;

/**
 * 
 */
class StoreChangeDtlService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\StoreChangeDtl';

    /**
     * 钩子-保存前
     */
    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    $rawDtlCounts = self::groupBatchCount('raw_dtl_id', $ids);
                    $rawDtlSum = self::groupBatchSum('raw_dtl_id', $ids, 'remainAmount');
                    foreach ($lists as &$v) {
                        $v['rawDtlCount'] = Arrays::value($rawDtlCounts, $v['id'], 0);
                        $v['rawDtlAmount'] = floatval(Arrays::value($rawDtlSum, $v['id'], 0));
                        // 2022-12-08:是否还有数量
                        $v['hasAmount'] = floatval($v['remainAmount']) ? 1 : 0;
                        // 2022-12-09：是否有退库
                        $v['hasRef'] = floatval($v['ref_amount']) ? 1 : 0;
                        // 2022-12-09：是否有出库
                        $v['hasOut'] = floatval($v['out_amount']) ? 1 : 0;
                    }
                    return $lists;
                });
    }

    /**
     * 出库/退库数量匹配
     */
    public static function outcomeAmountMatch($goodsId, $amount) {
        $con[] = ['goods_id', '=', $goodsId];
        $con[] = ['change_type', '=', 1];
        $con[] = ['remainAmount', '>', 0];
        $lists = self::mainModel()->where($con)->select();

        $arr = [];
        foreach ($lists as $v) {
            if ($amount <= 0) {
                continue;
            }
            $tmpArr = [];
            $tmpArr['raw_dtl_id'] = $v['id'];
            $tmpArr['goods_id'] = $goodsId;
            $tmpArr['unit_prize'] = $v['unit_prize'];
            $tmpArr['amount'] = $amount > $v['remainAmount'] ? $v['remainAmount'] : $amount;
            $amount -= $tmpArr['amount'];
            $arr[] = $tmpArr;
        }
        if ($amount > 0) {
            throw new Exception('库存不足，匹配失败，' . $amount . '商品id:' . $goodsId);
        }
        return $arr;
    }

    public static function ramPreSave(&$data, $uuid) {
        DataCheck::must($data, ['store_id', 'change_type', 'goods_id', 'amount']);
        $goodsId = $data['goods_id'];
        $amount = Arrays::value($data, 'amount');
        if (!Arrays::value($data, 'change_type')) {
            $data['change_type'] = $amount >= 0 ? 1 : 2;
        }
        //20220525入库；退库，正值
        if (in_array(Arrays::value($data, 'change_type'), [1, 3])) {
            $data['amount'] = abs($amount); //入账，正值
        }
        if (Arrays::value($data, 'change_type') == 2) {
            //20220524增加出库库存校验；没有库存的不允许出库；
            $stock = self::getStockByGoodsId($data['goods_id']);
            if ($stock < abs($amount)) {
                throw new Exception($data['goods_id'] . '库存不足无法出库,当前' . $stock);
            }
            $data['amount'] = -1 * abs($amount); //出库，负值
        }
        if (Arrays::value($data, 'raw_dtl_id')) {
            $rawDtlId = Arrays::value($data, 'raw_dtl_id');
            $rawDtlInfo = self::getInstance($rawDtlId)->get();
            if (!$rawDtlInfo) {
                throw new Exception('raw_dtl_id关联记录未找到');
            }
            //TODO，有bug??
            if (abs($rawDtlInfo['remainAmount']) < abs($data['amount'])) {
                throw new Exception('关联记录数量不足，不可操作');
            }
            $data['unit_prize'] = $rawDtlInfo['unit_prize'];
            $data['unit'] = $rawDtlInfo['unit'];
        }
        // 2022-12-07:增加单据时间（兼容补单）
        if (!Arrays::value($data, 'bill_time')) {
            $data['bill_time'] = date('Y-m-d H:i:s');
        }
        // 2022-12-09
        if (!Arrays::value($data, 'unit')) {
            $data['unit'] = GoodsService::getInstance($goodsId)->fUnit();
        }
    }

    /**
     * 钩子-保存后
     */
    public static function extraAfterSave(&$data, $uuid) {
        
    }

    public static function ramAfterSave(&$data, $uuid) {
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
     * 钩子-更新前
     */
    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 钩子-更新后
     */
    public static function extraAfterUpdate(&$data, $uuid) {
        
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

    /**
     * 钩子-删除前
     */
    public function extraPreDelete() {
        self::stopUse(__METHOD__);
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
            if ($count <> 1) {
                throw new Exception('不可删除，带多笔明细的出入库单');
            }
            StoreChangeService::getInstance($changeId)->doDeleteRam();
            StoreChangeService::getInstance($changeId)->objAttrsUnSet('storeChangeDtl', $this->uuid);
            StoreChangeService::getInstance($changeId)->dataSyncRam();
        }
        if ($info['goods_id']) {
            //商品信息更新库存
            GoodsService::getInstance($info['goods_id'])->updateStockRam();
        }
    }

    public function ramAfterDelete($data) {
        if (Arrays::value($data, 'raw_dtl_id')) {
            // 更新退库数量
            self::getInstance($data['raw_dtl_id'])->updateRefAmount();
            // 2022-12-08:更新出库数量
            self::getInstance($data['raw_dtl_id'])->updateOutAmount();
        }
    }

    /**
     * 一般用于删除单据
     */
    public static function changeIdSetNull($changeId) {
        if (!$changeId) {
            return false;
        }
        $con[] = ['change_id', 'in', $changeId];
        self::mainModel()->where($con)->update(['change_id' => null]);
    }

    /**
     * 20220627替代上述方法
     * @param type $changeId
     * @return boolean
     */
    public static function changeIdSetNullRam($changeId) {
        if (!$changeId) {
            return false;
        }
        $lists = StoreChangeService::getInstance($changeId)->objAttrsList('storeChangeDtl');
        foreach ($lists as $data) {
            StoreChangeDtlService::getInstance($data['id'])->updateRam(['change_id' => '']);
        }
    }

    /**
     * 钩子-删除后
     */
    public function extraAfterDelete() {
        
    }

    /**
     * 根据商品id，取库存值
     */
    public static function getStockByGoodsId($goodsId) {
        $con[] = ['goods_id', '=', $goodsId];
        return self::sum($con, 'amount');
    }

    /**
     * 逐步弃用，使用getStockBySpuIds 替代 spuid，获取旗下所有商品的库存总和
     * @param type $spuId
     * @return type
     */
    public static function getStockBySpuId($spuId) {
        $cond[] = ['spu_id', '=', $spuId];
        $goodsIds = GoodsService::ids($cond);

        $con[] = ['goods_id', 'in', $goodsIds];
        return self::sum($con, 'amount');
    }

    /**
     * 空明细设定出入库单id
     */
    public function setChangeId($changeId) {
        $info = $this->get(0);
        if (Arrays::value($info, 'change_id')) {
            throw new Exception('出入库明细' . $this->uuid . '已经对应了单号' . Arrays::value($info, 'change_id'));
        }
        return $this->update(['change_id' => $changeId]);
    }

    /**
     * 20220627
     * @param type $changeId
     * @return type
     * @throws Exception
     */
    public function setChangeIdRam($changeId) {
        $info = $this->get(0);
        if (Arrays::value($info, 'change_id')) {
            throw new Exception('出入库明细' . $this->uuid . '已经对应了单号' . Arrays::value($info, 'change_id'));
        }
        return $this->updateRam(['change_id' => $changeId]);
    }

    /**
     * 更新商品的库存余额
     */
    public function updateGoodsStockRam() {
        $info = $this->get(0);
        $goodsId = $info['goods_id'];
        GoodsService::getInstance($goodsId)->updateStockRam();
    }

    /**
     * 更新退库数量和金额
     * @global array $glSqlQuery
     * @return boolean
     */
    public function updateRefAmount() {
        global $glSqlQuery;
        $mainTable = self::getTable();
        $mainField = "ref_amount";
        $dtlTable = self::getTable();
        $dtlStaticField = "amount";
        $dtlUniField = "raw_dtl_id";
        $dtlCon[] = ['main.id', '=', $this->uuid];
        // 1入库；2出库；3退库
        $dtlCon[] = ['main.change_type', '=', 2];
        $sql = Sql::staticUpdate($mainTable, $mainField, $dtlTable, $dtlStaticField, $dtlUniField, $dtlCon);

        //扔一条sql到全局变量，方法执行结束后执行
        $glSqlQuery[] = $sql;
        return true;
    }

    /*
     * 2022-12-08 更新出库数量
     */

    public function updateOutAmount() {
        global $glSqlQuery;
        $mainTable = self::getTable();
        $mainField = "out_amount";
        $dtlTable = self::getTable();
        $dtlStaticField = "amount";
        $dtlUniField = "raw_dtl_id";
        $dtlCon[] = ['main.id', '=', $this->uuid];
        // 1入库；2出库；3退库
        $dtlCon[] = ['main.change_type', '=', 1];
        $sql = Sql::staticUpdate($mainTable, $mainField, $dtlTable, $dtlStaticField, $dtlUniField, $dtlCon);

        //扔一条sql到全局变量，方法执行结束后执行
        $glSqlQuery[] = $sql;
        return true;
    }

    /**
     *
     */
    public function fId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fAppId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fCompanyId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * [冗]客户id
     */
    public function fCustomerId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * [冗]仓库id
     */
    public function fStoreId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 入库/出库单id
     */
    public function fBillId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 明细id
     */
    public function fDtlId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * [冗]明细名称
     */
    public function fDtlName() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 数量
     */
    public function fAmount() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 单价
     */
    public function fUnitPrize() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 金额
     */
    public function fSumPrize() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * [冗]单位
     */
    public function fUnit() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 入库人
     */
    public function fUserId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 客户对接人
     */
    public function fCustomerUserId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 仓库管理员
     */
    public function fStoreUserId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 凭据id
     */
    public function fFileId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fSort() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 状态(0禁用,1启用)
     */
    public function fStatus() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 有使用(0否,1是)
     */
    public function fHasUsed() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 锁定（0：未锁，1：已锁）
     */
    public function fIsLock() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 锁定（0：未删，1：已删）
     */
    public function fIsDelete() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 备注
     */
    public function fRemark() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 创建者
     */
    public function fCreater() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 更新者
     */
    public function fUpdater() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 创建时间
     */
    public function fCreateTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 更新时间
     */
    public function fUpdateTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

}
