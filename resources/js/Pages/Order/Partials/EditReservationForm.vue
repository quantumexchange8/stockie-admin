<script setup>
import { computed, watch, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import DateInput from "@/Components/Date.vue";
import TextInput from '@/Components/TextInput.vue';
import dayjs from 'dayjs';
import { useCustomToast, useInputValidator } from '@/Composables/index.js';

const props = defineProps({
    reservationTable: Object
});

const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const emit = defineEmits(['close']);

const reserved_date = ref(props.reservationTable.reservation_date);

const form = useForm({
    table_id: props.reservationTable.table_id,
    reservation: props.reservationTable.reservation,
    pax: props.reservationTable.pax,
    user_id: props.reservationTable.user_id,
    status: props.reservationTable.status,
    reservation_date: dayjs(props.reservationTable.reservation_date).format('DD/MM/YYYY HH:mm'),
    order_id: props.reservationTable.order_id,
});

const formSubmit = () => { 
    form.reservation_date = reserved_date.value;

    form.put(route('orders.reservations.update', props.reservationTable.id), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'Changes saved',
                });
            }, 200);
            form.reset();
            emit('close');
        },
    })
};

const isFormValid = computed(() => {
    return ['reservation_date', 'pax'].every(field => form[field]);
});

watch(() => form.reservation_date, (newValue) => {
    reserved_date.value = dayjs(newValue, 'DD/MM/YYYY HH:mm').format('YYYY-MM-DD HH:mm:ss');
}, { deep: true });
</script>

<template>
    <form novalidate @submit.prevent="formSubmit">
        <div class="flex flex-col gap-9">
            <div class="flex flex-col justify-between items-center gap-6">
                <DateInput
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
                    @keypress="isValidNumberKey($event, false)"
                />
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
                    Save Changes
                </Button>
            </div>
        </div>
    </form>
</template>
