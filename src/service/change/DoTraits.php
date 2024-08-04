<?php
namespace xjryanse\store\service\change;

use xjryanse\logic\Arrays;
use xjryanse\logic\DataCheck;
use Exception;
/**
 * 分页复用列表
 */
trait DoTraits{
    /**
     * 入库直领
     */
    public static function doIncDirectOut($param){
        $notice = [
            'changeDtls'=>'请添加明细'
        ];
        $keys = ['changeDtls'];
        DataCheck::must($param, $keys, $notice);
        // 20240724
        $storeChangeDtl = Arrays::value($param, 'changeDtls');
        // 拼接出入库明细数组
        $arr = [];
        foreach($storeChangeDtl as $v){
            $amount = abs(Arrays::value($v, 'amount', 0 ));
            $rawDtlId = self::mainModel()->newId();
            // 一条入库
            $arr[] = array_merge($v,['id'=>$rawDtlId,'change_type'=>1,'amount'=>$amount]);
            // 一条出库
            $arr[] = array_merge($v,['raw_dtl_id'=>$rawDtlId,'change_type'=>2, 'amount'=>-1 * $amount]);
        }
        // 提取保存数据
        $sKeys = ['store_id','user_id','has_settle'];
        $data = Arrays::getByKeys($param, $sKeys);
        $data['changeDtls'] = $arr;
        // 20240724:到货直领，类型7
        // 1入库；2出库；3出后退库；4入后退货；5拆零；6盘点调账；7到货直领
        $data['change_type'] = 7;
        // 保存
        return self::saveRam($data);
    }

}
