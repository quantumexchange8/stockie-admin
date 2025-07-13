<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { UndetectableIllus, MergeTableIllust } from '@/Components/Icons/illus';
import { BreakIcon, CheckedIcon, CheckInIcon, CheckOutIcon, DefaultIcon, MergedIcon, WarningIcon } from '@/Components/Icons/solid';
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
    selectedAttendance: Object,
    selectedWaiter: Object,
})

const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();

const attendance = ref('');
const waiter = ref(props.selectedWaiter);
const isLoading = ref(false);

const emit = defineEmits(['update:attendance', 'close']);

// const form = useForm({
//     selectedItem: props.selectedItem,
// });

// const filterMergedHistories = (records) => {
//     return records.filter((record) => 
//         record.type === 'keep' 
//             ? record.keep_item.order_item_subitem.product_item.inventory_item_id === props.selectedItem.id
//             : record.inventory_id === props.selectedItem.inventory_id && record.inventory_item === props.selectedItem.item_name
//     );
// };

// const filterStockHistories = (records) => {
//     return records.filter((record) => 
//         record.inventory_id === props.selectedItem.inventory_id && record.inventory_item === props.selectedItem.item_name
//     );
// };

const getAttendanceDetail = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/waiter/getAttendanceListDetail/${props.selectedWaiter.id}`, {
            method: 'GET',
            params: {
                target_date: props.selectedAttendance.check_in,
            }
        });

        attendance.value = response.data;
        emit('update:attendance', response.data);

    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false;
    }
};

// Calculate the number of days crossed based on the start time and end time
const calculateDayDifference = (startTime, endTime) => {
  if (!startTime || !endTime) return 0;
  
  const startDate = dayjs(startTime).startOf('day');
  const endDate = dayjs(endTime).startOf('day');
  
  return endDate.diff(startDate, 'day');
};

// const keptItemFlowRecords = computed(() => {
//     let filteredStockHistories = stockHistories.value.filter((record) => record.current_stock < 0 || (record.old_stock < 0 && record.current_stock > 0));

    // let mergedRecords = [...filteredStockHistories, ...mergedHistories.value];

//     return mergedHistories.value;
// });

onMounted(() => {
    getAttendanceDetail();
})

watch(() => props.selectedAttendance, (newValue) => {
    attendance.value = newValue;
}, { deep: true });

watch(() => props.selectedWaiter, (newValue) => {
    waiter.value = newValue;
}, { deep: true });
</script>

<template>
    <div class="flex flex-col items-start gap-y-6 self-stretch">
        <div class="flex flex-col items-start gap-y-1 self-stretch">
            <div class="flex w-full py-1 gap-x-2 self-stretch items-center">
                <p class="w-1/4 text-base font-normal text-grey-500 truncate line-clamp-1">Date</p>
                <p class="w-3/4 text-base font-medium text-grey-900 truncate line-clamp-1 self-stretch">{{ attendance.date ?? '-' }}</p>
            </div>
            <div class="flex w-full py-1 gap-x-2 self-stretch items-center">
                <p class="w-1/4 text-base font-normal text-grey-500 truncate line-clamp-1">Shift</p>
                <p class="w-3/4 text-base font-medium text-grey-900 truncate line-clamp-1 self-stretch">{{ attendance.shift?.shifts?.shift_name ?? '-' }}</p>
            </div>
            <div class="flex w-full py-1 gap-x-2 self-stretch items-center">
                <p class="w-1/4 text-base font-normal text-grey-500 truncate line-clamp-1">Work</p>
                <p class="w-3/4 text-base font-medium text-grey-900 truncate line-clamp-1 self-stretch">{{ attendance.work_duration ?? '-' }}</p>
            </div>
            <div class="flex w-full py-1 gap-x-2 self-stretch items-center">
                <p class="w-1/4 text-base font-normal text-grey-500 truncate line-clamp-1">Break</p>
                <p class="w-3/4 text-base font-medium text-grey-900 truncate line-clamp-1 self-stretch">{{ attendance.break_duration ?? '-' }}</p>
            </div>
            <div class="flex w-full py-1 gap-x-2 self-stretch items-center" v-if="waiter.employment_type === 'Part-time'">
                <p class="w-1/4 text-base font-normal text-grey-500 truncate line-clamp-1">Est. rate</p>
                <p class="w-3/4 text-base font-medium text-grey-900 truncate line-clamp-1 self-stretch">{{ attendance.earnings ?? 'RM 0.00' }}</p>
            </div>
        </div>

       <hr class="common-hr" />
       
       <div class="flex flex-col items-start gap-y-4 self-stretch max-h-[calc(100dvh-24rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
            <!-- Check Out -->
            <div class="flex w-full py-2 gap-x-3 items-center" v-if="attendance.check_out">
                <p class="w-3/12 text-base font-semibold text-grey-950 truncate line-clamp-1">
                    {{ dayjs(attendance.check_out).format('HH:mm') }}
                    <span v-if="calculateDayDifference(attendance.check_in, attendance.check_out) > 0" class="text-sm">
                        (+{{ calculateDayDifference(attendance.check_in, attendance.check_out) }})
                    </span>
                </p>
                <div class="w-9/12 flex gap-x-3 items-center">
                    <div class="relative">
                        <CheckInIcon />
                        <div class="absolute inset-x-[50%] w-px h-8 bg-grey-200"></div>
                    </div>
                    <p class="text-sm font-normal text-grey-950 truncate line-clamp-1">Clock out</p>
                </div>
            </div>

            <!-- Breaks (in reverse order) -->
            <template v-if="attendance.break">
                <template v-for="(record, index) in attendance.break.sort((a, b) => new Date(b.start) - new Date(a.start))" :key="index">
                    <!-- Break End -->
                    <div class="flex w-full py-2 gap-x-3 items-center" v-if="record.end">
                        <p class="w-3/12 text-base font-semibold text-grey-950 truncate line-clamp-1">
                            {{ dayjs(record.end).format('HH:mm') }}
                            <span v-if="calculateDayDifference(attendance.check_in, record.end) > 0" class="text-sm">
                                (+{{ calculateDayDifference(attendance.check_in, record.end) }})
                            </span>
                        </p>
                        <div class="w-9/12 flex gap-x-3 items-center">
                            <div class="relative">
                                <BreakIcon />
                                <div class="absolute inset-x-[50%] w-px h-8 bg-grey-200"></div>
                            </div>
                            <p class="text-sm font-normal text-grey-950 truncate line-clamp-1">Break end</p>
                        </div>
                    </div>

                    <!-- Break Start -->
                    <div class="flex w-full py-2 gap-x-3 items-center" v-if="record.start">
                        <p class="w-3/12 text-base font-semibold text-grey-950 truncate line-clamp-1">
                            {{ dayjs(record.start).format('HH:mm') }}
                            <span v-if="calculateDayDifference(attendance.check_in, record.start) > 0" class="text-sm">
                                (+{{ calculateDayDifference(attendance.check_in, record.start) }})
                            </span>
                        </p>
                        <div class="w-9/12 flex gap-x-3 items-center">
                            <div class="relative">
                                <BreakIcon />
                                <div class="absolute inset-x-[50%] w-px h-8 bg-grey-200"></div>
                            </div>
                            <p class="text-sm font-normal text-grey-950 truncate line-clamp-1">Break start</p>
                        </div>
                    </div>
                </template>
            </template>
            
            <!-- Check In -->
            <div class="flex w-full py-2 gap-x-3 items-center" v-if="attendance.check_in">
                <p class="w-3/12 text-base font-semibold text-grey-950 truncate line-clamp-1">{{ dayjs(attendance.check_in).format('HH:mm') }}</p>
                <div class="w-9/12 flex gap-x-3 items-center">
                    <CheckOutIcon />
                    <p class="text-sm font-normal text-grey-950 truncate line-clamp-1">Clock in</p>
                </div>
            </div>
        </div>
    </div>
</template>
