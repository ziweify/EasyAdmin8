define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'bsst.software/index',
        add_url: 'bsst.software/add',
        edit_url: 'bsst.software/edit',
        delete_url: 'bsst.software/delete',
        export_url: 'bsst.software/export',
        modify_url: 'bsst.software/modify',
        recycle_url: 'bsst.software/recycle',
    };

    return {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'name', title: '软件名'},                    {field: 'soft_version', title: '软件版本，服务器版本, 如果客户端版本不对可以强制更新'},                    {field: 'config', title: 'json格式,客户端自行解析'},                    {field: 'api_public_key', title: 'API公钥'},                    {field: 'api_private_key', title: 'API私钥'},                    {field: 'api_token', title: 'API令牌'},                    {field: 'remark', title: '备注', templet: ea.table.text},                    {field: 'day_price', title: '日卡点数'},                    {field: 'week_price', title: '周卡点数'},                    {field: 'moon_price', title: '月卡点数'},                    {field: 'status', title: '状态(1:禁用,2:启用)'},                    {field: 'sort', title: '排序', edit: 'text'},                    {field: 'create_time', title: '创建时间'},                    {width: 250, title: '操作', templet: ea.table.tool},
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
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'name', title: '软件名'},                    {field: 'soft_version', title: '软件版本，服务器版本, 如果客户端版本不对可以强制更新'},                    {field: 'config', title: 'json格式,客户端自行解析'},                    {field: 'api_public_key', title: 'API公钥'},                    {field: 'api_private_key', title: 'API私钥'},                    {field: 'api_token', title: 'API令牌'},                    {field: 'remark', title: '备注', templet: ea.table.text},                    {field: 'day_price', title: '日卡点数'},                    {field: 'week_price', title: '周卡点数'},                    {field: 'moon_price', title: '月卡点数'},                    {field: 'status', title: '状态(1:禁用,2:启用)'},                    {field: 'sort', title: '排序', edit: 'text'},                    {field: 'create_time', title: '创建时间'},
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