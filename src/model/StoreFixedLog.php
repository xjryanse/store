<?php

namespace xjryanse\store\model;

/**
 * 
 */
class StoreFixedLog extends Base {
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'fixed_id',
            'uni_name'  =>'store_fixed',
            'in_list'   => false,            
            'del_check'=> true,
            'del_msg'   => '已有{$count}条交接记录，请先清理才能操作'
        ]
    ];
    
}
