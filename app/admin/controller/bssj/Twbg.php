<?php

namespace app\admin\controller\bssj;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;
use think\Request;
use think\facade\Log;
use think\facade\Db;
use think\db\exception\PDOException;

#[ControllerAnnotation(title: 'bssj_twbg')]
class Twbg extends AdminController
{

    private array $notes;

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = new \app\admin\model\BssjTwbg();
        $notes = self::$model::$notes;
        
        // 修改排序字段为 issueid
        $this->sort = [
            'issueid' => 'desc',
        ];
        
        $this->notes =$notes;
        $this->assign(compact('notes'));
    }

    /**
     * 导入开奖数据
     */
    #[NodeAnnotation(title: '导入开奖', auth: true)]
    public function import(Request $request): string|\think\response\Json
    {
        if ($request->isPost()) {
            $post = $request->post();
            $action = $post['action'] ?? '';
            
            try {
                switch($action) {
                    case 'parse':
                        // 解析数据
                        $rawData = $post['rawData'] ?? '';
                        $lines = explode("\n", str_replace("\r", "", $rawData));
                        $parsedData = [];
                        
                        foreach($lines as $line) {
                            $line = trim($line);
                            if(empty($line)) continue;
                            
                            // 使用正则表达式匹配CSV格式
                            if(preg_match('/^(\d+),\s*"([^"]+)"\s*,\s*"?([^"]*)"?\s*$/', $line, $matches)) {
                                $issueId = $matches[1];
                                $openData = $matches[2];
                                $openTime = $matches[3];
                                
                                // 解析开奖号码
                                $numbers = \app\common\library\TwbgLib::parseOpenData($openData);
                                
                                // 如果开奖时间为空，通过期号计算
                                if(empty($openTime)) {
                                    $openTime = \app\common\library\TwbgLib::calculateOpenTime($issueId);
                                } else {
                                    $openTime = strtotime($openTime);
                                    if($openTime === false) {
                                        $openTime = \app\common\library\TwbgLib::calculateOpenTime($issueId);
                                    }
                                }
                                
                                $parsedData[] = [
                                    'issueid' => $issueId,
                                    'open_data' => $openData,
                                    'p1' => $numbers[0],
                                    'p2' => $numbers[1],
                                    'p3' => $numbers[2],
                                    'p4' => $numbers[3],
                                    'p5' => $numbers[4],
                                    'open_time' => $openTime
                                ];
                            }
                        }
                        
                        return json(['code' => 1, 'msg' => '解析成功', 'data' => $parsedData])->contentType('application/json');
                        
                    case 'import':
                        // 导入数据
                        $data = json_decode($post['data'], true);
                        if(empty($data)) {
                            return json(['code' => 0, 'msg' => '没有数据需要导入'])->contentType('application/json');
                        }
                        
                        $results = [];
                        $successCount = 0;
                        $insertData = [];
                        
                        // 首先检查所有期号是否存在（包括软删除的记录）
                        $issueIds = array_column($data, 'issueid');
                        $existingRecords = self::$model::withTrashed()
                            ->whereIn('issueid', $issueIds)
                            ->select()
                            ->toArray();
                        
                        // 将结果转换为以issueid为键的数组
                        $existingMap = [];
                        foreach ($existingRecords as $record) {
                            $existingMap[$record['issueid']] = $record;
                        }
                        
                        $now = time();
                        
                        // 预处理数据
                        foreach($data as $item) {
                            $result = [
                                'issueid' => $item['issueid'],
                                'status' => 'failed',
                                'error_msg' => ''
                            ];
                            
                            try {
                                if(isset($existingMap[$item['issueid']])) {
                                    // 更新现有记录
                                    $existingRecord = self::$model::withTrashed()->find($item['issueid']);
                                    if ($existingRecord->trashed()) {
                                        $existingRecord->restore(); // 如果是软删除的记录，恢复它
                                    }
                                    $existingRecord->save([
                                        'open_data' => $item['open_data'],
                                        'p1' => $item['p1'],
                                        'p2' => $item['p2'],
                                        'p3' => $item['p3'],
                                        'p4' => $item['p4'],
                                        'p5' => $item['p5'],
                                        'open_time' => $item['open_time']
                                    ]);
                                    $result['status'] = 'success';
                                    $result['error_msg'] = '更新成功';
                                    $successCount++;
                                } else {
                                    // 收集要插入的新数据
                                    $insertData[] = [
                                        'issueid' => $item['issueid'],
                                        'open_data' => $item['open_data'],
                                        'p1' => $item['p1'],
                                        'p2' => $item['p2'],
                                        'p3' => $item['p3'],
                                        'p4' => $item['p4'],
                                        'p5' => $item['p5'],
                                        'open_time' => $item['open_time']
                                    ];
                                    $result['status'] = 'success';
                                    $result['error_msg'] = '新增成功';
                                }
                            } catch (\Exception $e) {
                                $result['error_msg'] = $e->getMessage();
                            }
                            
                            $results[] = $result;
                        }
                        
                        // 批量插入数据
                        if(!empty($insertData)) {
                            try {
                                foreach ($insertData as $data) {
                                    self::$model::create($data);
                                    $successCount++;
                                }
                            } catch (\Exception $e) {
                                // 记录错误信息
                                Log::error('批量插入失败：' . $e->getMessage());
                                
                                // 更新相关结果状态
                                foreach($results as &$result) {
                                    if($result['status'] === 'success' && $result['error_msg'] === '新增成功') {
                                        $result['status'] = 'failed';
                                        $result['error_msg'] = '插入失败：' . $e->getMessage();
                                    }
                                }
                                $successCount = 0;
                            }
                        }
                        
                        return json([
                            'code' => 1, 
                            'msg' => "成功导入 {$successCount} 条数据",
                            'data' => $results
                        ])->contentType('application/json');
                }
            } catch (\Exception $e) {
                return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()])->contentType('application/json');
            }
        }
        return $this->fetch();
    }
}