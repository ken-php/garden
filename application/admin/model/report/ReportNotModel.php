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
class ReportNotModel extends ModelBasic
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
                    $val['id'],
                    $val['project_num'],
                    $val['cate_name'],
                    $val['is_hatched'],
                    $val['corporate_name'],
                    $val['org_code'],
                    $val['project_synopsis'],
                    $val['is_register'],
                    $val['project_type'],
                    $val['jop_num'],
                    $val['entr_num'],
                    $val['legal_name'],
                    $val['legal_id_card'],
                    $val['legal_school'],
                    $val['legal_time'],
                    $val['legal_education'],
                    $val['legal_phone'],
                    $val['is_graduate_school'],
//                    $item['team_name'],
//                    $item['team_school'],
//                    $item['team_time'],
//                    $item['team_education'],
//                    $item['team_phone'],
//                    $item['residence_time'],
//                    $item['start_time'],
//                    $item['end_time'],
//                    $item['room_number'],
//                    $item['site_area'],
//                    $item['month_turnover'],
//                    $item['year_turnover'],
//                    $item['month_taxes'],
//                    $item['year_taxes'],
//                    $item['resource_docking'],
//                    $item['name_investor'],
//                    $item['financing_amount'],
//                    $item['gov_amount'],
//                    $item['project_awards'],
//                    $item['change_record'],
//                    $item['back_time'],
//                    $item['reason'],
//                    $item['industry_type'],
//                    $item['products_services'],
//                    $item['required_pro_serv'],
//                    $item['financing_needs'],
//                    $item['entrepr'],
//                    $item['products_services'],
//                    $item['required_pro_serv'],
                ];
            }
            PHPExcelService::setExcelHeader([
                '序号','项目编号','园区名称','是否入孵项目','公司名称','组织机构代码','项目简介',
                '是否工商注册','项目类别','就业人数','创业人数','法人姓名','法人身份证号','毕业院校',
                '法人毕业时间','法人学历','法人电话','法人是否毕业5年获在校',
//                '团队成员姓名','团队成员毕业院校',
//                '团队成员毕业时间','团队成员学历','团队成员电话','入驻园区时间','入园开始时间','入园结束时间',
//                '入驻房间编号','入驻场地面积','本月营业额(万元)','本年累计营业额(万元)','本月纳税额(万元)',
//                '本年累计纳税额(万元)','有效资源对接情况','出资单位名称','融资金额','政府扶持资金名称及金额(万元)',
//                '项目获奖及专利情况','信息变更记录','退园时间','退园原因','行业类型','项目提供的产品或服务',
//                '项目需要的产品或服务','是否有融资需求','是否需要创业辅导培训(财务、法务等)'
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
        $model = new ExamineModel();
        $model = $model->alias('e')->join('StoreCategory s','e.category_id=s.id','LEFT');

        if(!empty($where)){
            $model=$model->group('e.id');

            if (isset($where['month']) && $where['month'] != ''){
                // 查询上月月报,最新月报数据
                $curMonth = date('Y-m',strtotime("-1 month", time()));
                $month = date('Y-m',strtotime(date('Y-'.$where['month'],time())));
                if ($curMonth == $month){
                    $projectNum = self::where('month',$curMonth)->column('project_num');
                    $model= $model->where('project_num','not in',$projectNum);
                }else{
                    // 不是当前月
                    $projectNum = self::where('month',$month)->column('project_num');
                    if (!$projectNum){
                        $model= $model->where('project_num','not in',self::getProjectNum());
                    }
                    $model= $model->where('project_num','not in',$projectNum);
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
     * 获取所有项目编号
     * @return array
     */
    public static function getProjectNum()
    {
        return Db::name('examine')->column('project_num');
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
    public static function getUniqueness($project_num,$cate_id){
        return self::where(['category_id'=>$cate_id,'project_num'=>$project_num])->value('id');
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

}