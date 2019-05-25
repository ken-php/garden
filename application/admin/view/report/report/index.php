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
                    <table class="layui-hide" id="List" lay-filter="List"></table>
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
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
<script>
    var type=1;
    //实例化form
    layList.form.render();
    //加载列表
    layList.tableList('List',"{:Url('product_ist',['type'=>1])}",function (){
        var join=new Array();
        switch (parseInt(type)){
            case 1:
                join=[
                    {field: 'id', title: 'ID', sort: true,event:'id',width:'6%'},
                    {field: 'project_name', title: '企业或项目名',width:'10%'},
                    {field: 'is_register', title: '是否注册企业',width:'10%'},
                    {field: 'address', title: '注册地址',width:'10%'},
                    {field: 'is_small_business', title: '是否是科技型中小企业',align:'center'},
                    {field: 'is_high_tech', title: '是否是高新技术企业',width:'10%'},
                    {field: 'is_listed', title: '是否上市挂牌',width:'8%'},
                    {field: 'create_time', title: '填报时间',width:'8%'},
                    {field: 'right', title: '操作',align:'center',toolbar:'#act',width:'14%'},
                ];
                break;
        }
        return join;
    })
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
