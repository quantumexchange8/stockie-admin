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
        free_item: "",
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
