<?php
namespace app\admin\controller\store;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\JsonService;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use app\admin\model\store\StoreCategory as CategoryModel;
use think\Url;
use app\admin\model\system\SystemAttachment;
use think\Db;

/**
 * 产品分类控制器
 * Class StoreCategory
 * @package app\admin\controller\system
 */
class StoreCategory extends AuthController
{

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $this->assign('type',$this->request->get('type',1));
        $this->assign('pid',$this->request->get('pid',0));
        $this->assign('cate',CategoryModel::getTierList());
        return $this->fetch();
    }
    /*
     *  异步获取分类列表
     *  @return json
     */
    public function category_list(){
        $where = Util::getMore([
            ['is_show','1'],
            ['pid',$this->request->param('pid','')],
            ['cate_name',''],
            ['page',1],
            ['limit',20],
            ['order','']
        ]);
        return JsonService::successlayui(CategoryModel::CategoryList($where));
    }
    /**
     * 设置单个产品上架|下架
     *
     * @return json
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && JsonService::fail('缺少参数');
        $res=CategoryModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return JsonService::successful($is_show==1 ? '显示成功':'隐藏成功');
        }else{
            return JsonService::fail($is_show==1 ? '显示失败':'隐藏失败');
        }
    }
    /**
     * 快速编辑
     *
     * @return json
     */
    public function set_category($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && JsonService::fail('缺少参数');
        if(CategoryModel::where(['id'=>$id])->update([$field=>$value]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create($type=1,$pid=0)
    {
        if($type==1){
            $add_name = '园区';
            $field = [
                Form::input('cate_name','园区名称'),
                Form::number('sort','排序'),
                Form::hidden('type','1')
            ];
        }else{
            $add_name = '楼栋';
            $field = [
                Form::select('pid','归属园区',$pid)->setOptions(function(){
                    $list = CategoryModel::getTierList();
                    // $menus = [['value'=>0,'label'=>'全部']];
                    $menus = [];
                    foreach ($list as $menu){
                        if($menu['pid'] == 0){
                            $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['cate_name']];
                        }
                    }
                    return $menus;
                })->filterable(1)->disabled(1),
                Form::input('cate_name','楼栋名称'),
                // Form::frameImageOne('pic','楼栋图标',Url::build('admin/widget.images/index',array('fodder'=>'pic')))->icon('image'),
                Form::number('sort','排序'),
                // Form::radio('is_show','状态',1)->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])
                Form::hidden('type','2')
            ];
        }
        $form = Form::make_post_form('添加'.$add_name,$field,Url::build('save'),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }


    /**
     * 上传图片
     * @return \think\response\Json
     */
    public function upload()
    {
        $res = Upload::image('file','store/category'.date('Ymd'));
        $thumbPath = Upload::thumb($res->dir);
        //产品图片上传记录
        $fileInfo = $res->fileInfo->getinfo();
        SystemAttachment::attachmentAdd($res->fileInfo->getSaveName(),$fileInfo['size'],$fileInfo['type'],$res->dir,$thumbPath,1);

        if($res->status == 200)
            return Json::successful('图片上传成功!',['name'=>$res->fileInfo->getSaveName(),'url'=>Upload::pathToUrl($thumbPath)]);
        else
            return Json::fail($res->error);
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = Util::postMore([
            ['pid',0],
            'cate_name',
            ['pic',[]],
            'sort',
            ['is_show',1]
        ],$request);
        if($data['pid'] == '' && $request->post('type') == 2) return Json::fail('请选择父类');
        $name = '园区';
        if($request->post('type') == 2) $name = '楼栋';
        if(!$data['cate_name']) return Json::fail('请输入'.$name.'名称');
        // if(count($data['pic'])<1) return Json::fail('请上传分类图标');
        if($data['sort'] <0 ) $data['sort'] = 0;
        // $data['pic'] = $data['pic'][0];
        // 验证园区名称唯一性
        $only = Db::name('store_category')->where(['cate_name'=>$data['cate_name']])->count();
        if($only) return Json::fail('园区名称已存在');
        $data['add_time'] = time();
        CategoryModel::set($data);
        return Json::successful('添加'.$name.'成功!');
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $c = CategoryModel::get($id);
        if(!$c) return Json::fail('数据不存在!');
        if($c['pid'] == 0){
            $edit_name = '园区';
            $field = [
                Form::input('cate_name','园区名称',$c->getData('cate_name')),
                Form::number('sort','排序',$c->getData('sort')),
                Form::hidden('type','1')
            ];
        }else{
            $edit_name = '楼栋';
            $field = [
                Form::select('pid','归属园区',(string)$c->getData('pid'))->setOptions(function() use($id){
                    $list = CategoryModel::getTierList(CategoryModel::where('id','<>',$id));
                    $menus = [];
                    foreach ($list as $menu){
                        if($menu['pid'] == 0){
                            $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['cate_name']];
                        }
                    }
                    return $menus;
                })->filterable(1)->disabled(1),
                Form::input('cate_name','楼栋名称',$c->getData('cate_name')),
                // Form::frameImageOne('pic','分类图标',Url::build('admin/widget.images/index',array('fodder'=>'pic')),$c->getData('pic'))->icon('image'),
                Form::number('sort','排序',$c->getData('sort')),
                // Form::radio('is_show','状态',$c->getData('is_show'))->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])
                Form::hidden('type','2')
            ];
    
        }        
        $form = Form::make_post_form('编辑'.$edit_name,$field,Url::build('update',array('id'=>$id)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            'pid',
            'cate_name',
            // ['pic',[]],
            'sort',
            // 'is_show'
        ],$request);
        if($data['pid'] == '' && $request->post('type') == 2) return Json::fail('请选择父类');
        $name = '园区';
        if($request->post('type') == 2) $name = '楼栋';
        if(!$data['cate_name']) return Json::fail('请输入'.$name.'名称');
        // if(count($data['pic'])<1) return Json::fail('请上传图标');
        if($data['sort'] <0 ) $data['sort'] = 0;
        // $data['pic'] = $data['pic'][0];
        $only = Db::name('store_category')->where(['cate_name'=>$data['cate_name'],'id'=>['neq',$id]])->count();
        if($only) return Json::fail('园区名称已存在');
        CategoryModel::edit($data,$id);
        return Json::successful('修改'.$name.'成功!');
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $num = CategoryModel::delCategory($id);
        switch($num){
            case 1:
                return Json::fail(CategoryModel::getErrorInfo('园区包含楼栋,不允许删除!'));
            break;
            case 2:
                return Json::fail(CategoryModel::getErrorInfo('楼栋包含房间,不允许删除!'));
            break;
            case 3:
                return Json::successful('删除成功!');
            break;
            case 4:
                return Json::fail(CategoryModel::getErrorInfo('删除失败!'));
            break;
        }
    }
}