<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import Toggle from '@/Components/Toggle.vue';
import DateInput from "@/Components/Date.vue";
import Dropdown from '@/Components/Dropdown.vue'
import TextInput from '@/Components/TextInput.vue';
import { TimesIcon } from '@/Components/Icons/solid';
import dayjs from 'dayjs';
import { useCustomToast } from '@/Composables/index.js';

const props = defineProps({
    waiters: {
        type: Array,
        default: () => [],
    },
    table: Object,
});

const { showMessage } = useCustomToast();

const emit = defineEmits(['close']);

const form = useForm({
    table_id: props.table.id,
    table_no: props.table.table_no,
    reservation: false,
    pax: '',
    waiter_id: '',
    status: '',
    reservation_date: '',
    order_id: '',
});

const formSubmit = () => { 
    form.reservation_date = form.reservation_date ? dayjs(form.reservation_date).format('YYYY-MM-DD HH:mm:ss') : '';
    form.status = form.reservation ? 'Empty Seat' : 'Pending Order';

    form.post(route('orders.tables.store'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: form.reservation ? `Reservation has been made to '${props.table.table_no}'.` : `You've successfully check in customer to '${props.table.table_no}'.`,
                });
                form.reset();
            }, 200);
            emit('close');
        },
    })
};

const isNumber = (e, withDot = true) => {
    const { key, target: { value } } = e;
    
    if (/^\d$/.test(key)) return;

    if (withDot && key === '.' && /\d/.test(value) && !value.includes('.')) return;
    
    e.preventDefault();
};

const isFormValid = computed(() => {
    return form.reservation ? ['reservation_date', 'pax'].every(field => form[field])
                            : ['pax', 'waiter_id'].every(field => form[field]);
});

watch(() => form.reservation, (newValue) => {
    form.reservation_date = newValue ? form.reservation_date : '';
    form.waiter_id = newValue ? form.waiter_id : '';
})

</script>

<template>
    <form novalidate @submit.prevent="formSubmit">
        <div class="flex flex-col gap-9 w-[370px]">
            <div class="flex items-center justify-between">
                <span class="text-primary-950 text-center text-md font-medium">Assign Seat</span>
                <TimesIcon
                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                    @click="$emit('close')"
                />
            </div>
            
            <div class="flex flex-col justify-between gap-6">
                <div class="flex flex-nowrap justify-between gap-6">
                    <p class="text-xl text-primary-900 font-bold">{{ table.table_no }}</p>
                    <div class="flex items-center justify-end gap-3">
                        <p class="text-base text-grey-900 font-normal">This is a reservation</p>
                        <Toggle
                            :inputName="'reservation'"
                            :checked="form.reservation"
                            v-model:checked="form.reservation"
                            class="col-span-full xl:col-span-2"
                        />
                    </div>
                </div>
                <div class="flex flex-col gap-6 items-center self-stretch">
                    <DateInput
                        v-if="form.reservation"
                        :inputName="'reservation_date'"
                        :placeholder="'Select Date and Time'"
                        withTime
                        v-model="form.reservation_date"
                    />
                    <TextInput
                        :inputName="'pax'"
                        :labelText="'No. of pax'"
                        :placeholder="'No. of pax'"
                        :errorMessage="form.errors?.pax || ''"
                        v-model="form.pax"
                        @keypress="isNumber($event, false)"
                    />
                    <Dropdown
                        v-if="!form.reservation"
                        imageOption
                        :inputName="'waiter_id'"
                        :labelText="'Assign Waiter'"
                        :inputArray="waiters"
                        :errorMessage="form.errors?.waiter_id || ''"
                        v-model="form.waiter_id"
                    />
                </div>
            </div>

            <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                <Button
                    :type="'button'"
                    :variant="'tertiary'"
                    :size="'lg'"
                    @click="$emit('close')"
                >
                    Cancel
                </Button>
                <Button
                    :size="'lg'"
                    :disabled="!isFormValid"
                >
                    Done
                </Button>
            </div>
        </div>
    </form>
</template>
