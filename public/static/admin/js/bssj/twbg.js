define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'bssj.twbg/index',
        add_url: 'bssj.twbg/add',
        edit_url: 'bssj.twbg/edit',
        delete_url: 'bssj.twbg/delete',
        export_url: 'bssj.twbg/export',
        modify_url: 'bssj.twbg/modify',
        recycle_url: 'bssj.twbg/recycle',
    };

    return {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'issueid', title: '开奖期号'},
                    {field: 'open_data', title: '开奖数据,字符串格式'},
                    {field: 'p1', title: 'P1'},
                    {field: 'p2', title: 'P1'},
                    {field: 'p3', title: 'P1'},
                    {field: 'p4', title: 'P1'},
                    {field: 'p5', title: 'P1'},
                    {field: 'remark', title: '备注', templet: ea.table.text},
                    {field: 'status', title: '状态(1:未使用,2:已使用)'},
                    {field: 'sort', title: '排序', edit: 'text'},
                    {field: 'open_time', title: '开奖时间, 标准开奖时间'},
                    {field: 'create_time', title: '创建时间,采集到的时间'},
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
        recycle: function () {
            init.index_url = init.recycle_url;
            ea.table.render({
                init: init,
                toolbar: ['refresh',
                    [{
                        class: 'layui-btn layui-btn-sm',
                        method: 'get',
                        field: 'issueid',
                        icon: 'fa fa-refresh',
                        text: '全部恢复',
                        title: '确定恢复？',
                        auth: 'recycle',
                        url: init.recycle_url + '?type=restore',
                        checkbox: true
                    }, {
                        class: 'layui-btn layui-btn-danger layui-btn-sm',
                        method: 'get',
                        field: 'issueid',
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
                    {field: 'issueid', title: '开奖期号'},
                    {field: 'open_data', title: '开奖数据,字符串格式'},
                    {field: 'p1', title: 'P1'},
                    {field: 'p2', title: 'P1'},
                    {field: 'p3', title: 'P1'},
                    {field: 'p4', title: 'P1'},
                    {field: 'p5', title: 'P1'},
                    {field: 'remark', title: '备注', templet: ea.table.text},
                    {field: 'status', title: '状态(1:未使用,2:已使用)'},
                    {field: 'sort', title: '排序', edit: 'text'},
                    {field: 'open_time', title: '开奖时间, 标准开奖时间'},
                    {field: 'create_time', title: '创建时间,采集到的时间'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                title: '确认恢复？',
                                text: '恢复数据',
                                filed: 'issueid',
                                url: init.recycle_url + '?type=restore',
                                method: 'get',
                                auth: 'recycle',
                                class: 'layui-btn layui-btn-xs layui-btn-success',
                            }, {
                                title: '想好了吗？',
                                text: '彻底删除',
                                filed: 'issueid',
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