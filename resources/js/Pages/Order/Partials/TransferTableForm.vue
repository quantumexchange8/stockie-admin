<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { UndetectableIllus, MergeTableIllust } from '@/Components/Icons/illus';
import { CheckedIcon, DefaultIcon, MergedIcon, WarningIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import TabView from '@/Components/TabView.vue';
import RadioButton from '@/Components/RadioButton.vue';
import { useCustomToast, usePhoneUtils } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import { computed } from 'vue';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import TransferItemTableForm from './TransferItemTableForm.vue';

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
    },
    transferType: String
})

const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();

const zones = ref('');
const tabs = ref([]);
const order = ref(props.currentOrder);
const isLoading = ref(false);
const tableNames = ref('--');
const tables = ref([]);
const mergedTables = ref([]);
const isConfirmShow = ref(false);
const isSelectedCustomer = ref();
const selectedTable = ref('');
const customers = ref();
const checkedIn = ref([]);
const isTransferItemModalOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);

const emit = defineEmits(['close', 'closeDrawer', 'fetchZones']);

const form = useForm({
    customer_id: props.currentOrderCustomer?.id ?? null,
    current_table: '',
    current_matched_tables: [],
    target_table: '',
    target_matched_tables: [],
    transfer_type: props.transferType
})

// const addToMerged = (targetTable) => {
//     const index = form.tables.indexOf(targetTable);
//     if (index > -1) {
//         form.tables.splice(index, 1); 
//     } else {
//         form.tables.push(targetTable); 
//     }
    
//     // Find if there is any table already merged with the target table.
//     const matchingTables = zones.value
//         .flatMap((zone) => zone.tables)
//         .filter(
//             (table) =>
//                 table.order_id &&
//                 table.order_id === targetTable.order_id &&
//                 table.id !== targetTable.id
//         );
    
//     matchingTables.forEach((matchingTable) => {
//         // If exists then push into mergedTables to show disabled state on layout
//         const existingIndex = mergedTables.value.findIndex(
//             (table) => table.id === matchingTable.id
//         );
//         if (existingIndex > -1) {
//             mergedTables.value.splice(existingIndex, 1); 
//         } else {
//             mergedTables.value.push(matchingTable); 
//         }

//         // At the same time push into form.tables since it will be merged together
//         const formIndex = form.tables.findIndex(
//             (table) => table.id === matchingTable.id
//         );
//         if (formIndex > -1) {
//             form.tables.splice(formIndex, 1);
//         } else {
//             form.tables.push(matchingTable);
//         }
//     });
    
//     // Finally update the tableNames string
//     tableNames.value = form.tables.map((table) => table.table_no).join(", ");
//     if (form.tables.length === 0) tableNames.value = "--";
// };

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
    let currentOrderTable = table.order_tables.toSorted((a, b) => dayjs(b.created_at).diff(dayjs(a.created_at)));
    
    return setupDuration(currentOrderTable[0].created_at);
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

const openTransferItemModal = () => {
    isTransferItemModalOpen.value = true;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isTransferItemModalOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isTransferItemModalOpen.value = false;
            break;
        }
    }
}

const closeAll = () => {
    emit('fetchZones');
    emit('close');
    emit('closeDrawer');
};
const submit = () => {
    const selectedCustomer = isSelectedCustomer.value?.id ?? form.customer_id;
    form.customer_id = selectedCustomer;
    form.current_matched_tables = [];

    const tablesArray = zones.value.flatMap((zone) => zone.tables);

    tablesArray.forEach((table) => {
        if (order.value.order_table.some((orderTable) => orderTable.table_id === table.id)) {
            if (!form.current_matched_tables.find((formTable) => formTable.id === table.id)) {
                form.current_matched_tables.push(table);
            }
        }
    })

    form.post(route('orders.transferTableOrder'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            setTimeout(() => {
                showMessage({
                    severity: 'success',
                    summary: `This table's order has been successfully transferred to '${tableNames.value}'.`
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

const selectTable = (targetTable) => {
    selectedTable.value = targetTable.id;
    form.target_table = targetTable;

    // Find if there is any table already merged with the target table.
    const matchingTables = zones.value
        .flatMap((zone) => zone.tables)
        .filter((table) => {
            if ((targetTable.order_id && table.order_id === targetTable.order_id) || targetTable.id === table.id) {
                return table;
            }
        });

    let matchedTables = [];

    matchingTables.forEach((matchingTable) => {
        matchedTables.push(matchingTable); 
    });
    
    // If exists then push into tables to show disabled state on layout
    tables.value = matchedTables;
    form.target_matched_tables = matchedTables;

    // Finally update the tableNames string
    tableNames.value = matchingTables.map((table) => table.table_no).join(", ");
    if (matchingTables.length === 0) tableNames.value = "--";
};

const closeConfirm = () => {
    isConfirmShow.value = false;
}

const openConfirm = () => {
    const isCheckedIn = form.target_table.order_tables
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
        submit();
    }
};

watch(zones, (newValue) => {
    populateTabs();
    if (newValue) {
        form.current_table = newValue
                .flatMap((zone) => zone.tables)
                .find((table) => table.id === props.currentOrderTable.id);
    }
}, { immediate: true });

watch(() => props.currentOrder, (newValue) => (order.value = newValue));

onMounted(() => {
    getAllZones();
    getAllCustomer();
})

const currentTableName = computed(() => {
    const currTable = props.currentOrderTable;

    const currentMtchingTables = zones.value
            .flatMap((zone) => zone.tables)
            .filter((table) => {
                if ((currTable.order_id && table.order_id === currTable.order_id) || currTable.id === table.id) {
                    return table;
                }
            });

    return currentMtchingTables.map((table) => table.table_no).join(", ");
})

const currentTables = computed(() => {
    const currTable = props.currentOrderTable;

    return zones.value
            .flatMap((zone) => zone.tables)
            .filter((table) => {
                if ((currTable.order_id && table.order_id === currTable.order_id) || currTable.id === table.id) {
                    return table;
                }
            });
})

const hasTables = computed(() => {
    return zones.value.some((zone) => zone.tables.length > 0)
})

</script>

<template>
    <form @submit.prevent="submit">
        <div class="flex flex-col items-start gap-6 self-stretch pb-6">
            <TabView :tabs="tabs" v-if="zones.length">
                <template #all>
                    <div class="flex flex-col px-6 items-start gap-6 self-stretch">
                        <div class="grid grid-cols-6 items-start content-start gap-6 self-stretch flex-wrap max-h-[calc(100dvh-22rem)] overflow-y-auto scrollbar-webkit scrollbar-thin">
                            <template v-if="hasTables">
                                <template v-for="zone in zones">
                                    <template v-for="table in zone.tables">
                                        <div class="flex flex-col p-6 justify-center items-center gap-1 flex-[1_0_0] rounded-[5px] relative cursor-pointer" 
                                            :class="getTableClasses(table).state.value"
                                            @click="table.id === props.currentOrderTable.id || !!tables.find((selectedTable) => selectedTable === table) || order.order_table.some((orderTable) => orderTable.table_id === table.id) ? '' : selectTable(table)"
                                        >
                                            <span :class="getTableClasses(table).text.value">{{ table.table_no }}</span>
                                            <div :class="getTableClasses(table).duration.value" v-if="table.status !== 'Empty Seat'">
                                                {{ getCurrentOrderTableDuration(table) }}
                                            </div>
                                            <div class="text-base text-primary-900 font-normal text-center" v-else>{{ table.seat }} seats</div>
                                            <RadioButton 
                                                :name="'table'"
                                                :dynamic="false"
                                                :value="table.id"
                                                class="!w-fit absolute top-[11px] right-[12px]"
                                                :errorMessage="''"
                                                :disabled="form.target_table !== table && (table.id === props.currentOrderTable.id || !!tables.find((selectedTable) => selectedTable === table) || order.order_table.some((orderTable) => orderTable.table_id === table.id))"
                                                v-model:checked="selectedTable"
                                                @onChange="form.target_table = table"
                                            />
                                            <MergedIcon class="text-white size-5 absolute left-2 top-2" v-if="isMerged(table)" />
                                        </div>
                                    </template>
                                </template>
                            </template>
                            <template v-else>
                                <div class="flex flex-col items-center justify-center gap-5">
                                    <UndetectableIllus />
                                    <span class="text-primary-900 text-sm font-medium">No table in this zone..</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <template
                    v-for="tab in tabs.filter(tab => tab !== 'All').map((tab) => tab.toLowerCase().replace(/[/\s_]+/g, '-'))"
                    :key="tab"
                    v-slot:[tab]
                >
                    <div class="flex flex-col px-6 items-start gap-6 self-stretch">
                        <div class="grid grid-cols-6 items-start content-start gap-6 self-stretch flex-wrap max-h-[calc(100dvh-22rem)] overflow-y-auto scrollbar-webkit scrollbar-thin">
                            <template v-for="zone in zones.filter(zone => zone.text.toLowerCase().replace(/[/\s_]+/g, '-') === tab)">
                                <template v-if="zone.tables.length > 0">
                                    <template v-for="table in zone.tables">
                                        <div class="flex flex-col p-6 justify-center items-center gap-1 flex-[1_0_0] rounded-[5px] relative" 
                                            :class="getTableClasses(table).state.value"
                                            @click="table.id === props.currentOrderTable.id || !!tables.find((selectedTable) => selectedTable === table) || order.order_table.some((orderTable) => orderTable.table_id === table.id) ? '' : selectTable(table)"
                                        >
                                            <span :class="getTableClasses(table).text.value">{{ table.table_no }}</span>
                                            <div :class="getTableClasses(table).duration.value" v-if="table.status !== 'Empty Seat'">
                                                {{ getCurrentOrderTableDuration(table) }}
                                            </div>
                                            <div class="text-base text-primary-900 font-normal text-center" v-else>{{ table.seat }} seats</div>
                                            <RadioButton 
                                                :name="'table'"
                                                :dynamic="false"
                                                :value="table.id"
                                                class="!w-fit absolute top-[11px] right-[12px]"
                                                :errorMessage="''"
                                                :disabled="form.target_table !== table && (table.id === props.currentOrderTable.id || !!tables.find((selectedTable) => selectedTable === table) || order.order_table.some((orderTable) => orderTable.table_id === table.id))"
                                                v-model:checked="selectedTable"
                                                @onChange="form.target_table = table"
                                            />  
                                            <MergedIcon class="text-white size-5 absolute left-2 top-2" v-if="isMerged(table)" />
                                        </div>
                                    </template>
                                </template>
                                <template v-else>
                                    <div class="flex flex-col items-center justify-center gap-5">
                                        <UndetectableIllus />
                                        <span class="text-primary-900 text-sm font-medium">No table in this zone..</span>
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
                    Transfer to
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
                    :disabled="form.processing || !selectedTable"
                    @click="transferType === 'all' ? openConfirm() : openTransferItemModal()"
                >
                    Confirm
                </Button>
            </div>
        </div>
    </form>

    <Modal
        :show="isConfirmShow"
        :title="'Select a customer'"
        :maxWidth="'xs'"
        @close="closeConfirm"
    >
        <div class="flex flex-col items-start gap-6 rounded-[5px] bg-white">
            <div class="flex flex-col items-start gap-1 self-stretch">
                <span class="self-stretch text-grey-950 text-base font-bold">
                    The table you're merging with already has an existing customer
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
                    @click="submit"
                >
                    Confirm
                </Button>
            </div>
        </div>
    </Modal>
    
    <Modal
        :title="'Transfer item to new table'"
        :maxWidth="'xl'"
        :closeable="true"
        :show="isTransferItemModalOpen"
        @close="closeModal('close')"
    >
        <TransferItemTableForm
            :currentTable="form.current_table"
            :currentTableName="currentTableName"
            :currentTables="currentTables"
            :targetTable="form.target_table"
            :targetTableName="tableNames"
            :targetTables="tables"
            :customers="customers"
            @closeAll="closeAll"
            @closeModal="closeModal"
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
    </Modal>

</template>


