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

const props = defineProps({
    selectedItem: Object,
})

const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();

const zones = ref('');
const tabs = ref(['Stock Flow', 'Kept Item Flow']);
const isLoading = ref(false);
const tableNames = ref('--');
const tables = ref([]);
const mergedTables = ref([]);
const isConfirmShow = ref(false);
const isSelectedCustomer = ref();
const selectedTable = ref('');
const mergedHistories = ref();
const stockHistories = ref();
const checkedIn = ref([]);
const isTransferItemModalOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);

const emit = defineEmits(['close']);

const form = useForm({
    selectedItem: props.selectedItem,
});

const filterMergedHistories = (records) => {
    return records.filter((record) => 
        record.type === 'keep' 
            ? record.keep_item.order_item_subitem.product_item.inventory_item_id === props.selectedItem.id
            : record.inventory_id === props.selectedItem.inventory_id && record.inventory_item === props.selectedItem.item_name
    );
};

const filterStockHistories = (records) => {
    return records.filter((record) => 
        record.inventory_id === props.selectedItem.inventory_id && record.inventory_item === props.selectedItem.item_name
    );
};

const getStockFlowDetail = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('getStockFlowDetail', form));
        mergedHistories.value = filterMergedHistories(response.data.mergedHistories);
        stockHistories.value = filterStockHistories(response.data.stockHistories);

    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false;
    }
};

// const keptItemFlowRecords = computed(() => {
//     let filteredStockHistories = stockHistories.value.filter((record) => record.current_stock < 0 || (record.old_stock < 0 && record.current_stock > 0));

    // let mergedRecords = [...filteredStockHistories, ...mergedHistories.value];

//     return mergedHistories.value;
// });

onMounted(() => {
    getStockFlowDetail();
})

</script>

<template>
    <div class="flex flex-col items-start gap-6 self-stretch pb-6">
        <TabView :tabs="tabs">
            <template #stock-flow>
                <div class="w-full max-h-[calc(100dvh-20.4rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                    <table class="w-full border-spacing-3 border-collapse">
                        <thead class="bg-grey-100">
                            <tr>
                                <th class="w-[46%] py-2 px-3">
                                    <span class="flex justify-between items-center text-sm text-grey-950 font-semibold">
                                        Date
                                    </span> 
                                </th>
                                <th class="w-[18%] py-2 px-3">
                                    <span class="flex justify-between items-center text-sm text-grey-950 font-semibold">
                                        Sold
                                    </span>
                                </th>
                                <th class="w-[18%] py-2 px-3">
                                    <span class="flex justify-between items-center text-sm text-grey-950 font-semibold">
                                        Replenish
                                    </span>
                                </th>
                                <th class="w-[18%] py-2 px-3">
                                    <span class="flex justify-between items-center text-sm text-grey-950 font-semibold">
                                        Balance
                                    </span>
                                </th>   
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(record, index) in stockHistories" :key="index" class="border-b border-grey-100">
                                <td class="w-[46%]">
                                    <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden px-3 py-4">{{ dayjs(record.created_at).format('HH:mm, DD/MM/YYYY') }}</span>
                                </td>
                                <td class="w-[18%]">
                                    <div class="flex justify-start items-center gap-3 px-3">
                                        <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden py-4">{{ record.out }}</span>
                                    </div>
                                </td>
                                <td class="w-[18%]">
                                    <div class="flex justify-start items-center gap-3 px-3">
                                        <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden py-4">{{ record.in }}</span>
                                    </div>
                                </td>
                                <td class="w-[18%]">
                                    <div class="flex justify-start items-start gap-2 px-3">
                                        <span 
                                            :class="[
                                                'text-sm font-medium text-ellipsis overflow-hidden py-4',
                                                { 'text-grey-900': record.current_stock > 0 },
                                                { 'text-primary-500': record.current_stock <= 0 }
                                            ]"
                                        >
                                            {{ record.current_stock }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
            <template #kept-item-flow>
                <div class="w-full max-h-[calc(100dvh-20.4rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                    <table class="w-full border-spacing-3 border-collapse">
                        <thead class="bg-grey-100">
                            <tr>
                                <th class="w-[40%] py-2 px-3">
                                    <span class="flex justify-between items-center text-sm text-grey-950 font-semibold">
                                        Date
                                    </span> 
                                </th>
                                <th class="w-[15%] py-2 px-3">
                                    <span class="flex justify-between items-center text-sm text-grey-950 font-semibold">
                                        Kept
                                    </span>
                                </th>
                                <th class="w-[20%] py-2 px-3">
                                    <span class="flex justify-between items-center text-sm text-grey-950 font-semibold">
                                        Reallocated
                                    </span>
                                </th>
                                <th class="w-[25%] py-2 px-3">
                                    <span class="flex justify-between items-center text-sm text-grey-950 font-semibold">
                                        Kept Balance
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(record, index) in mergedHistories" :key="index" class="border-b border-grey-100">
                                <td class="w-[40%]">
                                    <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden px-3 py-4">{{ dayjs(record.created_at).format('HH:mm, DD/MM/YYYY') }}</span>
                                </td>
                                <td class="w-[15%]">
                                    <div class="flex justify-start items-center gap-3 px-3">
                                        <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden py-4">{{ record.kept ?? 0 }}</span>
                                    </div>
                                </td>
                                <td class="w-[20%]">
                                    <div class="flex justify-start items-start gap-2 px-3">
                                        <span 
                                            :class="[
                                                'text-sm font-medium text-ellipsis overflow-hidden py-4',
                                                { 'text-grey-900': record.reallocated == 0 },
                                                { 'text-green-500': record.reallocated > 0 },
                                                { 'text-primary-500': record.reallocated < 0 }
                                            ]"
                                        >
                                            {{ record.reallocated <= 0 ? record.reallocated : `+${record.reallocated}` }}
                                        </span>
                                    </div>
                                </td>
                                <td class="w-[25%]">
                                    <div class="flex justify-start items-center gap-3 px-3">
                                        <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden py-4">{{ record.kept_balance }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </TabView>
    </div>
</template>


