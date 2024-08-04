<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;

/**
 * 
 */
class ViewStoreDtlService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;


    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\ViewStoreDtl';

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    // TODO解决多仓库bug
                    $goodsId = array_column($lists, 'goods_id');
                    $changeDtlCounts = StoreChangeDtlService::groupBatchCount('goods_id', $goodsId);
                    $conIncome[] = ['change_type', '=', 1];
                    $incomeAmounts = StoreChangeDtlService::groupBatchSum('goods_id', $goodsId, 'amount', $conIncome);
                    $incomeDtlCounts = StoreChangeDtlService::groupBatchCount('goods_id', $goodsId, $conIncome);
                    $conOutcome[] = ['change_type', '=', 2];
                    $outcomeAmounts = StoreChangeDtlService::groupBatchSum('goods_id', $goodsId, 'amount', $conOutcome);
                    $outcomeDtlCounts = StoreChangeDtlService::groupBatchCount('goods_id', $goodsId, $conOutcome);
                    $conRef[] = ['change_type', '=', 3];
                    $refAmounts = StoreChangeDtlService::groupBatchSum('goods_id', $goodsId, 'amount', $conRef);
                    $refDtlCounts = StoreChangeDtlService::groupBatchCount('goods_id', $goodsId, $conRef);
                    foreach ($lists as &$v) {
                        $v['hasAmount'] = floatval($v['amount']) ? 1 : 0;
                        // 2022-12-09入库总数
                        $v['incomeAmount'] = Arrays::value($incomeAmounts, $v['goods_id'], 0);
                        // 2022-12-09出库总数
                        $v['outcomeAmount'] = Arrays::value($outcomeAmounts, $v['goods_id'], 0);
                        // 2022-12-09退库总数
                        $v['refAmount'] = Arrays::value($refAmounts, $v['goods_id'], 0);
                        //流水数
                        $v['changeDtlCounts'] = Arrays::value($changeDtlCounts, $v['goods_id'], 0);
                        // 入库流水数
                        $v['incomeDtlCounts'] = Arrays::value($incomeDtlCounts, $v['goods_id'], 0);
                        // 出库流水数
                        $v['outcomeDtlCounts'] = Arrays::value($outcomeDtlCounts, $v['goods_id'], 0);
                        // 退库流水数
                        $v['refDtlCounts'] = Arrays::value($refDtlCounts, $v['goods_id'], 0);
                        // 平均单价
                        $v['avgUnitPrize'] = intval($v['amount']) ? round($v['sum_prize'] / $v['amount'], 3) : 0;
                    }

                    return $lists;
                });
    }

    /**
     *
     */
    public function fAmount() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fCompanyId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 商品id，sku
     */
    public function fGoodsId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * [冗]仓库id
     */
    public function fStoreId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fSumPrize() {
        return $this->getFFieldValue(__FUNCTION__);
    }

}
