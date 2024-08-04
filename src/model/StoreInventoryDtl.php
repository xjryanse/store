<?php
namespace xjryanse\store\model;

/**
 * 库存盘点明细
 */
class StoreInventoryDtl extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'inventory_id',
            'uni_name'  =>'store_inventory',
            'in_list'   => false,
            'del_check' => true,
            'del_msg'   => '请先删除对应{$count}条盘点明细'
        ],
        [
            'field'     =>'goods_id',
            'uni_name'  =>'goods',
            'in_list'   => false,
            'del_check' => true
        ]
    ];
    

}