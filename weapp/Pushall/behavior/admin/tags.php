<?php

// 应用行为扩展定义文件
return array(
    // 模块初始化
    'module_init'  => array(
        'weapp\\Pushall\\behavior\\admin\\PushallBehavior',
    ),
    // 操作开始执行
    'action_begin' => array(
        'weapp\\Pushall\\behavior\\admin\\PushallBehavior',
    ),
    // 视图内容过滤
    'view_filter'  => array(
        'weapp\\Pushall\\behavior\\admin\\PushallBehavior',
    ),
    // 日志写入
    'log_write'    => array(
        'weapp\\Pushall\\behavior\\admin\\PushallBehavior',
    ),
    // 应用结束
    'app_end'      => array(
        'weapp\\Pushall\\behavior\\admin\\PushallBehavior',
    ),
);
