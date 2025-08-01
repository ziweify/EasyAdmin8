<?php

namespace app\admin\service\auth;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use app\admin\service\tool\CommonTool;
use ReflectionException;

/**
 * 节点处理类
 * Class Node
 * @package EasyAdmin\auth
 */
class Node
{

    /**
     * @var string 当前文件夹
     */
    protected string $basePath;

    /**
     * @var string 命名空间前缀
     */
    protected string $baseNamespace;

    /**
     * 构造方法
     * Node constructor.
     * @param string $basePath 读取的文件夹
     * @param string $baseNamespace 读取的命名空间前缀
     */
    public function __construct(string $basePath, string $baseNamespace)
    {
        $this->basePath      = $basePath;
        $this->baseNamespace = $baseNamespace;
        return $this;
    }

    /**
     * 获取所有节点
     * @return array
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function getNodeList(): array
    {
        list($nodeList, $controllerList) = [[], $this->getControllerList()];

        if (!empty($controllerList)) {
            AnnotationRegistry::loadAnnotationClass('class_exists');
            $parser = new DocParser();
            $parser->setIgnoreNotImportedAnnotations(true);
            $reader = new AnnotationReader($parser);

            foreach ($controllerList as $controllerFormat => $controller) {

                // 获取类和方法的注释信息
                $reflectionClass = new \ReflectionClass($controller);
                $methods         = $reflectionClass->getMethods();
                $actionList      = [];

                // 遍历读取所有方法的注释的参数信息
                foreach ($methods as $method) {

                    // 忽略掉不需要的节点
                    $property           = $reflectionClass->getProperty('ignoreNode');
                    $propertyAttributes = $property->getAttributes(NodeAnnotation::class);
                    if (!empty($propertyAttributes[0])) {
                        $propertyAttribute = $propertyAttributes[0]->newInstance();
                        if (in_array($method->name, $propertyAttribute->ignore)) continue;
                    }

                    $attributes = $reflectionClass->getMethod($method->name)->getAttributes(NodeAnnotation::class);
                    foreach ($attributes as $attribute) {
                        $annotation = $attribute->newInstance();
                        if (!empty($annotation->ignore)) if (strtolower($annotation->ignore) == 'node') continue;
                        $actionList[] = [
                            'node'    => $controllerFormat . '/' . $method->name,
                            'title'   => $annotation->title ?? null,
                            'is_auth' => $annotation->auth ?? false,
                            'type'    => 2,
                        ];
                    }
                }
                // 方法非空才读取控制器注解
                if (!empty($actionList)) {
                    // 读取Controller的注解
                    $attributes = $reflectionClass->getAttributes(ControllerAnnotation::class);
                    foreach ($attributes as $attribute) {
                        $controllerAnnotation = $attribute->newInstance();
                        $nodeList[]           = [
                            'node'    => $controllerFormat,
                            'title'   => $controllerAnnotation->title ?? null,
                            'is_auth' => $controllerAnnotation->auth ?? false,
                            'type'    => 1,
                        ];
                    }
                    $nodeList = array_merge($nodeList, $actionList);
                }

            }
        }
        return $nodeList;
    }

    /**
     * 获取所有控制器
     * @return array
     */
    public function getControllerList(): array
    {
        return $this->readControllerFiles($this->basePath);
    }

    /**
     * 遍历读取控制器文件
     * @param $path
     * @return array
     */
    protected function readControllerFiles($path): array
    {
        list($list, $temp_list, $dirExplode) = [[], scandir($path), explode($this->basePath, $path)];
        $middleDir = !empty($dirExplode[1]) ? str_replace('/', '\\', substr($dirExplode[1], 1)) . "\\" : '';

        foreach ($temp_list as $file) {
            // 排除根目录和没有开启注解的模块
            if ($file == ".." || $file == ".") {
                continue;
            }
            if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                // 子文件夹，进行递归
                $childFiles = $this->readControllerFiles($path . DIRECTORY_SEPARATOR . $file);
                $list       = array_merge($childFiles, $list);
            }else {
                // 判断是不是控制器
                $fileExplodeArray = explode('.', $file);
                if (count($fileExplodeArray) != 2 || end($fileExplodeArray) != 'php') {
                    continue;
                }
                // 根目录下的文件
                $className               = str_replace('.php', '', $file);
                $controllerFormat        = str_replace('\\', '.', $middleDir) . CommonTool::humpToLine(lcfirst($className));
                $list[$controllerFormat] = "{$this->baseNamespace}\\{$middleDir}" . $className;
            }
        }
        return $list;
    }

}