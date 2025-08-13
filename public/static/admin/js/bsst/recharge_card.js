define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'bsst.recharge_card/index',
        add_url: 'bsst.recharge_card/add',
        edit_url: 'bsst.recharge_card/edit',
        delete_url: 'bsst.recharge_card/delete',
        export_url: 'bsst.recharge_card/export',
        modify_url: 'bsst.recharge_card/modify',
        recycle_url: 'bsst.recharge_card/recycle',
    };

    return {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'soft_name', title: '软件名称:'},                    {field: 'card_type', title: '卡类型:会员卡|大客户卡|活动卡'},                    {field: 'card_number', title: '卡号:软件名缩写+会员卡类型缩写+卡号'},                    {field: 'count', title: '剩余使用次数'},                    {field: 'used_ip', title: '使用者IP'},                    {field: 'used_role', title: '使用者(玩家自己, 或者管理员'},                    {field: 'used_time', title: '使用时间'},                    {field: 'used_device', title: '使用设备信息'},                    {field: 'sub_card', title: '0表示主卡,1表示子卡(大客户卡,活动才有子卡)子卡仅仅记录使用情况'},                    {field: 'status', title: '状态(1:未使用,2:已使用,3:已用完,3已过期,4已作废)'},                    {field: 'sort', title: '排序', edit: 'text'},                    {field: 'remark', title: '备注:账号+由时间-续费到-新时间+操作人(API或管理员)', templet: ea.table.text},                    {field: 'create_time', title: '创建时间'},                    {width: 250, title: '操作', templet: ea.table.tool},
                ]],
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        recycle: function () {
            init.index_url = init.recycle_url;
            ea.table.render({
                init: init,
                toolbar: ['refresh',
                    [{
                        class: 'layui-btn layui-btn-sm',
                        method: 'get',
                        field: 'id',
                        icon: 'fa fa-refresh',
                        text: '全部恢复',
                        title: '确定恢复？',
                        auth: 'recycle',
                        url: init.recycle_url + '?type=restore',
                        checkbox: true
                    }, {
                        class: 'layui-btn layui-btn-danger layui-btn-sm',
                        method: 'get',
                        field: 'id',
                        icon: 'fa fa-delete',
                        text: '彻底删除',
                        title: '确定彻底删除？',
                        auth: 'recycle',
                        url: init.recycle_url + '?type=delete',
                        checkbox: true
                    }], 'export',
                ],
                cols: [[
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'soft_name', title: '软件名称:'},                    {field: 'card_type', title: '卡类型:会员卡|大客户卡|活动卡'},                    {field: 'card_number', title: '卡号:软件名缩写+会员卡类型缩写+卡号'},                    {field: 'count', title: '剩余使用次数'},                    {field: 'used_ip', title: '使用者IP'},                    {field: 'used_role', title: '使用者(玩家自己, 或者管理员'},                    {field: 'used_time', title: '使用时间'},                    {field: 'used_device', title: '使用设备信息'},                    {field: 'sub_card', title: '0表示主卡,1表示子卡(大客户卡,活动才有子卡)子卡仅仅记录使用情况'},                    {field: 'status', title: '状态(1:未使用,2:已使用,3:已用完,3已过期,4已作废)'},                    {field: 'sort', title: '排序', edit: 'text'},                    {field: 'remark', title: '备注:账号+由时间-续费到-新时间+操作人(API或管理员)', templet: ea.table.text},                    {field: 'create_time', title: '创建时间'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                title: '确认恢复？',
                                text: '恢复数据',
                                filed: 'id',
                                url: init.recycle_url + '?type=restore',
                                method: 'get',
                                auth: 'recycle',
                                class: 'layui-btn layui-btn-xs layui-btn-success',
                            }, {
                                title: '想好了吗？',
                                text: '彻底删除',
                                filed: 'id',
                                method: 'get',
                                url: init.recycle_url + '?type=delete',
                                auth: 'recycle',
                                class: 'layui-btn layui-btn-xs layui-btn-normal layui-bg-red',
                            }]]
                    }
                ]],
            });

            ea.listen();
        },
    };
});