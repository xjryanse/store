<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\DataCheck;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\sql\service\SqlService;
/**
 * 仓库盘点
 */
class StoreInventoryService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    use \xjryanse\store\service\inventory\AnalyseTraits;
    use \xjryanse\store\service\inventory\TriggerTraits;
    
    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\StoreInventory';

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                }, true);
    }
    /**
     * 20240721仓库盘点预取数据
     * 
     */
    public static function storeInvPreData($param){
        $keys       = ['store_id'];
        DataCheck::must($param, $keys);
        $storeId    = Arrays::value($param, 'store_id');
        // 提取当前仓库的库存(下钻库位)
        $sqlKey     = 'storeChangeDtlPlaceStaticsWithGoods';
        $con[]      = ['store_id','=',$storeId];
        // 库位
        $setPlace   = Arrays::value($param, 'set_place');
        if($setPlace){
            $con[]      = ['set_place','=',$setPlace];
        }

        $data       = SqlService::keySqlQueryData($sqlKey, $con);
        $keyRep     = [
            'goods_name'=>'goods_name',
            'realAmount'=>'storeAmount',
            'goods_id'  =>'goods_id',
            'set_place' =>'set_place',
            'unit'      =>'unit'
        ];
        $inventoryDtl = Arrays2d::keyReplace($data, $keyRep);
        foreach($inventoryDtl as &$v){
            // 盘点数量默认以当前库存数量写
            $v['amount'] = $v['storeAmount'];
        }
        
        $resp['store_id']           = $storeId;
        $resp['inventory_user_id']  = session(SESSION_USER_ID);
        $resp['inventory_time']     = date('Y-m-d H:i:s');
        $resp['inventoryDtls']       = $inventoryDtl;
        
        return $resp;
    }
	
	/**
     * 20240720
     */
    public function storeInventoryDtlWithSetPlace(){
        $lists = $this->objAttrsList('storeInventoryDtl');
        foreach($lists as &$v){
            // 商品id，加上库位
            $v['goodsSetPlace'] = $v['goods_id'].'_'.$v['set_place'];
        }
        return $lists;
    }
    
}
