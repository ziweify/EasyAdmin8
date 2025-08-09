define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: "#currentTable",
        table_render_id: "currentTableRenderId",
        index_url: "gdds.user/index",
        add_url: "gdds.user/add",
        edit_url: "gdds.user/edit",
        delete_url: "gdds.user/delete",
        export_url: "gdds.user/export",
        modify_url: "gdds.user/modify",  // 使用正确的控制器路径
        recycle_url: "gdds.user/recycle",
    };

    // 格式化VIP时间的辅助函数
    function formatVipTime(vipOffTime) {
        if (!vipOffTime || vipOffTime == 0 || vipOffTime == '0') {
            return '未开通';
        }
        
        // 确保是数字类型的时间戳
        var timestamp = parseInt(vipOffTime);
        if (isNaN(timestamp) || timestamp <= 0) {
            return '未开通';
        }
        
        try {
            var date = new Date(timestamp * 1000); // 转换为毫秒
            if (isNaN(date.getTime())) {
                return '时间格式错误';
            }
            return date.toLocaleString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        } catch (e) {
            return '时间格式错误';
        }
    }

    return {

        index: function () {
            var table = ea.table.render({
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
                    {field: "status", width: 100, align: "center", unresize: true, title: "状态", templet: function(d) {
    var checked = d.status === 2 ? 'checked' : '';
    return '<div style="text-align: center;" data-status="'+d.status+'"><input type="checkbox" name="status" value="'+d.id+'" lay-skin="switch" lay-text="启用|禁用" lay-filter="status" '+checked+' style="margin: 0;"></div>';
}},
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
                        var vipOffTime = d.vip_off_time;
                        var vipFunction = d.vip_function || "无";

                        return "<div style=\"line-height: 1.5;\">" +
                               "<div style=\"font-weight: bold;\">权限: " + vipFunction + "</div>" +
                               "<div style=\"color: #999; font-size: 11px;\">到期: " + formatVipTime(vipOffTime) + "</div>" +
                               "</div>";
                    }},
                    {field: "create_time", width: 150, title: "创建时间"},
                    {width: 200, title: "操作", templet: ea.table.tool},
                ]],
            });

            ea.listen();
            
            // 监听状态开关操作
            layui.form.on('switch(status)', function(obj){
                // 保存开关元素和状态
                var $switch = $(obj.elem);
                var isChecked = obj.elem.checked;
                
                var data = {
                    id: obj.value,
                    field: 'status',
                    value: isChecked ? 2 : 1  // 修改为正确的状态值：2=启用，1=禁用
                };
                
                // 发送修改请求
                $.ajax({
                    url: ea.url(init.modify_url),
                    data: data,
                    type: 'post',
                    dataType: 'json',
                    success: function(res){
                        if (res.code === 1) {
                            layui.layer.msg(res.msg || '保存成功', {icon: 1});
                            // 更新成功，刷新表格
                            table.reload(init.table_render_id);
                        } else {
                            layui.layer.msg(res.msg || 'VIP未开通或已过期，不能启用账号', {
                                icon: 2,
                                time: 2000
                            });
                            // 更新失败，恢复开关状态
                            $switch.prop('checked', !isChecked);
                            layui.form.render();
                            // 强制刷新表格
                            table.reload(init.table_render_id);
                        }
                    },
                    error: function(){
                        layui.layer.msg('操作失败，请重试', {
                            icon: 2,
                            time: 2000
                        });
                        // 发生错误，恢复开关状态
                        $switch.prop('checked', !isChecked);
                        layui.form.render();
                        // 强制刷新表格
                        table.reload(init.table_render_id);
                    }
                });
            });
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
                    {field: "status", width: 100, align: "center", unresize: true, title: "状态", templet: function(d) {
    var checked = d.status === 2 ? 'checked' : '';
    return '<div style="text-align: center;" data-status="'+d.status+'"><input type="checkbox" name="status" value="'+d.id+'" lay-skin="switch" lay-text="启用|禁用" lay-filter="status" '+checked+' style="margin: 0;"></div>';
}},
                    {field: "sort", width: 80, title: "排序", edit: "text"},
                    {field: "last_login_time", width: 180, title: "最后登录信息", templet: function(d) {
                        return "<div style=\"line-height: 1.5;\">" + 
                               "<div style=\"font-weight: bold;\">" + (d.last_login_time || "未登录") + "</div>" + 
                               "<div style=\"color: #666; font-size: 12px;\">" + (d.last_login_ip || "无IP记录") + "</div>" + 
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
                        var vipOffTime = d.vip_off_time;
                        var vipFunction = d.vip_function || "无";

                        return "<div style=\"line-height: 1.5;\">" +
                               "<div style=\"font-weight: bold;\">权限: " + vipFunction + "</div>" +
                               "<div style=\"color: #999; font-size: 11px;\">到期: " + formatVipTime(vipOffTime) + "</div>" +
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