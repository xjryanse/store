<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\store\service\StoreChangeService;
use xjryanse\store\service\StoreManageService;
use xjryanse\store\service\ViewStoreDtlService;
use xjryanse\logic\Arrays;

/**
 * 仓库管理
 */
class StoreService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    use \xjryanse\store\service\index\FieldTraits;
    
    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\Store';

    /**
     * 20220629获取仓库的管理用户id数组
     */
    public function getManageUserIds() {
        $managerIds = StoreManageService::manageUserIds($this->uuid);
        $info = $this->get();
        $managerIds[] = $info['manager_id'];
        return array_unique($managerIds);
    }

    public static function extraDetails( $ids ){
        return self::commExtraDetails($ids, function($lists) use ($ids){
            //sku查询数组
            $changeArr = StoreChangeService::groupBatchCount('store_id', $ids);
            $conIncome[] = ['change_type', '=', 1];
            $incomeChangeArr = StoreChangeService::groupBatchCount('store_id', $ids, $conIncome);
            $conOutcome[] = ['change_type', '=', 2];
            $outcomeChangeArr = StoreChangeService::groupBatchCount('store_id', $ids, $conOutcome);
            $conRef[] = ['change_type', '=', 3];
            $refChangeArr = StoreChangeService::groupBatchCount('store_id', $ids, $conRef);


            $dtlArr = StoreChangeDtlService::groupBatchCount('store_id', $ids);
            $incomeDtlArr = StoreChangeDtlService::groupBatchCount('store_id', $ids, $conIncome);
            $outcomeDtlArr = StoreChangeDtlService::groupBatchCount('store_id', $ids, $conOutcome);
            $refDtlArr = StoreChangeDtlService::groupBatchCount('store_id', $ids, $conRef);

            $goodsCount = ViewStoreDtlService::groupBatchCount('store_id', $ids);
            $prizeSum = ViewStoreDtlService::groupBatchSum('store_id', $ids, 'sum_prize');
            //库管员人数
            $manageCount = StoreManageService::groupBatchCount('store_id', $ids);

            foreach ($lists as &$v) {
                //订单数
                $v['changeCounts'] = Arrays::value($changeArr, $v['id'], 0);
                $v['incomeChangeCounts'] = Arrays::value($incomeChangeArr, $v['id'], 0);
                $v['outcomeChangeCounts'] = Arrays::value($outcomeChangeArr, $v['id'], 0);
                $v['refChangeCounts'] = Arrays::value($refChangeArr, $v['id'], 0);
                //订单详情数
                $v['dtlCounts'] = Arrays::value($dtlArr, $v['id'], 0);
                $v['incomeDtlCounts'] = Arrays::value($incomeDtlArr, $v['id'], 0);
                $v['outcomeDtlCounts'] = Arrays::value($outcomeDtlArr, $v['id'], 0);
                $v['refDtlCounts'] = Arrays::value($refDtlArr, $v['id'], 0);
                // 商品数
                $v['goodsCounts'] = Arrays::value($goodsCount, $v['id'], 0);
                // 总货值
                $v['prizeSum'] = Arrays::value($prizeSum, $v['id'], 0);
                // 库管员
                $v['manageCounts'] = Arrays::value($manageCount, $v['id'], 0);
            }

            return $lists;
        },true);
    }

}
