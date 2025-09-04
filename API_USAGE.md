# API统计系统使用说明

## 功能概述

本系统实现了完整的API调用统计功能，包括：
1. 用户登录时生成RSA密钥对和API Token
2. 记录详细的登录日志到系统日志表
3. 使用AOP方式统计API调用量
4. Redis缓存用户Token信息
5. 提供API统计查询接口

## 数据库设计

### 1. API统计表 (ea8_api_stats)
- 记录每日API调用统计数据
- 包含总调用次数、成功次数、失败次数、平均响应时间等

### 2. API日志表 (ea8_api_logs)
- 记录每次API调用的详细信息
- 包含请求参数、响应数据、响应时间等

### 3. 系统日志表 (ea8_system_log)
- 记录用户登录等系统操作日志

## AOP统计实现详解

### 1. 运行机制
本系统使用 **中间件模式** 实现AOP功能，通过ThinkPHP的中间件机制自动拦截所有API调用：

```php
protected $middleware = [
    'app\common\middleware\ApiStats',      // 统计中间件 - 拦截所有方法
    'app\common\middleware\ApiAuth' => ['except' => ['login', 'index']]  // 认证中间件
];
```

### 2. 统计覆盖范围
✅ **所有API方法都被自动统计**，包括：
- `index()` - 基本测试接口
- `test()` - 测试方法  
- `login()` - 用户登录接口
- `getUser()` - 获取用户信息
- `getDate()` - 获取日期时间
- `getSystemInfo()` - 获取系统信息
- `getBslotteryTwbg()` - 获取宾果开奖数据
- `getApiStats()` - 获取API调用统计
- `getApiLogs()` - 获取API调用日志
- `testApiStats()` - 测试API统计功能
- `getApiList()` - 获取API接口列表

### 3. AOP执行流程
```
请求到达 → ApiStats中间件拦截 → 记录开始时间 → 
执行API方法 → 记录结束时间 → 计算响应时间 → 
写入统计数据 → 返回响应
```

### 4. 组件说明
1. **ApiStatsAspect** - 切面类，负责统计逻辑
2. **ApiStats** - 中间件，拦截器实现
3. **ApiAuth** - 认证中间件，验证API Token

### 5. 数据记录内容
每次API调用自动记录：
- 用户ID和用户名
- API方法名
- 请求参数和响应数据
- 响应时间（毫秒）
- IP地址和User Agent
- 成功/失败状态

### 登录流程增强
1. 生成RSA密钥对（2048位）
2. 记录登录IP和时间
3. 生成API Token = md5(用户ID + 私钥)
4. 存储Redis数据（2小时过期）
5. 记录详细登录日志

## API接口

### 1. 登录接口
```
POST /api/gdds/login
参数: name=用户名&pwd=密码
返回: {
  "code": 200,
  "message": "登录成功",
  "data": {
    "user_id": 1,
    "name": "用户名",
    "api_token": "生成的token"
  }
}
```

### 2. 获取API统计
```
GET /api/gdds/getApiStats?date=2024-03-01
Header: api-token: 你的token
返回: 当日API调用统计数据
```

### 3. 获取API日志
```
GET /api/gdds/getApiLogs?date=2024-03-01&page=1&limit=20
Header: api-token: 你的token
返回: API调用日志列表
```

## Redis数据结构

### Token数据 (键名: api_token值)
```json
{
  "id": 1,
  "username": "用户名",
  "api_private_key": "RSA私钥",
  "api_public_key": "RSA公钥",
  "login_time": 1710000000,
  "login_ip": "192.168.1.1",
  "err": 0,
  "expire_time": 1710007200
}
```

### 用户Token映射 (键名: user_token:用户ID)
```
值: api_token值
```

## 部署说明

1. 执行SQL文件创建相关表：
```bash
mysql -u root -p < database/api_stats.sql
```

2. 确保Redis服务正常运行

3. 在控制器中应用中间件：
```php
protected $middleware = [
    'app\common\middleware\ApiStats',
    'app\common\middleware\ApiAuth' => ['except' => ['login', 'index']]
];
```

## 性能优化

1. **Redis缓存** - 统计数据先写入Redis，定期同步到数据库
2. **异步处理** - 日志记录采用异步方式，不影响API响应
3. **索引优化** - 数据库表添加了必要的索引
4. **批量更新** - 统计数据支持批量更新机制

## 监控建议

1. 定期清理过期的API日志数据
2. 监控Redis内存使用情况
3. 设置API调用频率限制
4. 定期备份统计数据