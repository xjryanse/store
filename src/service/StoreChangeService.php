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
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
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
    
    use \xjryanse\store\service\change\TriggerTraits;
    use \xjryanse\store\service\change\FieldTraits;
    use \xjryanse\store\service\change\DoTraits;    

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    //乘客查询数组
                    $dtlArr = StoreChangeDtlService::groupBatchCount('change_id', $ids);

                    foreach ($lists as &$v) {
                        //20240706：逐步弃用，使用uniStoreChangeDtlCount替代
                        $v['dtlCounts'] = Arrays::value($dtlArr, $v['id'], 0);
                    }

                    return $lists;
                }, true);
    }

    /**
     * 
     */
    protected function settleRam() {
        $info = $this->get();
        if (!$info['user_id'] || !$info['store_user_id']) {
            if (!$info['user_id']) {
                // 20240706
                // throw new Exception($info['change_type'] == 1 ? '入库人不能为空' : '领用人不能为空');
            }
            if (!$info['store_user_id']) {
                // 20240706
                // throw new Exception('库管员不能为空');
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
     * 生成新单，获取单号
     */
    public static function generateNewGetId($storeId,$changeType,$data = []){
        $data['store_id']       = $storeId;
        $data['change_type']    = $changeType;
        return self::saveGetIdRam($data);
    }
    


}
