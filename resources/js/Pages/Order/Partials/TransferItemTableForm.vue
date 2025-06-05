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

const props = defineProps({
    currentTable: {
        type: [String, Object],
        required: true
    },
    currentTableName: {
        type: String,
        required: true
    },
    currentTables: Array,
    targetTable: {
        type: [String, Object],
        required: true
    },
    targetTableName: {
        type: String,
        required: true
    },
    targetTables: Array,
    customers: Array
});

const { showMessage } = useCustomToast();
const emit = defineEmits(['closeModal', 'isDirty', 'closeAll']);

const selectedItems = ref([]);
const isCustomerModalOpen = ref(false);
const customerList = ref(props.customers);
const searchQuery = ref('');
const selectedCustomer = ref('');

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

const currentOrderItems = computed(() => 
    processOrderItems(props.currentTable.order_tables).map(item => ({
         ...item,
         origin: 'current'
    }))
);

const targetOrderItems = computed(() =>  
    processOrderItems(props.targetTable.order_tables).map(item => ({
         ...item,
         origin: 'target'
    }))
);

const form = useForm({
    currentTable: {
        table_id: props.currentTable.id,
        order_id: props.currentTable.order_id,
        status: props.currentTable.status,
        pax: props.currentTable.order_tables[0].pax,
        tables: props.currentTables,
        customer_id: props.currentTable.order_tables.sort((a, b) => b.id - a.id)[0].order.customer_id,
        order_items: [],
        transferred_items: []
    },
    targetTable: {
        table_id: props.targetTable.id,
        order_id: props.targetTable.order_id,
        status: props.targetTable.status,
        pax: props.targetTable.order_tables.length > 0 ? props.targetTable.order_tables[0].pax : props.currentTable.order_tables[0].pax,
        tables: props.targetTables,
        customer_id: '',
        order_items: [],
        transferred_items: []
    }
});

// Initialize form data
watch([currentOrderItems, targetOrderItems], ([current, target]) => {
    form.currentTable.order_items = [...current];
    form.targetTable.order_items = [...target];
}, { immediate: true });

// Helper to determine if selected items are from a specific table
const areItemsFromTable = (tableName) => {
    if (!selectedItems.value.length) return false;
    return selectedItems.value.every(item => item.origin === tableName);
};

const selectItem = (item) => {
    item.selected = !item.selected;
    if (item.selected) {
        selectedItems.value.push(item);
    } else {
        selectedItems.value = selectedItems.value.filter(selected => selected.id !== item.id);
    }
};

const moveItems = (direction) => {
    const sourceTable = direction === 'target' ? 'currentTable' : 'targetTable';
    const destinationTable = direction === 'target' ? 'targetTable' : 'currentTable';

    // Only process selected items
    const itemsToMove = form[sourceTable].order_items.filter(item => item.selected);

    itemsToMove.forEach(item => {
        const transferQty = item.transfer_qty;
        if (transferQty <= 0) return;

        // Find or create item in destination table
        let destinationItem = form[destinationTable].order_items.find(i => i.id === item.id);
        
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
                transfer_status: 'transferred',
                origin: destinationTable === 'targetTable' ? 'target' : 'current'
            };
            form[destinationTable].order_items.push(destinationItem);
        }

        // Update source item
        item.balance_qty -= transferQty;
        
        // Remove item if no quantity left
        if (item.balance_qty <= 0) {
            const index = form[sourceTable].order_items.findIndex(i => i.id === item.id);
            form[sourceTable].order_items.splice(index, 1);
        } else {
            // Reset transfer quantity to remaining balance
            item.transfer_qty = item.balance_qty;
        }

        // Track the transfer
        form[sourceTable].transferred_items.push({
            item_id: item.id,
            quantity: transferQty,
            destination_table_id: form[destinationTable].table_id
        });
    });

    // Clear selections after move
    selectedItems.value = [];
    form[sourceTable].order_items.forEach(item => item.selected = false);
    form[destinationTable].order_items.forEach(item => item.selected = false);

    emit('isDirty', true);
};

const reset = () => {
    form.currentTable.order_items = [...currentOrderItems.value].map((item) => {
        item.selected = false;
        item.balance_qty = item.original_qty;
        return item;
    });
    form.targetTable.order_items = [...targetOrderItems.value].map((item) => {
        item.selected = false;
        item.balance_qty = item.original_qty;
        return item;
    });
    form.currentTable.transferred_items = [];
    form.targetTable.transferred_items = [];
    selectedItems.value = [];
    emit('isDirty', false);
};

const submit = () => {
    form.post(route('orders.transferTable'), {
        preserveScroll: true,
        onSuccess: () => {
            showMessage({
                severity: 'success',
                summary: `You've successfully transfer the selected item from ${props.currentTableName} to ${props.targetTableName}.`
            });
            emit('isDirty', false);
            emit('closeModal', 'success');
            emit('closeAll');
        }
    });
};

const showCustomerModal = () => {
    isCustomerModalOpen.value = true;
}

const closeCustomerModal = () => {
    isCustomerModalOpen.value = false;
}

const filterCustomerList = computed(() => {
    if (!searchQuery.value) {
        return props.customers;
    }

    const search = searchQuery.value.toLowerCase();

    return customerList.value
            .filter(customer => {
                return customer.full_name.toLowerCase().includes(search)
                        || (customer.phone && customer.phone.toLowerCase().includes(search));
            });
});

const clearSelection = () => {
    form.targetTable.customer_id = '';
    selectedCustomer.value = '';
};

const isValidated = computed(() => {
    return !form.processing && 
            (form.currentTable.order_items.some((item) => (
                item.selected || item.transfer_status === 'transferred' || item.balance_qty !== item.original_qty
            )) ||
            form.targetTable.order_items.some((item) => (
                item.selected || item.transfer_status === 'transferred' || item.balance_qty !== item.original_qty
            )));
});
</script>

<template>
    <form class="h-full flex flex-col justify-between" @submit.prevent="submit">
        <div class="flex items-start size-full gap-x-5 self-stretch py-6 bg-grey-50">
            <!-- Current Table -->
            <div class="w-1/3 flex flex-col items-start gap-6 self-stretch bg-white relative rounded-[5px] border border-grey-100 shadow-md">
                <div class="flex p-4 gap-x-2 justify-start items-center self-stretch border-b border-grey-100">
                    <span class="w-1.5 h-[22px] bg-primary-800"></span>
                    <p class="text-grey-950 text-md font-semibold">{{ currentTableName }} (Current)</p>
                </div>
                <div class="flex flex-col p-3 items-start self-stretch max-h-[calc(100dvh-19.8rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                    <div
                        v-for="item in form.currentTable.order_items"
                        :key="item.id"
                        class="grid grid-cols-1 sm:grid-cols-12 items-center self-stretch py-3 gap-x-3 border-b border-grey-100"
                    >
                        <Checkbox 
                            class="col-span-full sm:col-span-1"
                            :checked="item.selected"
                            @update:checked="selectItem(item)"
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
                        />
                    </div>
                </div>
                <!-- Move overlay shown when items from target table are selected -->
                <div 
                    v-if="areItemsFromTable('target')"
                    class="absolute inset-0 bg-white/60 flex justify-center items-center"
                >
                    <Button
                        variant="primary"
                        type="button"
                        size="lg"
                        class="!w-fit"
                        @click="moveItems('current')"
                    >
                        Move to {{ currentTableName }}
                    </Button>
                </div>
            </div>

            <!-- Target Table -->
            <div class="w-1/3 flex flex-col items-start gap-6 self-stretch bg-white relative rounded-[5px] border border-grey-100 shadow-md">
                <div class="flex justify-between items-center w-full p-4">
                    <div class="flex gap-x-2 justify-start items-center self-stretch border-b border-grey-100">
                        <span class="w-1.5 h-[22px] bg-primary-800"></span>
                        <p class="text-grey-950 text-md font-semibold">{{ targetTableName }}</p>
                    </div>
                    <div 
                        class="flex items-center gap-x-2 cursor-pointer" 
                        @click="showCustomerModal"
                    >
                        <div class="flex gap-x-2 !w-fit">
                            <img 
                                :src="form.targetTable.customer ? form.targetTable.customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                alt="WaiterProfileImage"
                                class="size-5 rounded-full"
                                v-if="form.targetTable.customer"
                            >
                            <p :class="['text-base font-normal cursor-pointer', form.targetTable.customer ? 'text-grey-700' : 'text-grey-300']">{{ form.targetTable.customer?.full_name ?? 'Select' }}</p>
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

                <div class="flex flex-col p-3 items-start self-stretch max-h-[calc(100dvh-19.8rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                    <div
                        v-for="item in form.targetTable.order_items"
                        :key="item.id"
                        class="grid grid-cols-1 sm:grid-cols-12 items-center self-stretch py-3 gap-x-3 border-b border-grey-100"
                    >
                        <Checkbox 
                            class="col-span-full sm:col-span-1"
                            :checked="item.selected"
                            @update:checked="selectItem(item)"
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
                        />
                    </div>
                </div>
                <!-- Move overlay shown when items from current table are selected -->
                <div 
                    v-if="areItemsFromTable('current')"
                    class="absolute inset-0 bg-white/60 flex justify-center items-center"
                >
                    <Button
                        variant="primary"
                        type="button"
                        size="lg"
                        class="!w-fit"
                        @click="moveItems('target')"
                    >
                        Move to {{ targetTableName }}
                    </Button>
                </div>
            </div>
        </div>

        <div class="flex px-6 pt-6 pb-2 items-center justify-end gap-4 self-stretch rounded-b-[5px] bg-white shadow-[0_-8px_16.6px_0_rgba(0,0,0,0.04)] mx-[-20px]">
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
                Confirm
            </Button>
        </div>
    </form>
    
    <Modal
        :title="'Checked-in customer'"
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

            <div class="flex flex-col items-start self-stretch divide-y divide-grey-100 max-h-[calc(100dvh-28.4rem)] overflow-auto scrollbar-webkit scrollbar-thin">
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
                            @onChange="form.targetTable.customer_id = $event"
                        />
                    </div>
                </template>
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
                        @click="closeCustomerModal"
                    >
                        Confirm
                    </Button>
                </div>
            </div>
        </div>
    </Modal>
</template>