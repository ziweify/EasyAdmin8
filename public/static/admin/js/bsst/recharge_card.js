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
        // 百胜系统充值卡列表
        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh', 'add', [{
                    text: '批量创建',
                    url: 'bsst.recharge_card/batchCreate',
                    method: 'open',
                    auth: 'batchCreate',
                    class: 'layui-btn layui-btn-warm layui-btn-sm',
                    icon: 'fa fa-plus',
                    width: '600px',
                    height: '500px',
                }], 'delete', 'export'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'card_no', title: '充值卡号', width: 180},
                    {field: 'card_type', title: '卡类型', width: 100, templet: function(d){
                        var types = {1: '普通卡', 2: '大客户卡', 3: '活动卡'};
                        return types[d.card_type] || '未知';
                    }},
                    {field: 'amount', title: '金额', width: 100},
                    {field: 'status', title: '状态', width: 100, templet: function(d){
                        var status = {1: '未使用', 2: '已使用', 3: '已过期', 4: '已作废'};
                        var colors = {1: 'layui-bg-green', 2: 'layui-bg-blue', 3: 'layui-bg-orange', 4: 'layui-bg-red'};
                        return '<span class="layui-badge ' + (colors[d.status] || '') + '">' + (status[d.status] || '未知') + '</span>';
                    }},
                    {field: 'batch_no', title: '批次号', width: 150},
                    {field: 'soft_name', title: '软件名称', width: 120},
                    {field: 'expire_time', title: '过期时间', width: 160, templet: function(d){
                        if (!d.expire_time || d.expire_time == 0) return '永久有效';
                        return layui.util.toDateString(d.expire_time * 1000, 'yyyy-MM-dd HH:mm');
                    }},
                    {field: 'used_user_id', title: '使用用户', width: 100},
                    {field: 'used_time', title: '使用时间', width: 160, templet: function(d){
                        if (!d.used_time) return '';
                        return layui.util.toDateString(d.used_time * 1000, 'yyyy-MM-dd HH:mm');
                    }},
                    {field: 'create_time', title: '创建时间', width: 160, templet: function(d){
                        if (!d.create_time) return '';
                        if (typeof d.create_time === 'number' || /^\d+$/.test(d.create_time)) {
                            return layui.util.toDateString(d.create_time * 1000, 'yyyy-MM-dd HH:mm');
                        }
                        return d.create_time.substring(0, 16);
                    }},
                    {field: 'remark', title: '备注', templet: ea.table.text},
                    {width: 250, title: '操作', templet: ea.table.tool},
                ]],
            });

            ea.listen();
        },

        // 跟单大师充值卡列表
        indexGdds: function () {
            ea.table.render({
                init: {
                    ...init,
                    index_url: 'bsst.recharge_card/indexGdds',
                },
                toolbar: ['refresh', 'add', [{
                    text: '批量创建',
                    url: 'bsst.recharge_card/batchCreateGdds',
                    method: 'open',
                    auth: 'batchCreate',
                    class: 'layui-btn layui-btn-warm layui-btn-sm',
                    icon: 'fa fa-plus',
                    width: '600px',
                    height: '500px',
                }], 'delete', 'export'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'card_no', title: '充值卡号', width: 180},
                    {field: 'card_type', title: '卡类型', width: 100, templet: function(d){
                        var types = {1: '普通卡', 2: '大客户卡', 3: '活动卡'};
                        return types[d.card_type] || '未知';
                    }},
                    {field: 'amount', title: '金额', width: 100},
                    {field: 'status', title: '状态', width: 100, templet: function(d){
                        var status = {1: '未使用', 2: '已使用', 3: '已过期', 4: '已作废'};
                        var colors = {1: 'layui-bg-green', 2: 'layui-bg-blue', 3: 'layui-bg-orange', 4: 'layui-bg-red'};
                        return '<span class="layui-badge ' + (colors[d.status] || '') + '">' + (status[d.status] || '未知') + '</span>';
                    }},
                    {field: 'batch_no', title: '批次号', width: 150},
                    {field: 'expire_time', title: '过期时间', width: 160, templet: function(d){
                        if (!d.expire_time || d.expire_time == 0) return '永久有效';
                        return layui.util.toDateString(d.expire_time * 1000, 'yyyy-MM-dd HH:mm');
                    }},
                    {field: 'used_user_id', title: '使用用户', width: 100},
                    {field: 'used_time', title: '使用时间', width: 160, templet: function(d){
                        if (!d.used_time) return '';
                        return layui.util.toDateString(d.used_time * 1000, 'yyyy-MM-dd HH:mm');
                    }},
                    {field: 'create_time', title: '创建时间', width: 160, templet: function(d){
                        if (!d.create_time) return '';
                        if (typeof d.create_time === 'number' || /^\d+$/.test(d.create_time)) {
                            return layui.util.toDateString(d.create_time * 1000, 'yyyy-MM-dd HH:mm');
                        }
                        return d.create_time.substring(0, 16);
                    }},
                    {field: 'remark', title: '备注', templet: ea.table.text},
                    {width: 250, title: '操作', templet: ea.table.tool},
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

        batchCreate: function () {
            ea.listen();
        },

        batchCreateGdds: function () {
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
                    {type: 'checkbox'},
                    {field: 'card_no', title: '充值卡号', width: 180},
                    {field: 'card_type', title: '卡类型', width: 100, templet: function(d){
                        var types = {1: '普通卡', 2: '大客户卡', 3: '活动卡'};
                        return types[d.card_type] || '未知';
                    }},
                    {field: 'amount', title: '金额', width: 100},
                    {field: 'status', title: '状态', width: 100, templet: function(d){
                        var status = {1: '未使用', 2: '已使用', 3: '已过期', 4: '已作废'};
                        var colors = {1: 'layui-bg-green', 2: 'layui-bg-blue', 3: 'layui-bg-orange', 4: 'layui-bg-red'};
                        return '<span class="layui-badge ' + (colors[d.status] || '') + '">' + (status[d.status] || '未知') + '</span>';
                    }},
                    {field: 'batch_no', title: '批次号', width: 150},
                    {field: 'soft_name', title: '软件名称', width: 120},
                    {field: 'expire_time', title: '过期时间', width: 160, templet: function(d){
                        if (!d.expire_time || d.expire_time == 0) return '永久有效';
                        return layui.util.toDateString(d.expire_time * 1000, 'yyyy-MM-dd HH:mm');
                    }},
                    {field: 'used_user_id', title: '使用用户', width: 100},
                    {field: 'used_time', title: '使用时间', width: 160, templet: function(d){
                        if (!d.used_time) return '';
                        return layui.util.toDateString(d.used_time * 1000, 'yyyy-MM-dd HH:mm');
                    }},
                    {field: 'create_time', title: '创建时间', width: 160, templet: function(d){
                        if (!d.create_time) return '';
                        if (typeof d.create_time === 'number' || /^\d+$/.test(d.create_time)) {
                            return layui.util.toDateString(d.create_time * 1000, 'yyyy-MM-dd HH:mm');
                        }
                        return d.create_time.substring(0, 16);
                    }},
                    {field: 'remark', title: '备注', templet: ea.table.text},
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