<?php
namespace xjryanse\store\service\fixedLog;

use xjryanse\store\service\StoreFixedService;
use xjryanse\logic\Arrays;
/**
 * 分页复用列表
 */
trait TriggerTraits{

        /**
     * 钩子-保存前
     */
    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 钩子-更新前
     */
    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 钩子-删除前
     */
    public function extraPreDelete() {
        self::stopUse(__METHOD__);
    }
    
    /**
     * 钩子-保存前
     */
    public static function ramPreSave(&$data, $uuid) {
        
    }
    
    /**
     * 钩子-保存后
     */
    public static function ramAfterSave(&$data, $uuid) {
        $fixedId        = Arrays::value($data, 'fixed_id');
        StoreFixedService::getInstance($fixedId)->syncUseUserId();
    }
    
    /**
     * 钩子-更新前
     */
    public static function ramPreUpdate(&$data, $uuid) {

    }

    /**
     * 钩子-更新后
     */
    public static function ramAfterUpdate(&$data, $uuid) {
        $fixedId        = Arrays::value($data, 'fixed_id');
        StoreFixedService::getInstance($fixedId)->syncUseUserId();
    }

    /**
     * 钩子-删除前
     */
    public function ramPreDelete() {

    }

    /**
     * 钩子-删除后
     */
    public function ramAfterDelete($data) {
        $fixedId        = Arrays::value($data, 'fixed_id');
        StoreFixedService::getInstance($fixedId)->syncUseUserId();
    }

}
