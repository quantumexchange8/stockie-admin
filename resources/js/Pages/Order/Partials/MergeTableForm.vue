<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { MergeTableIllust, MovingIllus } from '@/Components/Icons/illus';
import { CheckedIcon, DefaultIcon, MergedIcon, WarningIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import TabView from '@/Components/TabView.vue';
import { useCustomToast, usePhoneUtils } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import { computed } from 'vue';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    currentOrderTable: {
        type: Object,
        required: true,
    },
    currentOrderCustomer: {
        type: Object,
        default: () => {},
    },
    currentOrder: {
        type: Object,
        default: () => {},
    }
})

const order = ref(props.currentOrder);
const zones = ref('');
const tabs = ref([]);
const isLoading = ref(false);
const tableNames = ref('--');
const mergedTables = ref([]);
const isConfirmShow = ref(false);
const isSelectedCustomer = ref();
const customers = ref();
const checkedIn = ref([]);
const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();
const removeRewardFormIsOpen = ref(false);
const currentHasVoucher = ref(false);
const targetHasVoucher = ref(false);

const emit = defineEmits(['close', 'closeDrawer', 'closeOrderDetails']);

const form = useForm({
    id: props.currentOrderTable.id,
    customer_id: props.currentOrderCustomer?.id ?? null,
    tables: [],
})

const addToMerged = (targetTable) => {
    const index = form.tables.indexOf(targetTable);
    if (index > -1) {
        form.tables.splice(index, 1); 
    } else {
        form.tables.push(targetTable); 
    }
    
    // Find if there is any table already merged with the target table.
    const matchingTables = zones.value
        .flatMap((zone) => zone.tables)
        .filter(
            (table) =>
                table.order_id &&
                table.order_id === targetTable.order_id &&
                table.id !== targetTable.id
        );
    
    matchingTables.forEach((matchingTable) => {
        // If exists then push into mergedTables to show disabled state on layout
        const existingIndex = mergedTables.value.findIndex(
            (table) => table.id === matchingTable.id
        );
        if (existingIndex > -1) {
            mergedTables.value.splice(existingIndex, 1); 
        } else {
            mergedTables.value.push(matchingTable); 
        }

        // At the same time push into form.tables since it will be merged together
        const formIndex = form.tables.findIndex(
            (table) => table.id === matchingTable.id
        );
        if (formIndex > -1) {
            form.tables.splice(formIndex, 1);
        } else {
            form.tables.push(matchingTable);
        }
    });
    
    // Finally update the tableNames string
    tableNames.value = form.tables.map((table) => table.table_no).join(", ");
    if (form.tables.length === 0) tableNames.value = "--";
};

const getAllZones = async() => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('orders.getAllZones'));
        zones.value = response.data;

    } catch(error) {
        console.error(error)
    } finally {
        isLoading.value = false;
    }
}

const getAllCustomer = async() => {
    isLoading.value = true;
    try {
        const customerResponse = await axios.get(route('orders.getAllCustomer'));
        customers.value = customerResponse.data;
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false;
    }
}
 
const populateTabs = () => {
    tabs.value = ['All'];
    for (const zone of zones.value) {
        if (zone.text) { 
            tabs.value.push(zone.text);
        }
    }
};

let intervals = ref([]);

const setupDuration = (created_at) => {
    const startTime = dayjs(created_at);
    const formattedDuration = ref(dayjs.duration(dayjs().diff(startTime)).format('HH:mm:ss'));

    const updateDuration = () => {
        const now = dayjs();
        const diff = now.diff(startTime);
        formattedDuration.value = dayjs.duration(diff).format('HH:mm:ss');
    };

    const intervalId = setInterval(updateDuration, 1000);
    intervals.value.push(intervalId);

    return formattedDuration.value;
};

onUnmounted(() => intervals.value.forEach(clearInterval));

const filterZones = () => {

}

const getCurrentOrderTableDuration = (table) => {
    let currentOrderTable = table.order_tables.filter((table) => table.status !== 'Pending Clearance').length === 1 
            ? table.order_tables.filter((table) => table.status !== 'Pending Clearance')[0].created_at
            : table.order_tables[0].created_at;

    return setupDuration(currentOrderTable);
};

const getTableClasses = (table) => ({
    state: computed(() => [
        table.is_reserved ? 'cursor-not-allowed' : 'cursor-pointer',
        {
            'bg-grey-50': table.status === 'Empty Seat' && table.is_reserved,
            'bg-white': table.status === 'Empty Seat' && !table.is_reserved,
            'bg-green-600': (table.status === 'All Order Served' || table.status === 'Order Placed' || table.status === 'Pending Order') && !table.is_reserved,
            'bg-orange-500': table.status === 'Pending Clearance' && !table.is_reserved,
        }
    ]),
    text: computed(() => [
        'text-xl font-bold self-stretch text-center',
        {
            'text-primary-900': table.status === 'Empty Seat' && !table.is_reserved,
            // 'text-primary-25': table.status === 'Pending Order',
            'text-green-50': (table.status === 'Order Placed' || table.status === 'All Order Served' || table.status === 'Pending Order') && !table.is_reserved,
            'text-orange-25': table.status === 'Pending Clearance'  && !table.is_reserved,
            'text-grey-400': table.is_reserved,
        }
    ]),
    duration: computed(() => [
        'text-base font-normal self-stretch text-center',
        {
            // 'text-primary-100': table.status === 'Pending Order',
            'text-green-100': table.status === 'Order Placed' || table.status === 'All Order Served' || table.status === 'Pending Order',
            'text-orange-100': table.status === 'Pending Clearance',
        }
    ]),
    count: computed(() => [
        'text-xs font-medium self-stretch text-center',
        {
            'text-primary-900': table.status === 'Empty Seat',
            'text-green-100': table.status === 'Order Placed' || table.status === 'All Order Served' || table.status === 'Pending Order',
            'text-orange-100': table.status === 'Pending Clearance',
        }
    ]),
});

const isMerged = (targetTable) => {
    return zones.value.some(zone =>
        zone.tables.some(table =>
            table.id !== targetTable.id && table.order_id === targetTable.order_id && table.status !== 'Empty Seat'
        )
    );
};

const mergeTable = async () => {
    const selectedCustomer = isSelectedCustomer.value?.id ?? form.customer_id;
    form.customer_id = selectedCustomer;
    
    const tables = zones.value.flatMap((zone) => zone.tables);

    tables.forEach((table) => {
        if (order.value.order_table.some((orderTable) => orderTable.table_id === table.id)) {
            if (!form.tables.find((formTable) => formTable.id === table.id)) {
                form.tables.push(table);
            }
        }
    })

    try {
        const response = await axios.post('/order-management/orders/mergeTable', form);

        setTimeout(() => {
            showMessage({
                severity: 'success',
                summary: `Selected table has been successfully merged with '${props.currentOrderTable.table_no}'.`
            })
        }, 200);

        form.reset();
        emit('closeOrderDetails');
        closeConfirm();
        emit('close');
    } catch (error) {
        console.error(error);
    } finally {

    }
}

const showRemoveRewardForm = () => {
    removeRewardFormIsOpen.value = true;
};

const hideRemoveRewardForm = () => {
    removeRewardFormIsOpen.value = false;
};

const openConfirm = () => {
    currentHasVoucher.value = ['Empty Seat', 'Pending Clearance'].includes(props.currentOrderTable.status) && order.value.voucher;
    targetHasVoucher.value = form.tables.some(table => {
        return table.order && 
                table.order.voucher_id && 
                !['Empty Seat', 'Pending Clearance'].includes(table.status);
    });

    const isCheckedIn = form.tables.flatMap((table) => table.order_tables)
                                    .filter((order_table) => !!order_table.order.customer_id)
                                    .map((order_table) => order_table.order.customer_id);

    isCheckedIn.push(props.currentOrderCustomer?.id);
    checkedIn.value = customers.value.filter((customer) =>
        isCheckedIn.includes(customer.id)
    );

    isSelectedCustomer.value = checkedIn.value.length > 0 ? checkedIn.value[0] : null;
    if (checkedIn.value.length > 1) {
        isConfirmShow.value = true;
    } else {
        if (currentHasVoucher.value || targetHasVoucher.value) {
            showRemoveRewardForm();
        } else {
            mergeTable();
        }
    }
};


const closeConfirm = () => {
    if (!removeRewardFormIsOpen.value) isConfirmShow.value = false;
    currentHasVoucher.value = false;
    targetHasVoucher.value = false;
}

watch(() => zones.value, populateTabs, { immediate: true });

watch(() => props.currentOrder, (newValue) => (order.value = newValue));

onMounted(() => {
    getAllZones();
    getAllCustomer();
})
</script>

<template>
    <form class="h-full flex flex-col justify-between" @submit.prevent="mergeTable">
        <div class="flex flex-col h-full items-start gap-6 self-stretch pb-6">
            <div class="flex flex-col items-start gap-2.5 self-stretch">
                <div class="flex flex-col p-3 justify-center items-start gap-3 self-stretch rounded-[5px] bg-[#FDFBED]">
                    <div class="flex items-start gap-3 self-stretch">
                        <WarningIcon class="size-6" />
                        <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                            <span class="self-stretch text-[#A35F1A] text-base font-bold">
                                Reminder
                            </span>
                            <span class="self-stretch text-[#3E200A] text-sm font-medium">
                                When you merge the tables, all the bills from the separate tables will be combined into one single bill.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <TabView :tabs="tabs" v-if="zones.length">
                <template #all>
                    <div class="flex flex-col px-6 items-start gap-6 self-stretch max-h-[calc(100dvh-26rem)] overflow-auto scrollbar-webkit scrollbar-thin">
                        <div class="grid min-[528px]:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 items-start content-start gap-6 self-stretch">
                        <template v-for="zone in zones">
                            <template v-for="table in zone.tables">
                                <div class="col-span-1 flex flex-col p-6 justify-center items-center gap-2 rounded-[5px] border border-solid border-grey-100 min-h-[137px] w-full relative" 
                                    :class="getTableClasses(table).state.value"
                                    @click="table.id === props.currentOrderTable.id || !!mergedTables.find((mergeTable) => mergeTable === table) || order.order_table.some((orderTable) => orderTable.table_id === table.id) || table.is_reserved ? '' : addToMerged(table)"
                                >
                                    <span :class="getTableClasses(table).text.value">{{ table.table_no }}</span>
                                    <div :class="getTableClasses(table).duration.value" v-if="table.status !== 'Empty Seat'">
                                        {{ getCurrentOrderTableDuration(table) }}
                                    </div>
                                    <template v-else>
                                        <div class="flex py-1 px-3 justify-center items-center gap-2.5 rounded-lg bg-primary-600" v-if="table.is_reserved">
                                            <p class="text-white text-center font-semibold text-2xs">RESERVED</p>
                                        </div>
                                        <div class="text-base font-normal text-center" :class="table.is_reserved ? 'text-grey-200' : 'text-primary-900'" v-else>{{ table.seat }} seats</div>
                                    </template>
                                    <Checkbox 
                                        :checked="!!form.tables.find((formTable) => formTable.id === table.id)"
                                        :disabled="table.id === props.currentOrderTable.id || !!mergedTables.find((mergeTable) => mergeTable === table) || order.order_table.some((orderTable) => orderTable.table_id === table.id) || table.is_reserved"
                                        class="absolute top-[11px] right-[12px]"
                                    />
                                    <MergedIcon class="text-white size-5 absolute left-2 top-2" v-if="isMerged(table)" />
                                </div>
                            </template>
                        </template>
                        </div>
                    </div>
                </template>
                <template
                    v-for="tab in tabs.filter(tab => tab !== 'All').map((tab) => tab.toLowerCase().replace(/[/\s_]+/g, '-'))"
                    :key="tab"
                    v-slot:[tab]
                >
                    <div class="flex flex-col px-6 items-start gap-6 self-stretch max-h-[calc(100dvh-28.4rem)] overflow-auto scrollbar-webkit scrollbar-thin">
                        <div class="grid min-[528px]:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 items-start content-start gap-6 self-stretch">
                        <template v-for="zone in zones.filter(zone => zone.text.toLowerCase().replace(/[/\s_]+/g, '-') === tab)">
                            <template v-for="table in zone.tables">
                                <div class="col-span-1 flex flex-col p-6 justify-center items-center gap-2 rounded-[5px] border border-solid border-grey-100 min-h-[137px] w-full relative" 
                                    :class="getTableClasses(table).state.value"
                                    @click="table.id === props.currentOrderTable.id || !!mergedTables.find((mergeTable) => mergeTable === table) || order.order_table.some((orderTable) => orderTable.table_id === table.id) || table.is_reserved ? '' : addToMerged(table)"
                                >
                                    <span :class="getTableClasses(table).text.value">{{ table.table_no }}</span>
                                    <div :class="getTableClasses(table).duration.value" v-if="table.status !== 'Empty Seat'">
                                        {{ getCurrentOrderTableDuration(table) }}
                                    </div>
                                    <template v-else>
                                        <div class="flex py-1 px-3 justify-center items-center gap-2.5 rounded-lg bg-primary-600" v-if="table.is_reserved">
                                            <p class="text-white text-center font-semibold text-2xs">RESERVED</p>
                                        </div>
                                        <div class="text-base font-normal text-center" :class="table.is_reserved ? 'text-grey-200' : 'text-primary-900'" v-else>{{ table.seat }} seats</div>
                                    </template>
                                    <Checkbox 
                                        :checked="!!form.tables.find((formTable) => formTable.id === table.id)"
                                        :disabled="table.id === props.currentOrderTable.id || !!mergedTables.find((mergeTable) => mergeTable === table) || order.order_table.some((orderTable) => orderTable.table_id === table.id) || table.is_reserved"
                                        class="absolute top-[11px] right-[12px]"
                                    />
                                    <MergedIcon class="text-white size-5 absolute left-2 top-2" v-if="isMerged(table)" />
                                </div>
                            </template>
                        </template>
                        </div>
                    </div>
                </template> 
            </TabView>
        </div>
        <div class="flex flex-col px-6 pt-6 pb-2 items-center gap-4 self-stretch rounded-b-[5px] bg-white shadow-[0_-8px_16.6px_0_rgba(0,0,0,0.04)] mx-[-20px]">
            <div class="flex h-[25px] items-end gap-2.5 self-stretch">
                <span class="flex-[1_0_0] self-stretch text-grey-950 text-base font-normal">
                    Selected Table(s):
                </span>
                <span>
                    {{ tableNames }}
                </span>
            </div>
            <div class="flex justify-center items-end gap-4 self-stretch">
                <Button
                    :variant="'tertiary'"
                    :type="'button'"
                    :size="'lg'"
                    @click="emit('close')"
                >
                    Cancel
                </Button>
                <Button
                    :variant="'primary'"
                    :type="'button'"
                    :size="'lg'"
                    @click="openConfirm()"
                >
                    Confirm
                </Button>
            </div>
        </div>

        <Modal
            :show="isConfirmShow"
            :title="'Select a customer'"
            :maxWidth="'xs'"
            @close="closeConfirm"
        >
            <div class="flex flex-col items-start gap-6 rounded-[5px] bg-white">
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <span class="self-stretch text-grey-950 text-base font-bold">
                        The table you’re merging with already has an existing customer
                    </span>
                    <span class="self-stretch text-grey-950 text-sm font-normal">
                        Please choose who will stay checked in after the table are merged.
                    </span>
                </div>

                <div class="flex flex-col items-start gap-5 self-stretch">
                    <template v-for="customer in checkedIn">
                        <div class="flex p-4 items-start gap-3 self-stretch rounded-[5px] border border-solid cursor-pointer relative"
                            :class="customer === isSelectedCustomer ? 'border-primary-900 bg-primary-25' : 'border-grey-100 bg-white shadow-[0_4px_15.8px_0_rgba(13,13,13,0.08)]'"
                            @click="isSelectedCustomer = customer"
                        >
                            <img
                                :src="customer.image"
                                alt="CustomerImage"
                                class="size-[52px] rounded-full"
                                v-if="customer.image"
                            >
                            <DefaultIcon class="size-[52px] object-contain" v-else />
                            <div class="flex flex-col items-start gap-2 flex-[1_0_0] self-stretch">
                                <span class="self-stretch text-grey-900 text-md font-semibold">{{ customer.full_name }}</span>
                                <div class="flex items-center gap-2 flex-[1_0_0] self-stretch">
                                    <span class="text-grey-500 text-sm font-base">{{ formatPhone(customer.phone) }}</span>
                                    <span class="text-grey-300">&#x2022;</span>
                                    <span class="line-clamp-1 flex-[1_0_0] text-sm font-normal">{{ customer.email }}</span>
                                </div>
                            </div>
                            <CheckedIcon 
                                class="size-4 absolute top-[-5px] right-[-5px]"
                                v-if="isSelectedCustomer === customer"
                            />
                        </div>
                    </template>
                </div>
                <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="closeConfirm"
                    >
                        Cancel
                    </Button>
                    <Button
                        :type="'button'"
                        :variant="'primary'"
                        :size="'lg'"
                        @click="currentHasVoucher || targetHasVoucher ? showRemoveRewardForm() : mergeTable()"
                    >
                        Confirm
                    </Button>
                </div>
            </div>
        </Modal>
    </form>

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
                <div class="text-center text-grey-900 text-base font-medium self-stretch" >The applied reward for both orders will be removed, but you can always reapply it during payment.</div>
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
                    @click="mergeTable"
                >
                    Remove
                </Button>
            </div>
        </div>
    </Modal>

</template>


