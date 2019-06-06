<?php

namespace app\admin\model\examine;

use service\PHPExcelService;
use think\Db;
use traits\ModelTrait;
use basic\ModelBasic;

/**
 * 审核管理 model
 * Class ExamineModel
 * @package app\admin\model\examine
 */
class ExamineModel extends ModelBasic
{
    protected $name = 'examine';
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
        $data=($data=$model->select()) && count($data) ? $data->toArray():[];
        foreach ($data as &$item){
            $item['is_hatched'] = $item['is_hatched'] == 1 ? '是' : '否';
        }
        if($where['excel']==1){
            $export = [];
            foreach ($data as $index=>$item){
                $export[] = [
                    $item['project_num'],
                    $item['cate_name'],
                    $item['is_hatched'],
                    $item['corporate_name'],
                    $item['create_time'],
                    $item['is_audited'] == 1 ? '已审核' : '未审核'
                ];
            }
            PHPExcelService::setExcelHeader(['项目编号','所属园区','是否入孵','公司名称','申请时间','状态'])
                ->setExcelTile('审核导出','审核信息'.time(),' 生成时间：'.date('Y-m-d H:i:s',time()))
                ->setExcelContent($export)
                ->ExcelSave();
        }
        $count=self::getModelObject($where)->count();
        return compact('count','data');
    }

    /**
     * 获取连表MOdel
     * @param $model
     * @return object
     */
    public static function getModelObject($where=[]){
        $model=new self();
        $model=$model->alias('e')->join('StoreCategory s','e.category_id=s.id','LEFT');
        if(!empty($where)){
            $model=$model->group('e.id');
            if(isset($where['type']) && $where['type']!='' && ($data=self::setData($where['type']))){
                $model = $model->where($data);
            }
            if(isset($where['search_name']) && $where['search_name']!=''){
                if($where['search_name']=='是' || $where['search_name']=='否'){
                    $is_hatched = $where['search_name']=='是' ? 1 : 0;
                    $model = $model->where('e.is_hatched',$is_hatched);
                }else{
                    $model = $model->where('e.project_num|s.cate_name|e.corporate_name|e.create_time','LIKE',"%$where[search_name]%");
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
            case 4:
                $data = ['e.is_audited'=>3,'e.is_del'=>0];
                break;
        };
        return isset($data) ? $data: [];
    }

    /**
     * 唯一性
     */
    public static function getUniqueness($project_num,$cate_id){
        return self::where(['category_id'=>$cate_id,'project_num'=>$project_num])->value('id');
    }

    /**
     * 获取上月未提交月报公司数
     * @param $where
     * @param $scienceProjectNum
     * @return int|string
     * @throws \think\Exception
     * @author ken
     * @date 2019/6/5
     */
    public static function getUnReportCount($where , $scienceProjectNum)
    {
       return self::where($where)->whereNotIn('project_num',$scienceProjectNum)->count();
    }

    /**
     * 获取uid
     * @param $id
     * @return mixed
     * @author ken
     * @date 2019/6/5
     */
    public static function getUidByExamineId($id)
    {
        return Db::name('project_user')->where('project_id',$id)->value('uid');
    }

    /**
     * 新增一条审核失败原因
     * @param $data
     * @return int|string
     * @author ken
     * @date 2019/6/5
     */
    public static function addNotice($data)
    {
        return Db::name('notice_user')->insert($data);
    }
}