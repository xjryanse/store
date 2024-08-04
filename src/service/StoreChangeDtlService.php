<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\goods\service\GoodsService;
use xjryanse\logic\Arrays;
use xjryanse\logic\DataCheck;
use xjryanse\logic\Sql;
use xjryanse\sql\service\SqlService;
use Exception;

/**
 * 
 */
class StoreChangeDtlService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\StoreChangeDtl';
    //直接执行后续触发动作
    protected static $directAfter = true;

    use \xjryanse\store\service\changeDtl\TriggerTraits;
    use \xjryanse\store\service\changeDtl\TypeCheckTraits;
    use \xjryanse\store\service\changeDtl\FieldTraits;
    use \xjryanse\store\service\changeDtl\CalTraits;
    use \xjryanse\store\service\changeDtl\DoTraits;
    use \xjryanse\store\service\changeDtl\StaticsTraits;

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    $rawDtlCounts = self::groupBatchCount('raw_dtl_id', $ids);
                    $rawDtlSum = self::groupBatchSum('raw_dtl_id', $ids, 'remainAmount');
                    foreach ($lists as &$v) {
                        $v['hasRawDtl']     = $v['raw_dtl_id'] ? 1:0;
                        // 20240706替换数据库中存的sum_prize字段;发现求和不好处理，还是算了吧
                        // $v['allPrize']      = $v['amount'] * Arrays::value($v, 'unit_prize', 0);
                        $v['rawDtlCount']   = Arrays::value($rawDtlCounts, $v['id'], 0);
                        $v['rawDtlAmount']  = floatval(Arrays::value($rawDtlSum, $v['id'], 0));
                        // 2022-12-08:是否还有数量
                        $v['hasAmount'] = floatval($v['remainAmount']) ? 1 : 0;
                        // 2022-12-09：是否有退库
                        $v['hasRef'] = floatval($v['ref_amount']) ? 1 : 0;
                        // 2022-12-09：是否有出库
                        $v['hasOut'] = floatval($v['out_amount']) ? 1 : 0;
                    }
                    return $lists;
                }, true);
    }

    /**
     * 【逐步废弃】出库/退库数量匹配
     * 20240706:使用outcomeMatch替代，逻辑比较清晰
     */
    public static function outcomeAmountMatch($goodsId, $amount) {
        $con[]  = ['goods_id', '=', $goodsId];
        $con[]  = ['change_type', '=', 1];
        $con[]  = ['remainAmount', '>', 0];
        $lists  = self::mainModel()->where($con)->select();

        $arr    = [];
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
    /**
     * 出库匹配
     * @param type $storeId
     * @param type $goodsId
     * @param type $amount
     * @param type $changeId    给定的绑定单号
     * @return type
     * @throws Exception
     */
    protected static function outcomeMatch($storeId, $goodsId, $amount, $tmpData = []){
        // 提取可用库存，及数量
        $sqlKey     = 'storeDtlWithRawDtlStatics';

        $con        = [];
        $con[]      = ['store_id','=',$storeId];
        $con[]      = ['goods_id','=',$goodsId];
        // $con[]      = ['change_type','=',1];
        // 20240718
        $con[]      = ['amount','>',0];
        $con[]      = ['hasRemain','=',1];
        //主要字段：id,store_id,goods_id,remainAmount
        $lists      = SqlService::keySqlQueryData($sqlKey, $con);
        // 
        $arr = [];
        foreach ($lists as $v) {
            if ($amount <= 0) {
                continue;
            }
            
            $tmpArr = $tmpData;
            $tmpArr['raw_dtl_id']   = $v['id'];
            // 1入库；2出库
            $tmpArr['change_type']  = '2';
            // 20240706 = 从 $tmpData 中传入change_id字段
            // $tmpArr['change_id']    = $changeId;
            $tmpArr['store_id']     = $v['store_id'];
            $tmpArr['goods_id']     = $v['goods_id'];
            $tmpArr['unit_prize']   = $v['unit_prize'];
            $tmpArr['amount']       = $amount > $v['remainAmount'] ? $v['remainAmount'] : $amount;
            $amount -= $tmpArr['amount'];
            $arr[] = $tmpArr;
        }
        if ($amount > 0) {
            $goodsName = GoodsService::getInstance($goodsId)->fGoodsName();
            throw new Exception($goodsName.' 库存不足，缺'.$amount.'件');
        }
        return $arr;
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
     * 根据商品id，取库存值
     */
    public static function getStockByGoodsId($goodsId, $con = []) {
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
            // 20240629：客户反馈bug注释
            // throw new Exception('出入库明细' . $this->uuid . '已经对应了单号' . Arrays::value($info, 'change_id'));
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


}
