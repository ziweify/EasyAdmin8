<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;
use think\Request;

#[ControllerAnnotation(title: '跨应用调用示例')]
class Example extends AdminController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /**
     * 调用 api 应用的模型示例
     */
    #[NodeAnnotation(title: '调用API模型', auth: true)]
    public function callApiModel(Request $request)
    {
        try {
            // 方式一：直接使用命名空间调用 api 应用的模型
            // 假设 api 应用有一个 User 模型
            // $apiUsers = \app\api\model\User::where('status', 1)->select();
            
            // 方式二：实例化 api 应用的模型
            // $apiUserModel = new \app\api\model\User();
            // $data = $apiUserModel->where('id', 1)->find();
            
            // 示例：调用 api 应用的某个模型
            // $result = \app\api\model\SomeModel::select();
            
            $this->success('调用成功', ['message' => '这是从 api 应用模型获取的数据']);
            
        } catch (\Exception $e) {
            $this->error('调用失败：' . $e->getMessage());
        }
    }

    /**
     * 调用 api 应用的控制器示例
     */
    #[NodeAnnotation(title: '调用API控制器', auth: true)]
    public function callApiController(Request $request)
    {
        try {
            // 方式一：直接实例化 api 应用的控制器
            // $apiController = new \app\api\controller\User();
            // $result = $apiController->someMethod();
            
            // 方式二：通过 HTTP 请求调用
            // use think\facade\Http;
            // $response = Http::get('http://your-domain/api/user/list');
            // $data = $response->json();
            
            $this->success('调用成功', ['message' => '这是从 api 应用控制器获取的数据']);
            
        } catch (\Exception $e) {
            $this->error('调用失败：' . $e->getMessage());
        }
    }

    /**
     * 综合示例：在 admin 中处理 api 数据
     */
    #[NodeAnnotation(title: '综合调用示例', auth: true)]
    public function integratedExample(Request $request)
    {
        try {
            // 1. 从 api 应用获取数据
            // $apiData = \app\api\model\SomeModel::select();
            
            // 2. 在 admin 中处理数据
            $processedData = [
                'total' => 0,
                'items' => []
            ];
            
            // 3. 保存到 admin 应用的数据表
            // self::$model::create($processedData);
            
            $this->success('处理成功', $processedData);
            
        } catch (\Exception $e) {
            $this->error('处理失败：' . $e->getMessage());
        }
    }
} 