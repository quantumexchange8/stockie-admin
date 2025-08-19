<script setup>
import Button from '@/Components/Button.vue';
import { Head, useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { computed, ref, watch } from 'vue';
import { useCustomToast } from '@/Composables';
import { ShiftWorkerIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import ShiftPayInOut from './ShiftPayInOut.vue';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps({
    currentSelectedShift : {
        type: Object,
        default: () => {}
    },
});

const emit = defineEmits(['update:shift-listing']);

const { showMessage } = useCustomToast();

const selectedShift = ref(props.currentSelectedShift);
const closeShiftFormIsOpen = ref(false);
const payInOutShiftFormIsOpen = ref(false);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);

const form = useForm({
    closing_cash : '',
});

const closeShift = async () => { 
    form.processing = true;

    try {
        const response = await axios.post(`/shift-management/shift-control/close/${selectedShift.value.id}`, form);

        closeModal('close-shift', 'leave');
        emit('update:shift-listing', response.data);

        setTimeout(() => { 
            showMessage({ 
                severity: 'success', 
                summary: wTrans('public.toast.shift_closed_success'), 
            }); 
        }, 200); 

    } catch (error) {
        form.setError(error.response.data.errors);
    } finally {
        form.processing = false;
    }
};

const openModal = (action) => {
    isDirty.value = false;
    switch (action) {
        case 'close-shift':
            closeShiftFormIsOpen.value = true;
            break;
    
        case 'pay-in-out':
            payInOutShiftFormIsOpen.value = true;
            break;
    };
}

const closeModal = (action, status) => {
    switch(status) {
        case 'close':{
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                if(action === 'close-shift') closeShiftFormIsOpen.value = false;
                if(action === 'pay-in-out') payInOutShiftFormIsOpen.value = false;
                
                setTimeout(() => {
                    form.reset();
                    form.clearErrors();
                }, 200); 
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            if(action === 'close-shift') closeShiftFormIsOpen.value = false;
            if(action === 'pay-in-out') payInOutShiftFormIsOpen.value = false;

            setTimeout(() => {
                form.reset();
                form.clearErrors();
            }, 200); 
            break;
        }
    }
}

const showPayInfo = computed(() => {
    return selectedShift.value
        ? selectedShift.value && 
            (
                selectedShift.value.shift_pay_histories.length > 0 || 
                selectedShift.value.net_sales > 0 || 
                selectedShift.value.status === 'closed'
            )
        : false;
});

watch(() => props.currentSelectedShift, (newValue) => {
    selectedShift.value = newValue;
});

watch(form, (newValue) => (isDirty.value = newValue.isDirty));

</script>

<template>
    <div 
        class="col-span-full md:col-span-7 lg:col-span-8 flex flex-col self-stretch items-center justify-start bg-white shadow-sm border border-grey-100 rounded-[5px] 
            h-[calc(100dvh-12rem)] shrink-0 overflow-y-auto scrollbar-thin scrollbar-webkit"
            :class="selectedShift ? 'justify-start' : 'justify-center'"
        >
        <template v-if="selectedShift">
            <div class="flex flex-col gap-y-5 p-5 justify-center items-center self-stretch border-b border-grey-100">
                <div class="flex flex-col gap-y-2 items-center self-stretch">
                    <p class="truncate self-stretch text-grey-950 text-lg font-bold text-center">{{ `${$t('public.shift.shift')} #${selectedShift.shift_no}` }}</p>
                    <p class="truncate self-stretch text-grey-950 text-sm font-normal text-center">{{ dayjs(selectedShift.shift_opened).format('YYYY-MM-DD HH:mm') }}</p>
                </div>

                <div class="flex flex-col gap-y-3 items-start self-stretch">
                    <div class="flex gap-x-3 items-center self-stretch">
                        <Button
                            :type="'button'"
                            :variant="'secondary'"
                            :size="'lg'"
                            @click="openModal('pay-in-out')"
                        >
                            {{ selectedShift?.status === 'opened' ? $t('public.shift.pay_in_out') : $t('public.shift.pay_in_out_history') }}
                        </Button>
                        <Button
                            v-if="selectedShift.status === 'opened'"
                            :type="'button'"
                            :size="'lg'"
                            @click="openModal('close-shift')"
                        >
                            {{ $t('public.action.close_shift') }}
                        </Button>
                    </div>

                </div>
            </div>

            <div class="flex flex-col items-start self-stretch gap-y-6 p-5">
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

                <hr class="w-full h-[1px] bg-grey-100">

                <div class="flex flex-col items-start self-stretch gap-y-2">
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.start_cash') }}</p>
                        <p class="text-grey-950 font-semibold text-base text-right">RM {{ Number(selectedShift.starting_cash).toFixed(2) }}</p>
                    </div>
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.paid_in') }}</p>
                        <p class="font-semibold text-base text-right" :class="showPayInfo ? 'text-green-500' : 'text-grey-950'">
                            {{ showPayInfo ? `RM ${Number(selectedShift.paid_in).toFixed(2)}` : $t('public.na') }}
                        </p>
                    </div>
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.paid_out') }}</p>
                        <p class="font-semibold text-base text-right" :class="showPayInfo ? 'text-primary-600' : 'text-grey-950'">
                            {{ showPayInfo ? `- RM ${Number(selectedShift.paid_out).toFixed(2)}` : $t('public.na') }}
                        </p>
                    </div>
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.close_cash') }}</p>
                        <p class="text-grey-950 font-semibold text-base text-right">{{ selectedShift.closing_cash ? `RM ${Number(selectedShift.closing_cash).toFixed(2)}` : $t('public.na') }}</p>
                    </div>
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.cash_diff') }}</p>
                        <p class="text-grey-950 font-semibold text-base text-right">{{ Number(selectedShift.difference) < 0 ? '-' : '' }}{{ selectedShift.difference ? ` RM ${Math.abs(Number(selectedShift.difference)).toFixed(2)}` : $t('public.na') }}</p>
                    </div>
                </div>

                <hr class="w-full h-[1px] bg-grey-100">

                <div class="flex flex-col items-start self-stretch gap-y-2">
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.close_date') }}</p>
                        <p class="text-grey-950 font-semibold text-base text-right">{{ selectedShift.shift_closed ? dayjs(selectedShift.shift_closed).format('YYYY-MM-DD HH:mm') : $t('public.na') }}</p>
                    </div>
                    <div class="flex justify-between items-start self-stretch">
                        <p class="text-grey-700 font-normal text-base">{{ $t('public.shift.closed_by') }}</p>
                        <div class="flex gap-x-1 items-center">
                            <p class="truncate font-semibold text-base text-grey-950 text-right">{{ selectedShift.closed_by?.full_name ?? $t('public.na') }}</p>
                            <img 
                                v-if="selectedShift.closed_by"
                                :src="selectedShift.closed_by.image ? selectedShift.closed_by.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt="UserImage" 
                                class="size-4 object-fit rounded-full border borer-grey-100"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template v-else>
            <div class="flex w-full flex-col items-center justify-center gap-5">
                <ShiftWorkerIcon />
                <div class="flex flex-col gap-y-1 items-center">
                    <span class="text-grey-950 text-md font-semibold">{{ $t('public.empty.no_shift_opened') }}</span>
                    <span class="text-grey-950 text-sm font-normal">{{ $t('public.shift.no_shift_opened_message') }}</span>
                </div>
            </div>
        </template>
    </div>   

    <Modal 
        :title="selectedShift?.status === 'opened' ? $t('public.shift.pay_in_out') : $t('public.shift.pay_in_out_history')"
        :show="payInOutShiftFormIsOpen" 
        :maxWidth="'md'" 
        :closeable="true" 
        @close="closeModal('pay-in-out', 'close')"
    >
        <ShiftPayInOut
            :currentSelectedShift="selectedShift"
            @close="closeModal"
            @isDirty="isDirty=$event"
            @update:shift-listing="$emit('update:shift-listing', $event)"
        />
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('pay-in-out', 'stay')"
            @leave="closeModal('pay-in-out', 'leave')"
        />
    </Modal>

    <Modal 
        :title="$t('public.action.close_shift')"
        :show="closeShiftFormIsOpen"
        :maxWidth="'2xs'" 
        :closeable="true"
        @close="closeModal('close-shift', 'close')"
    >
        <form @submit.prevent="closeShift">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-1" >
                    <p class="text-grey-950 text-base font-bold">
                        {{$t('public.shift.closing_cash_amount') }}
                    </p>
                    <div class="text-grey-950 text-sm font-normal self-stretch" >
                        {{$t('public.shift.enter_current_cash') }}
                    </div>
                </div>

                <TextInput
                    :inputId="'closing_cash'"
                    :iconPosition="'left'"
                    :placeholder="'0.00'"
                    :inputType="'number'"
                    withDecimal
                    :errorMessage="form.errors?.closing_cash?.[0] ??''"
                    v-model="form.closing_cash"
                    class="[&>div>input]:text-left"
                >
                    <template #prefix>RM</template>
                </TextInput>

                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeModal('close-shift', 'close')"
                    >
                        {{ $t('public.action.cancel') }}
                    </Button>
                    <Button
                        size="lg"
                        :disabled="form.processing || form.closing_cash == ''"
                    >
                        {{ $t('public.action.close') }}
                    </Button>
                </div>
            </div>
        </form>
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('close-shift', 'stay')"
            @leave="closeModal('close-shift', 'leave')"
        />
    </Modal>
</template>

