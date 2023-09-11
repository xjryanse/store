<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;

/**
 * 
 */
class StoreManageService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelQueryTrait;
    use \xjryanse\traits\StaticModelTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\StoreManage';

    /**
     * 20220629
     * @param type $storeId
     * @return type
     */
    public static function manageUserIds($storeId) {
        $con[] = ['store_id', '=', $storeId];
        return self::staticConColumn('manage_user_id', $con);
    }

    public static function extraPreSave(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 20220627
     * @param type $data
     * @param type $uuid
     */
    public static function ramPreSave(&$data, $uuid) {
        
    }

    public static function ramAfterSave(&$data, $uuid) {
        
    }

    public static function ramPreUpdate(&$data, $uuid) {
        
    }

    /**
     * 20220628
     * @param type $data
     * @param type $uuid
     */
    public static function ramAfterUpdate(&$data, $uuid) {
        
    }

    public static function extraPreUpdate(&$data, $uuid) {
        self::stopUse(__METHOD__);
    }

    /**
     * 不删明细
     */
    public function extraPreDelete() {
        //20220627
        self::stopUse(__METHOD__);
    }

    /**
     * 20220627:优化性能
     */
    public function ramPreDelete() {
        
    }

    /**
     *
     */
    public function fId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fAppId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fCompanyId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 客户id
     */
    public function fCustomerId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 仓库id
     */
    public function fStoreId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 出库说明
     */
    public function fDescribe() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 出库人
     */
    public function fUserId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 客户对接人
     */
    public function fCustomerUserId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 仓库管理员
     */
    public function fStoreUserId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 凭据id
     */
    public function fFileId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fSort() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 状态(0禁用,1启用)
     */
    public function fStatus() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 有使用(0否,1是)
     */
    public function fHasUsed() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 锁定（0：未锁，1：已锁）
     */
    public function fIsLock() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 锁定（0：未删，1：已删）
     */
    public function fIsDelete() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 备注
     */
    public function fRemark() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 创建者
     */
    public function fCreater() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 更新者
     */
    public function fUpdater() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 创建时间
     */
    public function fCreateTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 更新时间
     */
    public function fUpdateTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

}
