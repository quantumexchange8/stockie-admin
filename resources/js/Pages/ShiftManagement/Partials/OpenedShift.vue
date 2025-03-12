<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Button from '@/Components/Button.vue';
import Tag from '@/Components/Tag.vue';
import Toast from '@/Components/Toast.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { ref, watch } from 'vue';
import { useInputValidator, useCustomToast } from '@/Composables';
import { ShiftWorkerIcon } from '@/Components/Icons/solid';

const props = defineProps({
    currentOpenedShift: Object,
});

const emit = defineEmits(['update:shift-listing']);

const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const openedShift = ref(props.currentOpenedShift);
const isLoading = ref(false);

// const openNewShift = async () => { 
//     isLoading.value = true;

//     try {
//         const response = await axios.post(`/shift-management/shift-record`);
        
//         shiftTransactionsList.value = response.data; 
//         setTimeout(() => { 
//             showMessage({ 
//                 severity: 'success', 
//                 summary: "Shift successfully opened. You're all set to start the day!", 
//             }); 
//         }, 200); 
        
//         // showMessage({ 
//         //     severity: 'error', 
//         //     summary: "You can't open more than one shift at the same time.", 
//         //     detail: "To proceed, please close the current shift first.", 
//         // }); 
//     } catch (error) {
//         console.error(error);
//     } finally {
//         isLoading.value = false;
//     }
// };

watch(() => props.currentOpenedShift, (newValue) => {
    openedShift.value = newValue;
});
</script>

<template>
    <div 
        class="col-span-full md:col-span-7 lg:col-span-8 flex flex-col self-stretch items-center justify-start bg-white shadow-sm border border-grey-100 rounded-[5px] 
            h-[calc(100dvh-12rem)] shrink-0 overflow-y-auto scrollbar-thin scrollbar-webkit"
            :class="openedShift ? 'justify-start' : 'justify-center'"
        >
        <template v-if="openedShift">
            <div class="flex flex-col gap-y-5 p-5 justify-center items-center self-stretch border-b border-grey-100">
                <div class="flex flex-col gap-y-2 items-center self-stretch">
                    <p class="truncate self-stretch text-grey-950 text-lg font-bold text-center">{{ `Shift #${openedShift.shift_no}` }}</p>
                    <p class="truncate self-stretch text-grey-950 text-sm font-normal text-center">{{ dayjs(openedShift.shift_opened).format('YYYY-MM-DD HH:mm') }}</p>
                </div>

                <div class="flex flex-col gap-y-3 items-start self-stretch">
                    <div class="flex gap-x-3 items-center self-stretch">
                        <Button
                            :type="'button'"
                            :variant="'red-tertiary'"
                            :size="'lg'"
                        >
                            Delete shift
                        </Button>
                        <Button
                            :type="'button'"
                            :variant="'secondary'"
                            :size="'lg'"
                        >
                            Pay in/out
                        </Button>
                    </div>

                    <Button
                        :type="'button'"
                        :size="'lg'"
                    >
                        Close shift
                    </Button>
                </div>
            </div>

            <div class="flex flex-col items-start self-stretch gap-y-6 p-5">
                <div class="flex flex-col items-start self-stretch gap-y-2">
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">Opening date</p>
                        <p class="text-grey-950 font-semibold text-base text-right">{{ dayjs(openedShift.shift_opened).format('YYYY-MM-DD HH:mm') }}</p>
                    </div>
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">Opened by</p>
                        <div class="flex gap-x-1 items-center">
                            <p class="truncate font-semibold text-base text-grey-950">{{ openedShift.opened_by.full_name }}</p>
                            <img 
                                :src="openedShift.image ? openedShift.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt="ProductImage" 
                                class="size-4 object-fit rounded-full border borer-grey-100"
                            />
                        </div>
                    </div>
                </div>

                <hr class="w-full h-[1px] bg-grey-100">

                <div class="flex flex-col items-start self-stretch gap-y-2">
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">Starting cash</p>
                        <p class="text-grey-950 font-semibold text-base text-right">{{ Number(openedShift.starting_cash).toFixed(2) }}</p>
                    </div>
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">Paid in</p>
                        <p class="text-grey-950 font-semibold text-base text-right">{{ Number(openedShift.paid_in).toFixed(2) }}</p>
                    </div>
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">Paid out</p>
                        <p class="text-grey-950 font-semibold text-base text-right">{{ Number(openedShift.paid_out).toFixed(2) }}</p>
                    </div>
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">Closing cash</p>
                        <p class="text-grey-950 font-semibold text-base text-right">{{ openedShift.closing_cash ? Number(openedShift.closing_cash).toFixed(2) : 'N/A' }}</p>
                    </div>
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">Cash difference</p>
                        <p class="text-grey-950 font-semibold text-base text-right">{{ openedShift.difference ? Number(openedShift.difference).toFixed(2) : 'N/A' }}</p>
                    </div>
                </div>

                <hr class="w-full h-[1px] bg-grey-100">

                <div class="flex flex-col items-start self-stretch gap-y-2">
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">Closing date</p>
                        <p class="text-grey-950 font-semibold text-base text-right">{{ openedShift.shift_closed ? dayjs(openedShift.shift_closed).format('YYYY-MM-DD HH:mm') : 'N/A' }}</p>
                    </div>
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">Closed by</p>
                        <p class="text-grey-950 font-semibold text-base text-right">{{ openedShift.closed_by?.full_name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </template>
        <template v-else>
            <div class="flex w-full flex-col items-center justify-center gap-5">
                <ShiftWorkerIcon />
                <div class="flex flex-col gap-y-1 items-center">
                    <span class="text-grey-950 text-md font-semibold">No shift is opened yet</span>
                    <span class="text-grey-950 text-sm font-normal">Effortlessly keep an eye on the register shift at the store!</span>
                </div>
            </div>
        </template>
    </div>   
</template>

