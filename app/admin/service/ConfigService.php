<?php

namespace app\admin\service;

use think\facade\Cache;

class ConfigService
{

    public static function getVersion()
    {
        $version = cache('site_version');
        if (empty($version)) {
            $version = sysConfig('site', 'site_version');
            cache('site_version', $version);
        }
        return $version;
    }

}