define(["jquery", "easy-admin"], function ($, ea) {

    return {
        index: function () {
            if (top.location !== self.location) {
                top.location = self.location;
            }
            $(function () {
                if (backgroundUrl) {
                    $('body').css('background', 'url(' + backgroundUrl + ') 0% 0% / cover no-repeat')
                }
                $('.bind-password').on('click', function () {
                    if ($(this).hasClass('icon-5')) {
                        $(this).removeClass('icon-5');
                        $("input[name='password']").attr('type', 'password');
                    } else {
                        $(this).addClass('icon-5');
                        $("input[name='password']").attr('type', 'text');
                    }
                });

                $('.icon-nocheck').on('click', function () {
                    if ($(this).hasClass('icon-check')) {
                        $(this).removeClass('icon-check');
                    } else {
                        $(this).addClass('icon-check');
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.keyCode === 13) {
                        $('.login-btn').trigger('click')
                    }
                });

                $('.login-tip').on('click', function () {
                    $('.icon-nocheck').click();
                });

                ea.listen(function (data) {
                    data['keep_login'] = $('.icon-nocheck').hasClass('icon-check') ? 1 : 0;
                    return data;
                }, function (res) {
                    ea.msg.success(res.msg, function () {
                        window.location = ea.url('index');
                    })
                }, function (res) {
                    let data = res.data
                    if (data?.is_ga_code || false) {
                        let elem = $('#gaCode')
                        elem.removeClass('layui-hide');
                        elem.find('input').focus()
                    }
                    ea.msg.error(res.msg, function () {
                        $('#refreshCaptcha').trigger("click");
                    });
                });
            });
        },
    };
});
