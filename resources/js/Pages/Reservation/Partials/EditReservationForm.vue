<script setup>
import dayjs from 'dayjs';
import { computed, onMounted, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import DateInput from "@/Components/Date.vue";
import Dropdown from '@/Components/Dropdown.vue';
import TextInput from '@/Components/TextInput.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import { useCustomToast, usePhoneUtils } from '@/Composables/index.js';

const props = defineProps({
    reservation: Object,
    customers: Array,
    tables: Array,
});

const { showMessage } = useCustomToast();
const { formatPhone, transformPhone, formatPhoneInput } = usePhoneUtils();

const emit = defineEmits(['close', 'fetchReservations', 'isDirty', 'update:reservation']);

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)
const isUnsavedChangesOpen = ref(false);
const occupiedTables = ref([]);
const isLoading = ref(false);

const selectedTable = ref(
    props.tables.filter((table) => 
        props.reservation.table_no.some((reservedTable) => reservedTable.id === table.id)
    )
);

const form = useForm({
    reserved_by: userId.value,
    reservation_date: dayjs(props.reservation.reservation_date).toDate(),
    pax: props.reservation.pax,
    name: props.reservation.customer_id ?? props.reservation.name,
    phone: props.reservation.phone,
    phone_temp: formatPhone(props.reservation.phone, true, true),
    table_no: props.reservation.table_no,
    tables: props.reservation.table_no.map((table) => table.id),
    reserved_limit: props.reservation.grace_period.toString(),
    grace_period: props.reservation.grace_period.toString(),
});

const getOccupiedTables = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/reservation/getOccupiedTables');
        occupiedTables.value = response.data.filter((table) => !form.tables.includes(table.id));

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => getOccupiedTables());

const unsaved = (status) => {
    emit('close', status);
}

const submit = async () => { 
    form.reservation_date = form.reservation_date ? dayjs(form.reservation_date).format('YYYY-MM-DD HH:mm:ss') : '';
    form.phone = form.phone_temp ? transformPhone(form.phone_temp) : '';
    form.table_no = props.tables
        .filter(table => form.tables.includes(table.id))  // Filter to only selected tables
        .map(table => ({ id: table.id, name: table.table_no }));  // Map to required format
    form.grace_period = parseInt(form.reserved_limit);

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

        form.reserved_by = userId.value;
        form.reservation_date = dayjs(updatedReservation.reservation_date).toDate();
        form.pax = updatedReservation.pax;
        form.name = updatedReservation.customer_id ?? updatedReservation.name;
        form.phone = updatedReservation.phone;
        form.phone_temp = formatPhone(updatedReservation.phone, true, true);
        form.table_no = updatedReservation.table_no;
        form.tables = updatedReservation.table_no.map((table) => table.id);
        form.reserved_limit = updatedReservation.grace_period.toString();
        form.grace_period = updatedReservation.grace_period.toString();

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
    return props.tables.filter((table) => table.state === 'active')
                        .map((table) => {
                            return {
                                'text': table.table_no,
                                'value': table.id,
                                'disabled': occupiedTables.value.some((occupiedTable) => occupiedTable.id === table.id),
                            }
                        });
});

// Computed property for maximum seats
// const maxSeats = computed(() => selectedTable.value?.reduce((total, table) => total + table.seat, 0) ?? 0);

// Helper function to validate and format pax value
// const validatePaxValue = (value) => {
//     const numValue = parseInt(value) || '';
    
//     if (numValue < 0) return '';

//     if (numValue >= maxSeats.value && maxSeats.value !== 0) {
//         return selectedTable.value ? maxSeats.value : numValue;
//     }
    
//     return numValue;
// }

const updateSelectedTables = (event) => {
    // More efficient table selection using Set for O(1) lookups
    const selectedIds = new Set(event);
    selectedTable.value = props.tables.filter(table => selectedIds.has(table.id));
    
    // Reset selected table
    if (!selectedTable.value) {
        selectedTable.value = null;
    } 
  
    // Update pax value based on new selection
    // form.pax = validatePaxValue(form.pax).toString();
};

// const paxInputValidation = (event) => {
//     const pax = validatePaxValue(event.target.value).toString();
//     event.target.value = pax;
//     form.pax = pax;
// }

const isFormValid = computed(() => {
    return ['reservation_date', 'pax', 'name', 'phone_temp', 'tables', 'reserved_limit'].every(field => form[field]) && !form.processing;
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
                    :inputType="'number'"
                    labelText="No. of pax"
                    placeholder="Enter here"
                    class="col-span-full sm:col-span-6"
                    :errorMessage="form.errors?.pax || ''"
                    v-model="form.pax"
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
                    :inputType="'number'"
                    labelText="Contact no."
                    placeholder="11 1234 5678"
                    iconPosition="left"
                    class="col-span-full sm:col-span-6 [&>div:nth-child(2)>input]:text-left"
                    :errorMessage="form.errors?.phone || ''"
                    v-model="form.phone_temp"
                    @input="formatPhoneInput"
                >
                    <template #prefix> +60 </template>
                </TextInput>
                <MultiSelect 
                    inputName="table_no"
                    labelText="Select table/room"
                    placeholder="Select"
                    class="col-span-full sm:col-span-6"
                    :loading="isLoading"
                    :inputArray="tablesArr"
                    :errorMessage="form.errors?.table_no || ''"
                    :dataValue="form.tables"
                    v-model="form.tables"
                    @onChange="updateSelectedTables"
                />
                <TextInput
                    inputName="grace_period"
                    labelText="Grace period"
                    placeholder="1"
                    :inputType="'number'"
                    iconPosition="right"
                    class="col-span-full sm:col-span-6"
                    required
                    :errorMessage="form.errors?.grace_period || ''"
                    v-model="form.reserved_limit"
                    
                >
                    <template #prefix>hour</template>
                </TextInput>
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
