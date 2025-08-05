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
    currentTable: [String, Object],
    currentTableName: String,
    currentTables: Array,
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
const sourceTable = ref('');
const sourceTableId = ref(null);
const selectedTargetTableId = ref('')

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

const form = useForm({
    splitType: 'reassign',
    currentTable: {
        table_id: props.currentTable.id,
        order_id: props.currentTable.order_id,
        status: props.currentTable.status,
        pax: props.currentTable.order_tables[0].pax,
        tables: props.currentTables,
        customer_id: props.currentTable.order_tables.sort((a, b) => b.id - a.id)[0].order.customer_id,
        order_items: processOrderItems(props.currentTable.order_tables),
        transferred_items: []
    },
    targetTables: [
        ...props.targetTables.map((table) => {
            table.order_items = [];
            table.new_customer = null;
            return table;
        })
    ],
});

const selectItem = (item, table_id) => {
    sourceTable.value = !!form.targetTables.find(table => table.id === table_id) ? 'target' : 'current';
    sourceTableId.value = !!form.targetTables.find(table => table.id === table_id) ? table_id : null;

    item.selected = !item.selected;
    if (item.selected) {
        if (sourceTable.value === 'target') {
            let itemPicked = item;
            
            itemPicked.transfer_status = '';
            itemPicked.selected = true;

            selectedItems.value.push(itemPicked);
        } else {
            selectedItems.value.push(item);
        }
    } else {
        selectedItems.value = selectedItems.value.filter(selected => selected.id !== item.id);
        sourceTable.value = '';
        sourceTableId.value = null;
    }
};

const moveItems = (table_id = null) => {
    const source = sourceTable.value === 'target' 
            ? form.targetTables.find(table => table.id === sourceTableId.value)
            : form.currentTable;

    const destination = !!form.targetTables.find(table => table.id === table_id)
            ? form.targetTables.find(table => table.id === table_id)
            : form.currentTable;

    // Only process selected items
    selectedItems.value.forEach(item => {
        const transferQty = item.transfer_qty;
        if (transferQty <= 0) return;

        // Find or create item in destination table
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
    destination.order_items.forEach(item => item.selected = false);
    selectedItems.value = [];
    sourceTable.value = '';
    sourceTableId.value = null;

    emit('isDirty', true);
};

const reset = () => {
    form.currentTable.order_items = processOrderItems(props.currentTable.order_tables);
    form.targetTables.forEach(table => table.order_items = []);
    selectedItems.value = [];
    sourceTable.value = '';
    sourceTableId.value = null;
    emit('isDirty', false);
};

const submit = () => {
    let tableLocks = JSON.parse(sessionStorage.getItem('table_locks')) || [];

    form.post(route('orders.splitTable'), {
        preserveScroll: true,
        onSuccess: () => {
            showMessage({
                severity: 'success',
                summary: `You’ve successfully re-assigned the order(s) to other table.   `
            });

            tableLocks = tableLocks.filter((table) => {
                return !tableToBeSplit.value.find((tts) => tts == table.tableId);
            })

            sessionStorage.setItem('table_locks', JSON.stringify(tableLocks));

            emit('isDirty', false);
            emit('closeModal', 'leave');
            emit('closeAll');
        }
    });
};

const showCustomerModal = (table_id) => {
    selectedTargetTableId.value = table_id;
    isCustomerModalOpen.value = true;
}

const closeCustomerModal = () => {
    let targetTable = form.targetTables.find((table) => table.id === selectedTargetTableId.value)

    if (targetTable) {
        targetTable.new_customer = customerList.value.find((cust) => cust.id === selectedCustomer.value);
        selectedCustomer.value = '';
    }
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
    selectedCustomer.value = '';
};

const currentTableNames = computed(() => {
    return form.currentTable.tables
            .map((table) => table.table_no)
            .join(", ");
})

const tableToBeSplit = computed(() => {
    const splitTables = [];

    form.targetTables.forEach((table) => {
        splitTables.push(table.id)
    });

    return splitTables;
});

const isValidated = computed(() => {
    return !form.processing 
            && !selectedItems.value.length > 0
            && form.targetTables.every((table) => table.order_items.length > 0);
            // && form.targetTables.every((table) => table.new_customer != null);
            
});

</script>

<template>
    <form class="h-full flex flex-col justify-between" @submit.prevent="submit">
        <div class="flex items-start size-full gap-x-5 self-stretch py-6 bg-grey-50 overflow-x-auto scrollbar-thin scrollbar-webkit">
            <!-- Current Table -->
            <div class="min-w-[378px] max-w-[378px] flex flex-col items-start self-stretch bg-white relative rounded-[5px] border border-grey-100 shadow-md">
                <div class="flex p-4 gap-x-2 justify-start items-center self-stretch border-b border-grey-100">
                    <span class="w-1.5 h-[22px] bg-primary-800"></span>
                    <p class="text-grey-950 text-md font-semibold">{{ currentTableNames }} (Current)</p>
                </div>
                <div class="flex flex-col p-3 items-start self-stretch max-h-[calc(100dvh-19.8rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                    <template v-for="item in form.currentTable.order_items" :key="item.id">
                        <div
                            v-if="item.balance_qty > 0"
                            class="grid grid-cols-1 sm:grid-cols-12 items-center self-stretch py-3 gap-x-3 border-b border-grey-100"
                        >
                            <Checkbox 
                                class="col-span-full sm:col-span-1"
                                :checked="item.selected"
                                @update:checked="selectItem(item, form.currentTable.table_id)"
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
                    </template>
                </div>
                <!-- Move overlay shown when items from target table are selected -->
                <div 
                    v-if="sourceTable === 'target'"
                    class="absolute inset-0 bg-white/60 flex justify-center items-center"
                >
                    <Button
                        variant="primary"
                        type="button"
                        size="lg"
                        class="!w-fit"
                        @click="moveItems(form.currentTable.table_id)"
                    >
                        Move to {{ currentTableNames }}
                    </Button>
                </div>
            </div>

            <!-- Target Tables -->
            <template v-for="(target, index) in form.targetTables" :key="index">
                <div class="min-w-[378px] max-w-[378px] flex flex-col items-start self-stretch bg-white relative rounded-[5px] border border-grey-100 shadow-md">
                    <div class="flex justify-between items-center w-full p-4 border-b border-grey-100">
                        <div class="flex gap-x-2 justify-start items-center self-stretch">
                            <span class="w-1.5 h-[22px] bg-primary-800"></span>
                            <p class="text-grey-950 text-md font-semibold">{{ target.table_no }}</p>
                        </div>
                        <div 
                            class="flex items-center gap-x-2 cursor-pointer" 
                            @click="showCustomerModal(target.id)"
                        >
                            <div class="flex gap-x-2 !w-fit">
                                <img 
                                    :src="target.new_customer ? target.new_customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                    alt="WaiterProfileImage"
                                    class="size-5 rounded-full"
                                    v-if="target.new_customer"
                                >
                                <p :class="['text-base font-normal cursor-pointer', target.new_customer ? 'text-grey-700' : 'text-grey-300']">{{ target.new_customer?.full_name ?? 'Select' }}</p>
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
                        <template v-for="item in target.order_items" :key="item.id">
                            <div
                                v-if="item.balance_qty > 0"
                                class="grid grid-cols-1 sm:grid-cols-12 items-center self-stretch py-3 gap-x-3 border-b border-grey-100"
                            >
                                <Checkbox 
                                    class="col-span-full sm:col-span-1"
                                    :checked="item.selected"
                                    @update:checked="selectItem(item, target.id)"
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
                        </template>
                    </div>
                    <!-- Move overlay shown when items from current table are selected -->
                    <div 
                        v-if="sourceTable === 'current' || (sourceTable === 'target' && sourceTableId !== target.id)"
                        class="absolute inset-0 bg-white/60 flex justify-center items-center"
                    >
                        <Button
                            variant="primary"
                            type="button"
                            size="lg"
                            class="!w-fit"
                            @click="moveItems(target.id)"
                        >
                            Move to {{ target.table_no }}
                        </Button>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex px-6 pt-6 pb-2 items-center justify-end gap-4 self-stretch rounded-b-[5px] bg-white shadow-[0_-8px_16.6px_0_rgba(0,0,0,0.04)] mx-[-20px]">
            <Button
                variant="tertiary"
                type="button"
                size="lg"
                class="!w-fit"
                :disabled="form.processing"
                @click="$emit('closeModal', 'leave')"
            >
                Cancel & Exit
            </Button>
            <Button
                variant="tertiary"
                type="button"
                size="lg"
                class="!w-fit"
                :disabled="form.processing"
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
                        />
                            <!-- @onChange="form.targetTable.customer_id = $event" -->
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