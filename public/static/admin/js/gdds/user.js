define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: "#currentTable",
        table_render_id: "currentTableRenderId",
        index_url: "gdds.user/index",
        add_url: "gdds.user/add",
        edit_url: "gdds.user/edit",
        delete_url: "gdds.user/delete",
        export_url: "gdds.user/export",
        modify_url: "gdds.user/modify",
        recycle_url: "gdds.user/recycle",
    };

    return {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox", width: 50},
                    {field: "id", width: 80, title: "ID"},
                    {field: "username", width: 120, title: "用户名"},
                    {field: "password", width: 100, title: "密码"},
                    {field: "soft_version", width: 100, title: "软件版本"},
                    {field: "allow_window", width: 60, title: "窗口"},
                    {field: "remark", width: 120, title: "备注", templet: ea.table.text},
                    {field: "api_public_key", width: 150, title: "API公钥", hide: true},
                    {field: "api_private_key", width: 150, title: "API私钥", hide: true},
                    {field: "api_token", width: 150, title: "API令牌"},
                    {field: "status", width: 85, title: "状态", templet: ea.table.switch},
                    {field: "sort", width: 80, title: "排序", edit: "text"},
                    {field: "last_login_time", width: 180, title: "最后登录信息", templet: function(d) {
                        return "<div style=\"line-height: 1.5;\">" + 
                               "<div style=\"font-weight: bold;\">" + (d.last_login_time || "未登录") + "</div>" + 
                               "<div style=\"color: #999; font-size: 12px;\">" + (d.last_login_ip || "无IP记录") + "</div>" + 
                               "</div>";
                    }},
                    {field: "carday_consumption", width: 150, title: "消费:月|周|日", templet: function(d) {
                        return '<div style="text-align: center; line-height: 1.4;">' + 
                        '<span style="font-weight: bold; color: #e74c3c; font-size: 14px;">' + (d.carmonth_consumption || '0') + '</span>' + 
                        '<span style="color: #666; margin: 0 8px;">|</span>' + 
                        '<span style="font-weight: bold; color: #f39c12; font-size: 13px;">' + (d.carweek_consumption || '0') + '</span>' + 
                        '<span style="color: #666; margin: 0 8px;">|</span>' + 
                        '<span style="font-weight: bold; color: #27ae60; font-size: 12px;">' + (d.carday_consumption || '0') + '</span>' + 
                        '</div>';
                    }},
                    {field: "vip_function", width: 150, title: "VIP信息", templet: function(d) {
                        return "<div style=\"line-height: 1.5;\">" + 
                               "<div style=\"font-weight: bold;\">权限: " + (d.vip_function || "无") + "</div>" + 
                               "<div style=\"color: #999; font-size: 12px;\">到期: " + (d.vip_off_time || "未开通") + "</div>" + 
                               "</div>";
                    }},
                    {field: "create_time", width: 150, title: "创建时间"},
                    {width: 200, title: "操作", templet: ea.table.tool},
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
                toolbar: ["refresh",
                    [{
                        class: "layui-btn layui-btn-sm",
                        method: "get",
                        field: "id",
                        icon: "fa fa-refresh",
                        text: "全部恢复",
                        title: "确定恢复？",
                        auth: "recycle",
                        url: init.recycle_url + "?type=restore",
                        checkbox: true
                    }, {
                        class: "layui-btn layui-btn-danger layui-btn-sm",
                        method: "get",
                        field: "id",
                        icon: "fa fa-delete",
                        text: "彻底删除",
                        title: "确定彻底删除？",
                        auth: "recycle",
                        url: init.recycle_url + "?type=delete",
                        checkbox: true
                    }], "export",
                ],
                cols: [[
                    {type: "checkbox", width: 50},
                    {field: "id", width: 80, title: "ID"},
                    {field: "username", width: 120, title: "用户名"},
                    {field: "password", width: 100, title: "密码"},
                    {field: "soft_version", width: 100, title: "软件版本"},
                    {field: "allow_window", width: 60, title: "窗口"},
                    {field: "remark", width: 120, title: "备注", templet: ea.table.text},
                    {field: "api_public_key", width: 150, title: "API公钥", hide: true},
                    {field: "api_private_key", width: 150, title: "API私钥", hide: true},
                    {field: "api_token", width: 150, title: "API令牌"},
                    {field: "status", width: 85, title: "状态", templet: ea.table.switch},
                    {field: "sort", width: 80, title: "排序", edit: "text"},
                    {field: "last_login_time", width: 180, title: "最后登录信息", templet: function(d) {
                        return "<div style=\"line-height: 1.5;\">" + 
                               "<div style=\"font-weight: bold;\">" + (d.last_login_time || "未登录") + "</div>" + 
                               "<div style=\"color: #999; font-size: 12px;\">" + (d.last_login_ip || "无IP记录") + "</div>" + 
                               "</div>";
                    }},
                    {field: "carday_consumption", width: 150, title: "消费:月|周|日", templet: function(d) {
                        return '<div style="text-align: center; line-height: 1.4;">' + 
                               '<span style="font-weight: bold; color: #e74c3c; font-size: 14px;">' + (d.carmonth_consumption || '0') + '</span>' + 
                               '<span style="color: #666; margin: 0 8px;">|</span>' + 
                               '<span style="font-weight: bold; color: #f39c12; font-size: 13px;">' + (d.carweek_consumption || '0') + '</span>' + 
                               '<span style="color: #666; margin: 0 8px;">|</span>' + 
                               '<span style="font-weight: bold; color: #27ae60; font-size: 12px;">' + (d.carday_consumption || '0') + '</span>' + 
                               '</div>';
                    }},
                    {field: "vip_function", width: 150, title: "VIP信息", templet: function(d) {
                        return "<div style=\"line-height: 1.5;\">" + 
                               "<div style=\"font-weight: bold;\">权限: " + (d.vip_function || "无") + "</div>" + 
                               "<div style=\"color: #999; font-size: 12px;\">到期: " + (d.vip_off_time || "未开通") + "</div>" + 
                               "</div>";
                    }},
                    {field: "create_time", width: 150, title: "创建时间"},
                    {
                        width: 200,
                        title: "操作",
                        templet: ea.table.tool,
                        operat: [
                            [{
                                title: "确认恢复？",
                                text: "恢复数据",
                                filed: "id",
                                url: init.recycle_url + "?type=restore",
                                method: "get",
                                auth: "recycle",
                                class: "layui-btn layui-btn-xs layui-btn-success",
                            }, {
                                title: "想好了吗？",
                                text: "彻底删除",
                                filed: "id",
                                method: "get",
                                url: init.recycle_url + "?type=delete",
                                auth: "recycle",
                                class: "layui-btn layui-btn-xs layui-btn-normal layui-bg-red",
                            }]]
                    }
                ]],
            });

            ea.listen();
        },
    };
});