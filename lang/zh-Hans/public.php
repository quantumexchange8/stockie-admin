<?php
return [
    // ========================================================================
    //                      GLOBAL SHARED TERMS
    // ========================================================================

    "custom" => "Custom text here",
    "dashboard_header" => '主页面',
    "order_management_header" => "订单管理",
    "shift_management_header" => "班次管理",
    "shift_control_header" => "Shift Control",
    "shift_record_header" => "Shift Report",
    "menu_management_header" => "菜单管理",
    "all_report_header" => "全部报表",
    "inventory_header" => "库存量",
    "waiter_header" => "服务员",
    "customer_header" => "顾客",
    "table_room_header" => "桌台和厢房",
    "reservation_header" => "预订",
    "transaction_listing_header" => "交易清单",
    "einvoice_submission_header" => "电子发票呈交",
    "loyalty_programme_header" => "忠诚度计划",
    "admin_user_header" => "管理员用户",
    "sales_analysis_header" => "销售分析",
    "activity_logs_header" => "活动日志",
    "configuration_header" => "系统配置",
    "general_header" => "一般操作",
    "operation_header" => "执行操作",
    "sales_management_header" => "销售管理",
    "others_header" => "其它操作",
    "validation_error" => "Whoops! Something went wrong.",
    "all" => "全部",
    "search" => "查找",
    "filter" => "过滤",
    "filter_by" => "过滤条件",
    "date" => "日期",
    "item" => "商品",
    "almost_no_stock" => "几乎售罄 !",
    "is_no_stock" => "已经售罄.",
    "product_affected" => "受影响的产品:",
    "view_stock" => "查看库存量",
    "checked_in" => "已签到",
    "checked_out" => "已签退",
    "no_record_yet" => "暂无记录",
    "checked_in_at" => "签到在",
    "checked_out_at" => "签退在",
    "table_room_activity" => "桌台 / 厢房活动",
    "placed_order_for" => "已下单",
    "customer_checked_in_by" => "负责新顾客登记",
    "assigned" => "指派",
    "to_serve" => "服务",
    "merged_table" => "合并桌台",
    "customer_check_in" => "客户签到",
    "place_order" => "下订单",
    "waiter_assignment" => "指派服务员任务",
    "merge_table" => "桌台合并",
    "transfer_table" => "桌台转移",
    "status" => "状态",
    "na" => "N/A",
    "sales" => "销售",
    "left" => "剩余",
    "left_header" => "剩余",
    "category" => "类别",
    "week" => "周",
    "month" => "月",
    "year" => "年",
    "product_name" => "产品名称",

    // Input fields label name
    "field" => [
        "id" => "账号",
        "password" => "密码",
        "display_name" => "显示名称",
    ],

    // Toast messages
    "toast" => [
        "login" => "您已成功登入管理员后台",
        "logout" => "您已成功退出管理员后台",
        "changes_saved" => "更改已保存.",
        "update_profile_image" => "您已成功更改个人资料图片.",
    ],

    // Action buttons' label
    "action" => [
        "add" => "添加",
        "apply" => "引用",
        "cancel" => "取消",
        "change_image" => "更换图像",
        "clear" => "Clear",
        "clear_all" => "清除所有",
        "confirm" => "Confirm",
        "export" => "Export",
        "leave" => "离开",
        "login" => "立即登录",
        "logout" => "登出",
        "next" => "Next",
        "previous" => "Previous",
        "save" => "保存",
        "stay" => "停留",
        "take_me_back" => "带我回去",
        "refresh" => "刷新",
        "go_to_homepage" => "前往主页",
        "check_in_customer" => "办理顾客签到",
    ],

    // Empty states' messages
    "empty" => [
        "no_data" => "尚无数据可显示...",
        "no_notification" => "尚未有通知...",
        "no_result_found" => "我们找不到任何结果...",
        "no_activity" => "尚无活动可显示...",
        "no_reservation" => "You haven't added any reservation yet...",
    ],

    // Error pages' error messages
    "error" => [
        "error_404" => "页面不存在",
        "error_404_message" => "我们似乎找不到您要查找的页面, 请重试或返回上一页.",
        "error_408" => "连接丢失",
        "error_408_message" => "你的网络好像暂停服务了⛱️. 请尝试重新连接你的 Wi-Fi 或刷新页面, 看看是否有帮助.",
        "error_400" => "这不太对",
        "error_400_message" => "您的请求似乎在传输过程中出现问题. 请仔细检查 URL 或重试.",
        "error_401" => "拒绝访问",
        "error_401_message" => "看来您正试图无权限潜入. 请登录或提供正确凭证, 我们将很乐意为您开门.",
        "error_403" => "禁止偷看",
        "error_403_message" => "此区域似乎禁止进入. 请咨询您的管理员, 确认您是否有权访问.",
        "error_429" => "慢点儿！",
        "error_429_message" => "您发送请求的速度太快了, 我们无法跟上! 我们暂时暂停了, 以便喘口气. 请稍等片刻, 稍后再试.",
        "error_503" => "看到我们在整理 🔧",
        "error_503_message" => "我们的开发人员正在努力提升您的使用体验. 请稍事休息, 喝杯咖啡 ☕️, 您的旅程即将继续.",
        "error_500" => "出了点问题!",
        "error_500_message" => "我们的服务器在尝试处理您的请求时出现故障. 我们正在处理!  在此期间, 请随时刷新或稍后再回来.",
        "error_502" => "桥上的问题",
        "error_502_message" => "我们与其他服务器通信时遇到了一些问题. 这不是您的问题,  而是我们的问题. 请稍等片刻, 我们会尽快修复.",
    ],

    // ========================================================================
    // ========================================================================

    
    // ========================================================================
    //                      PAGE-SPECIFIC TERMS
    // ========================================================================

    "login" => [
        "description1" => "立即体验无忧虑库存管理系统的便捷.",
        "description2" => "您的库存得到完善的管理.",
        "id_placeholder" => "输入您的账号",
        "password_placeholder" => "输入您的密码",
    ],
    "profile" => [
        "account_detail" => "账户详情",
        "edit_display_name" => "编辑显示名称",
    ],
    "navbar" => [
        // For the main head titles, refer to global shared terms
        "latest_notification" => "最新通知",
        "view_all_notification" => "查看所有通知",
        "change_language" => "切换语言",
        "logout_message_title" => "你要走了……",
        "logout_message_desc" => "您确定要退出该帐户吗?",
        "all_notification" => "所有通知",
        "notification" => "通知",
    ],
    "notification" => [
        "waiter_attendance" => "服务员签到 / 签退",
    ],
    "dashboard" => [
        "sales_today" => "今日业绩",
        "products_sold_today" => "今日销售的产品",
        "orders_today" => "今日的订购",
        "low_in" => "低库存",
        "active_table_room" => "使用中的桌台和厢房: ",
        "on_duty_today" => "今天值班",
        "product_low_stock" => "产品库存不足",
        "today_reservation" => "今日预订",
        "at_today" => "在今天",
    ],
    "order" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "shift" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "menu" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "report" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "inventory" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "waiter" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "customer" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "table" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "reservation" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "transaction" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "einvoice" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "loyalty" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "admin" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "analysis" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "logs" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],
    "configuration" => [
        "cryptocurrency_market_price" => "Cryptocurrency Market Price",
    ],

    // ========================================================================
    // ========================================================================
];