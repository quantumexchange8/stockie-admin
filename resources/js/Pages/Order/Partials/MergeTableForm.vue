<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { MergeTableIllust } from '@/Components/Icons/illus';
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
    }
})

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

const emit = defineEmits(['close', 'closeDrawer', 'fetchZones']);

const form = useForm({
    id: props.currentOrderTable.id,
    customer_id: '',
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

const getCurrentOrderTableDuration = (table) => {
    let currentOrderTable = table.order_tables.filter((table) => table.status !== 'Pending Clearance').length === 1 
            ? table.order_tables.filter((table) => table.status !== 'Pending Clearance')[0].created_at
            : table.order_tables[0].created_at;

    return setupDuration(currentOrderTable);
};

const getTableClasses = (table) => ({
    state: computed(() => [
        'border border-solid rounded-[5px]',
        {
            'bg-white border-grey-100': table.status === 'Empty Seat',
            'bg-green-600 border-green-400': table.status === 'All Order Served' || table.status === 'Order Placed' || table.status === 'Pending Order',
            'bg-orange-500 border-orange-400': table.status === 'Pending Clearance',
        }
    ]),
    text: computed(() => [
        'text-xl font-bold self-stretch text-center',
        {
            'text-primary-900': table.status === 'Empty Seat',
            'text-green-50': table.status === 'Order Placed' || table.status === 'All Order Served' || table.status === 'Pending Order',
            'text-orange-25': table.status === 'Pending Clearance',
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

const mergeTable = () => {
    form.customer_id = isSelectedCustomer.value.id;
    const currentTable = zones.value.flatMap((zone) => zone.tables).find((table) => table.id === props.currentOrderTable.id);
    form.tables.push(currentTable);
    form.post(route('orders.mergeTable'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            setTimeout(() => {
                showMessage({
                    severity: 'success',
                    summary: `Selected table has been successfully merged with '${props.currentOrderTable.table_no}'.`
                })
            }, 200);
            form.reset();
            emit('fetchZones');
            closeConfirm();
            emit('close');
            emit('closeDrawer');
        }
    })
}

const openConfirm = () => {
    const isCheckedIn = form.tables.flatMap((table) => table.order_tables)
                                    .filter((order_table) => !!order_table.order.customer_id)
                                    .map((order_table) => order_table.order.customer_id);

    isCheckedIn.push(props.currentOrderCustomer.id);
    checkedIn.value = customers.value.filter((customer) =>
        isCheckedIn.includes(customer.id)
    );

    isSelectedCustomer.value = checkedIn.value.length > 0 ? checkedIn.value[0] : null;
    isConfirmShow.value = true;
};


const closeConfirm = () => {
    isConfirmShow.value = false;
}

watch(() => zones.value, populateTabs, { immediate: true });

onMounted(() => {
    getAllZones();
    getAllCustomer();
})
</script>

<template>
    <form @submit.prevent="mergeTable">
        <div class="flex flex-col items-start gap-6 self-stretch pb-6">
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
                    <div class="flex flex-col px-6 items-start gap-6 self-stretch">
                        <div class="grid grid-cols-6 items-start content-start gap-6 self-stretch flex-wrap">
                        <template v-for="zone in zones">
                            <template v-for="table in zone.tables">
                                <div class="flex flex-col p-6 justify-center items-center gap-1 flex-[1_0_0] rounded-[5px] relative" 
                                    :class="getTableClasses(table).state.value"
                                    @click="table.id === props.currentOrderTable.id || !!mergedTables.find((mergeTable) => mergeTable === table) ? '' : addToMerged(table)"
                                >
                                    <span :class="getTableClasses(table).text.value">{{ table.table_no }}</span>
                                    <div :class="getTableClasses(table).duration.value" v-if="table.status !== 'Empty Seat'">
                                        {{ getCurrentOrderTableDuration(table) }}
                                    </div>
                                    <div class="text-base text-primary-900 font-normal text-center" v-else>{{ table.seat }} seats</div>
                                    <Checkbox 
                                        :checked="!!form.tables.find((formTable) => formTable.id === table.id)"
                                        :disabled="table.id === props.currentOrderTable.id || !!mergedTables.find((mergeTable) => mergeTable === table)"
                                        class="absolute top-[11px] right-[12px]"
                                    />
                                    <MergedIcon class="text-white size-5 absolute left-2 top-2" v-if="isMerged(table)" />
                                </div>
                            </template>
                        </template>
                        </div>
                    </div>
                </template>
                <!-- <template  -->
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
                        @click="mergeTable"
                    >
                        Confirm
                    </Button>
                </div>
            </div>
        </Modal>
    </form>

</template>


