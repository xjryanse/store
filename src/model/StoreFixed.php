<?php

namespace xjryanse\store\model;

/**
 * 
 */
class StoreFixed extends Base {
    
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'dept_id',
            'uni_name'  =>'system_company_dept',
            'in_list'   => false,            
            'del_check'=> true,
        ],  
        [
            'field'     =>'goods_id',
            'uni_name'  =>'goods',
            'in_list'   => false,            
            'del_check'=> true,
        ],
        [
            'field'     =>'use_user_id',
            'uni_name'  =>'user',
            'in_list'   => false,            
            'del_check'=> true,
        ]
    ];
    
    
    public static $multiPicFields = ['file_id','discard_file'];
    
    public function setFileIdAttr($value) {
        return self::setImgVal($value);
    }
    public function getFileIdAttr($value) {
        return self::getImgVal($value,false);
    }
    
    public function setDiscardFileAttr($value) {
        return self::setImgVal($value);
    }
    public function getDiscardFileAttr($value) {
        return self::getImgVal($value,false);
    }
    
    
}
