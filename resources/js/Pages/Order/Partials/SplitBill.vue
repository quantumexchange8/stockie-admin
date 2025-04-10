<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Modal from '@/Components/Modal.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import Tag from '@/Components/Tag.vue';
import SearchBar from '@/Components/SearchBar.vue';
import RadioButton from '@/Components/RadioButton.vue';
import { useCustomToast } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { MovingIllus, UndetectableIllus } from '@/Components/Icons/illus';
import PayBillForm from './PayBillForm.vue';

const props = defineProps({
    currentOrder: Object,
    currentTable: Object,
    splitBillsState: Object,
    currentSplitBillMode: Boolean,
    currentHasVoucher: Boolean,
});

const { showMessage } = useCustomToast();
const emit = defineEmits(['closeModal', 'isDirty', 'closeAll', 'payBill', 'updateState']);

const initialState = ref(props.splitBillsState);
const selectedItems = ref([]);
const isCustomerModalOpen = ref(false);
const searchQuery = ref('');
const selectedCustomer = ref('');
const sourceTable = ref('');
const sourceTableId = ref(null);
const selectedTargetBillId = ref(null);
const initialCustomerList = ref([]);
const customerList = ref([]);
const payBillFormIsOpen = ref(false);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);
const isSplitBillMode = ref(props.currentSplitBillMode);
const hasVoucher = ref(props.currentHasVoucher);
const removeRewardFormIsOpen = ref(false);
const selectedBill = ref('');

const getAllCustomers = async () => {
    try {
        const response = await axios.get(route('customer.all-customers'));
        initialCustomerList.value = response.data;
        customerList.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        
    }
};

const calculateBillTotal = (bill) => {
  return bill.order_items.reduce((total, item) => {
    return total + (item.amount * (item.balance_qty / item.original_qty));
  }, 0);
};

// Process and initialize order items with transfer properties
const processOrderItems = (orderTables) => {
    const latestOrder = orderTables
        .sort((a, b) => b.id - a.id)
        .find(orderTable => 
            orderTable.status !== 'Order Cancelled' && 
            orderTable.status !== 'Pending Clearance' &&
            orderTable.status !== 'Order Voided'
        )?.order;

    if (!latestOrder) return [];

    return latestOrder.order_items
        ?.filter(item => item.status !== 'Cancelled')
        .sort((a, b) => (a.product_name > b.product_name ? 1 : -1))
        .map(item => ({
            ...item,
            transfer_qty: item.item_qty,
            balance_qty: item.item_qty,
            original_qty: item.item_qty,
            transfer_status: '',
            selected: false,
            order_id: latestOrder.id
        }));
};

const initialCurrentBill = ref({
    id: 'current',
    table_id: props.currentTable.id,
    order_id: props.currentTable.order_id,
    status: props.currentTable.status,
    pax: props.currentTable.order_tables[0].pax,
    tables: props.currentTable.order_tables.map(ot => ot.table),
    customer: props.currentOrder.customer,
    customer_id: props.currentOrder.customer_id,
    order_items: processOrderItems(props.currentTable.order_tables),
});

const form = useForm({
    splitType: 'split-bill',
    currentBill: {
        ...(initialState.value?.currentBill || initialCurrentBill.value),
        amount: calculateBillTotal(initialState.value?.currentBill || initialCurrentBill.value), // Initialize totalAmount
        total_amount: calculateBillTotal(initialState.value?.currentBill || initialCurrentBill.value) // PayBillForm purposes
    },
    splitBills: initialState.value?.splitBills?.map(bill => ({
        ...bill,
        amount: calculateBillTotal(bill),
        total_amount: calculateBillTotal(bill) // PayBillForm purposes
    })) || [],
});

// Add a new bill
const addBill = () => {
    form.splitBills.push({
        id: Date.now(), // Temporary ID for tracking
        status: props.currentTable.status,
        tables: props.currentTable.order_tables.map(ot => ot.table),
        customer: props.currentOrder.customer,
        customer_id: props.currentOrder.customer_id,
        // new_customer: null,
        order_items: [],
        amount: 0,
        total_amount: 0 // PayBillForm purposes
    });
};

// Remove a bill
const removeBill = (index) => {
    // Move items back to current bill before removing
    form.splitBills[index].order_items.forEach(item => {
        const currentItem = form.currentBill.order_items.find(i => i.id === item.id);
        if (currentItem) {
            currentItem.balance_qty += item.balance_qty;
            currentItem.transfer_qty = currentItem.balance_qty;
        } else {
            form.currentBill.order_items.push({
                ...item,
                balance_qty: item.balance_qty,
                transfer_qty: item.balance_qty,
                selected: false,
                amount: 0
            });
        }
    });
    
    form.splitBills.splice(index, 1);
    // emit('isDirty', true);
};

const selectItem = (item, billId, sourceChange = 'check') => {
    sourceTable.value = billId !== 'current' ? 'target' : 'current';
    sourceTableId.value = billId !== 'current' ? billId : null;

    if (sourceChange === 'check'){
        item.selected = !item.selected;
        if (item.selected) {
            selectedItems.value.push(item);
        } else {
            selectedItems.value = selectedItems.value.filter(selected => selected.id !== item.id);
            sourceTable.value = '';
            sourceTableId.value = null;
        };

    } else {
        if (!item.selected) {
            if (item.transfer_qty !== item.balance_qty) {
                selectedItems.value.push(item);
                item.selected = true;
            } else {
                item.selected = false;
                selectedItems.value = selectedItems.value.filter(selected => selected.id !== item.id);
                sourceTable.value = '';
                sourceTableId.value = null;
            };
        };
    };
};

const moveItems = (billId = null) => {
    const source = sourceTable.value === 'target' 
        ? form.splitBills.find(bill => bill.id === sourceTableId.value)
        : form.currentBill;

    const destination = billId !== 'current'
        ? form.splitBills.find(bill => bill.id === billId)
        : form.currentBill;

    // Only process selected items
    selectedItems.value.forEach(item => {
        const transferQty = item.transfer_qty;
        if (transferQty <= 0) return;

        // Find or create item in destination bill
        let destinationItem = destination.order_items.find(i => i.id === item.id);
        
        if (destinationItem) {
            // Update existing item
            destinationItem.balance_qty += transferQty;
            destinationItem.transfer_qty = destinationItem.balance_qty;
        } else {
            // Create new item in destination
            destinationItem = {
                ...item,
                balance_qty: transferQty,
                transfer_qty: transferQty,
                selected: false,
            };
            destination.order_items.push(destinationItem);
        }

        // Update source item
        item.balance_qty -= transferQty;
        item.transfer_qty = item.balance_qty;
    });

    // Clear selections after move
    source.order_items.forEach(item => item.selected = false);
    if (destination) destination.order_items.forEach(item => item.selected = false);
    selectedItems.value = [];
    sourceTable.value = '';
    sourceTableId.value = null;

    // emit('isDirty', true);
};

const openModal = () => {
    isDirty.value = false;
    payBillFormIsOpen.value = true;
}

const closeModal = (status) => {
    switch(status) {
        case 'close':{
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                payBillFormIsOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            payBillFormIsOpen.value = false;

            break;
        }
    }
}

const reset = () => {
    form.currentBill.order_items = processOrderItems(props.currentTable.order_tables);
    form.splitBills.forEach(bill => bill.order_items = []);
    selectedItems.value = [];
    sourceTable.value = '';
    sourceTableId.value = null;
    // emit('isDirty', false);
};

const submit = () => {
    form.post(route('orders.splitBill'), {
        preserveScroll: true,
        onSuccess: () => {
            showMessage({
                severity: 'success',
                summary: `You've successfully split the order into multiple bills.`
            });
            // emit('isDirty', false);
            emit('closeModal', 'leave');
            // emit('closeAll');
        }
    });
};

const showCustomerModal = (billId) => {
    getAllCustomers();
    selectedTargetBillId.value = billId;
    isCustomerModalOpen.value = true;
};

const closeCustomerModal = () => {
    selectedCustomer.value = '';
    isCustomerModalOpen.value = false;
};

const selectCustomer = () => {
    if (selectedTargetBillId.value === 'current') {
        form.currentBill.customer = initialCustomerList.value.find(cust => cust.id === selectedCustomer.value);
        form.currentBill.customer_id = initialCustomerList.value.find(cust => cust.id === selectedCustomer.value).id;
    } else {
        let targetBill = form.splitBills.find(bill => bill.id === selectedTargetBillId.value);
        if (targetBill) {
            targetBill.customer = initialCustomerList.value.find(cust => cust.id === selectedCustomer.value);
            targetBill.customer_id = initialCustomerList.value.find(cust => cust.id === selectedCustomer.value).id;
        }
    }

    closeCustomerModal();
};

const filterCustomerList = computed(() => {
    if (!searchQuery.value) {
        return initialCustomerList.value;
    }

    const search = searchQuery.value.toLowerCase();
    return customerList.value.filter(customer => {
        return customer.full_name.toLowerCase().includes(search) ||
               customer.phone.toLowerCase().includes(search);
    });
});

const clearSelection = () => {
    selectedCustomer.value = '';
};

const isValidated = computed(() => {
    return !form.processing && 
           form.splitBills.length > 0 &&
           form.splitBills.every(bill => bill.order_items.length > 0);
});

const showRemoveRewardForm = (bill) => {
    selectedBill.value = bill;
    removeRewardFormIsOpen.value = true;
};

const hideRemoveRewardForm = () => {
    selectedBill.value = '';
    removeRewardFormIsOpen.value = false;
};

const payThisBill = (bill) => {
    hideRemoveRewardForm();
    emit('payBill', bill);
};

watch(() => props.splitBillsState, (newValue) => initialState.value = newValue)

// Add this to emit state updates
watch(() => form.data(), (newValue) => {
    let currentBill = JSON.parse(JSON.stringify(newValue.currentBill));
    currentBill.order_items.forEach(item => {
        item.transfer_qty = item.balance_qty;
        item.selected = false;
    });
    
    let splitBills = JSON.parse(JSON.stringify(newValue.splitBills));
    splitBills.forEach(bill => {
        bill.order_items.forEach(item => {
            item.transfer_qty = item.balance_qty;
            item.selected = false;
        });
    });

    emit('updateState', {
        currentBill: currentBill,
        splitBills: splitBills,
    });
}, { deep: true });

// Watch for changes in order items and update totals
watch(() => [...form.currentBill.order_items, ...form.splitBills.flatMap(b => b.order_items)], () => {
    // Update current bill total
    form.currentBill.amount = calculateBillTotal(form.currentBill);
    form.currentBill.total_amount = calculateBillTotal(form.currentBill);
    // console.log(form.currentBill);
    // Update split bills totals
    form.splitBills.forEach(bill => {
        bill.amount = calculateBillTotal(bill);
        bill.total_amount = calculateBillTotal(bill);
    });
}, { deep: true });

watch(() => props.currentSplitBillMode, (newValue) => {
    isSplitBillMode.value = newValue;
});

watch(() => props.currentHasVoucher, (newValue) => {
    hasVoucher.value = newValue;
});

</script>

<template>
    <form @submit.prevent="submit">
        <div class="flex items-start w-full gap-x-5 self-stretch pt-6 py-2 bg-grey-50 overflow-x-auto scrollbar-thin scrollbar-webkit">
            <!-- Current Bill -->
            <div class="min-w-[378px] max-w-[378px] h-[531px] flex flex-col justify-between items-start self-stretch bg-white relative rounded-[5px] border border-grey-100 shadow-md">
                <div class="flex flex-col items-start self-stretch">
                    <div class="flex justify-between items-center w-full p-4 border-b border-grey-100">
                        <div class="w-full flex gap-x-2 justify-start items-center self-stretch">
                            <span class="w-1.5 h-[22px] bg-primary-800"></span>
                            <p class="text-grey-950 text-md font-semibold">Bill 1 (Current)</p>
                        </div>
                        <div 
                            class="flex items-center gap-x-2 cursor-pointer" 
                            @click="showCustomerModal('current')"
                        >
                            <div class="flex gap-x-2 !w-fit">
                                <img 
                                    :src="form.currentBill.customer ? form.currentBill.customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                    alt="CustomerImage"
                                    class="size-5 rounded-full"
                                    v-if="form.currentBill.customer"
                                >
                                <p :class="['text-base font-normal cursor-pointer', form.currentBill.customer ? 'text-grey-700' : 'text-grey-300']">
                                    {{ form.currentBill.customer?.full_name ?? 'Select' }}
                                </p>
                            </div>
                            <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_4705_26673)">
                                    <path d="M4.26826 7.61216H11.7331C12.0352 7.61216 12.3123 7.44478 12.4533 7.17785C12.5941 6.91038 12.5755 6.58772 12.4048 6.33789L8.62642 0.81698C8.49115 0.619372 8.2672 0.501073 8.02765 0.500002C7.78836 0.499418 7.5637 0.616258 7.42734 0.813282L3.59911 6.33429C3.42657 6.58302 3.40649 6.90726 3.54698 7.17575C3.68747 7.44378 3.96533 7.61216 4.26826 7.61216Z" fill="#7E171B"/>
                                    <path d="M11.7331 9.38867H4.26825C3.96532 9.38867 3.68746 9.55712 3.54697 9.82512C3.40648 10.0936 3.42659 10.4178 3.5991 10.6665L7.42733 16.1875C7.56369 16.3845 7.78835 16.5014 8.02764 16.5009C8.26719 16.4999 8.49114 16.3814 8.62641 16.1839L12.4048 10.6629C12.5755 10.4131 12.594 10.0904 12.4533 9.82291C12.3123 9.55605 12.0352 9.38867 11.7331 9.38867Z" fill="#7E171B"/>
                                </g>
                                <defs>
                                    <clipPath id="clip0_4705_26673">
                                        <rect width="16" height="16" fill="white" transform="translate(0 0.5)"/>
                                    </clipPath>
                                </defs>
                            </svg>
                        </div>
                    </div>

                    <div class="flex flex-col p-3 items-start self-stretch max-h-[calc(100dvh-33.3rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                        <template v-for="item in form.currentBill.order_items" :key="item.id">
                            <div
                                v-if="item.balance_qty > 0"
                                class="grid grid-cols-1 sm:grid-cols-12 items-center self-stretch py-5 gap-x-3 border-b border-grey-100"
                            >
                                <Checkbox 
                                    class="col-span-full sm:col-span-1"
                                    :checked="item.selected"
                                    @update:checked="selectItem(item, 'current')"
                                />
                                <div class="col-span-full sm:col-span-6 flex flex-col items-center gap-3" :class="item.bucket === 'set' ? '!line-clamp-3' : '!line-clamp-2'">
                                    <Tag value="Set" v-if="item.bucket === 'set'"/>
                                    <p>{{ item.product_name }}</p>
                                </div>
                                <NumberCounter
                                    v-model="item.transfer_qty"
                                    :maxValue="item.balance_qty"
                                    class="col-span-full sm:col-span-5"
                                    :disabled="item.balance_qty === 1"
                                    @onChange="selectItem(item, 'current', 'counter')"
                                />
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex p-4 gap-x-2 justify-start items-center self-stretch border-t border-grey-100">
                    <Button
                        type="button"
                        size="lg"
                        :disabled="form.currentBill.order_items.length === 0 || selectedItems.length > 0 || form.splitBills.length > 0"
                        @click="!isSplitBillMode && hasVoucher ? showRemoveRewardForm(form.currentBill) : payThisBill(form.currentBill)"
                    >
                        Pay this bill
                    </Button>
                </div>

                <!-- Move overlay shown when items from split bills are selected -->
                <div 
                    v-if="sourceTable === 'target'"
                    class="absolute inset-0 bg-white/60 flex justify-center items-center"
                >
                    <Button
                        variant="primary"
                        type="button"
                        size="lg"
                        class="!w-fit"
                        @click="moveItems('current')"
                    >
                        Move to Bill 1
                    </Button>
                </div>
            </div>

            <!-- Split Bills -->
            <template v-for="(bill, index) in form.splitBills" :key="bill.id">
                <div class="min-w-[378px] max-w-[378px] h-[531px] flex flex-col justify-between items-start self-stretch bg-white relative rounded-[5px] border border-grey-100 shadow-md">
                    <div class="flex flex-col items-start self-stretch">
                        <div class="flex justify-between items-center w-full p-4 border-b border-grey-100">
                            <div class="w-full flex gap-x-2 justify-start items-center self-stretch">
                                <span class="w-1.5 h-[22px] bg-primary-800"></span>
                                <p class="text-grey-950 text-md font-semibold">Bill {{ index + 2 }}</p>
                            </div>
                            <div 
                                class="flex items-center gap-x-2 cursor-pointer" 
                                @click="showCustomerModal(bill.id)"
                            >
                                <div class="flex gap-x-2 !w-fit">
                                    <img 
                                        :src="bill.customer ? bill.customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                        alt="CustomerImage"
                                        class="size-5 rounded-full"
                                        v-if="bill.customer"
                                    >
                                    <p :class="['text-base font-normal cursor-pointer', bill.customer ? 'text-grey-700' : 'text-grey-300']">
                                        {{ bill.customer?.full_name ?? 'Select' }}
                                    </p>
                                </div>
                                <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_4705_26673)">
                                        <path d="M4.26826 7.61216H11.7331C12.0352 7.61216 12.3123 7.44478 12.4533 7.17785C12.5941 6.91038 12.5755 6.58772 12.4048 6.33789L8.62642 0.81698C8.49115 0.619372 8.2672 0.501073 8.02765 0.500002C7.78836 0.499418 7.5637 0.616258 7.42734 0.813282L3.59911 6.33429C3.42657 6.58302 3.40649 6.90726 3.54698 7.17575C3.68747 7.44378 3.96533 7.61216 4.26826 7.61216Z" fill="#7E171B"/>
                                        <path d="M11.7331 9.38867H4.26825C3.96532 9.38867 3.68746 9.55712 3.54697 9.82512C3.40648 10.0936 3.42659 10.4178 3.5991 10.6665L7.42733 16.1875C7.56369 16.3845 7.78835 16.5014 8.02764 16.5009C8.26719 16.4999 8.49114 16.3814 8.62641 16.1839L12.4048 10.6629C12.5755 10.4131 12.594 10.0904 12.4533 9.82291C12.3123 9.55605 12.0352 9.38867 11.7331 9.38867Z" fill="#7E171B"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_4705_26673">
                                            <rect width="16" height="16" fill="white" transform="translate(0 0.5)"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                            </div>
                        </div>

                        <div class="flex flex-col p-3 items-start self-stretch max-h-[calc(100dvh-28.4rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                            <template v-for="item in bill.order_items" :key="item.id">
                                <div
                                    v-if="item.balance_qty > 0"
                                    class="grid grid-cols-1 sm:grid-cols-12 items-center self-stretch py-3 gap-x-3 border-b border-grey-100"
                                >
                                    <Checkbox 
                                        class="col-span-full sm:col-span-1"
                                        :checked="item.selected"
                                        @update:checked="selectItem(item, bill.id)"
                                    />
                                    <div class="col-span-full sm:col-span-6 flex flex-col items-center gap-3" :class="item.bucket === 'set' ? '!line-clamp-3' : '!line-clamp-2'">
                                        <Tag value="Set" v-if="item.bucket === 'set'"/>
                                        <p>{{ item.product_name }}</p>
                                    </div>
                                    <NumberCounter
                                        v-model="item.transfer_qty"
                                        :maxValue="item.balance_qty"
                                        class="col-span-full sm:col-span-5"
                                        :disabled="item.balance_qty === 1"
                                        @onChange="selectItem(item, bill.id, 'counter')"
                                    />
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex p-4 gap-x-2 justify-start items-center self-stretch border-t border-grey-100">
                        <Button
                            variant="tertiary"
                            type="button"
                            size="lg"
                            :disabled="selectedItems.length > 0"
                            @click="removeBill(index)"
                        >
                            Delete
                        </Button>
                        <Button
                            type="button"
                            size="lg"
                            :disabled="bill.order_items.length === 0 || selectedItems.length > 0"
                            @click="!isSplitBillMode && hasVoucher ? showRemoveRewardForm(bill) : payThisBill(bill)"
                        >
                            Pay this bill
                        </Button>
                    </div>
                    
                    <!-- Move overlay shown when items from current bill are selected -->
                    <div 
                        v-if="sourceTable === 'current' || (sourceTable === 'target' && sourceTableId !== bill.id)"
                        class="absolute inset-0 bg-white/60 flex justify-center items-center"
                    >
                        <Button
                            variant="primary"
                            type="button"
                            size="lg"
                            class="!w-fit"
                            @click="moveItems(bill.id)"
                        >
                            Move to Bill {{ index + 2 }}
                        </Button>
                    </div>
                </div>
            </template>

            <!-- Add New Bill Button -->
            <div 
                @click="addBill"
                class="min-w-[378px] max-w-[378px] h-[531px] flex flex-col justify-center px-6 items-start gap-6 self-stretch relative rounded-[5px] border-2 border-grey-200 shadow-md border-dashed cursor-pointer"
            >
                <div class="flex w-full flex-col items-center gap-y-2 text-grey-300">
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.9987 8.33203V31.6654M8.33203 19.9987H31.6654" stroke="#B2BEC7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="text-base font-normal text-center">Add another bill</p>
                </div>
            </div>
        </div>

        <!-- <div class="flex px-6 pt-6 pb-2 items-center justify-end gap-4 self-stretch rounded-b-[5px] bg-white shadow-[0_-8px_16.6px_0_rgba(0,0,0,0.04)] mx-[-20px]">
            <Button
                variant="tertiary"
                type="button"
                size="lg"
                class="!w-fit"
                @click="$emit('closeModal', 'leave')"
            >
                Cancel & Exit
            </Button>
            <Button
                variant="tertiary"
                type="button"
                size="lg"
                class="!w-fit"
                @click="reset"
            >
                Reset
            </Button>
            <Button
                variant="primary"
                type="submit"
                size="lg"
                class="!w-fit"
                :disabled="!isValidated"
            >
                Confirm Split
            </Button>
        </div> -->
    </form>
    
    <!-- Customer Selection Modal -->
    <Modal
        :title="'Select Customer'"
        :maxWidth="'xs'"
        :closeable="true"
        :show="isCustomerModalOpen"
        @close="closeCustomerModal"
    >
        <div class="flex flex-col items-start gap-6 rounded-[5px] bg-white">
            <SearchBar 
                :placeholder="'Search'"
                :showFilter="false"
                v-model="searchQuery"
            />

            <div class="flex flex-col items-center justify-center self-stretch divide-y divide-grey-100 max-h-[calc(100dvh-28.4rem)] overflow-auto scrollbar-webkit scrollbar-thin">
                <template v-if="filterCustomerList.length > 0">
                    <template v-for="customer in filterCustomerList">
                        <div class="flex justify-between items-center self-stretch py-2.5 pr-1">
                            <div class="flex flex-nowrap items-center gap-2 w-fit">
                                <img 
                                    :src="customer.image ? customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                    alt='CustomerProfileImage'
                                    class="size-11 rounded-full"
                                />
                                <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                    <span class="text-grey-900 text-base font-bold">{{ customer.full_name }}</span>
                                    <span class="text-grey-500 text-base font-normal">{{ customer.phone }}</span>
                                </div>
                            </div>

                            <RadioButton 
                                :name="'customer'"
                                :dynamic="false"
                                :value="customer.id"
                                class="!w-fit"
                                :errorMessage="''"
                                v-model:checked="selectedCustomer"
                            />
                        </div>
                    </template>
                </template>

                <div class="flex flex-col items-center justify-center" v-else>
                    <UndetectableIllus />
                    <span class="text-primary-900 text-sm font-medium pb-5">No data can be shown yet...</span>
                </div>
            </div>

            <div class="flex flex-col justify-end items-center gap-5 self-stretch">
                <div class="flex justify-center items-end gap-4 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="clearSelection"
                    >
                        Clear
                    </Button>

                    <Button
                        :type="'button'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="!selectedCustomer || form.processing"
                        @click="selectCustomer"
                    >
                        Confirm
                    </Button>
                </div>
            </div>
        </div>
    </Modal>

    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="removeRewardFormIsOpen"
        :withHeader="false"
        class="[&>div>div>div]:!p-0"
        @close="hideRemoveRewardForm"
    >
        <div class="flex flex-col gap-9">
            <div class="bg-primary-50 pt-6 flex items-center justify-center rounded-t-[5px]">
                <MovingIllus />
            </div>
            <div class="flex flex-col justify-center items-center self-stretch gap-1 px-6" >
                <div class="text-center text-primary-900 text-lg font-medium self-stretch">Remove Reward</div>
                <div class="text-center text-grey-900 text-base font-medium self-stretch" >Please note that splitting the bill will void any applied rewards, as rewards cannot be used with split bills.</div>
            </div>
            <div class="flex px-6 pb-6 justify-center items-end gap-4 self-stretch">
                <Button
                    variant="tertiary"
                    size="lg"
                    type="button"
                    @click="hideRemoveRewardForm"
                >
                    Cancel
                </Button>
                <Button
                    size="lg"
                    @click="payThisBill(selectedBill)"
                >
                    Remove
                </Button>
            </div>
        </div>
    </Modal>

    <!-- <Modal
        :title="'Pay Bill'"
        :maxWidth="'xl'" 
        :closeable="true"
        :show="payBillFormIsOpen"
        @close="closeModal('close')"
    >
        <PayBillForm
            :currentOrder="order"
            :currentTable="selectedTable"
            @update:order="order = $event"
            @update:order-customer="order.customer = $event"
            @close="closeModal"
            @fetchZones="$emit('fetchZones')"
            @fetchOrderDetails="$emit('fetchOrderDetails')"
            @closeDrawer="$emit('close', true)"
            @update:customer-point="$emit('update:customer-point', $event)"
            @update:customer-rank="$emit('update:customer-rank', $event)"
            @fetchPendingServe="$emit('fetchPendingServe')"
            @isDirty="isDirty = $event"
        />
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal> -->
</template>