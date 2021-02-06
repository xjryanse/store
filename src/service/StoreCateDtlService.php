<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;

/**
 * 
 */
class StoreCateDtlService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\StoreCateDtl';

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
     * 分类id
     */
    public function fCateId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 图片
     */
    public function fPicture() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 品牌/供应商
     */
    public function fSupplier() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 杂项名称
     */
    public function fDtlName() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 当前库存量
     */
    public function fCurrentCount() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 库存量单位
     */
    public function fUnit() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 库存金额
     */
    public function fCurrentMoney() {
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
