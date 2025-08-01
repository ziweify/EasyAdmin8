<?php

namespace app\admin\controller;

use app\admin\model\SystemAdmin;
use app\common\controller\AdminController;
use app\common\utils\Helper;
use think\captcha\facade\Captcha;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use app\Request;
use think\Response;
use Wolfcode\RateLimiting\Attributes\RateLimitingMiddleware;

class Login extends AdminController
{

    protected bool $ignoreLogin = true;

    public function initialize(): void
    {
        parent::initialize();
        $action = $this->request->action();
        if (!empty($this->adminUid) && !in_array($action, ['out'])) {
            $adminModuleName = config('admin.alias_name');
            $this->success('已登录，无需再次登录', [], __url("@{$adminModuleName}"));
        }
    }

    /**
     * 用户登录
     * @param Request $request
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    #[RateLimitingMiddleware(key: [Helper::class, 'getIp'], seconds: 1, limit: 1, message: '请求过于频繁')]
    public function index(Request $request): string
    {
        $captcha = env('EASYADMIN.CAPTCHA', 1);
        if (!$request->isPost()) return $this->fetch('', compact('captcha'));
        $post = $request->post();
        $rule = [
            'username|用户名'         => 'require',
            'password|密码'           => 'require',
            'keep_login|是否保持登录' => 'require',
        ];
        $captcha == 1 && $rule['captcha|验证码'] = 'require|captcha';
        $this->validate($post, $rule);
        $admin = SystemAdmin::where(['username' => $post['username']])->find();
        if (empty($admin)) {
            $this->error('用户不存在');
        }
        if (!password_verify($post['password'], $admin->password)) {
            $this->error('密码输入有误');
        }
        if ($admin->status == 0) {
            $this->error('账号已被禁用');
        }
        if ($admin->login_type == 2) {
            if (empty($post['ga_code'])) $this->error('请输入谷歌验证码', ['is_ga_code' => true]);
            $ga = new \Wolfcode\Authenticator\google\PHPGangstaGoogleAuthenticator();
            if (!$ga->verifyCode($admin->ga_secret, $post['ga_code'])) $this->error('谷歌验证码错误');;
        }
        $admin->login_num += 1;
        $admin->save();
        $admin = $admin->toArray();
        unset($admin['password']);
        $admin['expire_time'] = $post['keep_login'] == 1 ? 0 : time() + 7200;
        session('admin', $admin);
        $this->success('登录成功');
    }

    /**
     * 用户退出
     */
    public function out(): void
    {
        session('admin', null);
        $this->success('退出登录成功');
    }

    /**
     * 验证码
     * @return Response
     */
    public function captcha(): Response
    {
        return Captcha::instance()->create();
    }
}
