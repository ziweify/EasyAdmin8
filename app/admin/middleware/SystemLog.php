<?php

namespace app\admin\middleware;

use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\MiddlewareAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use app\admin\service\SystemLogService;
use app\common\traits\JumpTrait;
use app\Request;
use Closure;
use ReflectionException;

class SystemLog
{
    use JumpTrait;

    /**
     * 敏感信息字段，日志记录时需要加密
     * @var array
     */
    protected array $sensitiveParams = [
        'password',
        'password_again',
        'phone',
        'mobile',
    ];

    /**
     * @throws ReflectionException
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (!env('APP_ADMIN_SYSTEM_LOG', true)) return $response;
        $params = $request->param();
        if (isset($params['s'])) unset($params['s']);
        foreach ($params as $key => $val) {
            in_array($key, $this->sensitiveParams) && $params[$key] = "***********";
        }
        $method = strtolower($request->method());
        $url    = $request->url();

        if (env('APP_DEBUG')) {
            trace(['url' => $url, 'method' => $method, 'params' => $params,], 'requestDebugInfo');
        }
        if ($request->isAjax()) {
            if (in_array($method, ['post', 'put', 'delete'])) {

                $title = '';
                try {
                    $pathInfo    = $request->pathinfo();
                    $pathInfoExp = explode('/', $pathInfo);
                    $_action     = end($pathInfoExp) ?? '';
                    $pathInfoExp = explode('.', $pathInfoExp[0] ?? '');
                    $_name       = $pathInfoExp[0] ?? '';
                    $_controller = ucfirst($pathInfoExp[1] ?? '');
                    $className   = $_controller ? "app\admin\controller\\{$_name}\\{$_controller}" : "app\admin\controller\\{$_name}";
                    if ($_name && $_action) {
                        $reflectionMethod = new \ReflectionMethod($className, $_action);
                        $attributes       = $reflectionMethod->getAttributes(MiddlewareAnnotation::class);
                        foreach ($attributes as $attribute) {
                            $annotation = $attribute->newInstance();
                            $_ignore    = (array)$annotation->ignore;
                            if (in_array('log', array_map('strtolower', $_ignore))) return $response;
                        }
                        $controllerTitle      = $nodeTitle = '';
                        $controllerAttributes = (new \ReflectionClass($className))->getAttributes(ControllerAnnotation::class);
                        $actionAttributes     = $reflectionMethod->getAttributes(NodeAnnotation::class);
                        foreach ($controllerAttributes as $controllerAttribute) {
                            $controllerAnnotation = $controllerAttribute->newInstance();
                            $controllerTitle      = $controllerAnnotation->title ?? '';
                        }
                        foreach ($actionAttributes as $actionAttribute) {
                            $actionAnnotation = $actionAttribute->newInstance();
                            $nodeTitle        = $actionAnnotation->title ?? '';
                        }
                        $title = $controllerTitle . ' - ' . $nodeTitle;
                    }
                }catch (\Throwable $exception) {
                }

                $ip = $request->ip();
                // 限制记录的响应内容，避免过大
                $_response = json_encode($response->getData(), JSON_UNESCAPED_UNICODE);
                $_response = mb_substr($_response, 0, 3000, 'utf-8');

                $data = [
                    'admin_id'    => session('admin.id'),
                    'title'       => $title,
                    'url'         => $url,
                    'method'      => $method,
                    'ip'          => $ip,
                    'content'     => json_encode($params, JSON_UNESCAPED_UNICODE),
                    'response'    => $_response,
                    'useragent'   => $request->server('HTTP_USER_AGENT'),
                    'create_time' => time(),
                ];
                SystemLogService::instance()->save($data);
            }
        }
        return $response;
    }
}