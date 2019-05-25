<?php

namespace app\admin\controller\examine;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\JsonService;
use think\Db;
use traits\CurdControllerTrait;
use service\UtilService as Util;
use service\JsonService as Json;
use think\Request;
use app\admin\model\store\StoreCategory as CategoryModel;
use app\admin\model\examine\ExamineModel;
use think\Url;
use think\Session;

/**
 * 审核
 * Class Examine
 * @package app\admin\controller\examine
 */
class Examine extends AuthController
{
    use CurdControllerTrait;

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $type=$this->request->param('type');
        //获取分类
        $this->assign('cate',CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select());
        //待审核
        $toBeAudited =  ExamineModel::where(['is_audited'=>0,'is_del'=>0])->count();
        //已审核
        $audited =  ExamineModel::where(['is_audited'=>1,'is_del'=>0])->count();
        //回收站
        $recycle =  ExamineModel::where(['is_del'=>1])->count();

        $this->assign(compact('type','toBeAudited','audited','recycle'));
        return $this->fetch();
    }

    /**
     * 异步查找产品
     *
     * @return json
     */
    public function product_ist(){
        $where=Util::getMore([
            ['page',1],
            ['limit',20],
            ['search_name',''],
            ['cate_id',''],
            ['excel',0],
            ['order',''],
            ['type',$this->request->param('type')]
        ]);
        return JsonService::successlayui(ExamineModel::List($where));
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
            Form::radio('is_hatched','是否入孵项目',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),

            // 企业（项目）信息
            Form::input('qi1','----','企业（项目）信息')->readonly(1)->disabled(1),
            Form::input('corporate_name','公司名称')->col(12),
            Form::input('org_code','组织机构代码')->col(12),
            Form::input('project_synopsis','项目简介'),
            Form::input('project_type','项目类别'),
            Form::number('jop_num','就业人数')->col(6),
            Form::number('entr_num','创业人数')->col(6),
            Form::radio('is_register','是否工商注册',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(12),

            // 法人信息
            Form::input('qi2','----','法人信息')->readonly(1)->disabled(1),
            Form::input('legal_name','姓名')->col(8),
            Form::input('legal_id_card','身份证号')->col(8),
            Form::input('legal_school','毕业院校')->col(8),
            Form::idate('legal_time','毕业时间')->col(6),
            Form::input('legal_education','学历')->col(5),
            Form::input('legal_phone','联系电话')->col(7),
            Form::radio('is_graduate_school','是否毕业5年或在校',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(6),

            // 团队成员信息
            Form::input('qi3','----','团队成员信息')->readonly(1)->disabled(1),
            Form::input('team_name','姓名')->col(8),
            Form::input('team_school','毕业院校')->col(8),
            Form::idate('team_time','毕业时间')->col(6),
            Form::input('team_education','学历')->col(5),
            Form::input('team_phone','联系电话')->col(7),

            // 入驻园区信息
            Form::input('qi4','----','入驻园区信息')->readonly(1)->disabled(1),
            Form::idate('residence_time','入驻园区时间')->col(8),
            Form::idate('start_time','入园协议起时间')->col(8),
            Form::idate('end_time','入园协议止时间')->col(8),
            Form::input('room_number','入驻房间编号')->col(12),
            Form::number('site_area','入驻场地面积')->col(12),

            // 项目经营情况
            Form::input('qi5','----','项目经营情况')->readonly(1)->disabled(1),
            Form::number('month_turnover','营业额-本月(万元)')->precision(2)->col(6),
            Form::number('year_turnover','营业额-本年累计(万元)')->precision(2)->col(6),
            Form::number('month_taxes','纳税额-本月(万元)')->precision(2)->col(6),
            Form::number('year_taxes','纳税额-本年累计(万元)')->precision(2)->col(6),

            // 项目培育孵化情况
            Form::input('qi6','----','项目培育孵化情况')->readonly(1)->disabled(1),
            Form::input('resource_docking','有效资源对接情况')->col(12),
            Form::input('name_investor','出资单位名称')->col(12),
            Form::number('financing_amount','融资金额')->precision(2)->col(12),
            Form::number('gov_amount','政府扶持资金名称及金额(万元)')->precision(2)->col(12),

            // 其他信息
            Form::input('qi7','----','其他信息')->readonly(1)->disabled(1),
            Form::textarea('project_awards','项目获奖及专利情况')->col(24),
            Form::textarea('change_record','信息变更记录')->col(24),
            Form::idate('back_time','退园时间')->col(8),
            Form::input('reason','退园原因')->col(16),
            Form::input('industry_type','行业类型')->col(24),
            Form::input('products_services','项目提供的产品或服务')->col(24),
            Form::input('required_pro_serv','项目需要的产品或服务')->col(24),
            Form::number('financing_needs','是否有融资需求（1风险投资2贷款）')->col(8),
            Form::input('entrepr','是否需要创业辅导培训（财务、法务等）')->col(24)
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
            'project_num','category_id','is_hatched','corporate_name',
            'org_code','project_synopsis','project_type',
            'jop_num','entr_num','is_register',
            'legal_name','legal_id_card','legal_school',
            'legal_time','legal_education','legal_phone',
            'is_graduate_school','team_name','team_school',
            'team_time','team_education','team_phone',
            'residence_time','start_end_time','start_time','end_time',
            'room_number','site_area','month_turnover',
            'year_turnover','month_taxes','year_taxes',
            'resource_docking','name_investor','financing_amount',
            'gov_amount','project_awards','change_record',
            'back_time','reason','industry_type',
            'products_services','required_pro_serv','financing_needs','entrepr'
        ],$request);
        // 数据校验
        if(!$data['project_num']) return Json::fail('请输入项目编号');
        if(!$data['category_id']) return Json::fail('请选择所属园区');
        if(!$data['corporate_name']) return Json::fail('请输入公司名称');
        if(!$data['org_code']) return Json::fail('请输入组织机构代码');
        if($data['legal_phone'] && !preg_match("/^1[34578]\d{9}$/",$data['legal_phone'])) return Json::fail('法人信息 - 手机格式有误');
        if($data['team_phone'] && !preg_match("/^1[34578]\d{9}$/",$data['team_phone'])) return Json::fail('团队成员信息 - 手机格式有误');
        if(strtotime($data['back_time']) < strtotime($data['residence_time'])) return Json::fail('入驻园区时间 要小于 退园时间');
        if(strtotime($data['end_time']) < strtotime($data['start_time'])) return Json::fail('入园协议起时间 要小于 止时间');
        // 唯一性验证
        $onlyT = ExamineModel::getUniqueness($data['project_num'],$data['category_id']);
        if($onlyT && (($id && $id!=$onlyT) || $id==0)){
            return Json::fail('同一园区里项目编号不能重复');
        }
        // 组合起止时间
        $data['start_end_time'] = $data['start_time'].'-'.$data['end_time'];

        if($id){
            // 修改
            $isHas = Db::name('examine')->where('id',$id)->value('id');
            if(!$isHas) return Json::successful('修改失败，数据不存在!');
            $data['update_time'] = time();
            ExamineModel::edit($data,$id);
            return Json::successful('修改成功!');
        }

        // 新增
        $data['create_time'] = time();
        // 获取当前用户的uid
        $data['uid'] = getUidByAdminId(Session::get('adminId'));
        $res=ExamineModel::set($data);
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
        $product = ExamineModel::get($id);
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
            Form::radio('is_hatched','是否入孵项目',$product->getData('is_hatched'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),

            // 企业（项目）信息
            Form::input('qi1','----','企业（项目）信息')->readonly(1)->disabled(1),
            Form::input('corporate_name','公司名称',$product->getData('corporate_name'))->col(12),
            Form::input('org_code','组织机构代码',$product->getData('org_code'))->col(12),
            Form::input('project_synopsis','项目简介',$product->getData('project_synopsis')),
            Form::input('project_type','项目类别',$product->getData('project_type')),
            Form::number('jop_num','就业人数',$product->getData('jop_num'))->col(6),
            Form::number('entr_num','创业人数',$product->getData('entr_num'))->col(6),
            Form::radio('is_register','是否工商注册',$product->getData('is_register'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(12),

            // 法人信息
            Form::input('qi2','----','法人信息')->readonly(1)->disabled(1),
            Form::input('legal_name','姓名',$product->getData('legal_name'))->col(8),
            Form::input('legal_id_card','身份证号',$product->getData('legal_id_card'))->col(8),
            Form::input('legal_school','毕业院校',$product->getData('legal_school'))->col(8),
            Form::idate('legal_time','毕业时间',$product->getData('legal_time'))->col(6),
            Form::input('legal_education','学历',$product->getData('legal_education'))->col(5),
            Form::input('legal_phone','联系电话',$product->getData('legal_phone'))->col(7),
            Form::radio('is_graduate_school','是否毕业5年或在校',$product->getData('is_graduate_school'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(6),

            // 团队成员信息
            Form::input('qi3','----','团队成员信息')->readonly(1)->disabled(1),
            Form::input('team_name','姓名',$product->getData('team_name'))->col(8),
            Form::input('team_school','毕业院校',$product->getData('team_school'))->col(8),
            Form::idate('team_time','毕业时间',$product->getData('team_time'))->col(6),
            Form::input('team_education','学历',$product->getData('team_education'))->col(5),
            Form::input('team_phone','联系电话',$product->getData('team_phone'))->col(7),

            // 入驻园区信息
            Form::input('qi4','----','入驻园区信息')->readonly(1)->disabled(1),
            Form::idate('residence_time','入驻园区时间',$product->getData('residence_time'))->col(8),
            Form::idate('start_time','入园协议起时间',$product->getData('start_time'))->col(8),
            Form::idate('end_time','入园协议止时间',$product->getData('end_time'))->col(8),
            Form::input('room_number','入驻房间编号',$product->getData('room_number'))->col(12),
            Form::number('site_area','入驻场地面积',$product->getData('site_area'))->col(12),

            // 项目经营情况
            Form::input('qi5','----','项目经营情况')->readonly(1)->disabled(1),
            Form::number('month_turnover','营业额-本月(万元)',$product->getData('month_turnover'))->precision(2)->col(6),
            Form::number('year_turnover','营业额-本年累计(万元)',$product->getData('year_turnover'))->precision(2)->col(6),
            Form::number('month_taxes','纳税额-本月(万元)',$product->getData('month_taxes'))->precision(2)->col(6),
            Form::number('year_taxes','纳税额-本年累计(万元)',$product->getData('year_taxes'))->precision(2)->col(6),

            // 项目培育孵化情况
            Form::input('qi6','----','项目培育孵化情况')->readonly(1)->disabled(1),
            Form::input('resource_docking','有效资源对接情况',$product->getData('resource_docking'))->col(12),
            Form::input('name_investor','出资单位名称',$product->getData('name_investor'))->col(12),
            Form::number('financing_amount','融资金额',$product->getData('financing_amount'))->precision(2)->col(12),
            Form::number('gov_amount','政府扶持资金名称及金额(万元)',$product->getData('gov_amount'))->precision(2)->col(12),

            // 其他信息
            Form::input('qi7','----','其他信息')->readonly(1)->disabled(1),
            Form::textarea('project_awards','项目获奖及专利情况',$product->getData('project_awards'))->col(24),
            Form::textarea('change_record','信息变更记录',$product->getData('change_record'))->col(24),
            Form::idate('back_time','退园时间',$product->getData('back_time'))->col(8),
            Form::input('reason','退园原因',$product->getData('reason'))->col(16),
            Form::input('industry_type','行业类型',$product->getData('industry_type'))->col(24),
            Form::input('products_services','项目提供的产品或服务',$product->getData('products_services'))->col(24),
            Form::input('required_pro_serv','项目需要的产品或服务',$product->getData('required_pro_serv'))->col(24),
            Form::number('financing_needs','是否有融资需求（1风险投资2贷款）',$product->getData('financing_needs'))->col(8),
            Form::input('entrepr','是否需要创业辅导培训（财务、法务等）',$product->getData('entrepr'))->col(24)
        ];
        $form = Form::make_post_form('编辑申请',$field,Url::build('save',array('id'=>$id)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }
    

    /**
     * 快速编辑
     *
     * @return json
     */
    public function set_product($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && JsonService::fail('缺少参数');
        if($field == 'is_hatched'){
            $value = $value == '是' ? 1:0;
        }
        if(ExamineModel::where(['id'=>$id])->update([$field=>$value]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }

    /**
     * 设置申请项目的 审核|待审核
     *
     * @return json
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && JsonService::fail('缺少参数');
        $res=ExamineModel::where(['id'=>$id])->update(['is_audited'=>(int)$is_show]);
        if($res){
            return JsonService::successful($is_show==1 ? '审核成功':'恢复成功');
        }else{
            return JsonService::fail($is_show==1 ? '审核失败':'恢复失败');
        }
    }

    /**
     * 设置批量 审核|待审核
     *
     * @return json
     */
    public function product_show(){
        $post=Util::postMore([
            ['ids',[]]
        ]);
        if(empty($post['ids'])){
            return JsonService::fail('请选择需要审核的项目');
        }else{
            $res=ExamineModel::where('id','in',$post['ids'])->update(['is_audited'=>1]);
            if($res)
                return JsonService::successful('审核成功');
            else
                return JsonService::fail('审核失败');
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {

        if(!$id) return $this->failed('数据不存在');
        if(!ExamineModel::be(['id'=>$id])) return $this->failed('数据不存在');
        if(ExamineModel::be(['id'=>$id,'is_del'=>1])){
            $data['is_del'] = 0;
            if(!ExamineModel::edit($data,$id))
                return Json::fail(ExamineModel::getErrorInfo('恢复失败,请稍候再试!'));
            else
                return Json::successful('成功恢复!');
        }else{
            $data['is_del'] = 1;
            if(!ExamineModel::edit($data,$id))
                return Json::fail(ExamineModel::getErrorInfo('删除失败,请稍候再试!'));
            else
                return Json::successful('成功移到回收站!');
        }

    }


}