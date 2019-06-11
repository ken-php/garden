<?php

namespace app\admin\model\report;

use service\PHPExcelService;
use think\Db;
use traits\ModelTrait;
use basic\ModelBasic;
use app\admin\model\examine\ExamineModel;

/**
 * 审核管理 model
 * Class ReportModel
 * @package app\admin\model\report
 */
class ReportModel extends ModelBasic
{
    protected $name = 'report';
    use ModelTrait;

    /*
     * 获取申请列表
     * @param $where array
     * @return array
     *
     */
    public static function List($where){
//        if (isset($where['type']) && $where['type'] == 1){
//            $model=self::getModelObject($where)->field('e.*,b.corporate_name,s.cate_name');
//        }else{
            $model=self::getModelObject($where)->field('e.*,s.cate_name');


        if($where['excel']==0)$model=$model->page((int)$where['page'],(int)$where['limit']);
        $data = ($data=$model->select()) && count($data) ? $data->toArray():[];

        foreach ($data as &$item){
            $item['is_hatched'] = $item['is_hatched'] == 1 ? '已入孵' : '待入孵';
            $item['is_register'] = $item['is_register'] == 1 ? '已注册' : '未注册';
            $item['is_graduate_school'] = $item['is_graduate_school'] == 1 ? '是' : '否';
            $item['financing_needs'] = $item['financing_needs'] == 1 ? '风险投资' : '贷款';
        }

        if($where['excel']==1){
            $export = [];
            foreach ($data as $key => $val){
                $export[] = [
                    $val['corporate_name'],  // 企业或项目名
                    $val['cate_name'],  // 所属园区
                    $val['is_register'],  // 是否注册企业
                    $val['address'],  // 注册地址
                    $val['area'],  // 场地面积
                    $val['is_new_teams'],  // 是否新增创客/团队
                    $val['is_science'],  // 是否科技型中小企业
                    $val['is_high_tech'],  // 是否高新技术企业
                    $val['enterprises_num'],  // 与合作大学创办企业数
                    $val['interns_num'],  // 接纳大学生/研究生实习人员数
                    $val['is_sale'],  // 是否上市挂牌
                    $val['add_jop_num'],  // 新增从业人员
                    $val['add_entr_num'],  // 新增应届毕业生就业人员数
                    $val['turnover'],  // 当前月营业额
                    $val['taxes'],  // 当前月纳税额
                    $val['funds'],  // 研发经费投入
                    $val['financial'],  // 享受财政支持金额
                    $val['activity_num'],  // 参加的投融资对接活动次数
                    $val['is_investment'],  // 是否获得投资
                    $val['investment_amount'],  // 获得投资金额
                    $val['intellectual_num'],  // 知识产权申请数
                    $val['has_intel_num'],  // 拥有有效知识产权数(已注册公司)
                    $val['patents_num'],  // 申请发明专利数量(已注册公司)
                    $val['re_has_intel_num'],  // 拥有有效知识产权数(未注册公司)
                    $val['re_patents_num'],  // 申请发明专利数量(未注册公司)
                    $val['achievement_num'],  // 科技成果转化数
                ];
            }

            if ($where['month'] == '' || $where['month'] == null){
                $where['month'] = '当前';
            }

            PHPExcelService::setExcelHeader([
                '企业或项目名','所属园区',
                '是否注册企业','注册地址',
                "场地面积(众创空间一般都是15平科技园大一些)",$where['month'].'月是否新增创客/团队',
                $where['month'].'月是否新增科技型中小企业',$where['month'].'月是否新增高新技术企业',
                $where['month'].'月新增与合作大学创办企业数',$where['month'].'月新增接纳大学生/研究生实习人员数',
                $where['month'].'月是否新增上市挂牌',$where['month'].'月新增从业人员数(团队人数)',
                $where['month'].'月新增应届毕业生就业人员数',$where['month'].'月新增营业额(千元)',
                $where['month'].'月新增纳税额(千元)',$where['month'].'月新增研发经费投入(千元)',
                $where['month'].'月新增享受的财政支持金额(仅限企业)',$where['month'].'月新增参加的投资融资对接活动次数',
                $where['month'].'月新增是否获得投资',$where['month'].'月新增获得投资金额(千元)',
                $where['month'].'月新增知识产权申请数',$where['month'].'月新增拥有有效知识产权数',
                $where['month'].'月新增申请发明专利数量',$where['month'].'月新增拥有有效知识产权数',
                $where['month'].'月新增申请发明专利数量',$where['month'].'月新增科技成果转化数',
            ])
                ->setExcelTile('月报导出','月报信息'.time(),' 生成时间：'.date('Y-m-d H:i:s',time()))
                ->setExcelContent($export)
                ->ExcelSave();
        }
        $count=self::getModelObject($where)->count();

        return compact('count','data');
    }

    /**
     * 获取连表Model
     * @param $model
     * @return object
     */
    public static function getModelObject($where=[]){
        $model=new self();
        $model = $model->alias('e')->join('StoreCategory s','e.category_id=s.id','LEFT');

        if(!empty($where)){
            $model=$model->group('e.id');
            $time['data']='';
            if($where['start_time']!='' && $where['end_time']!=''){
                $time['data']=$where['start_time'].' - '.$where['end_time'];

                $model=self::getModelTime($time,self::alias('e')
                    ->join('StoreCategory s','e.category_id=s.id','LEFT')
                    ->join('examine B','B.project_num=e.project_num')
//                    ->where('e.project_num','not in','B.project_num')
                    ->order('e.create_time desc'),'e.create_time');



            }

            // 科技园上月没提交月报列表
            if (isset($where['type']) && $where['type'] == 2){
                $model = new ExamineModel();
                $model = $model->alias('e')->join('StoreCategory s','e.category_id=s.id','LEFT');
                $model=$model->group('e.id');
                $curMonth = date('Y-m',strtotime("-1 month", time()));
                    $projectNum = self::where(['month' => $curMonth , 'category_id' => 23])->column('project_num');
                    $model= $model->where('project_num','not in',$projectNum)->where('category_id',23);
            }

            // 众创空间上月没提交月报列表
            if (isset($where['type']) && $where['type'] == 3){
                $model = new ExamineModel();
                $model = $model->alias('e')->join('StoreCategory s','e.category_id=s.id','LEFT');
                $model=$model->group('e.id');
                $curMonth = date('Y-m',strtotime("-1 month", time()));
                $projectNum = self::where(['month' => $curMonth , 'category_id' => 63])->column('project_num');
                $model= $model->where('project_num','not in',$projectNum)->where('category_id',63);
            }

            // 上月已提交月报列表
            if (isset($where['type']) && $where['type'] == 4){
                $curMonth = date('Y-m',strtotime("-1 month", time()));
                $model = $model->where('e.month', $curMonth)->order('e.sort desc');

            }

            // 历史月报列表,默认显示上月的月报列表
            if (isset($where['type']) && $where['type'] == 1){
                $curMonth = date('Y-m',strtotime("-1 month", time()));
                $model = $model->join('examine b','b.project_num=e.project_num')
                               ->where('e.month', $curMonth)
//                               ->where('b.project_num','not in','e.project_num')
                               ->order('e.sort desc');

            }

            // 月份查询
            if (isset($where['month']) && $where['month'] != ''){
                // 查询上月月报,最新月报数据
                $curMonth = date('Y-m',strtotime("-1 month", time()));
                $month = date('Y-m',strtotime(date('Y-'.$where['month'],time())));
                if ($curMonth == $month){
                    $model = $model->where('e.month',$curMonth)->order('e.sort desc');
                }else{
                    // 不是当前月
                    $model = $model->where('e.month',$month);
                }
            }

            if(isset($where['search_name']) && $where['search_name']!=''){
                if($where['search_name']=='是' || $where['search_name']=='否'){
                    $is_hatched = $where['search_name']=='是' ? 1 : 0;
                    $model = $model->where('e.is_register|e.is_small_business|e.is_high_tech|e.is_listed',$is_hatched);
                }else{
                    $model = $model->where('e.project_name|s.address','LIKE',"%$where[search_name]%");
                }
            }
            if(isset($where['cate_id']) && trim($where['cate_id'])!=''){
                $model = $model->where('e.category_id',$where['cate_id']);
            }
            if(isset($where['order']) && $where['order']!=''){
                $model = $model->order(self::setOrder($where['order']));
            }
        }
        return $model;
    }

    /**
     * 获取连表查询条件
     * @param $type
     * @return array
     */
    public static function setData($type){
        switch ((int)$type){
            case 1:
                $data = ['e.is_audited'=>0,'e.is_del'=>0];
                break;
            case 2:
                $data = ['e.is_audited'=>1,'e.is_del'=>0];
                break;
            case 3:
                $data = ['e.is_del'=>1];
                break;
        };
        return isset($data) ? $data: [];
    }

    /**
     * 验证同园区项目编号
     * @param $project_num
     * @param $cate_id
     * @return mixed
     * @author ken
     * @date 2019/5/30
     */
    public static function getUniqueness($project_num,$cate_id,$month){
        return self::where(['category_id'=>$cate_id,'project_num'=>$project_num,'month'=>$month])->value('id');
    }

    /**
     * 月报列表
     * @param $where
     * @param $order
     * @param string $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @author ken
     * @date 2019/5/28
     */
    public static function getReportRes($where, $order, $limit = '')
    {
        $res = Db::name('report')
            ->where($where)
            ->field('id,category_id,project_name,address,FROM_UNIXTIME(create_time) as create_time')
            ->order($order)
            ->limit($limit)
            ->select();

        return $res;
    }

    /**
     * 获取所有项目编号
     * @return array
     * @author ken
     * @date 2019/6/3
     */
    public static function getProjectNum()
    {
        return Db::name('report')->column('project_num');
    }

    /**
     * 查询某个字段的所有的值
     * @param string $where
     * @param $val
     * @return array
     * @author ken
     * @date 2019/6/5
     */
    public static function getSameAllValue($where = '' , $val)
    {
        return self::where($where)->column($val);
    }

    /**
     * 查询上月已提交月报数
     * @return int|string
     * @throws \think\Exception
     * @author ken
     * @date 2019/6/6
     */
    public static function getSubmittedReportNum()
    {
        $curMonth = date('Y-m',strtotime("-1 month", time()));
        return self::where('month',$curMonth)->count();
    }
}