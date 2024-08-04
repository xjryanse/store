<?php
namespace xjryanse\store\service\inventory;

use xjryanse\logic\DataCheck;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\Number;
use xjryanse\store\service\StoreChangeDtlService;
use xjryanse\logic\ModelQueryCon;
use Exception;
/**
 * 分析复用类
 */
trait AnalyseTraits{

    /**
     * 盘点结果，带库存量进行分析（指定盘点的时间点）
     */
    public static function resultWithChangeDtl($param){
        $keys = ['inventory_id'];
        DataCheck::must($param, $keys);

        $inventoryId = Arrays::value($param, 'inventory_id');
        $setPlace    = Arrays::value($param, 'set_place');
        // 【1】提取全量结果
        $arr = self::inventoryAnalysisList($inventoryId, $setPlace);
        // 【2】拼接查询条件
        $fields = [];
        $fields['equal'] = ['goods_id', 'inventoryState','adjustState'];
        $con = ModelQueryCon::queryCon($param, $fields);
        // 【3】过滤返回结果
        return Arrays2d::listFilter($arr, $con);
    }
    
    /**
     * 盘点评价全量结果
     */
    private static function inventoryAnalysisList($inventoryId, $setPlace = ''){
        $info = self::getInstance($inventoryId)->get();
        // 盘点时间和仓库校验，如有报错，说明盘点登记步骤有问题。
        $nmKeys = ['inventory_time','store_id'];
        DataCheck::must($info, $nmKeys);

        //【1】基础信息的处理
        // 盘点时间
        $inventoryTime  = Arrays::value($info, 'inventory_time');
        // 库位
        $storeId        = Arrays::value($info, 'store_id');

        //【2】提取盘点时间点之前的流水，统计得出账面库存
        $groupFields    = ['set_place'];
        // 提取出库流水的统计结果
        $con            = [['b.store_id','=',$storeId]];
        if($setPlace){
            $con[]      = ['a.set_place','=',$setPlace];
        }
        $lists          = StoreChangeDtlService::storeStaticsWithTime($inventoryTime, $con, $groupFields);

        $goodsIdsO      = Arrays2d::uniqueColumn($lists, 'goodsSetPlace');
        // $goodsIdsO      = Arrays2d::uniqueColumn($lists, 'goods_id');

        // 【3】提取盘点单的明细
        // $invList        = self::getInstance($inventoryId)->objAttrsList('storeInventoryDtl');
        $invList        = self::getInstance($inventoryId)->storeInventoryDtlWithSetPlace();
        // 20240720：带库位
        $goodsIdsN      = Arrays2d::uniqueColumn($invList, 'goodsSetPlace');
        // $goodsIdsN      = Arrays2d::uniqueColumn($invList, 'goods_id');
        $goodsIds       = array_unique(array_merge($goodsIdsO, $goodsIdsN));
        // dump($invList);
        // 【4】提取调账明细记录
        $adjustLists            = StoreChangeDtlService::inventoryAdjustStatics($inventoryId);

        $arr                = [];
        $listsObj           = Arrays2d::fieldSetKey($lists, 'goodsSetPlace');
        $invListObj         = Arrays2d::fieldSetKey($invList, 'goodsSetPlace');
        $adjustListObj      = Arrays2d::fieldSetKey($adjustLists, 'goodsSetPlace');
        // $listsObj           = Arrays2d::fieldSetKey($lists, 'goods_id');
        // $invListObj         = Arrays2d::fieldSetKey($invList, 'goods_id');
        // $adjustListObj      = Arrays2d::fieldSetKey($adjustLists, 'goods_id');

        foreach($goodsIds as &$goodId){
            $tArr = explode('_',$goodId);
            
            // 账面库存
            $storeObj   = Arrays::value($listsObj, $goodId) ? : [];
            // 盘点数
            $invObj     = Arrays::value($invListObj, $goodId) ? : [];
            // 调账数
            $adjustObj  = Arrays::value($adjustListObj, $goodId) ? : [];

            $diffAmount = Arrays::value($invObj, 'amount',0) - Arrays::value($storeObj, 'storeAmount', 0 );
            
            $afterAdjustAmount = $diffAmount - Arrays::value($adjustObj, 'adjustAmount', 0);
            
            $arr[] = [
                // 盘点单号
                'inventory_id'      => $inventoryId,
                'store_id'          => $storeId,
                'goods_id'          => $tArr[0],
                'set_place'         => $tArr[1],
                'unit'              => '测',
                // 盘点情况
                'storeAmount'       => Arrays::value($storeObj, 'storeAmount'),
                'inventoryAmount'   => Arrays::value($invObj, 'amount'),
                'diffAmount'        => $diffAmount,
                'inventoryState'    => Number::signum($diffAmount),
                // 调账情况
                'adjustCount'           => Arrays::value($adjustObj, 'adjustCount'),
                'adjustAmount'          => Arrays::value($adjustObj, 'adjustAmount'),
                'adjustDescribe'        => Arrays::value($adjustObj, 'adjustDescribe'),
                'afterAdjustDiffAmount' => $afterAdjustAmount,
                // 0-未平账；1-已平账
                'adjustState'           => $afterAdjustAmount == 0 ? 1: 0 ,
                // 时间段，用于明细带入条件
                'timeScope'             => ['1970-01-01 00:00:00', $inventoryTime],
                // 20240720:前端测试
                // '$test'=>$adjustLists
            ];
        }
        
        return $arr;
    }
    
}
