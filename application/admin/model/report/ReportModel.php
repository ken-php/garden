<?php

namespace app\admin\model\report;

use service\PHPExcelService;
use think\Db;
use traits\ModelTrait;
use basic\ModelBasic;

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
        $model=self::getModelObject($where)->field('e.*,s.cate_name');
        if($where['excel']==0) $model=$model->page((int)$where['page'],(int)$where['limit']);
        $data = ($data=$model->select()) && count($data) ? $data->toArray():[];
        foreach ($data as &$item){
            $item['is_hatched'] = $item['is_hatched'] == 1 ? '已入孵' : '待入孵';
            $item['is_register'] = $item['is_register'] == 1 ? '已注册' : '未注册';
            $item['is_graduate_school'] = $item['is_graduate_school'] == 1 ? '是' : '否';
        }
        if($where['excel']==1){
            $export = [];
            foreach ($data as $index=>$item){
                $export[] = [
                    $item['project_name'],
                    $item['is_register'],
                    $item['address'],
                    $item['is_small_business'],
                    $item['is_high_tech'],
                    $item['is_listed']
                ];
            }
            PHPExcelService::setExcelHeader(['企业或项目名','是否注册企业','注册地址','是否是科技型中小企业','是否是高新技术企业','是否上市挂牌'])
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
        $model=$model->alias('e')->join('StoreCategory s','e.category_id=s.id','LEFT');
        if(!empty($where)){
            $model=$model->group('e.id');
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