<?php
namespace xjryanse\store\service\changeDtl;

use xjryanse\store\service\StoreChangeService;
use xjryanse\store\service\StoreService;
use xjryanse\logic\Arrays;
/**
 * 统计类
 * 
 */
trait CalTraits{
    /**
     * 计算是否需要库位
     */
    public function calNeedPlace(){
        // 明细
        $info       = $this->get();
        // 库单号
        $changeId   = Arrays::value($info, 'change_id');
        // 仓库号
        $storeId    = StoreChangeService::getInstance($changeId)->fStoreId();
        // 需要库位
        $needPlace  = StoreService::getInstance($storeId)->fNeedPlace();
        return $needPlace;
    }
    
    /**
     * 20240726:获取当笔记录剩余可后向操作数量
     * 用于：出库时，计算库存是否不足
     */
    public function calRemainAmount(){
        $amount = $this->fAmount();
        
        $con[] = ['raw_dtl_id','=',$this->uuid];
        $oAmount = self::mainModel()->where($con)->sum('amount');

        // 源单数量+全部后向单数量
        return $amount + $oAmount;
    }
    
    /**
     * 20240726：计算仓库id
     */
    public function calStoreId(){
        $changeId = $this->fChangeId();
        $storeId = StoreChangeService::getInstance($changeId)->fStoreId();
        return $storeId;
    }
    
}
