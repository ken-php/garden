<?php

namespace app\admin\controller\report;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\JsonService;
use think\Db;
use traits\CurdControllerTrait;
use service\UtilService as Util;
use service\JsonService as Json;
use think\Request;
use app\admin\model\store\StoreCategory as CategoryModel;
use app\admin\model\report\ReportModel;
use app\admin\model\examine\ExamineModel;
use think\Url;
use think\Session;

/**
 * 审核
 * Class Report
 * @package app\admin\controller\report
 */
class Report extends AuthController
{
    use CurdControllerTrait;

    public function index()
    {
        $type = $this->request->param('type');

        //获取分类
        $this->assign('cate',CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select());

        // 月报列表
        $reportNum =  ReportModel::where(['is_del'=>0])->count();
        // 本月待提交列表
        $curMonth = date('Y-m',strtotime("-1 month", time())); // 上月月份

        // 查询科技园上月月报所有的项目编号
        $scienceProjectNum = ReportModel::getSameAllValue(['month' => $curMonth , 'category_id' => 23],'project_num');
        // 获取科技园上月未提交月报公司档案信息
        $scienceReportNotNum =  ExamineModel::getUnReportCount(['is_audited' => 1 , 'category_id' => 23] , $scienceProjectNum);

        // 查询众创空间上月月报所有的项目编号
        $makerProjectNum = ReportModel::getSameAllValue(['month' => $curMonth , 'category_id' => 63],'project_num');
        // 获取众创空间上月未提交月报公司档案信息
        $makerProjectNotNum = ExamineModel::getUnReportCount(['is_audited' => 1 , 'category_id' => 63] , $makerProjectNum);

        // 上月已提交月报数
        $submittedReportNum = ReportModel::getSubmittedReportNum();

        $this->assign(compact('type','reportNum','scienceReportNotNum','makerProjectNotNum','submittedReportNum'));
        return $this->fetch();
    }

    /**
     * 异步查找产品
     *
     * @return json
     */
    public function product_ist()
    {
        $where = Util::getMore([
            ['page',1],
            ['limit',10],
            ['month',''],
            ['start_time',''],
            ['end_time',''],
            ['search_name',''],
            ['cate_id',''],
            ['excel',0],
            ['order',''],
            ['report',''],
            ['type',$this->request->param('type')]
        ]);

        return JsonService::successlayui(ReportModel::List($where));
    }

    /**
     * 快速编辑
     *
     * @return json
     */
    public function set_product($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && JsonService::fail('缺少参数');
        $arr = ['is_register','is_small_business','is_high_tech','is_listed'];
        if(in_array($field,$arr)){
            $value = $value == '是' ? 1:0;
        }
        if(ReportModel::where(['id'=>$id])->update([$field=>$value]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }


    /**
     * 显示创建项目表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $field = [
            Form::input('project_num','项目编号')->col(8),
            Form::select('category_id','所属园区')->setOptions(function(){
                $list = CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select();
                $menus=[];
                foreach ($list as $v){
                    $menus[] = ['value'=>$v['id'],'label'=>$v['cate_name']];
                }
                return $menus;
            })->filterable(1)->multiple(0)->col(8),

            // 企业（项目）信息
            Form::input('qi1','','企业（项目）信息')->readonly(1)->disabled(1),

            Form::input('corporate_name','企业或项目名')->col(24),
            Form::radio('is_register','是否注册企业',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
            Form::input('address','注册地址')->col(24),
            Form::number('area','场地面积')->col(8),
            Form::radio('is_new_teams','是否新增创客/团队',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_science','是否科技型中小企业',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_high_tech','是否高新技术企业',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::number('enterprises_num','与合作大学创办企业数')->col(16),


            // 团队成员信息
            Form::input('qi2','','团队成员信息')->readonly(1)->disabled(1),

            Form::number('interns_num','新增接纳大学生/研究生实习人员数')->col(8),
            Form::radio('is_sale','是否上市挂牌',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::number('add_entr_num','新增应届毕业生就业人员数')->col(8),
            Form::number('add_jop_num','新增从业人员')->col(8),

            // 项目经营情况
            Form::input('qi3','','项目经营情况')->readonly(1)->disabled(1),

            Form::number('turnover','上月营业额(千元)')->precision(2)->col(8),
            Form::number('taxes','上月纳税额(千元)')->precision(2)->col(8),
            Form::number('funds','上月研发经费投入(千元)')->precision(2)->col(8),
            Form::number('financial','上月享受财政支持金额(千元)')->precision(2)->col(8),
            Form::number('activity_num','参加的投融资对接活动次数')->precision(2)->col(8),
            Form::radio('is_investment','是否获得投资',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::number('investment_amount','获得投资金额(千元)')->precision(2)->col(24),

            // 知识产权专利信息
            Form::input('qi4','','知识产权专利信息')->readonly(1)->disabled(1),

            Form::number('intellectual_num','知识产权申请数')->col(8),
            Form::number('has_intel_num','拥有有效知识产权数(已注册公司填此项)')->col(8),
            Form::number('patents_num','申请发明专利数量(已注册公司填此项)')->col(8),
            Form::number('re_has_intel_num','拥有有效知识产权数(未注册公司填此项)')->col(8),
            Form::number('re_patents_num','申请发明专利数量(未注册公司填此项)')->col(8),
            Form::number('achievement_num','科技成果转化数')->col(8),


            // 其他信息
            Form::input('qi7','','其他信息')->readonly(1)->disabled(1),
            Form::input('month','几月份的月报(格式: 2019-05)')->col(24)
        ];
        $form = Form::make_post_form('添加申请',$field,Url::build('save'),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 新增或修改申请
     */
    public function save(Request $request,$id=0)
    {

        $data = Util::postMore([
            'project_num','category_id','corporate_name','is_register',
            'address','area', 'is_new_teams','is_science',
            'is_high_tech', 'enterprises_num','interns_num','is_sale',
            'add_jop_num', 'add_entr_num', 'turnover','taxes',
            'funds', 'financial', 'activity_num','is_investment',
            'investment_amount', 'intellectual_num', 'has_intel_num','patents_num',
            're_has_intel_num', 're_patents_num', 'achievement_num', 'month'
        ],$request);
        // 数据校验
        if(!$data['project_num']) return Json::fail('请输入项目编号');
        if(!$data['category_id']) return Json::fail('请选择所属园区');
        if(!$data['corporate_name']) return Json::fail('请输入公司名称');
//        if(!$data['org_code']) return Json::fail('请输入组织机构代码');
        if(!$data['month']) return Json::fail('请输入几月份的月报');
       // if($data['legal_phone'] && !preg_match("/^1[34578]\d{9}$/",$data['legal_phone'])) return Json::fail('法人信息 - 手机格式有误');
       // if($data['team_phone'] && !preg_match("/^1[34578]\d{9}$/",$data['team_phone'])) return Json::fail('团队成员信息 - 手机格式有误');
       // if($data['residence_time'] && $data['back_time'] && strtotime($data['back_time']) < strtotime($data['residence_time'])) return Json::fail('入驻园区时间 要小于 退园时间');
       // if($data['start_time'] && $data['end_time'] && strtotime($data['end_time']) < strtotime($data['start_time'])) return Json::fail('入园协议起时间 要小于 止时间');
        // 唯一性验证
        $onlyT = ReportModel::getUniqueness($data['project_num'],$data['category_id'],$data['month']);
        if($onlyT && (($id && $id!=$onlyT) || $id==0)){
            return Json::fail('同一园区里项目编号不能重复');
        }
        // 组合起止时间
//        $data['start_end_time'] = $data['start_time'].'-'.$data['end_time'];

        if($id){
            // 修改
            $isHas = Db::name('report')->where('id',$id)->value('id');
            if(!$isHas) return Json::successful('修改失败，数据不存在!');
            $data['update_time'] = time();
            ReportModel::edit($data,$id);
            return Json::successful('修改成功!');
        }

        // 新增
        $data['create_time'] = time();
        $data['status'] = 1;
        // 获取当前用户的uid
        $data['uid'] = getUidByAdminId(Session::get('adminId'));

        $res = ReportModel::set($data);
        return Json::successful('添加申请成功!');
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $product = ReportModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $field = [

            Form::input('project_num','项目编号',$product->getData('project_num'))->col(8),

            Form::select('category_id','所属园区',[$product->getData('category_id')])->setOptions(function(){
                $list = CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select();
                $menus=[];
                foreach ($list as $v){
                    $menus[] = ['value'=>$v['id'],'label'=>$v['cate_name']];
                }
                return $menus;
            })->filterable(1)->multiple(0)->col(8),

            // 企业（项目）信息
            Form::input('qi1','','企业（项目）信息')->readonly(1)->disabled(1),

            Form::input('corporate_name','企业或项目名',$product->getData('corporate_name'))->col(24),
            Form::radio('is_register','是否注册企业',$product->getData('is_register'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
            Form::input('address','注册地址',$product->getData('address'))->col(24),
            Form::number('area','场地面积',$product->getData('area'))->col(8),
            Form::radio('is_new_teams','是否新增创客/团队',$product->getData('is_new_teams'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_science','是否科技型中小企业',$product->getData('is_science'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_high_tech','是否高新技术企业',$product->getData('is_high_tech'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::number('enterprises_num','与合作大学创办企业数',$product->getData('enterprises_num'))->col(16),


            // 团队成员信息
            Form::input('qi2','','团队成员信息')->readonly(1)->disabled(1),

            Form::number('interns_num','新增接纳大学生/研究生实习人员数',$product->getData('interns_num'))->col(8),
            Form::radio('is_sale','是否上市挂牌',$product->getData('is_sale'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::number('add_entr_num','新增应届毕业生就业人员数',$product->getData('add_entr_num'))->col(8),
            Form::number('add_jop_num','新增从业人员',$product->getData('add_jop_num'))->col(8),

            // 项目经营情况
            Form::input('qi3','','项目经营情况')->readonly(1)->disabled(1),

            Form::number('turnover','上月营业额(千元)',$product->getData('turnover'))->precision(2)->col(8),
            Form::number('taxes','上月纳税额(千元)',$product->getData('taxes'))->precision(2)->col(8),
            Form::number('funds','上月研发经费投入(千元)',$product->getData('funds'))->precision(2)->col(8),
            Form::number('financial','上月享受财政支持金额(千元)',$product->getData('financial'))->precision(2)->col(8),
            Form::number('activity_num','参加的投融资对接活动次数',$product->getData('activity_num'))->precision(2)->col(8),
            Form::radio('is_investment','是否获得投资',$product->getData('is_investment'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::number('investment_amount','获得投资金额(千元)',$product->getData('investment_amount'))->precision(2)->col(24),

            // 知识产权专利信息
            Form::input('qi4','','知识产权专利信息')->readonly(1)->disabled(1),

            Form::number('intellectual_num','知识产权申请数',$product->getData('intellectual_num'))->col(8),
            Form::number('has_intel_num','拥有有效知识产权数(已注册公司填此项)',$product->getData('has_intel_num'))->col(8),
            Form::number('patents_num','申请发明专利数量(已注册公司填此项)',$product->getData('patents_num'))->col(8),
            Form::number('re_has_intel_num','拥有有效知识产权数(未注册公司填此项)',$product->getData('re_has_intel_num'))->col(8),
            Form::number('re_patents_num','申请发明专利数量(未注册公司填此项)',$product->getData('re_patents_num'))->col(8),
            Form::number('achievement_num','科技成果转化数',$product->getData('achievement_num'))->col(8),


            // 其他信息
            Form::input('qi7','','其他信息')->readonly(1)->disabled(1),
            Form::input('month','几月份的月报(格式: 2019-05)',$product->getData('month'))->col(24),

        ];
        $form = Form::make_post_form('编辑申请',$field,Url::build('save',array('id'=>$id)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 通知用户提交月报信息
     * @param $id
     * @author ken
     * @date 2019/6/14
     */
    public function sendNotify($id)
    {
        if (!$id){
            return $this->failed('数据不存在');
        }

        // 获取uid
        $uid = ExamineModel::getUidByExamineId($id);
        if ($uid){
            $data = [
                'content' => '请尽快提交上月月报',
                'uid' => $uid,
                'create_time' => time(),
                'status' => 1
            ];

            $res = ExamineModel::addNotice($data);
            if ($res){
                return Json::successful('通知成功!');
            }
        }

        return Json::fail('通知失败!');

    }

    /**
     * 科技月未提交月报一键通知
     */
    public function ksendNotifyAll()
    {
        $curMonth = date('Y-m',strtotime("-1 month", time())); // 上月月份
        // 查询出所有的上月已提交的项目编号
        $project_nums = ReportModel::getSameAllValue(['month' => $curMonth , 'category_id' => 23],'project_num');
        // 获取上月为提交月报的项目id
        $project_ids = ExamineModel::getSameAllValue(['category_id' => 23],$project_nums,'id');

        foreach ($project_ids as $key => $val){
            $data = [
                'content' => '请尽快提交上月月报',
                'uid' => $val,
                'create_time' => time(),
                'status' => 1
            ];

            ExamineModel::addNotice($data);

        }
        return Json::successful('通知成功!');
    }

    /**
     * 众创空间一键通知
     */
    public function zsendNotifyAll()
    {
        $curMonth = date('Y-m',strtotime("-1 month", time())); // 上月月份
        // 查询出所有的上月已提交的项目编号
        $project_nums = ReportModel::getSameAllValue(['month' => $curMonth , 'category_id' => 63],'project_num');
        // 获取上月为提交月报的项目id
        $project_ids = ExamineModel::getSameAllValue(['category_id' => 63],$project_nums,'id');

        $data = [];
        foreach ($project_ids as $key => $val){
            $data[] = [
                'content' => '请尽快提交上月月报',
                'uid' => $val,
                'create_time' => time(),
                'status' => 1
            ];

            ExamineModel::addNotice($data);

        }
        return Json::successful('通知成功!');
    }

}