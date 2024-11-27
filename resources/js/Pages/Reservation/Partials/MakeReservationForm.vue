<script setup>
import dayjs from 'dayjs';
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import DateInput from "@/Components/Date.vue";
import Dropdown from '@/Components/Dropdown.vue';
import TextInput from '@/Components/TextInput.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import { useCustomToast, usePhoneUtils, useInputValidator } from '@/Composables/index.js';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    customers: Array,
    tables: Array,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)
const isUnsavedChangesOpen = ref(false);

const { showMessage } = useCustomToast();
const { transformPhone, formatPhoneInput } = usePhoneUtils();
const { isValidNumberKey } = useInputValidator();

const emit = defineEmits(['close', 'isDirty']);

const form = useForm({
    reserved_by: userId.value,
    reservation_date: '',
    pax: '',
    name: '',
    phone: '',
    phone_temp: '',
    table_no: null,
    tables: null,
});

const getTableNames = (table_no) => table_no.map(selectedTable => selectedTable.name).join(', ');

const unsaved = (type) => {
    emit('close', type);
}

const submit = () => { 
    form.reservation_date = form.reservation_date ? dayjs(form.reservation_date).format('YYYY-MM-DD HH:mm:ss') : '';
    form.phone = form.phone_temp ? transformPhone(form.phone_temp) : '';
    const reservedTables = [];

    form.tables.forEach(selectedTable => {
        props.tables.forEach(table => {
            if (table.id === selectedTable) {
                reservedTables.push({
                    id : table.id,
                    name : table.table_no,
                });
            };
        });
    });

    form.table_no = reservedTables;

    form.post(route('reservations.store'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: `Reservation has been made to '${getTableNames(form.table_no)}'.`,
                });
                form.reset();
            }, 200);
            unsaved('leave');
        },
    })
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
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :closeable="true"
            :show="isUnsavedChangesOpen"
            @close="unsaved('stay')"
            @leave="unsaved('leave')"
        >
        </Modal>
    </form>
</template>
