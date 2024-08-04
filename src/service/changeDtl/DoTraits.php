<?php
namespace xjryanse\store\service\changeDtl;

use xjryanse\logic\DataCheck;
use xjryanse\logic\Arrays;
use xjryanse\store\service\StoreChangeService;
use xjryanse\goods\service\GoodsTearService;
use Exception;

/**
 * 操作复用类
 * 一个库单，一个仓库，一个经办人
 * 允许对应多个车辆
 * 
 */
trait DoTraits{

    /**
     * 20240705:出库操作
     * 先关联好前向记录（先进先出）
     */
    public static function doChangeOut($param){
        // 仓库、品名、数量
        $keys = ['store_id','goods_id','amount'];
        DataCheck::must($param, $keys);

        $storeId    = Arrays::value($param, 'store_id');
        $goodsId    = Arrays::value($param, 'goods_id');
        $amount     = Arrays::value($param, 'amount');

        $sD['has_settle']   = 1;
        $sD['store_id']     = $storeId;
        // 20240718:使用部门
        $sD['use_dept_id']  = Arrays::value($param, 'use_dept_id');
        $sD['user_id']      = Arrays::value($param, 'user_id');
        $changeType         = 2;
        // 使用同一个单号
        $changeId           = StoreChangeService::generateNewGetId($storeId, $changeType, $sD);
        
        // 20240707：申请明细号
        $tD['apply_dtl_id'] = Arrays::value($param, 'apply_dtl_id');
        $tD['change_id']    = $changeId;
        $tD['bus_id']       = Arrays::value($param, 'bus_id');
        $tD['user_id']      = Arrays::value($param, 'user_id');

        $arr                = self::outcomeMatch($storeId, $goodsId, $amount, $tD);
        // 保存单据
        $res                = self::saveAllRam($arr);
        return $res;
    }
    
    /**
     * 20240726：拆零方案处理
     */
    public static function doTear($param){
        $keys = ['raw_dtl_id','goodsTearId','amount'];
        DataCheck::must($param, $keys);
        
        $rawDtlId       = Arrays::value($param, 'raw_dtl_id');
        $goodsTearId    = Arrays::value($param, 'goodsTearId');
        $amount         = Arrays::value($param, 'amount');
        // 生成拆零数组
        $sData = [];
        $sData['store_id']      = self::getInstance($rawDtlId)->fStoreId();
        $sData['user_id']       = Arrays::value($param, 'user_id') ? : session(SESSION_USER_ID);
        $sData['changeDtls']    = self::generateTearArr($rawDtlId, $goodsTearId, $amount);
        $sData['change_type']   = 5;
        // 存到同一单去
        return StoreChangeService::saveRam($sData);
    }
    /**
     * 生成拆零数组
     */
    private static function generateTearArr($rawDtlId, $goodsTearId, $amount){
        // 拆零
        $changeType = 5;
        
        $rawDtlInfo     = self::getInstance($rawDtlId)->get();
        
        $tearInfo = GoodsTearService::getInstance($goodsTearId)->get();
        if(!$tearInfo){
            throw new Exception('拆零方案不存在'.$goodsTearId);
        }

        if(Arrays::value($rawDtlInfo, 'goods_id') != Arrays::value($tearInfo, 'goods_id')){
            throw new Exception('拆解方案物品不匹配');
        }
        
        // 拆零，一条出库；一条入库
        $arr    = [];
        $arr[]  = [
            'change_type'   => $changeType,
            'raw_dtl_id'    =>$rawDtlId,
            'amount'        => -1 * abs($amount),
        ];
        $arr[]  = [
            'change_type'   => $changeType,
            'goods_id'      => $tearInfo['tear_goods_id'],
            'amount'        => abs($amount) * $tearInfo['tear_amount'],
        ];
        
        return $arr;
    }
    
}
