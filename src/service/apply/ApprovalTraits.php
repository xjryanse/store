<?php
namespace xjryanse\store\service\apply;

use xjryanse\approval\service\ApprovalThingService;
use xjryanse\user\service\UserService;
use xjryanse\logic\Arrays;
use think\facade\Request;
/**
 * 分页复用列表
 */
trait ApprovalTraits{
    /**
     * 20230704:接口规范写法
     * @return type
     */
    public function approvalAdd() {
        $infoArr    = $this->get();
        $exiApprId  = ApprovalThingService::belongTableIdToId($this->uuid);
        // 20240407
        $rqParam    = Request::param('table_data') ? : Request::param();
        $infoArr['nextAuditUserId'] = Arrays::value($rqParam, 'nextAuditUserId');
        //已有直接写，没有的加审批
        $data['approval_thing_id']  = $exiApprId ?: self::addAppr($infoArr);
        $data['need_appr']          = 1;
        return $this->updateRam($data);
    }

    /**
     * 事项提交去审批
     */
    protected static function addAppr($data) {
        $sData                      = Arrays::getByKeys($data, ['dept_id','nextAuditUserId']);
        $sData['user_id']           = session(SESSION_USER_ID);
        $sData['belong_table']      = self::getTable();
        $sData['belong_table_id']   = $data['id'];
        $sData['userName']          = UserService::getInstance($sData['user_id'])->fRealName();
        // 20230907:改成ram
        // 出库申请：storeOutApply
        return ApprovalThingService::thingCateAddApprRam('storeOutApply', $data['user_id'], $sData);
    }
    /**
     * 20240718:取消审批单
     */
    protected function approvalCancel(){
        $info               = $this->get();
        $approvalThingId    = Arrays::value($info, 'approval_thing_id');
        
        ApprovalThingService::getInstance($approvalThingId)->doCancel();
    }
}
