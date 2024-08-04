<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;
/**
 * 固定资产台账
 */
class StoreFixedService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\StoreFixed';

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {

                    foreach ($lists as &$v) {
                        // 1闲置：当前使用人是空
                        // 2使用中：
                        // 3报废：
                        $v['fixedState'] = $v['discard_time'] ? 3 : ($v['use_user_id'] ? 2:1);
                    }
                    return $lists;
                });
    }
    
    /**
     * 20240622:同步更新使用人
     */
    public function syncUseUserId(){
        $lists = $this->objAttrsList('storeFixedLog');
        // 最新排最前
        $rev = array_reverse($lists);
        $new = $rev ? $rev[0] : [];

        $useUserId = Arrays::value($new, 'change_type') == 1 ? $new['accept_user_id'] : '';
        $this->doUpdateRam(['use_user_id'=>$useUserId]);
    }
    
}
