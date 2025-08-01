<?php

namespace app\admin\controller\system;

use app\admin\model\SystemConfig;
use app\admin\service\TriggerService;
use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use app\Request;
use think\App;
use think\facade\Cache;
use think\response\Json;

#[ControllerAnnotation(title: '系统配置管理')]
class Config extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = SystemConfig::class;
        $this->assign('upload_types', config('admin.upload_types'));
        $this->assign('editor_types', config('admin.editor_types'));
    }

    #[NodeAnnotation(title: '列表', auth: true)]
    public function index(Request $request): Json|string
    {
        return $this->fetch();
    }

    #[NodeAnnotation(title: '保存', auth: true)]
    public function save(Request $request): void
    {
        $this->checkPostRequest();
        $post         = $request->post();
        $notAddFields = ['_token', 'file', 'group'];
        try {
            $group = $post['group'] ?? '';
            if (empty($group)) $this->error('保存失败');
            if ($group == 'upload') {
                $upload_types = config('admin.upload_types');
                // 兼容旧版本
                self::$model::where('name', 'upload_allow_type')->update(['value' => implode(',', array_keys($upload_types))]);
            }
            foreach ($post as $key => $val) {
                if (in_array($key, $notAddFields)) continue;
                if (self::$model::where('name', $key)->count()) {
                    self::$model::where('name', $key)->update(['value' => $val,]);
                }else {
                    self::$model::create(
                        [
                            'name'  => $key,
                            'value' => $val,
                            'group' => $group,
                        ]);
                }
                if (Cache::has($key)) Cache::set($key, $val);
            }
            TriggerService::updateMenu();
            TriggerService::updateSysConfig();
        }catch (\Exception $e) {
            $this->error('保存失败' . $e->getMessage());
        }
        $this->success('保存成功');
    }

}