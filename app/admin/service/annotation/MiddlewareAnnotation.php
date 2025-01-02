<?php

namespace app\admin\service\annotation;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
final class MiddlewareAnnotation
{
    /** 过滤日志 */
    const IGNORE_LOG = 'LOG';

    public function __construct(public string $type = '', public string|array $ignore = '')
    {
    }
}