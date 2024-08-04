<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;

/**
 * 固定资产台账变动记录
 * 变动类型：登记；回收；发放；移交；报废；
 * 1发放；2回收
 * 
 * 
 */
class StoreFixedLogService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\StoreFixedLog';


    use \xjryanse\store\service\fixedLog\TriggerTraits;
    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    return $lists;
                }, true);
    }
}
