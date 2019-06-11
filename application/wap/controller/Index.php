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

class Index extends AuthController
//class Index extends WapBasic
{

    public function index($project_id=0,$type=0)
    {
        $uid = User::getActiveUid();
        if($project_id && $type){
            Db::name('project_user')->where(['uid'=>$uid,'project_id'=>$project_id])->update(['status'=>1]);
        }else{
            Db::name('project_user')->where(['uid'=>$uid,'project_id'=>$project_id])->update(['status'=>0]);
        }
        // 申请项目
        $id = Db::name('project_user')->where(['uid'=>$uid,'status'=>1])->value('project_id');
        // 是否申绑定过项目
        if(empty($id)){
            $list = CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select();
            $idArr = Db::name('project_user')->where(['uid'=>$uid])->column('project_id');
            $projectlist = Db::name('examine')->whereIn('id',$idArr)->where(['is_del'=>0])->field('id,project_num,corporate_name,is_audited')->order('id desc')->select();
            $this->assign(compact('projectlist','list'));
            return $this->fetch('project/bind_project');
        }

        

        $this->assign([
            'banner'=>GroupDataService::getData('store_home_banner')?:[],
            'roll_news'=>Db::name('notice')->select()?:[],
            'notice_user'=>Db::name('notice_user')->where('uid',$uid)->select()?:[],
            'examine'=>Db::name('examine')->where('id',$id)->select()?:[],
            'iphone' => Db::name('user')->where('uid',$uid)->value('phone')
        ]);

        return $this->fetch();
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
        // if(!$data['project_num']) return Json::fail('请输入项目编号');
        $data['project_num'] = 'HS'.rand(1000000, 9999999);

        $validate = validate('ReportList');
        if(!$validate->check($data)){
            return Json::fail($validate->getError());
        }

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
        $uid = User::getActiveUid();
        // $res=ExamineModel::set($data);
        $projectId = Db::name('examine')->insertGetId($data);
        // 建立关联关系
        Db::name('project_user')->insert(['project_id'=>$projectId,'uid'=>$uid]);
        return Json::successful('添加申请成功!');
    }


    // 申请公司/项目 列表
    public function project($type=0,$id=0,$xt=1)
    {
        // 新增申请公司/项目页面
        if($type==1){
            $field = $this->getProjectfield();
            $form = Form::make_post_form('添加申请',$field,Url::build('save'),2);
            $this->assign(compact('form'));
            return $this->fetch('project');
        }else if($type==2){
            if(!$id) return $this->failed('数据不存在');
            $product = ExamineModel::get($id);
            if(!$product) return Json::fail('数据不存在!');
            $field = $this->getProjectEditfield($product);
            $form = Form::make_post_form('编辑申请',$field,Url::build('save',array('id'=>$id)),2);
            $this->assign(compact('form'));
            return $this->fetch('project');
        }
        $uid = User::getActiveUid();
        // 申请的项目
        $idArr = Db::name('project_user')->where(['uid'=>$uid,'status'=>1])->column('project_id');
       if($xt == 1){
           $projectlist = Db::name('examine')->whereIn('id',$idArr)->where(['is_audited'=>0,'is_del'=>0])->field('id,project_num,corporate_name,FROM_UNIXTIME(create_time) as create_time')->order('id desc')->select();
       }else if($xt == 2){
           $projectlist = Db::name('examine')->whereIn('id',$idArr)->where(['is_audited'=>1,'is_del'=>0])->field('id,project_num,corporate_name,FROM_UNIXTIME(create_time) as create_time')->order('id desc')->select();
       }else{
           $projectlist = Db::name('examine')->whereIn('id',$idArr)->where(['is_del'=>1])->field('id,project_num,corporate_name,FROM_UNIXTIME(create_time) as create_time')->order('id desc')->select();
       }
        // 项目列表
        $this->assign(compact('projectlist','xt'));
        return $this->fetch('project_list');
    }





    public function index_back()
    {
        try{
            $uid = User::getActiveUid();
            $notice = UserNotice::getNotice($uid);
        }catch (\Exception $e){
            $notice = 0;
        }
        $storePink = StorePink::where('p.add_time','GT',time()-86300)->alias('p')->where('p.status',1)->join('User u','u.uid=p.uid')->field('u.nickname,u.avatar as src,p.add_time')->order('p.add_time desc')->limit(20)->select();
        if($storePink){
            foreach ($storePink as $k=>$v){
                $remain = $v['add_time']%86400;
                $hour = floor($remain/3600);
                $storePink[$k]['nickname'] = $v['nickname'].$hour.'小时之前拼单';
            }
        }
        $seckillnum=(int)GroupDataService::getData('store_seckill');
        $storeSeckill=StoreSeckill::where('is_del',0)->where('status',1)
               ->where('start_time','<',time())->where('stop_time','>',time())
               ->limit($seckillnum)->order('sort desc')->select()->toArray();
        foreach($storeSeckill as $key=>$value){
            if($value['stock']>0)
            $round = round($value['sales']/$value['stock'],2)*100;
            else $round = 100;
            if($round<100){
                $storeSeckill[$key]['round']=$round;
            }else{
                $storeSeckill[$key]['round']=100;
            }
        }
        $this->assign([
            'banner'=>GroupDataService::getData('store_home_banner')?:[],
            'menus'=>GroupDataService::getData('store_home_menus')?:[],
            'roll_news'=>GroupDataService::getData('store_home_roll_news')?:[],
            'category'=>StoreCategory::pidByCategory(0,'id,cate_name'),
            'pinkImage'=>SystemConfigService::get('store_home_pink'),
            'notice'=>$notice,
            'storeSeckill'=>$storeSeckill,
            'storePink'=>$storePink,
        ]);
        return $this->fetch();
    }

    public function about()
    {
        return $this->fetch();
    }

    public function spread($uni = '')
    {
        if(!$uni || $uni == 'now') $this->redirect(Url::build('spread',['uni'=>$this->oauth()]));
        $wechatUser = WechatUser::getWechatInfo($uni);
        $statu = (int)SystemConfigService::get('store_brokerage_statu');
        if($statu == 1){
            if(!User::be(['uid'=>$this->userInfo['uid'],'is_promoter'=>1]))
                return $this->failed('没有权限访问!');
        }
        $qrInfo = QrcodeService::getTemporaryQrcode('spread',$wechatUser['uid']);
        $this->assign([
            'qrInfo'=>$qrInfo,
            'wechatUser'=>$wechatUser
        ]);
        return $this->fetch();
    }


    public function getProjectfield()
    {
        $field = [
            Form::input('project_num','项目编号')->col(24),
            Form::select('category_id','所属园区')->setOptions(function(){
                $list = CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select();
                $menus=[];
                foreach ($list as $v){
                    $menus[] = ['value'=>$v['id'],'label'=>$v['cate_name']];
                }
                return $menus;
            })->filterable(1)->multiple(0)->col(24),
            Form::radio('is_hatched','是否入孵项目',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),

            // 企业（项目）信息
            Form::input('qi1','','企业（项目）信息')->readonly(1)->disabled(1),
            Form::input('corporate_name','公司名称')->col(24),
            Form::input('org_code','组织机构代码')->col(24),
            Form::input('project_synopsis','项目简介')->col(24),
            Form::input('project_type','项目类别')->col(24),
            Form::number('jop_num','就业人数')->col(24),
            Form::number('entr_num','创业人数')->col(24),
            Form::radio('is_register','是否工商注册',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),

            // 法人信息
            Form::input('qi2','','法人信息')->readonly(1)->disabled(1),
            Form::input('legal_name','姓  名')->col(24),
            Form::input('legal_id_card','身份证号')->col(24),
            Form::input('legal_school','毕业院校')->col(24),
            Form::input('legal_time','毕业时间')->col(24),
            Form::input('legal_education','学  历')->col(24),
            Form::input('legal_phone','联系电话')->col(24),
            Form::radio('is_graduate_school','是否毕业5年或在校',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),

            // 团队成员信息
            Form::input('qi3','','团队成员信息')->readonly(1)->disabled(1),
            Form::input('team_name','姓  名')->col(24),
            Form::input('team_school','毕业院校')->col(24),
            Form::input('team_time','毕业时间')->col(24),
            Form::input('team_education','学  历')->col(24),
            Form::input('team_phone','联系电话')->col(24),

            // 入驻园区信息
            // Form::input('qi4','','入驻园区信息')->readonly(1)->disabled(1),
            // Form::idate('residence_time','入驻园区时间')->col(24),
            // Form::idate('start_time','入园协议起时间')->col(24),
            // Form::idate('end_time','入园协议止时间')->col(24),
            // Form::input('room_number','入驻房间编号')->col(24),
            // Form::number('site_area','入驻场地面积')->col(24),

            // 项目经营情况
            // Form::input('qi5','','项目经营情况')->readonly(1)->disabled(1),
            // Form::number('month_turnover','营业额-本月(万元)')->precision(2)->col(24),
            // Form::number('year_turnover','营业额-本年累计(万元)')->precision(2)->col(24),
            // Form::number('month_taxes','纳税额-本月(万元)')->precision(2)->col(24),
            // Form::number('year_taxes','纳税额-本年累计(万元)')->precision(2)->col(24),

            // 项目培育孵化情况
            // Form::input('qi6','','项目培育孵化情况')->readonly(1)->disabled(1),
            // Form::input('resource_docking','有效资源对接情况')->col(24),
            // Form::input('name_investor','出资单位名称')->col(24),
            // Form::number('financing_amount','融资金额')->precision(2)->col(24),
            // Form::number('gov_amount','政府扶持资金名称及金额(万元)')->precision(2)->col(24),

            // 其他信息
            // Form::input('qi7','','其他信息')->readonly(1)->disabled(1),
            // Form::textarea('project_awards','项目获奖及专利情况')->col(24),
            // Form::textarea('change_record','信息变更记录')->col(24),
            // Form::idate('back_time','退园时间')->col(24),
            // Form::input('reason','退园原因')->col(24),
            // Form::input('industry_type','行业类型')->col(24),
            // Form::input('products_services','项目提供的产品或服务')->col(24),
            // Form::input('required_pro_serv','项目需要的产品或服务')->col(24),
            // Form::number('financing_needs','是否有融资需求(1风险投资2贷款)')->col(24),
            // Form::input('entrepr','是否需要创业辅导培训(财务、法务等)')->col(24)
        ];
        return $field;
    }

    public function getProjectEditfield($product)
    {
        $field = [
            Form::input('project_num','项目编号',$product->getData('project_num'))->disabled(1)->col(24),
            Form::select('category_id','所属园区',[$product->getData('category_id')])->setOptions(function(){
                $list = CategoryModel::where(['pid'=>0,'is_show'=>1])->field('id,cate_name')->select();
                $menus=[];
                foreach ($list as $v){
                    $menus[] = ['value'=>$v['id'],'label'=>$v['cate_name']];
                }
                return $menus;
            })->filterable(1)->multiple(0)->disabled(1)->col(24),
            Form::radio('is_hatched','是否入孵项目',$product->getData('is_hatched'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),

            // 企业（项目）信息
            Form::input('qi1','','企业（项目）信息')->readonly(1)->disabled(1),
            Form::input('corporate_name','公司名称',$product->getData('corporate_name'))->col(24),
            Form::input('org_code','组织机构代码',$product->getData('org_code'))->col(24),
            Form::input('project_synopsis','项目简介',$product->getData('project_synopsis')),
            Form::input('project_type','项目类别',$product->getData('project_type')),
            Form::number('jop_num','就业人数',$product->getData('jop_num'))->col(24),
            Form::number('entr_num','创业人数',$product->getData('entr_num'))->col(24),
            Form::radio('is_register','是否工商注册',$product->getData('is_register'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),

            // 法人信息
            Form::input('qi2','','法人信息')->readonly(1)->disabled(1),
            Form::input('legal_name','姓名',$product->getData('legal_name'))->col(24),
            Form::input('legal_id_card','身份证号',$product->getData('legal_id_card'))->col(24),
            Form::input('legal_school','毕业院校',$product->getData('legal_school'))->col(24),
            Form::input('legal_time','毕业时间',$product->getData('legal_time'))->col(24),
            Form::input('legal_education','学历',$product->getData('legal_education'))->col(24),
            Form::input('legal_phone','联系电话',$product->getData('legal_phone'))->col(24),
            Form::radio('is_graduate_school','是否毕业5年或在校',$product->getData('is_graduate_school'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(24),

            // 团队成员信息
            Form::input('qi3','','团队成员信息')->readonly(1)->disabled(1),
            Form::input('team_name','姓名',$product->getData('team_name'))->col(24),
            Form::input('team_school','毕业院校',$product->getData('team_school'))->col(24),
            Form::input('team_time','毕业时间',$product->getData('team_time'))->col(24),
            Form::input('team_education','学历',$product->getData('team_education'))->col(24),
            Form::input('team_phone','联系电话',$product->getData('team_phone'))->col(24),

            // // 入驻园区信息
            // Form::input('qi4','','入驻园区信息')->readonly(1)->disabled(1),
            // Form::idate('residence_time','入驻园区时间',$product->getData('residence_time'))->col(24),
            // Form::idate('start_time','入园协议起时间',$product->getData('start_time'))->col(24),
            // Form::idate('end_time','入园协议止时间',$product->getData('end_time'))->col(24),
            // Form::input('room_number','入驻房间编号',$product->getData('room_number'))->col(24),
            // Form::number('site_area','入驻场地面积',$product->getData('site_area'))->col(24),

            // // 项目经营情况
            // Form::input('qi5','','项目经营情况')->readonly(1)->disabled(1),
            // Form::number('month_turnover','营业额-本月(万元)',$product->getData('month_turnover'))->precision(2)->col(24),
            // Form::number('year_turnover','营业额-本年累计(万元)',$product->getData('year_turnover'))->precision(2)->col(24),
            // Form::number('month_taxes','纳税额-本月(万元)',$product->getData('month_taxes'))->precision(2)->col(24),
            // Form::number('year_taxes','纳税额-本年累计(万元)',$product->getData('year_taxes'))->precision(2)->col(24),

            // // 项目培育孵化情况
            // Form::input('qi6','','项目培育孵化情况')->readonly(1)->disabled(1),
            // Form::input('resource_docking','有效资源对接情况',$product->getData('resource_docking'))->col(24),
            // Form::input('name_investor','出资单位名称',$product->getData('name_investor'))->col(24),
            // Form::number('financing_amount','融资金额',$product->getData('financing_amount'))->precision(2)->col(24),
            // Form::number('gov_amount','政府扶持资金名称及金额(万元)',$product->getData('gov_amount'))->precision(2)->col(24),

            // // 其他信息
            // Form::input('qi7','','其他信息')->readonly(1)->disabled(1),
            // Form::textarea('project_awards','项目获奖及专利情况',$product->getData('project_awards'))->col(24),
            // Form::textarea('change_record','信息变更记录',$product->getData('change_record'))->col(24),
            // Form::idate('back_time','退园时间',$product->getData('back_time'))->col(24),
            // Form::input('reason','退园原因',$product->getData('reason'))->col(24),
            // Form::input('industry_type','行业类型',$product->getData('industry_type'))->col(24),
            // Form::input('products_services','项目提供的产品或服务',$product->getData('products_services'))->col(24),
            // Form::input('required_pro_serv','项目需要的产品或服务',$product->getData('required_pro_serv'))->col(24),
            // Form::number('financing_needs','是否有融资需求（1风险投资2贷款）',$product->getData('financing_needs'))->col(24),
            // Form::input('entrepr','是否需要创业辅导培训（财务、法务等）',$product->getData('entrepr'))->col(24)
        ];
        return $field;
    }

}