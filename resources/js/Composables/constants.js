// constants.js

// ----------------------------------------------------------------------------------
//                                  MENU MANAGEMENT
// ----------------------------------------------------------------------------------
export const keepOptions = [
    { text: 'Keep is allowed', value: 'Active' },
    { text: 'Keep is not allowed', value: 'Inactive' },
];

export const defaultProductItem = {
    inventory_item_id: null,
    qty: 1,
    inventory_stock_qty: 0,
};

// ----------------------------------------------------------------------------------
//                                  MENU MANAGEMENT
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
        type: "",
        min_purchase: "inactive",
        min_purchase_amount: "",
        amount: "",
        valid_period: "",
        date_range: "",
        valid_period_from: "",
        valid_period_to: "",
        bonus_point: "",
        free_item: "",
        item_qty: 0,
        error: "",
    }
};
