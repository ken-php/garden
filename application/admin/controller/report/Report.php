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
        //获取分类
        $this->assign('cate',CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select());
        return $this->fetch();
    }

    /**
     * 异步查找产品
     *
     * @return json
     */
    public function product_ist(){
        $where = Util::getMore([
            ['page',1],
            ['limit',20],
            ['search_name',''],
            ['cate_id',''],
            ['excel',0],
            ['order','']
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
            Form::select('category_id','所属园区')->setOptions(function(){
                $list = CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select();
                $menus=[];
                foreach ($list as $v){
                    $menus[] = ['value'=>$v['id'],'label'=>$v['cate_name']];
                }
                return $menus;
            })->filterable(1)->multiple(0)->col(24),
            Form::input('project_name','企业或项目名')->col(24),
            Form::radio('is_register','是否注册企业',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
            Form::input('address','注册地址')->col(24),
            Form::radio('is_small_business','是否科技型中小企业',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_high_tech','是否是高新技术企业',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_listed','是否上市挂牌',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),

            Form::number('team_num','从业人员数（团队人数）')->col(8),
            Form::number('persons_num','应届毕业生就业人员数')->col(8),
            Form::number('site_area','场地面积(一般都是15平，个别的大一些)')->col(8),

            Form::number('one_turnover','1到3月营业额（千元）')->precision(2)->col(8),
            Form::number('current_turnover','4月营业额（千元）')->precision(2)->col(8),
            Form::number('current_tax','4月纳税额（千元）')->precision(2)->col(8),

            Form::radio('investment','4月是否获得投资',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),

            Form::number('funding','研发经费投入（千元）')->precision(2)->col(8),
            Form::number('amount','享受的财政支持金额（仅限企业）')->precision(2)->col(8),
            Form::number('activities_num','参加的投融资对接活动次数')->col(8),

            Form::number('investment_amount','1到3月获得投资金额(千元)')->precision(2)->col(8),
            Form::number('intellectual','1到3月知识产权申请数')->col(8),
            Form::number('have_num','拥有有效知识产权数')->col(8),
            Form::number('patents','1到3月申请发明专利数量')
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
            'category_id','project_name','is_register',
            'address','is_small_business','is_high_tech',
            'is_listed','team_num','persons_num',
            'site_area','one_turnover','current_turnover',
            'current_tax','funding','amount',
            'activities_num','investment','investment_amount',
            'intellectual','have_num','patents'
        ],$request);
        // 数据校验
        if(!$data['category_id']) return Json::fail('请选择所属园区');
        if(!$data['project_name']) return Json::fail('请输入企业或项目名');

        if($id){
            // 修改
            $isHas = Db::name('examine')->where('id',$id)->value('id');
            if(!$isHas) return Json::successful('修改失败，数据不存在!');
            $data['update_time'] = time();
            ReportModel::edit($data,$id);
            return Json::successful('修改月报成功!');
        }

        // 新增
        $data['create_time'] = time();
        // 获取当前用户的uid
        $data['uid'] = getUidByAdminId(Session::get('adminId'));
        $res=ReportModel::set($data);
        return Json::successful('添加月报成功!');
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
            Form::select('category_id','所属园区',[$product->getData('category_id')])->setOptions(function(){
                $list = CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select();
                $menus=[];
                foreach ($list as $v){
                    $menus[] = ['value'=>$v['id'],'label'=>$v['cate_name']];
                }
                return $menus;
            })->filterable(1)->multiple(0)->col(24),
            Form::input('project_name','企业或项目名',$product->getData('project_name'))->col(24),
            Form::radio('is_register','是否注册企业',$product->getData('is_register'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
            Form::input('address','注册地址',$product->getData('address'))->col(24),
            Form::radio('is_small_business','是否科技型中小企业',$product->getData('is_small_business'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_high_tech','是否是高新技术企业',$product->getData('is_high_tech'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_listed','是否上市挂牌',$product->getData('is_listed'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),

            Form::number('team_num','从业人员数(团队人数)',$product->getData('team_num'))->col(8),
            Form::number('persons_num','应届毕业生就业人员数',$product->getData('persons_num'))->col(8),
            Form::number('site_area','场地面积(一般都是15平，个别的大一些)',$product->getData('site_area'))->col(8),

            Form::number('one_turnover','1到3月营业额(千元)',$product->getData('one_turnover'))->precision(2)->col(8),
            Form::number('current_turnover','4月营业额(千元)',$product->getData('current_turnover'))->precision(2)->col(8),
            Form::number('current_tax','4月纳税额(千元)',$product->getData('current_tax'))->precision(2)->col(8),

            Form::radio('investment','4月是否获得投资',$product->getData('investment'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),

            Form::number('funding','研发经费投入(千元)',$product->getData('funding'))->precision(2)->col(8),
            Form::number('amount','享受的财政支持金额(仅限企业)',$product->getData('amount'))->precision(2)->col(8),
            Form::number('activities_num','参加的投融资对接活动次数',$product->getData('activities_num'))->col(8),

            Form::number('investment_amount','1到3月获得投资金额(千元)',$product->getData('investment_amount'))->precision(2)->col(8),
            Form::number('intellectual','1到3月知识产权申请数',$product->getData('intellectual'))->col(8),
            Form::number('have_num','拥有有效知识产权数',$product->getData('have_num'))->col(8),
            Form::number('patents','1到3月申请发明专利数量',$product->getData('patents'))
        ];
        $form = Form::make_post_form('编辑申请',$field,Url::build('save',array('id'=>$id)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }


}