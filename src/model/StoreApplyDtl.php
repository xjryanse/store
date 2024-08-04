<?php
namespace xjryanse\store\model;

/**
 * 出入库申请单明细
 */
class StoreApplyDtl extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'apply_id',
            // 去除prefix的表名
            'uni_name'  =>'store_apply',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'bus_id',
            // 去除prefix的表名
            'uni_name'  =>'bus',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'goods_id',
            // 去除prefix的表名
            'uni_name'  =>'goods',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'plan_store_id',
            // 去除prefix的表名
            'uni_name'  =>'store',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'real_store_id',
            // 去除prefix的表名
            'uni_name'  =>'store',
            'uni_field' =>'id',
            'del_check' => true,
        ]
        
    ];

}