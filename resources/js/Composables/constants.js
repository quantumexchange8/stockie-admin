// constants.js

// ----------------------------------------------------------------------------------
//                                  MENU MANAGEMENT
// ----------------------------------------------------------------------------------
export const keepOptions = [
    { text: 'Keep is allowed', value: 'Active' },
    { text: 'Keep is not allowed', value: 'Inactive' },
];

export const redeemOptions = [
    { text: 'Redemption is allowed', value: true },
    { text: 'Redemption is not allowed', value: false },
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
    {
        text: "Discount (Amount)",
        value: "Discount (Amount)",
    },
    {
        text: "Discount (Percentage)",
        value: "Discount (Percentage)",
    },
    {
        text: "Bonus Point",
        value: "Bonus Point",
    },
    {
        text: "Free Item",
        value: "Free Item",
    },
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
    }
};

export const defaultPointItem = {
    inventory_item_id: null,
    item_qty: 1,
    inventory_stock_qty: 0,
};

// ----------------------------------------------------------------------------------
//                                  TABLE & ROOM
// ----------------------------------------------------------------------------------

export const tableType = [
    { text: 'Table', value: 'table' },
    { text: 'Room', value: 'room' },
];

// ----------------------------------------------------------------------------------
//                                  Reservation
// ----------------------------------------------------------------------------------

export const cancelTypes = [
    { text: 'Change of plan', value: 'Change of plan' },
    { text: 'Feeling unwell', value: 'Feeling unwell' },
    { text: 'Bad weather', value: 'Bad weather' },
    { text: 'Work conflicts', value: 'Work conflicts' },
    { text: 'Family emergency', value: 'Family emergency' },
    { text: 'Forgotten reservation', value: 'Forgotten reservation' },
    { text: 'Others (specify under Remarks)', value: 'Others (specify under Remarks)' },
];

// ----------------------------------------------------------------------------------
//                                  Order Keep Items
// ----------------------------------------------------------------------------------

export const expiryDates = [
    { text: "1 month", value: 1 },
    { text: "2 months", value: 2 },
    { text: "3 months", value: 3 },
    { text: "4 months", value: 4 },
    { text: "5 months", value: 5 },
    { text: "6 months", value: 6 },
]

export const deleteReason = [
    { text: "Incorrect information", value: 'Incorrect information'},
    { text: "Duplicate entry", value: 'Duplicate entry'},
    { text: "Customer asked to delete", value: 'Customer asked to delete'},
    { text: "Others (specify under Remarks)", value: 'Others'},
]

// ----------------------------------------------------------------------------------
//                                  Admin Users
// ----------------------------------------------------------------------------------

export const permissionList = [
    { text: "Dashboard", value: 'dashboard' },
    { text: "Order Management", value: 'order-management' },
    { text: "Shift Control", value: 'shift-control' },
    { text: "Shift Record", value: 'shift-record' },
    { text: "Menu Management", value: 'menu-management' },
    { text: "Inventory", value: 'inventory' },
    { text: "Waiter", value: 'waiter' },
    { text: "Customer", value: 'customer' },
    { text: "Table & Room", value: 'table-room' },
    { text: "Reservation", value: 'reservation' },
    { text: "Transaction Listing", value: 'transcation-listing' },
    { text: "e-Invoice Submission", value: 'e-invoice-submission' },
    { text: "Loyalty Programme", value: 'loyalty-programme' },
    { text: "Admin User", value: 'admin-user' },
    { text: "Sales Analysis", value: 'sales-analysis' },
    { text: "Activity Logs", value: 'activity-logs' },
    { text: "Configuration", value: 'configuration' },
]