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
    use \xjryanse\traits\MainModelQueryTrait;

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

    public static function extraDetails($ids) {
        //数组返回多个，非数组返回一个
        $isMulti = is_array($ids);
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $con[] = ['id', 'in', $ids];
        $lists = self::selectX($con);

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

        return $isMulti ? $lists : $lists[0];
    }

    public function extraPreDelete() {
        self::checkTransaction();
        $con[] = ['order_id', '=', $this->uuid];
        $res = StoreChangeService::mainModel()->master()->where($con)->count(1);
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
     * 归属部门
     */
    public function fDeptId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 仓库名称
     */
    public function fStoreName() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 仓库地址
     */
    public function fAddress() {
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

    public function fManagerId() {
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
