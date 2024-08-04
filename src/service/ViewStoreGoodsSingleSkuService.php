<?php

namespace xjryanse\store\service;

use xjryanse\goods\service\ViewGoodsSingleSkuService;

/**
 * 用户收藏商品
 */
class ViewStoreGoodsSingleSkuService {

    use \xjryanse\traits\DebugTrait;
    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;


    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\ViewStoreGoodsSingleSku';

    public static function extraDetails($ids) {
        return ViewGoodsSingleSkuService::extraDetails($ids);
    }

    /**
     * 2022-12-09：改写原方法
     * @param type $param
     */
    public static function saveGetIdRam($param) {
        // 2022-12-10:默认写死
        $param['sale_type'] = 'store';
        return ViewGoodsSingleSkuService::saveGetIdRam($param);
    }

}
