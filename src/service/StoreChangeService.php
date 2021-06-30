<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;

/**
 * 
 */
class StoreChangeService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\StoreChange';

    public static function extraPreSave(&$data, $uuid) {
        self::checkTransaction();
        //【关联已有对账单明细】
        if(isset( $data['storeChangeDtlIds'])){
            //明细笔数
            $storeChangeDtlIdCount = count($data['storeChangeDtlIds']);
            //更新对账单订单的账单id
            foreach( $data['storeChangeDtlIds'] as $value){
                //财务账单-订单；
                StoreChangeDtlService::getInstance( $value )->setChangeId( $uuid );
            }
            $data['dtl_count'] = $storeChangeDtlIdCount;
        }
    }
    
    public function extraPreDelete() {
        self::checkTransaction();
        $info = $this->get(0);
        if( Arrays::value($info, 'has_settle') ){
            throw new Exception('出入库单已结，不可删');
        }
        //删除对账单的明细
        $con[] = ['change_id','=',$this->uuid];
        $storeChangeDtls = StoreChangeDtlService::lists( $con );
        foreach( $storeChangeDtls as $value){
            //一个个删，可能涉及状态更新
            StoreChangeDtlService::getInstance($value['id'])->delete();
        }        
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
