<script setup>
import axios from 'axios';
import { ref, computed, onMounted, watch } from 'vue'
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import Button from '@/Components/Button.vue'
import { useCustomToast, useInputValidator, usePhoneUtils } from '@/Composables/index.js';
import Modal from '@/Components/Modal.vue';
import Textarea from '@/Components/Textarea.vue';
import dayjs from 'dayjs';
import TabView from '@/Components/TabView.vue';

const props = defineProps({
    currentSelectedShift: Object,
});

const selectedShift = ref(props.currentSelectedShift);
const tabs = ref([
    { title: 'Shift Overview', disabled: false },
    { title: 'Pay In/Out History', disabled: false }
]);

watch(() => props.currentSelectedShift, (newValue) => {
    selectedShift.value = newValue;
});
</script>

<template>
    <div class="flex flex-col gap-8">
        <TabView 
            :withDisabled="true"
            :tabs="tabs" 
        >
            <template #shift-overview>
                <div class="flex flex-col gap-y-8 items-start self-stretch pr-1 max-h-[calc(100dvh-20rem)] shrink-0 overflow-y-auto scrollbar-thin scrollbar-webkit">
                    <div class="flex flex-col items-start gap-y-4 self-stretch">
                        <div class="flex items-center gap-x-4 self-stretch">
                            <div class="w-[6px] h-6 bg-primary-800"></div>
                            <p class="text-primary-900 font-semibold text-md">Sales detail</p>
                        </div>

                        <div class="flex flex-col items-start self-stretch gap-y-3">
                            <div class="flex flex-col items-start self-stretch gap-y-2">
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Cash Sales</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.cash_sales).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Card Sales</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.card_sales).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">E-Wallet Sales</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.ewallet_sales).toFixed(2) }}</p>
                                </div>
                            </div>

                            <hr class="w-full h-[1px] bg-white">

                            <div class="flex flex-col items-start self-stretch gap-y-2">
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Gross Sales</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.gross_sales).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">SST <!-- ({{ selectedShift.gross_sales > 0 ? ((selectedShift.sst_amount / selectedShift.gross_sales) * 100): 0 }}%) --></p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.sst_amount).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Service tax <!-- ({{ selectedShift.gross_sales > 0 ? ((selectedShift.service_tax_amount / selectedShift.gross_sales) * 100) : 0 }}%) --></p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.service_tax_amount).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Refunds</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">- RM {{ Number(selectedShift.total_refund).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Voids</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">- RM {{ Number(selectedShift.total_void).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Discounts</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">- RM {{ Number(selectedShift.total_discount).toFixed(2) }}</p>
                                </div>
                            </div>

                            <hr class="w-full h-[1px] bg-white">

                            <div class="flex flex-col items-start self-stretch gap-y-2">
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Net Sales (excl. taxes)</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.net_sales).toFixed(2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col items-start gap-y-4 self-stretch">
                        <div class="flex items-center gap-x-4 self-stretch">
                            <div class="w-[6px] h-6 bg-primary-800"></div>
                            <p class="text-primary-900 font-semibold text-md">Shift &amp cash drawer detail</p>
                        </div>

                        <div class="flex flex-col items-start self-stretch gap-y-3">
                            <div class="flex flex-col items-start self-stretch gap-y-2">
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Opening date</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">{{ dayjs(selectedShift.shift_opened).format('YYYY-MM-DD HH:mm') }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Opened by</p>
                                    <div class="flex gap-x-1 items-center">
                                        <p class="truncate font-semibold text-base text-grey-950">{{ selectedShift.opened_by.full_name }}</p>
                                        <img 
                                            :src="selectedShift.opened_by.image ? selectedShift.opened_by.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt="UserImage" 
                                            class="size-4 object-fit rounded-full border borer-grey-100"
                                        />
                                    </div>
                                </div>
                            </div>

                            <hr class="w-full h-[1px] bg-white">

                            <div class="flex flex-col items-start self-stretch gap-y-2">
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Starting cash</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.starting_cash).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Paid in</p>
                                    <p class="font-semibold text-base text-right text-grey-950">RM {{ Number(selectedShift.paid_in).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Paid out</p>
                                    <p class="font-semibold text-base text-right text-grey-950">- RM {{ Number(selectedShift.paid_out).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Cash refunds</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">- RM {{ Number(selectedShift.cash_refund).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Expected cash</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.expected_cash).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Closing cash</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.closing_cash).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Cash difference</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">{{ Number(selectedShift.difference) < 0 ? '-' : '' }}{{ ` RM ${Math.abs(Number(selectedShift.difference)).toFixed(2)}` }}</p>
                                </div>
                            </div>

                            <hr class="w-full h-[1px] bg-white">

                            <div class="flex flex-col items-start self-stretch gap-y-2">
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Closing date</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">{{ dayjs(selectedShift.shift_closed).format('YYYY-MM-DD HH:mm') }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">Closed by</p>
                                    <div class="flex gap-x-1 items-center">
                                        <p class="truncate font-semibold text-base text-grey-950 text-right">{{ selectedShift.closed_by.full_name }}</p>
                                        <img 
                                            :src="selectedShift.closed_by.image ? selectedShift.closed_by.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt="UserImage" 
                                            class="size-4 object-fit rounded-full border borer-grey-100"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #pay-in-out-history>
                <div class="flex flex-col gap-y-4" >
                    <div class="flex flex-col gap-1" >
                        <p class="text-grey-950 text-base font-bold">
                            Pay in/out History
                        </p>
                        <div class="text-grey-950 text-sm font-normal self-stretch" >
                            Your pay in/out history for this shift.
                        </div>
                    </div>

                    <div class="flex flex-col items-start self-stretch pr-1 max-h-[calc(100dvh-24rem)] shrink-0 overflow-y-auto scrollbar-thin scrollbar-webkit">
                        <div 
                            v-for="(history, index) in selectedShift.shift_pay_histories" 
                            :key="index"
                            class="flex py-3 flex-col items-end gap-4 self-stretch border-b border-grey-100"
                        >
                            <div class="flex justify-between items-center self-stretch">
                                <div class="flex items-center gap-x-8">
                                    <p class="text-grey-950 text-base font-semibold">{{ dayjs(history.created_at).format('HH:mm') }}</p>
                                    <p class="text-grey-950 text-base font-normal">{{ history.reason }}</p>
                                </div>

                                <div class="flex flex-col items-end gap-y-2">
                                    <p class="text-base font-semibold" :class="history.type === 'in' ? 'text-green-500' : 'text-primary-600'">{{ history.type === 'in' ? '+' : '-' }}{{ ` RM ${history.amount}` }}</p>

                                    <div class="flex gap-x-1 self-stretch justify-end items-center">
                                        <p class="truncate font-normal text-xs text-grey-00 text-right">{{ history.handled_by.full_name }}</p>
                                        <img 
                                            :src="history.handled_by.image ? history.handled_by.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt="UserImage" 
                                            class="size-3 object-fit rounded-full border borer-grey-100"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div 
                            v-for="(history, index) in selectedShift.shift_pay_histories" 
                            :key="index"
                            class="flex py-3 flex-col items-end gap-4 self-stretch border-b border-grey-100"
                        >
                            <div class="flex justify-between items-center self-stretch">
                                <div class="flex items-center gap-x-8">
                                    <p class="text-grey-950 text-base font-semibold">{{ dayjs(history.created_at).format('HH:mm') }}</p>
                                    <p class="text-grey-950 text-base font-normal">{{ history.reason }}</p>
                                </div>

                                <div class="flex flex-col items-end gap-y-2">
                                    <p class="text-base font-semibold" :class="history.type === 'in' ? 'text-green-500' : 'text-primary-600'">{{ history.type === 'in' ? '+' : '-' }}{{ ` RM ${history.amount}` }}</p>

                                    <div class="flex gap-x-1 self-stretch justify-end items-center">
                                        <p class="truncate font-normal text-xs text-grey-00 text-right">{{ history.handled_by.full_name }}</p>
                                        <img 
                                            :src="history.handled_by.image ? history.handled_by.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt="UserImage" 
                                            class="size-3 object-fit rounded-full border borer-grey-100"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </TabView>
    </div>
    
    <div class="w-full flex flex-col p-6 justify-center gap-6 self-stretch bg-white">
        <Button
            type="button"
            variant="tertiary"
            size="lg"
            @click=""
        >
            Export Report
        </Button>
    </div>
</template>
