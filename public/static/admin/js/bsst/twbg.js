define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'bsst.twbg/index',
        add_url: 'bsst.twbg/add',
        edit_url: 'bsst.twbg/edit',
        delete_url: 'bsst.twbg/delete',
        export_url: 'bsst.twbg/export',
        modify_url: 'bsst.twbg/modify',
        recycle_url: 'bsst.twbg/recycle',
    };

    return {
        // 导入页面
        import: function () {
            ea.listen();
        },

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh', 'add', [{
                    text: '导入开奖',
                    url: 'bsst.twbg/import',
                    method: 'open',
                    auth: 'import',
                    class: 'layui-btn layui-btn-normal layui-btn-sm',
                    icon: 'fa fa-upload',
                    width: '800px',
                    height: '600px',
                }], 'delete', 'export'],
                cols: [[
                    {type: 'checkbox'},
                    {field: 'issueid', title: '开奖期号', width: 120},
                    {field: 'open_data', title: '开奖数据', width: 180},
                    {field: 'p1', title: 'P1'},
                    {field: 'p2', title: 'P1'},
                    {field: 'p3', title: 'P1'},
                    {field: 'p4', title: 'P1'},
                    {field: 'p5', title: 'P1'},
                    {field: 'remark', title: '备注', templet: ea.table.text},
                    {field: 'status', title: '状态(1:未使用,2:已使用)'},
                    {field: 'sort', title: '排序', edit: 'text'},
                    {field: 'open_time', title: '开奖时间', width: 160, templet: function(d){
                        if (!d.open_time || d.open_time == 0) return '';
                        // 开奖时间是时间戳格式
                        return layui.util.toDateString(d.open_time * 1000, 'yyyy-MM-dd HH:mm');
                    }},
                    {field: 'create_time', title: '创建时间', width: 160, templet: function(d){
                        if (!d.create_time) return '';
                        // 如果是时间戳格式
                        if (typeof d.create_time === 'number' || /^\d+$/.test(d.create_time)) {
                            return layui.util.toDateString(d.create_time * 1000, 'yyyy-MM-dd HH:mm');
                        }
                        // 如果是字符串格式，直接截取前16位（yyyy-MM-dd HH:mm）
                        return d.create_time.substring(0, 16);
                    }},
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
                    {field: 'issueid', title: '开奖期号', width: 120},
                    {field: 'open_data', title: '开奖数据', width: 180},
                    {field: 'p1', title: 'P1'},
                    {field: 'p2', title: 'P1'},
                    {field: 'p3', title: 'P1'},
                    {field: 'p4', title: 'P1'},
                    {field: 'p5', title: 'P1'},
                    {field: 'remark', title: '备注', templet: ea.table.text},
                    {field: 'status', title: '状态(1:未使用,2:已使用)'},
                    {field: 'sort', title: '排序', edit: 'text'},
                    {field: 'open_time', title: '开奖时间', width: 160, templet: function(d){
                        if (!d.open_time || d.open_time == 0) return '';
                        // 开奖时间是时间戳格式
                        return layui.util.toDateString(d.open_time * 1000, 'yyyy-MM-dd HH:mm');
                    }},
                    {field: 'create_time', title: '创建时间', width: 160, templet: function(d){
                        if (!d.create_time) return '';
                        // 如果是时间戳格式
                        if (typeof d.create_time === 'number' || /^\d+$/.test(d.create_time)) {
                            return layui.util.toDateString(d.create_time * 1000, 'yyyy-MM-dd HH:mm');
                        }
                        // 如果是字符串格式，直接截取前16位（yyyy-MM-dd HH:mm）
                        return d.create_time.substring(0, 16);
                    }},
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