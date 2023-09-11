<?php
namespace xjryanse\store\model;

/**
 * 单sku的商品，方便维护
 */
class ViewStoreGoodsSingleSku extends Base
{
    public static $picFields = ['main_pic'];

    public function getMainPicAttr($value) {
        return self::getImgVal($value);
    }

    public function setMainPicAttr($value) {
        return self::setImgVal($value);
    }
}