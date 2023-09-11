<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\goods\service\GoodsService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Prize;
use Exception;

/**
 * 
 */
class StoreChangeService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelQueryTrait;
    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\StoreChange';
    ///从ObjectAttrTrait中来
    // 定义对象的属性
    protected $objAttrs = [];
    // 定义对象是否查询过的属性
    protected $hasObjAttrQuery = [];
    // 定义对象属性的配置数组
    protected static $objAttrConf = [
        'storeChangeDtl' => [
            'class' => '\\xjryanse\\store\\service\\StoreChangeDtlService',
            'keyField' => 'change_id',
            'master' => true
        ],
    ];

    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 20220627
     * @param type $data
     * @param type $uuid
     */
    public static function ramPreSave(&$data, $uuid) {
        //【关联已有对账单明细】
        if (isset($data['storeChangeDtlIds'])) {
            //明细唯一性校验，用于批量生成账单
            self::dtlUniqueData($data['storeChangeDtlIds'], $data);
            //明细笔数
            $data['dtl_count'] = count($data['storeChangeDtlIds']);
            foreach ($data['storeChangeDtlIds'] as $value) {
                $info = StoreChangeDtlService::getInstance($value)->get();
                $data['change_type'] = $info['change_type'];
                $data['store_id'] = $info['store_id'];
                $data['user_id'] = $info['user_id'];
                $data['store_user_id'] = $info['store_user_id'];
            }
        }
        if (Arrays::value($data, 'has_settle')) {
            $data['change_time'] = Arrays::value($data, 'change_time') ?: date('Y-m-d H:i:s');
        }
    }

    public static function ramAfterSave(&$data, $uuid) {
        //【关联已有对账单明细】
        if (isset($data['storeChangeDtlIds'])) {
            //更新对账单订单的账单id
            foreach ($data['storeChangeDtlIds'] as $value) {
                //财务账单-订单；
                StoreChangeDtlService::getInstance($value)->setChangeIdRam($uuid);
            }
        }
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
        $dataDetail = Arrays::getByKeys($data, ['user_id', 'customer_user_id', 'store_user_id', 'bus_id', 'is_noticed', 'has_settle']);
        $lists = self::getInstance($uuid)->objAttrsList('storeChangeDtl');
        foreach ($lists as $v) {
            StoreChangeDtlService::getInstance($v['id'])->doUpdateRam($dataDetail);
        }
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

    public static function extraDetails($ids) {
        //数组返回多个，非数组返回一个
        $isMulti = is_array($ids);
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $con[] = ['id', 'in', $ids];
        $lists = self::selectX($con, '', '', ['password']);
        //乘客查询数组
        $dtlArr = StoreChangeDtlService::groupBatchCount('change_id', $ids);

        foreach ($lists as &$v) {
            //推荐人数
            $v['dtlCounts'] = Arrays::value($dtlArr, $v['id'], 0);
        }

        return $isMulti ? $lists : $lists[0];
        // return $isMulti ? Arrays2d::fieldSetKey($lists, 'id') : $lists[0];
    }

    /**
     * 结算
     */
//    protected function settle() {
//        $con[] = ['change_id', '=', $this->uuid];
//        StoreChangeDtlService::mainModel()->where($con)->update(['has_settle' => 1]);
//    }

    /**
     * 
     */
    protected function settleRam() {
        $info = $this->get();
        if (!$info['user_id'] || !$info['store_user_id']) {
            if (!$info['user_id']) {
                throw new Exception($info['change_type'] == 1 ? '入库人不能为空' : '领用人不能为空');
            }
            if (!$info['store_user_id']) {
                throw new Exception('库管员不能为空');
            }
        }

        $lists = $this->objAttrsList('storeChangeDtl');
        foreach ($lists as $v) {
            $data = [];
            $data['has_settle'] = 1;
            $data['change_time'] = date('Y-m-d H:i:s');
            StoreChangeDtlService::getInstance($v['id'])->updateRam($data);
        }
    }

//    /**
//     * 取消结算
//     */
//    protected function cancelSettle() {
//        $con[] = ['change_id', '=', $this->uuid];
//        StoreChangeDtlService::mainModel()->where($con)->update(['has_settle' => 0]);
//    }

    protected function cancelSettleRam() {
        $lists = $this->objAttrsList('storeChangeDtl');
        foreach ($lists as $v) {
            $data = [];
            $data['has_settle'] = 0;
            $data['change_time'] = null;

            StoreChangeDtlService::getInstance($v['id'])->updateRam($data);
        }
    }

    /**
     * 明细唯一性校验，用于批量生成账单,并将结果拼入data数组
     * 
     */
    public static function dtlUniqueData($dtlIds, &$data) {
        $cond[] = ['id', 'in', $dtlIds];
        $storeChangeList = StoreChangeDtlService::mainModel()->where($cond)->select();
        $storeChangeArr = $storeChangeList ? $storeChangeList->toArray() : [];
        //唯一性字段
        $uniqFields = ['store_id', 'user_id', 'customer_user_id', 'store_user_id'];
        $uniqExcep['store_id'] = '请选择同一仓库数据';
        $uniqExcep['user_id'] = '请选择同一入库/领用人数据';
        $uniqExcep['customer_user_id'] = '请选择同一客户数据';
        $uniqExcep['store_user_id'] = '请选择同一库管员数据';
        //唯一性校验
        foreach ($uniqFields as &$uniqField) {
            $uniqIds = array_column($storeChangeArr, $uniqField);
            if (count(array_unique($uniqIds)) > 1) {
                throw new Exception($uniqExcep[$uniqField]);
            }
            $data[$uniqField] = $uniqIds[0];
        }

        //【2】判断是否有已出单0
        $cone[] = ['hasChange', '=', 1];
        $hasChangeArr = Arrays2d::listFilter($storeChangeArr, $cone);
        if (count($hasChangeArr) > 0) {
            throw new Exception('请选择未生成单据的流水');
        }
    }

    /*
     * 获取描述信息
     */

    public function getDescribe() {
        $lists = $this->objAttrsList('storeChangeDtl');
        $con[] = ['id', 'in', array_column($lists, 'goods_id')];
        $goods = GoodsService::mainModel()->where($con)->column('goods_name,unit', 'id');
        $describeArr = [];
        foreach ($lists as $v) {
            // 20230519:Illegal string offset 'goods_name'
            $goods = Arrays::value($goods, $v['goods_id'], []);
            $describeArr[] = $goods['goods_name'] . ' ' . floatval($v['amount']) . $goods['unit'];
        }
        return implode(',', $describeArr);
    }

    /**
     * 取货值
     * @return type
     */
    public function getSumPrize() {
        $lists = $this->objAttrsList('storeChangeDtl');
        $money = 0;
        foreach ($lists as $v) {
            $money += Arrays::value($v, 'amount', 0) * Arrays::value($v, 'unit_prize', 0);
        }
        return $money;
    }

    /**
     * 20220629：单车的才有车辆id
     * @return type
     */
    public function getBusId() {
        $lists = $this->objAttrsList('storeChangeDtl');
        $buses = array_column($lists, 'bus_id');
        return count($buses) <> 1 ? '' : $buses[0];
    }

    /**
     * 获取同步数据
     */
    public function dataSyncRam() {
        $data['describe'] = $this->getDescribe();
        $data['sum_prize'] = $this->getSumPrize();
        // 2022-12-09：尝试放出？？
        $data['bus_id'] = $this->getBusId();
        return $this->updateRam($data);
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
     * 客户id
     */
    public function fCustomerId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 仓库id
     */
    public function fStoreId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 出库说明
     */
    public function fDescribe() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 出库人
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
