define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'gdds.user/index',
        add_url: 'gdds.user/add',
        edit_url: 'gdds.user/edit',
        delete_url: 'gdds.user/delete',
        export_url: 'gdds.user/export',
        modify_url: 'gdds.user/modify',
        recycle_url: 'gdds.user/recycle',
    };

    return {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'username', title: '用户名'},                    {field: 'password', title: '密码'},                    {field: 'soft_version', title: '软件版本'},                    {field: 'remark', title: '备注', templet: ea.table.text},                    {field: 'api_public_key', title: 'API公钥'},                    {field: 'api_private_key', title: 'API私钥'},                    {field: 'api_token', title: 'API令牌'},                    {field: 'status', title: '状态(1:禁用,2:启用)'},                    {field: 'sort', title: '排序', edit: 'text'},                    {field: 'last_login_time', title: '最后登录时间'},                    {field: 'last_login_ip', title: '最后登录IP'},                    {field: 'create_time', title: '创建时间'},                    {width: 250, title: '操作', templet: ea.table.tool},
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
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'username', title: '用户名'},                    {field: 'password', title: '密码'},                    {field: 'soft_version', title: '软件版本'},                    {field: 'remark', title: '备注', templet: ea.table.text},                    {field: 'api_public_key', title: 'API公钥'},                    {field: 'api_private_key', title: 'API私钥'},                    {field: 'api_token', title: 'API令牌'},                    {field: 'status', title: '状态(1:禁用,2:启用)'},                    {field: 'sort', title: '排序', edit: 'text'},                    {field: 'last_login_time', title: '最后登录时间'},                    {field: 'last_login_ip', title: '最后登录IP'},                    {field: 'create_time', title: '创建时间'},
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