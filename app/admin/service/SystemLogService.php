<?php

namespace app\admin\service;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Config;
use think\facade\Env;

/**
 * 系统日志表
 * Class SystemLogService
 * @package app\admin\service
 */
class SystemLogService
{

    protected static ?SystemLogService $instance = null;

    /**
     * 表前缀
     * @var string
     */
    protected string $tablePrefix;

    /**
     * 表后缀
     * @var string
     */
    protected string $tableSuffix;

    /**
     * 表名
     * @var string
     */
    protected string $tableName;

    /**
     * 构造方法
     * SystemLogService constructor.
     */
    protected function __construct()
    {
        $this->tablePrefix = Config::get('database.connections.mysql.prefix');
        $this->tableSuffix = date('Ym', time());
        $this->tableName   = "{$this->tablePrefix}system_log_{$this->tableSuffix}";
    }

    /**
     * 获取实例对象
     * @return SystemLogService
     */
    public static function instance(): SystemLogService
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }


    /**
     * 保存数据
     * @param $data
     * @return bool|string
     */
    public function save($data): bool|string
    {
        Db::startTrans();
        try {
            $this->detectTable();
            Db::table($this->tableName)->strict(false)->insert($data);
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
        return true;
    }

    /**
     * 检测数据表
     * @return bool
     */
    public function detectTable(): bool
    {
        $_key = "system_log_{$this->tableName}_table";
        // 手动删除日志表时候 记得清除缓存
        $isset = Cache::get($_key);
        if ($isset) return true;
        $check = Db::query("show tables like '{$this->tableName}'");
        if (empty($check)) {
            $sql = $this->getCreateSql();
            Db::execute($sql);
        }
        Cache::set($_key, !empty($check));
        return true;
    }

    public function getAllTableList()
    {

    }

    /**
     * 根据后缀获取创建表的sql
     * @return string
     */
    protected function getCreateSql(): string
    {
        return <<<EOT
CREATE TABLE `{$this->tableName}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) unsigned DEFAULT '0' COMMENT '管理员ID',
  `url` varchar(1500) NOT NULL DEFAULT '' COMMENT '操作页面',
  `method` varchar(50) NOT NULL COMMENT '请求方法',
  `title` varchar(100) DEFAULT '' COMMENT '日志标题',
  `content` json NOT NULL COMMENT '请求数据',
  `response` json DEFAULT NULL COMMENT '回调数据',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) DEFAULT '' COMMENT 'User-Agent',
  `create_time` int(10) DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=COMPACT COMMENT='后台操作日志表 - {$this->tableSuffix}';
EOT;
    }

}
