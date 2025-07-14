<script setup>
import axios from 'axios';
import { ref, computed, onMounted, watch } from 'vue'
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import Button from '@/Components/Button.vue'
import { useCustomToast, usePhoneUtils } from '@/Composables/index.js';
import Modal from '@/Components/Modal.vue';
import Textarea from '@/Components/Textarea.vue';
import dayjs from 'dayjs';
import { UndetectableIllus } from '@/Components/Icons/illus';

const props = defineProps({
    currentSelectedShift: Object,
});

const emit = defineEmits(['close', 'isDirty', 'update:shift-listing']);

const { showMessage } = useCustomToast();

const isUnsavedChangesOpen = ref(false);
const selectedShift = ref(props.currentSelectedShift);

const form = useForm({
    type: '',
    amount: '',
    reason: '',
});

const unsaved = (status) => {
    emit('close', status);
    form.type = '';
    form.reset();
    form.clearErrors();
}

const submit = async () => { 
    try {
        const response = await axios.post(`/shift-management/shift-control/pay/${selectedShift.value.id}`, form);

        showMessage({
            severity: 'success',
            summary: form.type === 'in' ? 'Pay in history for this shift has been updated.' : 'Pay out history for this shift has been updated.',
        });

        emit('update:shift-listing', response.data);
        form.reset();
        form.clearErrors();
        unsaved('leave');

    } catch (error) {
        if (error.response) {
            form.setError(error.response.data.errors);
            console.error('An unexpected error occurred:', error);
        }
    }
};

const requiredFields = ['amount', 'reason'];

const isFormValid = computed(() => requiredFields.every(field => form[field]) && !form.processing);

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

watch(() => props.currentSelectedShift, (newValue) => {
    selectedShift.value = newValue;
});
</script>

<template>
    <form class="flex flex-col gap-8 max-h-[calc(100dvh-10rem)] overflow-y-auto scrollbar-thin scrollbar-webkit" novalidate @submit.prevent="submit">
        <template v-if="selectedShift.status === 'opened'">
            <div class="flex flex-col gap-y-5" >
                <div class="flex flex-col gap-y-4" >
                    <div class="flex flex-col gap-1" >
                        <p class="text-grey-950 text-base font-bold">
                            Enter Amount
                        </p>
                        <div class="text-grey-950 text-sm font-normal self-stretch" >
                            The amount for pay in/out.
                        </div>
                    </div>

                    <TextInput
                        :inputId="'amount'"
                        :iconPosition="'left'"
                        :placeholder="'0.00'"
                        :inputType="'number'"
                        withDecimal
                        :errorMessage="form.errors?.amount?.[0] ??''"
                        v-model="form.amount"
                        class="[&>div>input]:text-left"
                    >
                        <template #prefix>RM</template>
                    </TextInput>
                </div>

                <div class="flex flex-col gap-y-4" >
                    <div class="flex flex-col gap-1" >
                        <p class="text-grey-950 text-base font-bold">
                            Reason
                        </p>
                        <div class="text-grey-950 text-sm font-normal self-stretch" >
                            Enter the reason you pay in/out.
                        </div>
                    </div>

                    <Textarea 
                        :inputName="'reason'"
                        :errorMessage="form.errors.reason ? form.errors.reason[0] : ''"
                        :placeholder="'Enter the reason'"
                        :rows="3"
                        v-model="form.reason"
                    />
                </div>
            </div>

            <div class="flex justify-center items-end gap-4 self-stretch">
                <Button
                    :variant="'green'"
                    :size="'lg'"
                    :disabled="!isFormValid"
                    @click="form.type = 'in'"
                >
                    Pay in
                </Button>
                <Button
                    :variant="'red'"
                    :size="'lg'"
                    :disabled="!isFormValid"
                    @click="form.type = 'out'"
                >
                    Pay out
                </Button>
            </div>
        </template>
        
        <div class="flex flex-col gap-y-4" >
            <div class="flex flex-col gap-1" v-if="selectedShift.status === 'opened'">
                <p class="text-grey-950 text-base font-bold">
                    Pay in/out History
                </p>
                <div class="text-grey-950 text-sm font-normal self-stretch" >
                    Your pay in/out history for this shift.
                </div>
            </div>

            <div class="flex flex-col items-start self-stretch pr-1 shrink-0">
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
                    <span class="text-sm font-medium text-primary-900">No data can be shown yet...</span>
                </div>
            </div>
        </div>

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="unsaved('stay')"
            @leave="unsaved('leave')"
        />
    </form>
</template>
