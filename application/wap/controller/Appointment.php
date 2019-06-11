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

class Appointment extends AuthController
{

    // 预约会议
    public function index($xt=1)
    {
        $day = date('w');
        switch($day){
            case '1':
                $day1 = '星期三';$day2 = '星期四';$day3 = '星期五';
            break;
            case '2':
                $day1 = '星期四';$day2 = '星期五';$day3 = '星期六';
            break;
            case '3':
                $day1 = '星期五';$day2 = '星期六';$day3 = '星期日';
            break;
            case '4':
                $day1 = '星期六';$day2 = '星期日';$day3 = '星期一';
            break;
            case '5':
                $day1 = '星期日';$day2 = '星期一';$day3 = '星期二';
            break;
            case '6':
                $day1 = '星期一';$day2 = '星期二';$day3 = '星期三';
            break;
            default:
            $day1 = '星期二';$day2 = '星期三';$day3 = '星期四';
        }

        switch($xt){
            case 1:
                $time = strtotime(date('Y-m-d'));
            break;
            case 2:
                $time = strtotime(date('Y-m-d')) + 86400;
            break;
            case 3:
                $time = strtotime(date('Y-m-d')) + 86400*2;
            break;
            case 4:
                $time = strtotime(date('Y-m-d')) + 86400*3;
            break;
            default:
                $time = strtotime(date('Y-m-d')) + 86400*4;
        }
        $list = Db::name('appointment')->where('create_time','eq',$time)->find();
        // dump($time);
        // halt(Db::name('appointment')->getLastSql());
        $this->assign(compact('xt','day1','day2','day3','list'));
        return $this->fetch();
    }

    // 预约
    public function checkTime()
    {
        if($this->request->isAjax()){
            $data = Request::instance()->post();
            $xt = isset($data['xt']) ? $data['xt'] : 1;
            $ids = isset($data['ids']) ? $data['ids'] : [];
            if(empty($xt) || count($ids)==0){
                return Json::fail('预约失败');
            }
            $uid = User::getActiveUid();
            switch($xt){
                case 1:
                    $time = strtotime(date('Y-m-d'));
                break;
                case 2:
                    $time = strtotime(date('Y-m-d')) + 86400;
                break;
                case 3:
                    $time = strtotime(date('Y-m-d')) + 86400*2;
                break;
                case 4:
                    $time = strtotime(date('Y-m-d')) + 86400*3;
                break;
                default:
                    $time = strtotime(date('Y-m-d')) + 86400*4;
            }
            $list = Db::name('appointment')->where('create_time','eq',$time)->find();
            $arr=[];
            if($list){
                foreach($ids as $v){
                    if($list['time'.$v]){
                        return Json::successful('勾选里存在被占用的时间段，请重新选择时间段');
                    }
                    $arr['time'.$v] = $uid;
                }
                Db::name('appointment')->where('id',$list['id'])->update($arr);
            }else{
                foreach($ids as $v){
                    $arr['time'.$v] = $uid;
                }
                $arr['create_time'] = $time;
                Db::name('appointment')->insert($arr);
            }
            return Json::successful('预约成功');
        }
        return Json::fail('预约失败');
    }



}