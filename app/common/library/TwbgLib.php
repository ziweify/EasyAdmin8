<?php

namespace app\common\library;

class TwbgLib
{
    /**
     * 通过期号计算开奖时间
     * @param string $issueId 期号
     * @return int 开奖时间戳
     */
    public static function calculateOpenTime(string $issueId): int
    {
        // TODO: 这里实现真实的计算逻辑
        // 目前随机返回最近24小时内的时间戳
        return time() - rand(0, 86400);
    }

    /**
     * 解析开奖数据字符串，提取前5个数字
     * @param string $openData 开奖数据字符串
     * @return array [p1,p2,p3,p4,p5]
     */
    public static function parseOpenData(string $openData): array
    {
        // 移除所有空格和双引号
        $openData = str_replace([' ', '"'], '', $openData);
        // 分割数字
        $numbers = explode(',', $openData);
        $result = [];
        
        // 处理前5个数字
        $count = 0;
        foreach ($numbers as $number) {
            if ($count >= 5) break;
            
            // 清理数字
            $number = trim($number);
            // 确保是两位数格式
            if (strlen($number) == 1) {
                $number = '0' . $number;
            }
            // 验证是否为有效数字
            if (!preg_match('/^\d{2}$/', $number)) {
                $number = '00';
            }
            
            $result[] = $number;
            $count++;
        }
        
        // 确保有5个数字
        while (count($result) < 5) {
            $result[] = '00';
        }
        
        return $result;
    }
}