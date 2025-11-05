<?php
/**
 * Plugin Name: Decimal Year Increment ID Generator
 * Plugin URI: https://github.com/YOURLS/YOURLS
 * Description: 生成基于年份后两位和十进制递增的自定义短链接ID
 * Version: 1.0
 * Author: 69伙伴

 */

// 避免直接访问
defined('YOURLS_ABSPATH') || exit;

/**
 * 十进制年份递增ID生成器
 * 
 * 使用PHP 8.4特性：
 * - 属性类型声明
 * - 构造函数属性提升
 * - 只读属性
 * - 匹配表达式
 */
class DecimalYearIncrementIDGenerator
{
    private readonly string $optionName;
    private readonly string $currentYear;
    private array $counterData;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->optionName = 'decimal_year_increment_counter';
        $this->currentYear = date("y");
        $this->loadCounterData();
    }
    
    /**
     * 加载计数器数据
     */
    private function loadCounterData(): void
    {
        $defaultData = [
            'year' => $this->currentYear,
            'counter' => 0
        ];
        
        $savedData = yourls_get_option($this->optionName);
        $this->counterData = $savedData ?: $defaultData;
        
        // 如果年份改变，重置计数器
        if ($this->counterData['year'] !== $this->currentYear) {
            $this->counterData = $defaultData;
            $this->saveCounterData();
        }
    }
    
    /**
     * 保存计数器数据
     */
    private function saveCounterData(): void
    {
        yourls_update_option($this->optionName, $this->counterData);
    }
    
    /**
     * 生成下一个ID
     */
    public function generateNextID(): string
    {
        $this->counterData['counter']++;
        $this->saveCounterData();
        
        return $this->currentYear . $this->counterData['counter'];
    }
    
    /**
     * 获取当前计数器状态
     */
    public function getCounterStatus(): array
    {
        return [
            'current_id' => $this->currentYear . $this->counterData['counter'],
            'next_id' => $this->currentYear . ($this->counterData['counter'] + 1),
            'year' => '20' . $this->currentYear,
            'counter_value' => $this->counterData['counter']
        ];
    }
}

// 初始化生成器实例
$decimalIDGenerator = new DecimalYearIncrementIDGenerator();

/**
 * 自定义短链接ID生成过滤器
 */
yourls_add_filter('random_keyword', function($keyword) use ($decimalIDGenerator) {
    return $decimalIDGenerator->generateNextID();
});

/**
 * 添加管理页面
 */
yourls_add_action('plugins_loaded', function() use ($decimalIDGenerator) {
    yourls_register_plugin_page('decimal_id_generator', 'Decimal ID Generator', function() use ($decimalIDGenerator) {
        $status = $decimalIDGenerator->getCounterStatus();
        
        echo <<<HTML
        <div class="wrap">
            <h2>Decimal Year Increment ID Generator</h2>
            <div class="notice notice-info">
                <p><strong>当前状态:</strong></p>
                <ul>
                    <li>当前年份: {$status['year']}</li>
                    <li>当前ID: <code>{$status['current_id']}</code></li>
                    <li>下一个ID: <code>{$status['next_id']}</code></li>
                    <li>计数器值: {$status['counter_value']}</li>
                </ul>
            </div>
            <div class="notice notice-warning">
                <p><strong>ID生成规则:</strong></p>
                <ul>
                    <li>格式: 年份后两位 + 十进制递增数字</li>
                    <li>示例: 218 → 219 → 2110 → 2111</li>
                    <li>新年自动重置: 23年 → 24年 (240开始)</li>
                </ul>
            </div>
        </div>
HTML;
    });
});

/**
 * 插件激活时的初始化
 */
yourls_add_action('activated_decimal-year-increment-id-generator/plugin.php', function() {
    $initialData = [
        'year' => date("y"),
        'counter' => 0
    ];
    
    if (!yourls_get_option('decimal_year_increment_counter')) {
        yourls_add_option('decimal_year_increment_counter', $initialData);
    }
});

/**
 * 插件停用时的清理（可选）
 */
yourls_add_action('deactivated_decimal-year-increment-id-generator/plugin.php', function() {
    // 可以选择保留数据以便重新激活时继续使用
    // yourls_delete_option('decimal_year_increment_counter');
});

/**
 * 提供API端点获取计数器状态
 */
yourls_add_action('api_decimal_id_status', function() use ($decimalIDGenerator) {
    $status = $decimalIDGenerator->getCounterStatus();
    
    return [
        'status' => 'success',
        'code' => 200,
        'message' => 'Decimal ID Generator status',
        'data' => $status
    ];
});

// 演示使用PHP 8.4的匹配表达式进行ID验证
yourls_add_filter('custom_keyword', function($keyword) {
    // 使用匹配表达式验证ID格式
    $result = match(true) {
        empty($keyword) => 'empty',
        !preg_match('/^\d{2,}$/', $keyword) => 'invalid_format',
        strlen($keyword) > 50 => 'too_long',
        default => 'valid'
    };
    
    if ($result !== 'valid') {
        yourls_add_notice("ID格式无效: $result");
    }
    
    return $keyword;
});

?>
