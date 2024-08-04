<?php
namespace xjryanse\store\model;

/**
 * 
 */
class StoreManage extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20240722:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'store_id',
            // 去除prefix的表名
            'uni_name'  =>'store',
            'uni_field' =>'id',
            'del_check' => true,
        ],
        [
            'field'     =>'manage_user_id',
            // 去除prefix的表名
            'uni_name'  =>'user',
            'uni_field' =>'id',
            'del_check' => true,
        ],
    ];

}