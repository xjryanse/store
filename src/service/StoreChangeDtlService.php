<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\goods\service\GoodsService;
/**
 * 
 */
class StoreChangeDtlService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\StoreChangeDtl';

    
    /**
     * 钩子-保存前
     */
    public static function extraPreSave(&$data, $uuid) {
        
    }
    /**
     * 钩子-保存后
     */
    public static function extraAfterSave(&$data, $uuid) {
        self::getInstance($uuid)->updateGoodsStock();
    }
    /**
     * 钩子-更新前
     */
    public static function extraPreUpdate(&$data, $uuid) {

    }
    /**
     * 钩子-更新后
     */
    public static function extraAfterUpdate(&$data, $uuid) {
        self::getInstance($uuid)->updateGoodsStock();
    }    
    /**
     * 钩子-删除前
     */
    public function extraPreDelete()
    {
        self::checkTransaction();
    }
    
    public function delete()
    {
        $info = $this->get(0);
        //删除前
        if(method_exists( __CLASS__, 'extraPreDelete')){
            $this->extraPreDelete();      //注：id在preSaveData方法中生成
        }
        //删除
        $res = $this->commDelete();
        //删除后
        if(method_exists( __CLASS__, 'extraAfterDelete')){
            $this->extraAfterDelete();      //注：id在preSaveData方法中生成
        }
        //商品信息更新库存
        GoodsService::getInstance($info['goods_id'])->updateStock();
        return $res;
    }
    /**
     * 钩子-删除后
     */
    public function extraAfterDelete()
    {

    }
    
    /**
     * 根据商品id，取库存值
     */
    public static function getStockByGoodsId( $goodsId ){
        $con[] = ['goods_id','=',$goodsId];
        return self::sum($con, 'amount');
    }
    /**
     * 逐步弃用，使用getStockBySpuIds 替代 spuid，获取旗下所有商品的库存总和
     * @param type $spuId
     * @return type
     */
    public static function getStockBySpuId( $spuId ){
        $cond[]     = ['spu_id','=',$spuId];
        $goodsIds   = GoodsService::ids( $cond );
        
        $con[]      = ['goods_id','in',$goodsIds];
        return self::sum($con, 'amount');
    }
    /**
     * 空明细设定出入库单id
     */
    public function setChangeId( $changeId )
    {
        $info = $this->get(0);
        if(Arrays::value($info, 'change_id')){
            throw new Exception( '出入库明细'.$this->uuid.'已经对应了单号'. Arrays::value($info, 'change_id') );
        }
        return $this->update([ 'change_id'=>$changeId]);
    }
    
    /**
     * 更新商品的库存余额
     */
    public function updateGoodsStock()
    {
        self::checkTransaction();
        $info = $this->get(0);
        $goodsId = $info['goods_id'];
        $con[] = ['goods_id','=',$goodsId];
        $stock = self::sum($con,'amount');
        //更新商品库存量
        GoodsService::getInstance( $goodsId )->update(['stock'=>$stock]);
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
     * [冗]客户id
     */
    public function fCustomerId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * [冗]仓库id
     */
    public function fStoreId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 入库/出库单id
     */
    public function fBillId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 明细id
     */
    public function fDtlId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * [冗]明细名称
     */
    public function fDtlName() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 数量
     */
    public function fAmount() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 单价
     */
    public function fUnitPrize() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 金额
     */
    public function fSumPrize() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * [冗]单位
     */
    public function fUnit() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 入库人
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
