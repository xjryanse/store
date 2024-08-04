<?php
namespace xjryanse\store\service\apply;

use xjryanse\user\service\UserService;
use xjryanse\sql\service\SqlService;
use xjryanse\goods\service\GoodsService;
use think\facade\Request;
use xjryanse\logic\Arrays;
use xjryanse\system\service\SystemCompanyUserService;
use xjryanse\system\service\SystemCompanyDeptService;

/**
 * 当前登录用户的相关逻辑
 */
trait MeTraits{
    /**
     * 我的出库申请预提取数据
     */
    public static function myApplyOutPreGet($param = []){
        $userId             = session(SESSION_USER_ID);
        $data['user_id']    = $userId;
        // 默认出库2
        $data['change_type']= 2;
        // 提取用户上一次填写的人员；当没有上一次人员，提取用户所在部门主管
        $info = SystemCompanyUserService::findByCurrentUser();
        // 部门
        $deptId = Arrays::value($info, 'dept_id');
        $data['use_dept_id'] = $deptId;
        // 部门主管
        
        
        $data['nextAuditUserId']                 = $deptId ? SystemCompanyDeptService::getInstance($deptId)->fManagerId() : '';

        $data['dynDataList']['use_dept_id'][$deptId] = SystemCompanyDeptService::getInstance($deptId)->fDeptName();
        $data['dynDataList']['user_id'][$userId] = UserService::getInstance($userId)->fNamePhone();
        $data['dynDataList']['nextAuditUserId'][$data['nextAuditUserId']]    = UserService::getInstance($data['nextAuditUserId'])->fNamePhone();

        return $data;
    }
    
    
    
    /**
     * 20240524:用于提取当前驾驶员上报的记录
     * @param type $data
     * @param type $uuid
     */
    public static function paginateUserMe($con = [], $order = '', $perPage = 10, $having = '', $field = "*", $withSum = false) {
        $param  = Request::param('table_data') ? : Request::param();
        
        $sqlKey = 'storeApplyWithOutState';
        $sqlId  = SqlService::keyToId($sqlKey);

        $con    = SqlService::getInstance($sqlId)->whereFields($param);
        $con[]  = ['user_id','=',session(SESSION_USER_ID)];

        $res    = SqlService::sqlPaginateData($sqlKey, $con);

        foreach($res['data'] as &$v){
            $sLists = self::getInstance($v['id'])->objAttrsList('storeApplyDtl');
            foreach($sLists as &$i){
                $i['goodsName'] = GoodsService::getInstance($i['goods_id'])->fGoodsName();
            }
            $v['applyDtls'] = $sLists;
        }

        return $res;
    }
    
}
