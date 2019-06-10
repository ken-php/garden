<?php
namespace app\wap\validate;

use think\Validate;

class ReportList extends Validate
{
    protected $rule =   [
        'project_num'   => 'require',
        'category_id' => 'require|number',
        'corporate_name'  => 'require|max:35',
        'org_code'  => 'max:35',
        'project_type'=>'max:60',
        'jop_num' => 'number|egt:0',
        'entr_num' => 'number|egt:0',
        'legal_name'=>'max:25',
        'legal_id_card'=>'is_idcard',
        'legal_education'=>'education:1',
        'legal_phone'=>'mobile:1',
        'team_education'=>'education:2',
        'team_phone'=>'mobile:2',
        'turnover'=>'float|egt:0',
        'taxes'=>'float|egt:0',
        'area'=>'number|egt:0',
        'enterprises_num'=>'number|egt:0',
        'interns_num'=>'number|egt:0',
        'add_jop_num'=>'number|egt:0',
        'add_entr_num'=>'number|egt:0',
        'funds'=>'float|egt:0',
        'financial'=>'float|egt:0',
        'activity_num'=>'number|egt:0',
        'investment_amount'=>'float|egt:0',
        'intellectual_num'=>'number|egt:0',
        'has_intel_num'=>'number|egt:0',
        'patents_num'=>'number|egt:0',
        're_has_intel_num'=>'number|egt:0',
        're_patents_num'=>'number|egt:0',
        'achievement_num'=>'number|egt:0'        
    ];
    
    protected $message  =   [
        'project_num.require' => '请输入项目编号',
        'category_id.require'  => '请选择所属园区',
        'category_id.number'   => '所属园区必须是数字',
        'corporate_name.require' => '请输入公司名称',
        'corporate_name.max'     => '公司名称最多不能超过35个字符',
        'org_code.require' => '请输入组织机构代码',
        'org_code.max'     => '组织机构代码最多不能超过35个字符',
        'project_type.max'     => '项目类别最多不能超过60个字符',
        'jop_num.number'   => '就业人数必须是正整数',
        'jop_num.egt'   => '就业人数必须是正整数',
        'entr_num.number'   => '创业人数必须是正整数',
        'entr_num.egt'   => '创业人数必须是正整数',
        'legal_name.max'     => '法人姓名最多不能超过25个字符',
        'turnover.float' => '营业额必须是数字',
        'turnover.egt' => '营业额必须是正数',
        'taxes.float' => '纳税额必须是数字',
        'taxes.egt' => '纳税额必须是正数',
        'area.number' => '场地面积必须是正整数',
        'area.egt' => '场地面积必须是正整数',
        'enterprises_num.number' => '新增与合作大学创办企业数必须是正整数',
        'enterprises_num.egt' => '新增与合作大学创办企业数必须是正整数',
        'interns_num.number' => '新增接纳大学生/研究生实习人员数必须是正整数',
        'interns_num.egt' => '新增接纳大学生/研究生实习人员数必须是正整数',
        'add_jop_num.number'   => '新增从业人员数（团队人数）必须是正整数',
        'add_jop_num.egt'   => '新增从业人员数（团队人数）必须是正整数',
        'add_entr_num.number'   => '新增应届毕业生就业人员数必须是正整数',
        'add_entr_num.egt'   => '新增应届毕业生就业人员数必须是正整数',
        'funds.float' => '研发经费投入必须是数字',
        'funds.egt' => '研发经费投入必须是正数',
        'financial.float' => '享受的财政支持金额必须是数字',
        'financial.egt' => '享受的财政支持金额必须是正数',
        'activity_num.number'   => '参加的投融资对接活动次数必须是正整数',
        'activity_num.egt'   => '参加的投融资对接活动次数必须是正整数',
        'investment_amount.float' => '获得投资金额必须是数字',
        'investment_amount.egt' => '获得投资金额必须是正数',
        'intellectual_num.number'   => '知识产权申请数必须是正整数',
        'intellectual_num.egt'   => '知识产权申请数必须是正整数',
        'has_intel_num.number'   => '新增拥有有效知识产权数(已注册公司的填此项)必须是正整数',
        'has_intel_num.egt'   => '新增拥有有效知识产权数(已注册公司的填此项)必须是正整数',
        'patents_num.number'   => '新增申请发明专利数量(已注册公司的填此项)必须是正整数',
        'patents_num.egt'   => '新增申请发明专利数量(已注册公司的填此项)必须是正整数',
        're_has_intel_num.number'   => '新增拥有有效知识产权数(未注册公司的填此项)必须是正整数',
        're_has_intel_num.egt'   => '新增拥有有效知识产权数(未注册公司的填此项)必须是正整数',
        're_patents_num.number'   => '新增申请发明专利数量(未注册公司的填此项)必须是正整数',
        're_patents_num.egt'   => '新增申请发明专利数量(未注册公司的填此项)必须是正整数',
        'achievement_num.number'   => '新增科技成果转化数必须是正整数',
        'achievement_num.egt'   => '新增科技成果转化数必须是正整数'        
    ];

    protected function is_idcard($value,$rule,$data) {
        $chars = "/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}(\d|x|X)$/";
        if (preg_match($chars, $data['legal_id_card'])) {
            return true;
        } else {
            return '法人信息 - 身份证格式有误';
        }
    }

    protected function education($value,$rule,$data) {
        $arr = ['','无','小学','初中','初中中专','高中','高中中专','中专','大专','本科','研究生','硕士','博士'];
        if($rule==1){
            if (in_array($data['legal_education'],$arr)) {
                return true;
            } else {
                return "法人信息 - 无效的学历<br> 请参考['小学','初中','初中中专','高中','高中中专','中专','大专','本科','研究生','硕士','博士']";
            }
        }else{
            if (in_array($data['team_education'],$arr)) {
                return true;
            } else {
                return "团队信息 - 无效的学历<br> 请参考['小学','初中','初中中专','高中','高中中专','中专','大专','本科','研究生','硕士','博士']";
            }
        }
    }

    protected function mobile($value,$rule,$data) {
        $chars = "/^1[345789]\d{9}$/";
        if($rule==1){
            if (preg_match($chars, $data['legal_phone'])) {
                return true;
            } else {
                return '法人信息 - 手机格式有误';
            }
        }else{
            if (preg_match($chars, $data['team_phone'])) {
                return true;
            } else {
                return '团队信息 - 手机格式有误';
            }
        }
    }

}




?>