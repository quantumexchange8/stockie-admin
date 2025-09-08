// constants.js

// ----------------------------------------------------------------------------------
//                                  MENU MANAGEMENT
// ----------------------------------------------------------------------------------
export const keepOptions = [
    { text: 'public.keep_allowed', value: 'Active' },
    { text: 'public.keep_not_allowed', value: 'Inactive' },
];

export const redeemOptions = [
    { text: 'public.redemption_allowed', value: true },
    { text: 'public.redemption_not_allowed', value: false },
];

export const defaultProductItem = {
    inventory_item_id: null,
    formatted_item_name: '',
    qty: 1,
    inventory_stock_qty: 0,
};

// ----------------------------------------------------------------------------------
//                                  INVENTORY
// ----------------------------------------------------------------------------------
export const defaultInventoryItem = {
    item_name: '',
    item_code: '',
    item_cat_id: '',
    stock_qty: '',
    low_stock_qty: '',
    keep: '',
    status: 'In stock',
};

// ----------------------------------------------------------------------------------
//                                  WAITER
// ----------------------------------------------------------------------------------
export const employementTypeOptions = [
    { text: 'public.waiter.full_time', value: 'Full-time' },
    { text: 'public.waiter.part_time', value: 'Part-time' },
];

// ----------------------------------------------------------------------------------
//                                  LOYALTY PROGRAMME
// ----------------------------------------------------------------------------------
export const periodOption = [
    { text: "1 month starting from entry date", value: 1 },
    { text: "3 months starting from entry date", value: 3 },
    { text: "6 months starting from entry date", value: 6 },
    { text: "12 months starting from entry date", value: 12 },
    { text: "Customise range...", value: 0 },
];

export const rewardOption = [
    { text: "public.amount_discount", value: "Discount (Amount)" },
    { text: "public.percent_discount", value: "Discount (Percentage)" },
    { text: "public.bonus_point", value: "Bonus Point" },
    { text: "public.free_item", value: "Free Item" },
];

export const emptyReward = () => {
    return {    
        reward_type: "",
        min_purchase: "inactive",
        discount: "",
        min_purchase_amount: "",
        bonus_point: "",
        free_item: null,
        item_qty: '',
        date_range: "",
        valid_period: "",
        valid_period_from: "",
        valid_period_to: "",
        status: "Active",
    }
};

export const defaultPointItem = {
    inventory_item_id: null,
    item_qty: 1,
    inventory_stock_qty: 0,
};

// ----------------------------------------------------------------------------------
//                                  Order
// ----------------------------------------------------------------------------------

// Keep Items
export const expiryDates = [
    { text: "1 month", value: 1 },
    { text: "2 months", value: 2 },
    { text: "3 months", value: 3 },
    { text: "4 months", value: 4 },
    { text: "5 months", value: 5 },
    { text: "6 months", value: 6 },
]

export const deleteReason = [
    { text: "public.incorrect_info", value: 'Incorrect information'},
    { text: "public.duplicate_entry", value: 'Duplicate entry'},
    { text: "public.customer_asked_delete", value: 'Customer asked to delete'},
    { text: "public.others_specify_remark", value: 'Others'},
]

// ----------------------------------------------------------------------------------
//                                  Admin Users
// ----------------------------------------------------------------------------------

export const permissionList = [
    { text: "public.dashboard_header", value: 'dashboard' },
    { text: "public.order_management_header", value: 'order-management' },
    { text: "public.shift_control_header", value: 'shift-control' },
    { text: "public.shift_record_header", value: 'shift-record' },
    { text: "public.menu_management_header", value: 'menu-management' },
    { text: "public.all_report_header", value: 'all-report' },
    { text: "public.inventory_header", value: 'inventory' },
    { text: "public.waiter_header", value: 'waiter' },
    { text: "public.customer_header", value: 'customer' },
    { text: "public.table_room_header", value: 'table-room' },
    { text: "public.reservation_header", value: 'reservation' },
    { text: "public.transaction_listing_header", value: 'transaction-listing' },
    { text: "public.einvoice_submission_header", value: 'einvoice-submission' },
    { text: "public.loyalty_programme_header", value: 'loyalty-programme' },
    { text: "public.admin_user_header", value: 'admin-user' },
    { text: "public.sales_analysis_header", value: 'sales-analysis' },
    { text: "public.activity_logs_header", value: 'activity-logs' },
    { text: "public.configuration_header", value: 'configuration' },
    { text: "public.action.free_up_table", value: 'free-up-table' },
]

// ----------------------------------------------------------------------------------
//                                  CONFIG PRINTER
// ----------------------------------------------------------------------------------
export const kickCashDrawerOptions = [
    { text: 'public.configuration.enable_cash_drawer', value: true },
    { text: 'public.configuration.disable_cash_drawer', value: false },
];
