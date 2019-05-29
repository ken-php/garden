<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/11
 */

namespace app\wap\controller;

use app\wap\model\store\StoreCombination;
use app\wap\model\store\StoreSeckill;
use app\wap\model\store\StoreCategory;
use app\wap\model\store\StoreOrder;
use app\wap\model\store\StorePink;
use app\wap\model\store\StoreProduct;
use app\wap\model\user\User;
use app\admin\model\report\ReportModel;
use app\wap\model\user\UserNotice;
use app\wap\model\user\WechatUser;
use basic\WapBasic;
use app\admin\model\store\StoreCategory as CategoryModel;
use service\FormBuilder as Form;
use service\JsonService as Json;
use service\UtilService as Util;
use app\admin\model\examine\ExamineModel;
use think\Request;
use app\core\util\GroupDataService;
use app\core\util\QrcodeService;
use app\core\util\SystemConfigService;
use think\Db;
use think\Url;
use think\Session;
use function GuzzleHttp\json_decode;

/**
 * 月报控制器
 * Class Report
 * @package app\wap\controller
 */
class Report extends AuthController
{
    /**
     * 月报首页
     * @return mixed
     * @author ken
     * @date 2019/5/28
     */
    public function index($type = 0, $id = 0)
    {
        // 添加月报
        if ($type == 1){
            $field = $this->getReportField();
            $form = Form::make_post_form('添加月报',$field,Url::build('save'),2);
            $this->assign(compact('form'));
            return $this->fetch('report');

        }elseif ($type == 2){
            // 编辑月报
            if (!$id) return $this->failed('数据不存在');

            $report = ReportModel::get($id);
            if (!$report) return Json::fail('数据不存在');

            $field = $this->getReportEditField($report);
            $form = Form::make_post_form('编辑月报',$field,Url::build('save',array('id'=>$id)),2);
            $this->assign(compact('form'));

            return $this->fetch('report');

        }elseif($type == 3){
            // 查看月报
            if (!$id) return $this->failed('数据不存在');
            $reportDetail = ReportModel::get($id);
            if (!$reportDetail) return Json::fail('数据不存在');
            $this->assign('reportDetail',$reportDetail);

            return $this->fetch('report_detail');
        }

        // 月报列表
        $uid = User::getActiveUid();
        $where = ['uid' => $uid];
        $order = 'id desc';
        $reportResult = ReportModel::getReportRes($where,$order,4);
        $this->assign('projectlist',$reportResult);
        return $this->fetch();
    }

    /**
     * 新增月报
     * @param Request $request
     * @param int $id
     * @author ken
     * @date 2019/5/29
     */
    public function save(Request $request,$id=0)
    {
        $data = Util::postMore([
            'category_id','project_name','is_register','address',
            'is_small_business','is_high_tech','is_listed',
            'team_num','persons_num','site_area',
            'one_turnover','current_turnover','current_tax',
            'funding','amount','activities_num','investment',
            'investment_amount','intellectual','have_num',
            'patents',
        ],$request);

        // 数据校验
        if(!$data['category_id']) return Json::fail('请选择所属园区');
        if(!$data['project_name']) return Json::fail('请输入公司名称');
        if(!$data['address']) return Json::fail('请输入注册地址');
        if(!$data['team_num']) return Json::fail('请输入从业人数');
        if(!$data['site_area']) return Json::fail('请输入场地面积');
        if(!$data['one_turnover']) return Json::fail('请输入1-3月营业额');
        if(!$data['current_turnover']) return Json::fail('请输入上月营业额');
        if(!$data['current_tax']) return Json::fail('请输入上月纳税额');
        if(!$data['funding']) return Json::fail('请输入上月研发经费');
        if(!$data['amount']) return Json::fail('请输入享受的财政支持额');
        if(!$data['activities_num']) return Json::fail('请输入参加的投融资对接活动次数');
        if(!$data['investment_amount']) return Json::fail('请输入1到3月获得投资金额');
        if(!$data['intellectual']) return Json::fail('请输入1到3月知识产权申请数');
        if(!$data['have_num']) return Json::fail('请输入有效知识产权数');
        if(!$data['patents']) return Json::fail('请输入1到3月申请发明专利数');

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
        // 获取当前用户的uid
        $data['uid'] = User::getActiveUid();
        $res = ReportModel::set($data);
        if (!$res) return Json::fail('添加月报失败');
        return Json::successful('添加月报成功!');
    }

    /**
     * 快速生成表单 月报
     * @throws \FormBuilder\exception\FormBuilderException
     * @author ken
     * @date 2019/5/29
     */
    public function getReportField()
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

            // 企业信息
            Form::input('qi1','','企业（项目）信息')->readonly(1)->disabled(1),
            Form::input('project_name','企业或项目名')->col(24),
            Form::input('address','注册地址')->col(24),
            Form::radio('is_register','是否注册企业',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
            Form::radio('is_small_business','是否科技型中小企业',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
            Form::radio('is_high_tech','是否是高新技术企业',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
            Form::radio('is_listed','是否上市挂牌',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),

            // 团队信息
            Form::input('qi2','','团队成员信息')->readonly(1)->disabled(1),
            Form::number('team_num','员工（团队人数）')->precision(2)->col(24),
            Form::number('persons_num','应届毕业生数')->precision(2)->col(24),
            Form::number('site_area','场地面积(一般是15平)')->precision(2)->col(24),

            // 营业信息
            Form::input('qi3','','营业信息')->readonly(1)->disabled(1),
            Form::number('one_turnover','1到3月营业额（千元）')->precision(2)->col(24),
            Form::number('current_turnover','4月营业额（千元）')->precision(2)->col(24),
            Form::number('current_tax','4月纳税额 (千元)')->precision(2)->col(24),

            // 投资信息
            Form::input('qi4','','投资信息')->readonly(1)->disabled(1),
            Form::number('funding','研发经费投入（千元）')->precision(2)->col(24),
            Form::number('amount','享受财政支持金额(限企业)')->precision(2)->col(24),
            Form::number('activities_num','参加投融资对接活动次数')->precision(2)->col(24),
            Form::number('investment_amount','1到3月获得投资金额(千元)')->precision(2)->col(24),
            Form::number('intellectual','1到3月知识产权申请数')->precision(2)->col(24),
            Form::number('have_num','拥有有效知识产权数')->precision(2)->col(24),
            Form::number('patents','1到3月申请发明专利数量')->precision(2)->col(24),
            Form::radio('investment','4月是否获得投资',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
        ];

        return $field;
    }

    /**
     * 快速生成编辑表单
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     * @author ken
     * @date 2019/5/29
     */
    public function getReportEditField($report)
    {
        $field = [
            Form::select('category_id','所属园区',[$report->getData('category_id')])->setOptions(function(){
                $list = CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select();
                $menus=[];
                foreach ($list as $v){
                    $menus[] = ['value'=>$v['id'],'label'=>$v['cate_name']];
                }
                return $menus;
            })->filterable(1)->multiple(0)->col(24),

            // 企业信息
            Form::input('qi1','','企业（项目）信息')->readonly(1)->disabled(1),
            Form::input('project_name','企业或项目名',$report->getData('project_name'))->col(24),
            Form::input('address','注册地址',$report->getData('address'))->col(24),
            Form::radio('is_register','是否注册企业',$report->getData('is_register'))
                ->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
            Form::radio('is_small_business','是否科技型中小企业',$report->getData('is_small_business'))
                ->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
            Form::radio('is_high_tech','是否是高新技术企业',$report->getData('is_high_tech'))
                ->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
            Form::radio('is_listed','是否上市挂牌',$report->getData('is_listed'))
                ->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),

            // 团队信息
            Form::input('qi2','','团队成员信息')->readonly(1)->disabled(1),
            Form::number('team_num','员工（团队人数）',$report->getData('team_num'))->precision(2)->col(24),
            Form::number('persons_num','应届毕业生数',$report->getData('persons_num'))->precision(2)->col(24),
            Form::number('site_area','场地面积(一般是15平)',$report->getData('site_area'))->precision(2)->col(24),

            // 营业信息
            Form::input('qi3','','营业信息')->readonly(1)->disabled(1),
            Form::number('one_turnover','1到3月营业额（千元）',$report->getData('one_turnover'))->precision(2)->col(24),
            Form::number('current_turnover','4月营业额（千元）',$report->getData('current_turnover'))->precision(2)->col(24),
            Form::number('current_tax','4月纳税额 (千元)',$report->getData('current_tax'))->precision(2)->col(24),

            // 投资信息
            Form::input('qi4','','投资信息')->readonly(1)->disabled(1),
            Form::number('funding','研发经费投入（千元）',$report->getData('funding'))->precision(2)->col(24),
            Form::number('amount','享受财政支持金额(限企业)',$report->getData('amount'))->precision(2)->col(24),
            Form::number('activities_num','参加投融资对接活动次数',$report->getData('activities_num'))->precision(2)->col(24),
            Form::number('investment_amount','1到3月获得投资金额(千元)',$report->getData('investment_amount'))->precision(2)->col(24),
            Form::number('intellectual','1到3月知识产权申请数',$report->getData('intellectual'))->precision(2)->col(24),
            Form::number('have_num','拥有有效知识产权数',$report->getData('have_num'))->precision(2)->col(24),
            Form::number('patents','1到3月申请发明专利数量',$report->getData('patents'))->precision(2)->col(24),
            Form::radio('investment','4月是否获得投资',$report->getData('investment'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),
        ];

        return $field;
    }
}