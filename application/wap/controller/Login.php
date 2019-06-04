<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2018/01/15
 */

namespace app\wap\controller;


use app\wap\model\user\User;
use app\wap\model\user\WechatUser;
use service\UtilService;
use think\Cookie;
use think\Request;
use think\Session;
use think\Url;
use think\Db;
use think\Controller;

class Login extends WapBasic
// class Login extends Controller
{
    public function index($ref = '')
    {
        Cookie::set('is_bg',1);
        $ref && $ref=htmlspecialchars_decode(base64_decode($ref));
        // if(UtilService::isWechatBrowser()){
        //     $this->_logout();
        //     $openid = $this->oauth();
        //     Cookie::delete('_oen');
        //     exit($this->redirect(empty($ref) ? Url::build('Index/index') : $ref));
        // }
        $this->assign('ref',$ref);
        return $this->fetch();
    }

    public function check(Request $request)
    {
        list($account,$pwd,$ref) = UtilService::postMore(['account','pwd','ref'],$request,true);
        if(!$account) return $this->failed('请输入登陆手机号');
        if(!preg_match("/^1[34578]\d{9}$/",$account)) return $this->failed('登陆手机号格式有误');
        if(!$pwd) return $this->failed('请输入登录密码');
        if(!User::be(['account'=>$account])) return $this->failed('登陆手机号不存在!');
        $userInfo = User::where('account',$account)->find();
        $errorInfo = Session::get('login_error_info','wap')?:['num'=>0];
        $now = time();
        if($errorInfo['num'] > 5 && $errorInfo['time'] < ($now - 900))
            return $this->failed('错误次数过多,请稍候再试!');
        if($userInfo['pwd'] != md5($pwd)){
            Session::set('login_error_info',['num'=>$errorInfo['num']+1,'time'=>$now],'wap');
            return $this->failed('手机号或密码输入错误!');
        }
        if(!$userInfo['status']) return $this->failed('手机号已被锁定,无法登陆!');
        if($userInfo['is_del']) return $this->failed('无效的手机号,无法登陆!');
        $this->_logout();
        Session::set('loginUid',$userInfo['uid'],'wap');
        $userInfo['last_time'] = time();
        $userInfo['last_ip'] = $request->ip();
        $userInfo->save();
        Session::delete('login_error_info','wap');
        Cookie::set('is_login',1);
        exit($this->redirect(empty($ref) ? Url::build('Index/index') : $ref));
    }

    public function logout()
    {
        $this->_logout();
        $this->successful('退出登陆成功',Url::build('Index/index'));
    }

    private function _logout()
    {
        Session::clear('wap');
        Cookie::delete('is_login');
    }

    public function register($ref = '')
    {
        Cookie::set('is_bg',1);
        $ref && $ref=htmlspecialchars_decode(base64_decode($ref));
        $this->assign('ref',$ref);
        return $this->fetch();
    }

    public function checkReg(Request $request)
    {
        list($account,$pwd,$ref) = UtilService::postMore(['account','pwd','ref'],$request,true);
        if(!$account) return $this->failed('请输入注册手机号');
        if(!preg_match("/^1[34578]\d{9}$/",$account)) return $this->failed('注册手机号格式有误');
        if(!$pwd) return $this->failed('请输入注册密码');
        if(User::be(['account'=>$account])) return $this->failed('手机号已存在!');

        // pc用户
        $data['account'] = $account;
        $data['pwd'] = md5($pwd);
        $data['real_name'] = $account;
        $data['phone'] = $account;
        $data['add_time'] = time();
        $id = Db::name('system_admin')->insertGetId($data);

        // wap用户
        $data['admin_id'] = $id;
        $data['account'] = $account;
        $data['pwd'] = md5($pwd);
        $data['nickname'] = $account;
        $data['phone'] = $account;
        $data['add_time'] = time();
        $data['add_ip'] = $request->ip();
        $uid = Db::name('user')->insertGetId($data);
        
        $this->_logout();
        Session::set('loginUid',$uid,'wap');
        Session::delete('login_error_info','wap');
        Cookie::set('is_login',1);
        exit($this->redirect(empty($ref) ? Url::build('Index/index') : $ref));
    }

}