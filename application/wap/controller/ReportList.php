<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/11
 */

namespace app\wap\controller;

use app\wap\model\user\User;
use app\admin\model\store\StoreCategory as CategoryModel;
use service\JsonService as Json;
use service\UtilService as Util;
use app\admin\model\examine\ExamineModel;
use think\Request;
use app\core\util\GroupDataService;
use think\Db;
use think\Url;
use app\admin\model\report\ReportModel;

class ReportList extends AuthController
{

    public function index($page=1,$start=0,$end=0)
    {
        if($start==0) $start=null;
        if($end==0) $end=null;
        $this->assign(compact('start','end'));
        $uid = User::getActiveUid();
        // 申请项目
        $id = Db::name('project_user')->where(['uid'=>$uid,'status'=>1])->value('project_id');
        // 是否绑定过项目
        if(empty($id)){
            $title = '信息提示';
            $msg = '未绑定项目';
            $url = 0;
            $this->assign(compact('title', 'msg', 'url'));
            exit($this->fetch('public/error'));
        }
        $da = Db::name('examine')->where('id',$id)->find();
        // 项目是否审核通过
        if(empty($da) || $da['is_audited']!=1){
            $title = '信息提示';
            $msg = '绑定的项目，未审核，不能填报';
            $url = 0;
            $this->assign(compact('title', 'msg', 'url'));
            exit($this->fetch('public/error'));
        }

        // 填报列表
        if($start!=0 && $end!=0){
            $start = date('Y-m',strtotime($start));$end = date('Y-m',strtotime($end));
            $reportList = Db::name('report')->where('uid',$uid)->where('month','between',[$start,$end])->page($page)->order('id desc')->select();
        }else{
            $reportList = Db::name('report')->where('uid',$uid)->page($page)->order('id desc')->select();
        }
        // 园区列表
        $list = CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select();
        // 是否填报过
        if(count($reportList)==0){
            $data = $da;
        }else{
            $month = Db::name('report')->where('uid',$uid)->value('max(month)');
            $data= Db::name('report')->where('uid',$uid)->where('month',$month)->find();
        }
        
        $this->assign(compact('reportList','list','data'));
        return $this->fetch();
    }

    // 选择时间
    public function searchTime(Request $request)
    {
        $month = $request->post('start_time');
        if(empty($month)) return Json::fail('请选择时间');
        if(strtotime($month) > time()) return Json::fail('不能大于当前时间');
        $uid = User::getActiveUid();
        $month = date('Y-m',strtotime($month));
        $num = Db::name('report')->where('uid',$uid)->where('month',$month)->count();
        if($num) return Json::fail('本月填报已存在，请选择其他月份');
        return Json::success('可以');
    }

    /**
     * 新增或修改申请
     */
    public function save(Request $request,$id=0)
    {
        $data = Util::postMore([
            'project_num','category_id','is_hatched','corporate_name',
            'org_code','project_synopsis','project_type',
            'jop_num','entr_num','is_register',
            'legal_name','legal_id_card','legal_school',
            'legal_time','legal_education','legal_phone',
            'is_graduate_school','team_name','team_school',
            'team_time','team_education','team_phone',
            'residence_time','start_end_time',
            'room_number','site_area','month_turnover',
            'year_turnover','month_taxes','year_taxes',
            'resource_docking','name_investor','financing_amount',
            'gov_amount','project_awards','change_record',
            'back_time','reason','industry_type',
            'products_services','required_pro_serv','financing_needs','entrepr','month_time',
            'address','is_science','is_high_tech','is_sale','add_jop_num','add_entr_num','area','turnover','taxes','funds','financial',
            'activity_num','is_investment','investment_amount','intellectual_num','has_intel_num','patents_num'
        ],$request);
        // 数据校验
        if(!$data['project_num']) return Json::fail('请输入项目编号');
        if(!$data['category_id']) return Json::fail('请选择所属园区');
        if(!$data['corporate_name']) return Json::fail('请输入公司名称');
        if(!$data['org_code']) return Json::fail('请输入组织机构代码');
        if($data['legal_phone'] && !preg_match("/^1[34578]\d{9}$/",$data['legal_phone'])) return Json::fail('法人信息 - 手机格式有误');
        if($data['team_phone'] && !preg_match("/^1[34578]\d{9}$/",$data['team_phone'])) return Json::fail('团队成员信息 - 手机格式有误');        

        if($data['project_num']){
            // 修改
            $isHas = Db::name('examine')->where('project_num',$data['project_num'])->value('id');
            if(!$isHas) return Json::fail('修改失败，数据不存在!');
            $data['update_time'] = time();
            ExamineModel::edit($data,$isHas);
        }

        // 新增
        $data['month'] = date('Y-m',strtotime($data['month_time']));
        $data['create_time'] = time();
        // 获取当前用户的uid
        $data['uid'] = User::getActiveUid();
        $res=ReportModel::set($data);
        return Json::successful('添加成功!');
    }

    public function edit($id)
    {
        return $this->fetch();
    }


}