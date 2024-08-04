<?php
namespace xjryanse\store\service\changeDtl;

use xjryanse\store\service\StoreChangeService;
use xjryanse\store\service\StoreInventoryService;
/**
 * 统计类
 * 
 */
trait StaticsTraits{
    
    /**
     * 指定时间节点，进行库存量的统计
     */
    public static function storeStaticsWithTime($time, $con = [], $groupFields = []){
        // 仓库时间筛选
        $con[]          = ['b.change_time','<=',$time];
        $storeChangeTable  = StoreChangeService::mainModel()->getTable();
        $groupFields[]  = 'a.goods_id';

        $fields = $groupFields;
        $fields[] = 'count(1) as dtlCount';
        // 库存数量
        $fields[] = 'sum(a.amount) as storeAmount';
        // 库存货值
        $fields[] = 'sum(a.unit_prize * a.amount) as storePrize';
        // 20240720:带库位
        $fields[] = 'concat(a.goods_id,\'_\',ifnull(a.set_place,\'\')) as goodsSetPlace';
        
        $arr = self::mainModel()->alias('a')
                ->join($storeChangeTable.' b','a.change_id = b.id')
                ->where($con)
                ->field($fields)
                ->group($groupFields)
                ->select();
        return $arr ? $arr->toArray() : [];
    }
    
    /**
     * 盘点单，进行调账的统计
     */
    public static function inventoryAdjustStatics($inventoryId){
        
        $invTable = StoreInventoryService::mainModel()->getTable();

        $con[] = ['belong_table','=',$invTable];
        $con[] = ['belong_table_id','=',$inventoryId];
        
        $fields   = [];
        $fields[] = 'goods_id';
        $fields[] = 'count(1) as adjustCount';
        $fields[] = 'sum(amount) as adjustAmount';
        $fields[] = 'group_concat(`describe`) as adjustDescribe';
        $fields[] = 'concat(goods_id,\'_\',ifnull(set_place,\'\')) as goodsSetPlace';

        $arr = self::mainModel()->where($con)
                ->group('goods_id')
                ->field($fields)
                ->select();
        return $arr ? $arr->toArray() : [];

    }
    
}
