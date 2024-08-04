<?php
namespace xjryanse\store\model;

/**
 * 
 */
class StoreChangeDtl extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'change_id',
            'uni_name'  =>'store_change',
            'in_list'   => false,
        ],
    ];
    
    public static $multiPicFields = ['file_id'];

    /**
     * 2023-10-10多图
     * @param type $value
     * @return type
     */
    public function getFileIdAttr($value) {
        return self::getImgVal($value, true);
    }

    public function setFileIdAttr($value) {
        return self::setImgVal($value);
    }


}