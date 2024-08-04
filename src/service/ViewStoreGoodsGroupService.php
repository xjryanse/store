<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\goods\service\GoodsGroupService;

/**
 * 
 */
class ViewStoreGoodsGroupService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;


    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\ViewStoreGoodsGroup';

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {

                    foreach ($lists as &$v) {
                        // 平均单价
                        $v['avgUnitPrize'] = 77;
                    }

                    return $lists;
                });
    }

    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    public static function ramPreSave(&$data, $uuid) {
        // 2022-12-09:写死仓库
        $data['group'] = 'store';
    }

    /**
     *
     */
    public function fAmount() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fCompanyId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 商品id，sku
     */
    public function fGoodsId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * [冗]仓库id
     */
    public function fStoreId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fSumPrize() {
        return $this->getFFieldValue(__FUNCTION__);
    }

}
