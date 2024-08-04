<?php
namespace xjryanse\store\service\changeDtl;

use xjryanse\logic\DataCheck;
use xjryanse\logic\Arrays;
use Exception;
/**
 * 分页复用列表
 */
trait TypeCheckTraits{
    /**
     * 写入源头明细号，关联的冗余数据
     */
    public static function pushRawDtlRedundData(&$data){
        $rawDtlId = Arrays::value($data, 'raw_dtl_id');
        if($rawDtlId){
            $data['set_place']  = self::getInstance($rawDtlId)->fSetPlace();
            $data['unit']       = self::getInstance($rawDtlId)->fUnit();
            $data['store_id']   = self::getInstance($rawDtlId)->calStoreId();
            $data['goods_id']   = self::getInstance($rawDtlId)->fGoodsId();
        }
    }
    
    /**
     * 
     * @param type $changeType
     * @param type $data
     */
    public static function changeTypeCheckDealData($changeType, $data){
        if(!$changeType){
            throw new Exception('$changeType必须');
        }
        // 20240726:通用的处理
        self::pushRawDtlRedundData($data);
        
        $methodName = 'changeType'.$changeType.'Check';
        // 20240726:如果不通过，在内部直接抛异常
        $dataN = self::$methodName($data);   
        // 20240726未指名的，默认已结算
        return $dataN;
    }
    
    /**
     * 1入库校验
     */
    protected static function changeType1Check($data){
        DataCheck::must($data, ['goods_id','amount']);
        
        return $data;
    }
    
    /**
     * 2出库校验
     */
    protected static function changeType2Check($data){
        // DataCheck::must($data, ['raw_dtl_id','amount','user_id']);
        // 20240727：发现已有系统无法出库，暂时取消raw_dtl_id管控
        DataCheck::must($data, ['amount','user_id']);
        
        return $data;
    }

    /**
     * 3退库校验
     */
    protected static function changeType3Check($data){
        DataCheck::must($data, ['raw_dtl_id','amount']);
        // 数量一定是加
        $data['amount'] = abs($data['amount']);
        
        $rawDtlId = Arrays::value($data, 'raw_dtl_id');
        $remainAmount = self::getInstance($rawDtlId)->calRemainAmount();
        
        if($data['amount'] + $remainAmount < 0 ){
            throw new Exception('退库数量不足，本条可退'.$remainAmount);
        }
        
        return $data;
    }
 
    /**
     * 4退货校验
     */
    protected static function changeType4Check($data){
        $notice = [];
        $notice['describe'] = '退货，请填写说明';
        DataCheck::must($data, ['raw_dtl_id','amount','describe'], $notice);
        // 数量一定是减
        $data['amount'] = -1 * abs($data['amount']);
        
        $rawDtlId = Arrays::value($data, 'raw_dtl_id');
        $remainAmount = self::getInstance($rawDtlId)->calRemainAmount();
        
        if($data['amount'] + $remainAmount < 0 ){
            throw new Exception('退货数量不足，本条可退'.$remainAmount);
        }
        
        return $data;
    }
    
    /**
     * 5拆零校验
     */
    protected static function changeType5Check($data){
        
        return $data;
    }

    /**
     * 6盘点调账校验
     */
    protected static function changeType6Check($data){
        $notice = [];
        $notice['describe'] = '盘点调账，请填写说明';
        DataCheck::must($data, ['belong_table', 'belong_table_id','describe'], $notice);

        
        return $data;
    }
    
    /**
     * 7到货直领校验
     */
    protected static function changeType7Check($data){
        
        return $data;
    }
    
    
}
