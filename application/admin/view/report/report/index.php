{extend name="public/container"}
{block name="content"}
<div class="layui-fluid" style="background: #fff;margin-top: -10px;">
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
                            <div class="layui-inline">
                                <label class="layui-form-label">筛选值</label>
                                <div class="layui-input-block">
                                    <input type="text" name="search_name" class="layui-input" placeholder="请输入">
                                </div>
                            </div>
                            <div class="layui-inline">
                                <div class="layui-input-inline">
                                    <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit="search" lay-filter="search">
                                        <i class="layui-icon layui-icon-search"></i>搜索</button>
                                    <button class="layui-btn layui-btn-primary layui-btn-sm export"  lay-submit="export" lay-filter="export">
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
<!--                    <table class="layui-hide" id="List" lay-filter="List"></table>-->
                    <table class="layui-hide" id="List" lay-filter="List" ></table>
                    <!--操作-->
                    <script type="text/html" id="act">
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-normal" onclick="$eb.createModalFrame('{{d.project_name}}-编辑','{:Url('edit')}?id={{d.id}}',{h:700,w:1100})">
                            编辑
                        </button>
                    </script>
                </div>
            </div>
        </div>

    </div>
</div>
<!--<script src="{__ADMIN_PATH}js/layuiList.js"></script>-->
<script src="{__ADMIN_PATH}js/layui.js"></script>
<script>

    layui.use('table', function() {
        var table = layui.table;

        table.render({
            elem: '#List',
            url: "{:Url('product_ist',['type'=>1])}",
            cols: [[
                {type: 'checkbox', fixed: 'left'},
                {field: 'id', title: 'ID', sort: true, event: 'id', width: 60},
                {field: 'project_num', title: '项目编号', width: 90},
                {field: 'cate_name', title: '归属园区', width: 110},
                {field: 'is_hatched', title: '是否入孵', width: 90},
                {field: 'corporate_name', title: '企业或项目名', width: 110},
                {field: 'org_code', title: '组织机构代码', width: 110},
                {field: 'project_synopsis', title: '项目简介', width: 90},
                {field: 'is_register', title: '是否注册企业', width: 110},
                {field: 'project_type', title: '项目类别', width: 90},
                {field: 'jop_num', title: '就业人数', width: 90},
                {field: 'entr_num', title: '创业人数', width: 90},
                {field: 'legal_name', title: '法人姓名', width: 90},
                {field: 'legal_id_card', title: '法人身份证', width: 100},
                {field: 'legal_school', title: '毕业院校', width: 90},
                {field: 'legal_time', title: '法人毕业时间', width: 110},
                {field: 'legal_education', title: '法人学历', width: 90},
                {field: 'legal_phone', title: '法人电话', width: 90},
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
                {field: 'month_turnover', title: '营业额-本月(万元)', width: 150},
                {field: 'year_turnover', title: '营业额-本年累计(万元)', width: 160},
                {field: 'month_taxes', title: '纳税额-本月(万元)', width: 150},
                {field: 'year_taxes', title: '纳税额-本年累计(万元)', width: 160},
                {field: 'resource_docking', title: '有效资源对接情况', width: 150},
                {field: 'name_investor', title: '出资单位名称', width: 110},
                {field: 'financing_amount', title: '融资金额', width: 90},
                {field: 'gov_amount', title: '政府扶持资金名称及金额(万元)', width: 210},
                {field: 'project_awards', title: '项目获奖及专利情况', width: 150},
                {field: 'change_record', title: '信息变更记录', width: 110},
                {field: 'back_time', title: '退园时间', width: 90},
                {field: 'reason', title: '退园原因', width: 90},
                {field: 'industry_type', title: '行业类型', width: 90},
                {field: 'products_services', title: '项目提供的产品或服务', width: 160},
                {field: 'required_pro_serv', title: '项目需要的产品或服务', width: 160},
                {field: 'financing_needs', title: '是否有融资需求', width: 130},
                {field: 'entrepr', title: '是否需要创业辅导培训', width: 160},

                {field: 'create_time', title: '填报时间', width: 180},
                {field: 'right', title: '操作', align: 'center', toolbar: '#act', width: 80},
            ]]
            , page: true
        });

    });
    // var type=1;
    // //实例化form
    // layList.form.render({
    //     elem: '#List'
    // });
    // //加载列表
    // layList.tableList('List',"{:Url('product_ist',['type'=>1])}",function (){
    //
    //     var join=new Array();
    //     switch (parseInt(type)){
    //         case 1:
    //             join=[
    //                 {field: 'id', title: 'ID', sort: true,event:'id',width:60},
    //                 {field: 'project_num', title: '项目编号',width:60},
    //                 {field: 'cate_name', title: '归属园区',width:80},
    //                 {field: 'is_hatched', title: '是否入孵',width:60},
    //                 {field: 'corporate_name', title: '企业或项目名',width:80},
    //                 {field: 'org_code', title: '组织机构代码',width:60},
    //                 {field: 'project_synopsis', title: '项目简介',width:80},
    //                 {field: 'is_register', title: '是否注册企业',width:60},
    //                 {field: 'project_type', title: '项目类别',width:60},
    //                 {field: 'jop_num', title: '就业人数',width:60},
    //                 {field: 'entr_num', title: '创业人数',width:60},
    //                 {field: 'legal_name', title: '法人姓名',width:60},
    //                 {field: 'legal_id_card', title: '法人身份证',width:80},
    //                 {field: 'legal_school', title: '毕业院校',width:60},
    //                 {field: 'legal_time', title: '法人毕业时间',width:80},
    //                 {field: 'legal_education', title: '法人学历',width:60},
    //                 {field: 'legal_phone', title: '法人电话',width:'80'},
    //                 {field: 'is_graduate_school', title: '法人是否毕业或在校5年',width:60},
    //                 {field: 'team_name', title: '团队成员姓名',width:60},
    //                 {field: 'team_school', title: '团队成员毕业院校',width:60},
    //                 {field: 'team_time', title: '团队成员毕业时间',width:80},
    //                 {field: 'team_education', title: '团队成员学历',width:60},
    //                 {field: 'team_phone', title: '团队成员电话',width:60},
    //                 {field: 'residence_time', title: '入住园区时间',width:80},
    //                 {field: 'start_time', title: '入园协议起时间',width:60},
    //                 {field: 'end_time', title: '入园协议止时间',width:60},
    //                 {field: 'room_number', title: ' 入驻房间编号',width:50},
    //                 {field: 'site_area', title: ' 入驻场地面积',width:50},
    //                 {field: 'month_turnover', title: '营业额-本月(万元)',width:50},
    //                 {field: 'year_turnover', title: '营业额-本年累计(万元)',width:50},
    //                 {field: 'month_taxes', title: '纳税额-本月(万元)',width:50},
    //                 {field: 'year_taxes', title: '纳税额-本年累计(万元)',width:50},
    //                 {field: 'resource_docking', title: '有效资源对接情况',width:50},
    //                 {field: 'name_investor', title: '出资单位名称',width:80},
    //                 {field: 'financing_amount', title: '融资金额',width:50},
    //                 {field: 'gov_amount', title: '政府扶持资金名称及金额(万元)',width:80},
    //                 {field: 'project_awards', title: '项目获奖及专利情况',width:80},
    //                 {field: 'change_record', title: '信息变更记录',width:80},
    //                 {field: 'back_time', title: '退园时间',width:80},
    //                 {field: 'reason', title: '退园原因',width:80},
    //                 {field: 'industry_type', title: '行业类型',width:60},
    //                 {field: 'products_services', title: '项目提供的产品或服务',width:80},
    //                 {field: 'required_pro_serv', title: '项目需要的产品或服务',width:80},
    //                 {field: 'financing_needs', title: '是否有融资需求',width:80},
    //                 {field: 'entrepr', title: '是否需要创业辅导培训',width:60},
    //
    //                 {field: 'create_time', title: '填报时间',width:80},
    //                 {field: 'right', title: '操作',align:'center',toolbar:'#act',width:80},
    //             ];
    //             break;
    //     }
    //     return join;
    // })

    //excel下载
    layList.search('export',function(where){
        location.href=layList.U({c:'report.report',a:'product_ist',q:{
                cate_id:where.cate_id,
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
</script>
{/block}
