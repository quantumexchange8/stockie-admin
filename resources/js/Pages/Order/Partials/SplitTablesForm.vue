<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { FileBundleIllust } from '@/Components/Icons/illus';
import { CheckedIcon, DefaultIcon, MergedIcon, WarningIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import TabView from '@/Components/TabView.vue';
import { useCustomToast, usePhoneUtils } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import { onMounted, onUnmounted, ref, watch, computed } from 'vue';
import ReassignOrder from './ReassignOrder.vue';

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
const zones = ref([]);
const tabs = ref([]);
const isLoading = ref(false);
// const tableNames = ref('--');
// const mergedTables = ref([]);
const isConfirmShow = ref(false);
const isSelectedCustomer = ref();
const isReassignOrderModalOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const customers = ref([]);
const checkedIn = ref([]);
const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();

const emit = defineEmits(['close', 'closeDrawer', 'closeOrderDetails']);

const getAllZones = async() => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('orders.getAllZones'));
        zones.value = response.data;

        form.tables.tables_to_remain = zones.value
                ?.flatMap((zone) => zone.tables)
                .filter((table) =>
                    order.value.order_table.some((orderTable) => orderTable.table_id === table.id)
                ) ?? []

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
 
const mergedTables = computed(() => {
    if (!zones.value) return [];
    
    return zones.value
        ?.flatMap((zone) => zone.tables)
        .filter((table) =>
            order.value.order_table?.some((orderTable) => orderTable.table_id === table.id) ?? null
        );
    ;
});

const form = useForm({
    id: props.currentOrderTable.id,
    customer_id: props.currentOrderCustomer?.id ?? '',
    splitType: 'unmerge',
    tables: {
        tables_to_remain: [],
        tables_to_split: [],
    },
})

const updateTable = (targetTable) => {
    const remainTable = form.tables.tables_to_remain.find((table) => table.id === targetTable.id);
    if (remainTable) {
        const index = form.tables.tables_to_remain.indexOf(remainTable);
        form.tables.tables_to_remain.splice(index, 1); 

        form.tables.tables_to_split.push(targetTable);
    } else {
        const index = form.tables.tables_to_split.indexOf(remainTable);
        form.tables.tables_to_split.splice(index, 1); 

        form.tables.tables_to_remain.push(targetTable);
    }
    
    // // Find if there is any table already merged with the target table.
    // const matchingTables = zones.value
    //     .flatMap((zone) => zone.tables)
    //     .filter(
    //         (table) =>
    //             table.order_id &&
    //             table.order_id === targetTable.order_id &&
    //             table.id !== targetTable.id
    //     );
    
    // matchingTables.forEach((matchingTable) => {
    //     // If exists then push into mergedTables to show disabled state on layout
    //     // const existingIndex = mergedTables.value.findIndex(
    //     //     (table) => table.id === matchingTable.id
    //     // );
    //     // if (existingIndex > -1) {
    //     //     mergedTables.value.splice(existingIndex, 1); 
    //     // } else {
    //     //     mergedTables.value.push(matchingTable); 
    //     // }

    //     // At the same time push into form.tables since it will be merged together
    //     const formIndex = form.tables.findIndex(
    //         (table) => table.id === matchingTable.id
    //     );
    //     if (formIndex > -1) {
    //         form.tables.splice(formIndex, 1);
    //     } else {
    //         form.tables.push(matchingTable);
    //     }
    // });
    
    // Finally update the tableNames string
    
};

const populateTabs = () => {
    tabs.value = ['All'];
    for (const zone of zones.value) {
        if (zone.text) { 
            tabs.value.push(zone.text);
        }
    }
};

const unmergeTables = () => {
    form.post(route('orders.splitTable'), {
        preserveScroll: true,
        onSuccess: () => {
            showMessage({
                severity: 'success',
                summary: "You’ve successfully unmerged the selected table(s)."
            });
            isDirty.value = false;
            closeModal('leave');
            closeAll();
        }
    });
};


const openConfirm = () => {
    order.value.order_table.sort((a, b) => b.id - a.id)[0];

    if (props.currentOrderTable.status === 'Pending Clearance' || (props.currentOrderTable.status === 'Pending Order' && order.value.order_items.length == 0)) {
        unmergeTables();
        
    } else {
        isConfirmShow.value = true;
    }

};


const closeConfirm = () => {
    isConfirmShow.value = false;

    unmergeTables();
}


const openReassignOrderModal = () => {
    closeConfirm();
    isReassignOrderModalOpen.value = true;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isReassignOrderModalOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isReassignOrderModalOpen.value = false;
            break;
        }
    }
}

const closeAll = () => {
    emit('close');
    emit('closeOrderDetails');
};

const mergeTable = async () => {
    form.customer_id = isSelectedCustomer.value?.id;
    
    const tables = zones.value.flatMap((zone) => zone.tables);

    tables.forEach((table) => {
        if (order.value.order_table.some((orderTable) => orderTable.table_id === table.id)) {
            if (!form.tables.find((formTable) => formTable.id === table.id)) {
                form.tables.push(table);
            }
        }
    })
    
    
    // try {
    //     const response = await axios.post('/order-management/orders/mergeTable', form);

    //     setTimeout(() => {
    //         showMessage({
    //             severity: 'success',
    //             summary: `Selected table has been successfully merged with '${props.currentOrderTable.table_no}'.`
    //         })
    //     }, 200);

    //     form.reset();
    //     emit('closeOrderDetails');
    //     closeConfirm();
    //     emit('close');
    // } catch (error) {
    //     console.error(error);
    // } finally {

    // }
    // form.post(route('orders.mergeTable'), {
    //     preserveScroll: true,
    //     preserveState: true,
    //     onSuccess: () => {
    //     }
    // })
}
const toRemainTableNames = computed(() => {
    return form.tables.tables_to_remain.length === 1
        ?  "--"
        : form.tables.tables_to_remain
            .filter((table) => table.id !== props.currentOrderTable.id)
            .map((table) => table.table_no)
            .join(", ");
})

// const toReassignTableNames = computed(() => {
//     return form.tables.tables_to_split.length === 1
//         ?  "--"
//         : form.tables.tables_to_split
//             .filter((table) => table.id !== props.currentOrderTable.id)
//             .map((table) => table.table_no)
//             .join(", ");
// })

watch(() => zones.value, populateTabs, { immediate: true });
watch(() => props.currentOrder, (newValue) => (order.value = newValue));
watch(() => props.currentOrderCustomer, (newValue) => (form.customer_id = newValue?.id ?? ''));

onMounted(() => {
    getAllZones();
    getAllCustomer();
})
</script>

<template>
    <div class="flex flex-col px-6 items-start gap-6 self-stretch max-h-[calc(100dvh-28.4rem)] overflow-auto scrollbar-webkit scrollbar-thin">
        <div class="grid grid-cols-6 items-start content-start gap-6 self-stretch flex-wrap">
            <template v-for="table in mergedTables">
                <!-- <template v-if=""></template> -->
                <div class="flex flex-col p-6 justify-center items-center gap-1 flex-[1_0_0] rounded-[5px] relative border border-solid bg-white border-grey-100" 
                    :class="table.id === props.currentOrderTable.id ? 'cursor-not-allowed' : 'cursor-pointer'"
                    @click="table.id === props.currentOrderTable.id ? '' : updateTable(table)"
                >
                    <span class="text-xl font-bold self-stretch text-center text-primary-900">{{ table.table_no }}</span>
                    <div class="text-base text-primary-900 font-normal text-center">{{ table.id === props.currentOrderTable.id ? 'Main table' : 'Merged' }}</div>
                    <Checkbox 
                        :checked="!!form.tables.tables_to_remain.find((formTable) => formTable.id === table.id)"
                        :disabled="table.id === props.currentOrderTable.id"
                        class="absolute top-[11px] right-[12px]"
                    />
                </div>
            </template>
        </div>
    </div>

    <div class="flex flex-col px-6 pt-6 pb-2 items-center gap-4 self-stretch rounded-b-[5px] bg-white shadow-[0_-8px_16.6px_0_rgba(0,0,0,0.04)] mx-[-20px]">
        <div class="flex h-[25px] items-end gap-2.5 self-stretch">
            <span class="flex-[1_0_0] self-stretch text-grey-950 text-base font-normal">
                Table merged with:
            </span>
            <span>
                {{ toRemainTableNames }}
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
                :disabled="form.tables.tables_to_split.length == 0"
                @click="openConfirm()"
            >
                Confirm
            </Button>
        </div>
    </div>

    <!-- <Modal
            :show="isConfirmShow"
            :title="'Reassign '"
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
        </Modal> -->

    <Modal
        :maxWidth="'2xs'"
        :closeable="true"
        :show="isConfirmShow"
        @close="closeConfirm"
        :withHeader="false"
    >
        <div class="w-full flex flex-col gap-9" >
            <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] pt-6 mx-[-24px] mt-[-24px]">
                <FileBundleIllust />
            </div>
            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-1 text-center">
                    <span class="text-primary-900 text-lg font-medium self-stretch">Reassign order?</span>
                    <span class="text-grey-900 text-base font-medium self-stretch">Would you like to reassign the order from the main table to the unmerged table?</span>
                </div>
            </div>

            <div class="flex gap-3 w-full">
                <Button
                    variant="tertiary"
                    size="lg"
                    type="button"
                    @click="closeConfirm"
                >
                    No
                </Button>
                <Button
                    size="lg"
                    type="button"
                    @click="openReassignOrderModal"
                >
                    Reassign
                </Button>
            </div>
        </div>
    </Modal>
    
    <Modal
        :title="'Reassign Order'"
        :maxWidth="'xl'"
        :closeable="true"
        :show="isReassignOrderModalOpen"
        @close="closeModal('close')"
    >
        <ReassignOrder
            :currentTable="form.tables.tables_to_remain.find((table) => table.id === props.currentOrderTable.id)"
            :currentTableName="toRemainTableNames"
            :currentTables="form.tables.tables_to_remain"
            :targetTables="form.tables.tables_to_split"
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


