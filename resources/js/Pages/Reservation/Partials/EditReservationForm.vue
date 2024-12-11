<script setup>
import dayjs from 'dayjs';
import { computed, onMounted, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import DateInput from "@/Components/Date.vue";
import Dropdown from '@/Components/Dropdown.vue';
import TextInput from '@/Components/TextInput.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import { useCustomToast, usePhoneUtils, useInputValidator } from '@/Composables/index.js';

const props = defineProps({
    reservation: Object,
    customers: Array,
    tables: Array,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)
const isUnsavedChangesOpen = ref(false);

const { showMessage } = useCustomToast();
const { formatPhone, transformPhone, formatPhoneInput } = usePhoneUtils();
const { isValidNumberKey } = useInputValidator();

const emit = defineEmits(['close', 'fetchReservations', 'isDirty', 'update:reservation']);

const form = useForm({
    reserved_by: userId.value,
    reservation_date: dayjs(props.reservation.reservation_date).toDate(),
    pax: props.reservation.pax,
    name: props.reservation.customer_id ?? props.reservation.name,
    phone: props.reservation.phone,
    phone_temp: formatPhone(props.reservation.phone, true, true),
    table_no: props.reservation.table_no,
    tables: props.reservation.table_no.map((table) => table.id),
});

const unsaved = (status) => {
    emit('close', status);
}

const submit = async () => { 
    form.reservation_date = form.reservation_date ? dayjs(form.reservation_date).format('YYYY-MM-DD HH:mm:ss') : '';
    form.phone = form.phone_temp ? transformPhone(form.phone_temp) : '';
    form.table_no = props.tables
        .filter(table => form.tables.includes(table.id))  // Filter to only selected tables
        .map(table => ({ id: table.id, name: table.table_no }));  // Map to required format

    // form.put(route('reservations.update', props.reservation.id), {
    //     preserveScroll: true,
    //     preserveState: true,
    //     onSuccess: () => {
    //         setTimeout(() => {
    //             showMessage({ 
    //                 severity: 'success',
    //                 summary: 'Changes saved',
    //             });
    //             form.reset();
    //         }, 200);
    //         unsaved('leave');
    //         emit('fetchReservations');
    //     },
    // })

    try {
        const response = await axios.put(`/reservation/${props.reservation.id}`, form);
        let updatedReservation = response.data;

        form.reserved_by = userId.value,
        form.reservation_date = dayjs(updatedReservation.reservation_date).toDate(),
        form.pax = updatedReservation.pax,
        form.name = updatedReservation.customer_id ?? updatedReservation.name,
        form.phone = updatedReservation.phone,
        form.phone_temp = formatPhone(updatedReservation.phone, true, true),
        form.table_no = updatedReservation.table_no,
        form.tables = updatedReservation.table_no.map((table) => table.id),

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Changes saved',
            });
            form.reset();
        }, 200);
        unsaved('leave');
        emit('update:reservation', response.data);
        emit('fetchReservations');
    } catch (error) {
        console.error(error);
    } finally {

    }
};

const customersArr = computed(() => {
    return props.customers.map((customer) => {
        return {
            'text': customer.full_name,
            'value': customer.id,
        }
    })
});

const tablesArr = computed(() => {
    return props.tables.map((table) => {
        return {
            'text': table.table_no,
            'value': table.id,
        }
    })
});

const isFormValid = computed(() => {
    return ['reservation_date', 'pax', 'name', 'phone_temp', 'tables'].every(field => form[field]) && !form.processing;
});

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form novalidate @submit.prevent="submit">
        <div class="flex flex-col gap-6">
            <div class="w-full grid grid-cols-1 sm:grid-cols-12 items-start self-stretch gap-5">
                <DateInput 
                    inputName="reservation_date"
                    labelText="Select date and time"
                    placeholder="Select Date and Time"
                    class="col-span-full sm:col-span-6"
                    withTime
                    :minDate="new Date()"
                    :errorMessage="form.errors?.reservation_date || ''"
                    v-model="form.reservation_date"
                />
                <TextInput
                    inputName="pax"
                    labelText="No. of pax"
                    placeholder="Enter here"
                    class="col-span-full sm:col-span-6"
                    :errorMessage="form.errors?.pax || ''"
                    v-model="form.pax"
                    @keypress="isValidNumberKey($event, false)"
                />
                <Dropdown
                    inputName="name"
                    labelText="Select or enter guest name"
                    placeholder="Select"
                    class="col-span-full sm:col-span-6"
                    imageOption
                    editable
                    :dataValue="form.name"
                    :inputArray="customersArr"
                    :errorMessage="form.errors?.name || ''"
                    v-model="form.name"
                />
                <TextInput
                    inputName="phone"
                    labelText="Contact no."
                    placeholder="11 1234 5678"
                    iconPosition="left"
                    class="col-span-full sm:col-span-6 [&>div:nth-child(2)>input]:text-left"
                    :errorMessage="form.errors?.phone || ''"
                    v-model="form.phone_temp"
                    @keypress="isValidNumberKey($event, false)"
                    @input="formatPhoneInput"
                >
                    <template #prefix> +60 </template>
                </TextInput>
                <MultiSelect 
                    inputName="table_no"
                    labelText="Select table/room"
                    placeholder="Select"
                    class="col-span-full sm:col-span-6"
                    :inputArray="tablesArr"
                    :errorMessage="form.errors?.table_no || ''"
                    :dataValue="form.tables"
                    v-model="form.tables"
                />
            </div>

            <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                <Button
                    type="button"
                    variant="tertiary"
                    size="lg"
                    @click="unsaved('close')"
                >
                    Cancel
                </Button>
                <Button
                    size="lg"
                    :disabled="!isFormValid"
                >
                    Done
                </Button>
            </div>
        </div>
    </form>
</template>
