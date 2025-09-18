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
import { wTrans } from 'laravel-vue-i18n';
import { UndetectableIllus } from '@/Components/Icons/illus';

const props = defineProps({
    currentSelectedShift: Object,
});

const { showMessage } = useCustomToast();

const selectedShift = ref(props.currentSelectedShift);
const tabs = ref([
    { key: 'Shift Overview', title: wTrans('public.shift.shift_overview'), disabled: false },
    { key: 'Pay In/Out History', title: wTrans('public.shift.pay_in_out_history'), disabled: false }
]);

const printShfitReportReceipt = async () => {
    try {
        const params = { 
            shift_transaction: selectedShift.value
        };

        const response = await axios.post('/shift-management/shift-record/getShiftReportReceipt', params);
        const printer = response.data.printer;

        if (!printer || !printer.ip_address || !printer.port_number) {
            let summary = '';
            let detail = '';

            if (!printer) {
                summary = 'Unable to detect selected printer';
                detail = 'Please contact admin to setup the printer.';

            } else if (!printer.ip_address) {
                summary = 'Invalid printer IP address';
                detail = 'Please contact admin to setup the printer IP address.';

            } else if (!printer.port_number) {
                summary = 'Invalid printer port number';
                detail = 'Please contact admin to setup the printer port number.';
            }

            showMessage({
                severity: 'error',
                summary: summary,
                detail: detail,
            });

            return;
        }

        const base64 = response.data.data;
        const url = `stockie-app://print?base64=${base64}&ip=${printer.ip_address}&port=${printer.port_number}`;

        try {
            window.location.href = url;

        } catch (e) {
            console.error('Failed to open app:', e);
            alert(`Failed to open STOXPOS app \n ${e}`);

        } finally {
            emit('close');
        }

        
    } catch (err) {
        console.error("Print failed:", err);
        showMessage({
            severity: 'error',
            summary: 'Print failed',
            detail: err.message
        });
    }
}

watch(() => props.currentSelectedShift, (newValue) => {
    selectedShift.value = newValue;
});
</script>

<template>
    <div class="flex flex-col gap-8">
        <TabView :tabs="tabs">
            <template #shift-overview>
                <div class="flex flex-col gap-y-8 items-start self-stretch pr-1 max-h-[calc(100dvh-20rem)] shrink-0 overflow-y-auto scrollbar-thin scrollbar-webkit">
                    <div class="flex flex-col items-start gap-y-4 self-stretch">
                        <div class="flex items-center gap-x-4 self-stretch">
                            <div class="w-[6px] h-6 bg-primary-800"></div>
                            <p class="text-primary-900 font-semibold text-md">{{ $t('public.sales_detail') }}</p>
                        </div>

                        <div class="flex flex-col items-start self-stretch gap-y-3">
                            <div class="flex flex-col items-start self-stretch gap-y-2">
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.cash_sales') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.cash_sales).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.card_sales') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.card_sales).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.e_wallet_sales') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.ewallet_sales).toFixed(2) }}</p>
                                </div>
                            </div>

                            <hr class="w-full h-[1px] bg-white">

                            <div class="flex flex-col items-start self-stretch gap-y-2">
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.gross_sales') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.gross_sales).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">SST <!-- ({{ selectedShift.gross_sales > 0 ? ((selectedShift.sst_amount / selectedShift.gross_sales) * 100): 0 }}%) --></p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.sst_amount).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.service_tax') }} <!-- ({{ selectedShift.gross_sales > 0 ? ((selectedShift.service_tax_amount / selectedShift.gross_sales) * 100) : 0 }}%) --></p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.service_tax_amount).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $tChoice('public.refund', 1) }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">- RM {{ Number(selectedShift.total_refund).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $tChoice('public.void', 1) }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">- RM {{ Number(selectedShift.total_void).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.discounts') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">- RM {{ Number(selectedShift.total_discount).toFixed(2) }}</p>
                                </div>
                            </div>

                            <hr class="w-full h-[1px] bg-white">

                            <div class="flex flex-col items-start self-stretch gap-y-2">
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.net_sales_excl_tax') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.net_sales).toFixed(2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col items-start gap-y-4 self-stretch">
                        <div class="flex items-center gap-x-4 self-stretch">
                            <div class="w-[6px] h-6 bg-primary-800"></div>
                            <p class="text-primary-900 font-semibold text-md">{{ $t('public.shift.shift_cash_drawer_detail') }}</p>
                        </div>

                        <div class="flex flex-col items-start self-stretch gap-y-3">
                            <div class="flex flex-col items-start self-stretch gap-y-2">
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.open_date') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">{{ dayjs(selectedShift.shift_opened).format('YYYY-MM-DD HH:mm') }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.opened_by') }}</p>
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
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.start_cash') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.starting_cash).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.paid_in') }}</p>
                                    <p class="font-semibold text-base text-right text-grey-950">RM {{ Number(selectedShift.paid_in).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.paid_out') }}</p>
                                    <p class="font-semibold text-base text-right text-grey-950">- RM {{ Number(selectedShift.paid_out).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.cash_refunds') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">- RM {{ Number(selectedShift.cash_refund).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.expected_cash') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.expected_cash).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.close_cash') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.closing_cash).toFixed(2) }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.cash_diff') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">{{ Number(selectedShift.difference) < 0 ? '-' : '' }}{{ ` RM ${Math.abs(Number(selectedShift.difference)).toFixed(2)}` }}</p>
                                </div>
                            </div>

                            <hr class="w-full h-[1px] bg-white">

                            <div class="flex flex-col items-start self-stretch gap-y-2">
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.close_date') }}</p>
                                    <p class="text-grey-950 font-semibold text-base text-right">{{ dayjs(selectedShift.shift_closed).format('YYYY-MM-DD HH:mm') }}</p>
                                </div>
                                <div class="flex justify-between items-start self-stretch">
                                    <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.closed_by') }}</p>
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
                            {{ $t('public.shift.pay_in_out_history') }}
                        </p>
                        <div class="text-grey-950 text-sm font-normal self-stretch" >
                            {{ $t('public.shift.shift_pay_history') }}
                        </div>
                    </div>

                    <div class="flex flex-col items-start self-stretch pr-1 max-h-[calc(100dvh-24rem)] shrink-0 overflow-y-auto scrollbar-thin scrollbar-webkit">
                        <template v-if="selectedShift.shift_pay_histories.length > 0">
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
                        </template>
                        <div class="w-full flex flex-col items-center" v-else>
                            <UndetectableIllus class="w-44 h-44" />
                            <span class="text-sm font-medium text-primary-900">{{ $t('public.empty.no_data') }}</span>
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
            @click="printShfitReportReceipt"
        >
            {{ $t('public.shift.print_report') }}
        </Button>
    </div>
</template>
