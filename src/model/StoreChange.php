<?php
namespace xjryanse\store\model;

/**
 * 
 */
class StoreChange extends Base
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