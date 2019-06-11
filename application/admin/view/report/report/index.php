{extend name="public/container"}
{block name="content"}
<div class="layui-fluid" style="background: #fff;margin-top: -10px;">
    <div class="layui-tab layui-tab-brief" lay-filter="tab">
        <ul class="layui-tab-title">
            <li lay-id="list" {eq name='type' value='1'}class="layui-this" {/eq} >
            <a href="{eq name='type' value='1'}javascript:;{else}{:Url('index',['type'=>1])}{/eq}">历史月报列表({$reportNum})</a>
            </li>

            <li lay-id="list" {eq name='type' value='4'}class="layui-this" {/eq} >
            <a href="{eq name='type' value='4'}javascript:;{else}{:Url('index',['type'=>4])}{/eq}">上月已提交月报({$submittedReportNum})</a>
            </li>

            <li lay-id="list" {eq name='type' value='2'}class="layui-this" {/eq} >
            <a href="{eq name='type' value='2'}javascript:;{else}{:Url('index',['type'=>2])}{/eq}">科技园上月待提交月报({$scienceReportNotNum})</a>
            </li>

            <li lay-id="list" {eq name='type' value='3'}class="layui-this" {/eq} >
            <a href="{eq name='type' value='3'}javascript:;{else}{:Url('index',['type'=>3])}{/eq}">众创空间上月待提交月报({$makerProjectNotNum})</a>
            </li>
        </ul>
    </div>
    <div class="layui-row layui-col-space15"  id="app">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body">
                    <form class="layui-form layui-form-pane" action="">
                        <div class="layui-form-item">
                            <div class="layui-inline">
                                <label class="layui-form-label">归属园区:</label>
                                <div class="layui-input-block">
                                    <select name="cate_id">
                                        <option value=" ">全部</option>
                                        {volist name='cate' id='vo'}
                                        <option value="{$vo['id']}">{$vo['cate_name']}</option>
                                        {/volist}
                                    </select>
                                </div>
                            </div>
<!--                            <div class="layui-inline">-->
<!--                                <label class="layui-form-label">时间范围</label>-->
<!--                                <div class="layui-input-inline" style="width: 200px;">-->
<!--                                    <input type="text" name="start_time" placeholder="开始时间" id="start_time" class="layui-input">-->
<!--                                </div>-->
<!--                                <div class="layui-form-mid">-</div>-->
<!--                                <div class="layui-input-inline" style="width: 200px;">-->
<!--                                    <input type="text" name="end_time" placeholder="结束时间" id="end_time" class="layui-input">-->
<!--                                </div>-->
<!--                            </div>-->


                            <div class="layui-inline">
                                <label class="layui-form-label">上报月份</label>
                                <div class="layui-input-block">
<!--                                    <input type="text" name="month" class="layui-input" placeholder="请输入">-->
                                    <select name="month" lay-verify="month">
                                        <option value="">(默认上月) -全部</option>
                                        <option value="01">1月份</option>
                                        <option value="02">2月份</option>
                                        <option value="03">3月份</option>
                                        <option value="04">4月份</option>
                                        <option value="05">5月份</option>
                                        <option value="06">6月份</option>
                                        <option value="07">7月份</option>
                                        <option value="08">8月份</option>
                                        <option value="09">9月份</option>
                                        <option value="10">10月份</option>
                                        <option value="11">11月份</option>
                                        <option value="12">12月份</option>
                                    </select>
                                </div>
                            </div>
                            <div class="layui-inline">
                                <label class="layui-form-label">筛选值</label>
                                <div class="layui-input-block">
                                    <input type="text" name="search_name" class="layui-input" placeholder="请输入">
                                </div>
                            </div>
<!--                            <div class="layui-inline">-->
<!--                                <label class="layui-form-label">查看项目</label>-->
<!--                                <div class="layui-input-block">-->
<!--                                    <!--                                    <input type="text" name="month" class="layui-input" placeholder="请输入">-->
<!--                                    <select name="report" lay-verify="report">-->
<!--                                        <option value="">全部</option>-->
<!--                                        <option value="1">上月所有项目</option>-->
<!--                                        <option value="2">上月已提交月报项目</option>-->
<!--                                        <option value="3">上月未提交月报项目</option>-->
<!--                                    </select>-->
<!--                                </div>-->
<!--                            </div>-->
                            <div class="layui-inline">
                                <div class="layui-input-inline">
                                    <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit="search" lay-filter="search">
                                        <i class="layui-icon layui-icon-search"></i>搜索</button>
                                    <button class="layui-btn layui-btn-sm export"  lay-submit="export" lay-filter="export" >
                                        <i class="fa fa-floppy-o" style="margin-right: 3px;"></i>导出</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--列表-->
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body">
                    <div class="layui-btn-container">
                        <button class="layui-btn layui-btn-sm" onclick="$eb.createModalFrame(this.innerText,'{:Url('create')}',{h:700,w:1100})">添加月报</button>
                    </div>

                    <!--数据表格-->
                    <table class="layui-hide" id="List" lay-filter="List" ></table>


                    <!--操作-->
                    <script type="text/html" id="act">
                        {eq name="type" value="2"}
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-normal" onclick="$eb.createModalFrame('{{d.project_name}}-编辑','{:Url('edit')}?id={{d.id}}',{h:700,w:1100})">
                            通知
                        </button>
                        {else/}
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-normal" onclick="$eb.createModalFrame('{{d.project_name}}-编辑','{:Url('edit')}?id={{d.id}}',{h:700,w:1100})">
                            编辑
                        </button>
                        {/eq}

                    </script>
                </div>
            </div>
        </div>

    </div>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
<!--<script src="{__ADMIN_PATH}js/layui.js"></script>-->
<script>
    // 时间选择框
    // layList.date({elem:'#start_time',theme:'#393D49',type:'datetime'});
    // layList.date({elem:'#end_time',theme:'#393D49',type:'datetime'});

    var type=<?=$type?>;
    //实例化form
    layList.form.render();

    //加载列表
    layList.tableList('List',"{:Url('product_ist',['type'=>$type])}",function (){

        var join=new Array();
        switch (parseInt(type)){
            case 1:case 4:
                join=[
                    {field: 'id', title: 'ID', sort: true, event: 'id', width: 60,fixed: 'left'},  // id
                    {field: 'sort', title: '排序',edit:'sort',width:60,fixed: 'left'},  // 排序
                    {field: 'corporate_name', title: '企业或项目名', width: 280,fixed: 'left'},  // 企业或项目名
                    {field: 'cate_name', title: '归属园区', width: 100},  // 归属园区
                    {field: 'is_register', title: '是否注册企业', width: 110},  // 是否注册企业
                    {field: 'address', title: '注册地址', width: 620},  // 注册地址
                    {field: 'area', title: ' 场地面积', width: 90},  // 场地面积
                    {field: 'is_new_teams', title: '是否新增创客/团队', width: 140},  // 是否新增创客/团队
                    {field: 'is_science', title: '是否科技型中小企业', width: 150},  // 是否科技型中小企业
                    {field: 'is_high_tech', title: '是否高新技术企业', width: 140},  // 是否高新技术企业
                    {field: 'enterprises_num', title: '与合作大学创办企业数', width: 160},  // 与合作大学创办企业数
                    {field: 'interns_num', title: '接纳大学生/研究生实习人员数', width: 210},
                    {field: 'is_sale', title: '是否上市挂牌', width: 110},  // 是否上市挂牌
                    {field: 'add_jop_num', title: '新增从业人员', width: 110},  // 新增从业人员
                    {field: 'add_entr_num', title: '新增应届毕业生就业人员数', width: 190},  // 新增应届毕业生就业人员数
                    {field: 'turnover', title: '当前月营业额(千元)', width: 150},  // 当前月营业额
                    {field: 'taxes', title: '当前月纳税额(千元)', width: 150},  // 当前月纳税额
                    {field: 'funds', title: '研发经费投入(千元)', width: 150},  // 研发经费投入
                    {field: 'financial', title: '享受财政支持金额', width: 140},  // 享受财政支持金额
                    {field: 'activity_num', title: '参加的投融资对接活动次数', width: 190},  // 参加的投融资对接活动次数
                    {field: 'is_investment', title: '是否获得投资', width: 110},  // 是否获得投资
                    {field: 'investment_amount', title: '获得投资金额(千元)', width: 150},  // 获得投资金额
                    {field: 'intellectual_num', title: '知识产权申请数', width: 140},  // 知识产权申请数
                    {field: 'has_intel_num', title: '拥有有效知识产权数(已注册公司)', width: 230},  // 拥有有效知识产权数
                    {field: 'patents_num', title: '申请发明专利数量(已注册公司)', width: 220},  // 申请发明专利数量
                    {field: 're_has_intel_num', title: '拥有有效知识产权数(未注册公司)', width: 230},  // 拥有有效知识产权数
                    {field: 're_patents_num', title: '申请发明专利数量(未注册公司)', width: 220},  // 申请发明专利数量
                    {field: 'achievement_num', title: '科技成果转化数', width: 130},  //科技成果转化数

                    {field: 'month', title: '填报时间', width: 100},
                    {field: 'right', title: '操作', align: 'center', toolbar: '#act', width: 80},
                ];
                break;
            case 2: case 3:
                join=[
                    {field: 'id', title: 'ID', sort: true, event: 'id', width: 60},
                    {field: 'project_num', title: '项目编号', width: 90},
                    {field: 'cate_name', title: '归属园区', width: 110},
                    {field: 'is_hatched', title: '是否入孵', width: 90},
                    {field: 'corporate_name', title: '企业或项目名', width: 240},
                    {field: 'org_code', title: '组织机构代码', width: 200},
                    {field: 'project_synopsis', title: '项目简介', width: 560},
                    {field: 'is_register', title: '是否注册企业', width: 110},
                    {field: 'project_type', title: '项目类别', width: 550},
                    {field: 'jop_num', title: '就业人数', width: 90},
                    {field: 'entr_num', title: '创业人数', width: 90},
                    {field: 'legal_name', title: '法人姓名', width: 120},
                    {field: 'legal_id_card', title: '法人身份证', width: 170},
                    {field: 'legal_school', title: '毕业院校', width: 160},
                    {field: 'legal_time', title: '法人毕业时间', width: 110},
                    {field: 'legal_education', title: '法人学历', width: 90},
                    {field: 'legal_phone', title: '法人电话', width: 120},
                    {field: 'is_graduate_school', title: '法人是否毕业或在校5年', width: 170},
                    {field: 'team_name', title: '团队成员姓名', width: 110},
                    {field: 'team_school', title: '团队成员毕业院校', width: 140},
                    {field: 'team_time', title: '团队成员毕业时间', width: 140},
                    {field: 'team_education', title: '团队成员学历', width: 120},
                    {field: 'team_phone', title: '团队成员电话', width: 120},
                    {field: 'residence_time', title: '入住园区时间', width: 120},
                    {field: 'start_time', title: '入园协议起时间', width: 130},
                    {field: 'end_time', title: '入园协议止时间', width: 130},
                    {field: 'room_number', title: ' 入驻房间编号', width: 120},
                    {field: 'site_area', title: ' 入驻场地面积', width: 120},

                    {field: 'right', title: '操作', align: 'center', toolbar: '#act', width: 80},
                ];
                break;
        }
        return join;
    })

    //excel下载
    layList.search('export',function(where){
        location.href=layList.U({c:'report.report',a:'product_ist',q:{
                cate_id:where.cate_id,
                month:where.month,
                report:where.report,
                search_name:where.search_name,
                type:where.type,
                excel:1
            }});
    })

    //下拉框
    $(document).click(function (e) {
        $('.layui-nav-child').hide();
    })
    function dropdown(that){
        var oEvent = arguments.callee.caller.arguments[0] || event;
        oEvent.stopPropagation();
        var offset = $(that).offset();
        var top=offset.top-$(window).scrollTop();
        var index = $(that).parents('tr').data('index');
        $('.layui-nav-child').each(function (key) {
            if (key != index) {
                $(this).hide();
            }
        })
        if($(document).height() < top+$(that).next('ul').height()){
            $(that).next('ul').css({
                'padding': 10,
                'top': - ($(that).parent('td').height() / 2 + $(that).height() + $(that).next('ul').height()/2),
                'min-width': 'inherit',
                'position': 'absolute'
            }).toggle();
        }else{
            $(that).next('ul').css({
                'padding': 10,
                'top':$(that).parent('td').height() / 2 + $(that).height(),
                'min-width': 'inherit',
                'position': 'absolute'
            }).toggle();
        }
    }


    //快速编辑
    layList.edit(function (obj) {
        var id=obj.data.id,value=obj.value;
        switch (obj.field) {
            case 'project_name':
                action.set_product('project_name',id,value);
                break;
            case 'is_register':
                action.set_product('is_register',id,value);
                break;
            case 'address':
                action.set_product('address',id,value);
                break;
            case 'is_small_business':
                action.set_product('is_small_business',id,value);
                break;
            case 'is_high_tech':
                action.set_product('is_high_tech',id,value);
                break;
            case 'is_listed':
                action.set_product('is_listed',id,value);
                break;
            case 'sort':
                action.set_product('sort',id,value);
                break;
        }
    });

    //排序
    layList.sort(function (obj) {
        var type = obj.type;
        switch (obj.field){
            case 'id':
                layList.reload({order: layList.order(type,'p.id')},true,null,obj);
                break;
        }
    });


    //查询
    layList.search('search',function(where){
        layList.reload(where);
    });

    //自定义方法
    var action={
        set_product:function(field,id,value){
            layList.baseGet(layList.Url({c:'report.report',a:'set_product',q:{field:field,id:id,value:value}}),function (res) {
                layList.msg(res.msg);
            });
        },
        show:function(){
            var ids=layList.getCheckData().getIds('id');
            if(ids.length){
                layList.basePost(layList.Url({c:'report.report',a:'product_show'}),{ids:ids},function (res) {
                    layList.msg(res.msg);
                    layList.reload();
                });
            }else{
                layList.msg('请选择要上架的产品');
            }
        }
    };

    //多选事件绑定
    $('.layui-btn-container').find('button').each(function () {
        var type=$(this).data('type');
        $(this).on('click',function(){
            action[type] && action[type]();
        })
    });
</script>
{/block}
