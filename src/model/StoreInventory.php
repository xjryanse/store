<?php
namespace xjryanse\store\model;

/**
 * 库存盘点记录
 */
class StoreInventory extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'store_id',
            // 去除prefix的表名
            'uni_name'  =>'store',
            'uni_field' =>'id',
            'del_check' => true,
        ]
    ];


}