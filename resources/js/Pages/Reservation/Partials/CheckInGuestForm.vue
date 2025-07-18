<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import Dropdown from '@/Components/Dropdown.vue'
import TextInput from '@/Components/TextInput.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import { useCustomToast } from '@/Composables/index.js';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    reservation: Object,
    waiters: Array,
    tables: Array,
    occupiedTables: Array,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id);

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'isDirty']);

const hasChangedTables = ref(false);
const newSelectedTables = ref('');
const isUnsavedChangesOpen = ref(false);
const occupiedTables = ref([]);
const isLoading = ref(false);

// const getOccupiedTables = async () => {
//     isLoading.value = true;
//     try {
//         const response = await axios.get('/reservation/getOccupiedTables');
//         occupiedTables.value = response.data;

//     } catch (error) {
//         console.error(error);
//     } finally {
//         isLoading.value = false;
//     }
// };

const getTableUpcomingReservations = async (date = null) => {
    isLoading.value = true;
    form.processing = true;

     try {
        const formData = { 
            'date': date,
            'reserved_before_limit': form.reserved_before_limit,
            'reserved_limit': form.reserved_limit,
        };
        const response = await axios.get(route('reservations.getTableUpcomingReservations', formData));
        occupiedTables.value = response.data.occupied_tables.filter((table) => !props.reservation.table_no.map((tableNo) => tableNo['id']).includes(table));
        
    } catch (error) {
        console.error(error);

    } finally {
        isLoading.value = false;
        form.processing = false;
    }
};

onMounted(() => getTableUpcomingReservations());

const selectedTables = computed(() => {
    return props.reservation.table_no.map((table) => {
        return {
            'id': table.id,
            'text': table.name,
            // 'disabled': occupiedTables.value.some((occupiedTable) => table.id === occupiedTable.id),
        }
    })
});

const filteredSelectedTables = computed(() => {
    return props.reservation.table_no.map(selectedTable => {
        return occupiedTables.value.some((occupiedTable) => selectedTable.id === occupiedTable.id) ? null : selectedTable.id;
    }).filter((table) => table !== null);
});

const form = useForm({
    table_no: '',
    tables: selectedTables.value,
    pax: props.reservation.pax,
    handled_by: userId.value,
    assigned_waiter: '',
});

const submit = () => { 
    form.table_no = props.tables
        .filter(table => form.tables.map(table => table.id).includes(table.id))  // Filter to only selected tables
        .map(table => ({ id: table.id, name: table.table_no }));  // Map to required format
        
    form.post(route('reservations.checkInGuest', props.reservation.id), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: "We've checked in the customers to selected table.",
                });
                form.reset();
            }, 200);
            unsaved('leave');
            // window.location.href = 'order-management/orders';
        },
        onError: (errors) => {
            if (errors.summary && errors.detail) {
                showMessage({ 
                    severity: 'warn',
                    summary: errors.summary,
                    detail: errors.detail,
                });
            }
        }
    })
};

const tablesArr = computed(() => {
    return props.tables.map((table) => {
        return {
            'text': table.table_no,
            'value': table.id,
            'disabled': occupiedTables.value.some((occupiedTable) => occupiedTable.id === table.id),
        }
    })
});

const isFormValid = computed(() => ['tables', 'assigned_waiter'].every(field => field === 'tables' ? form[field].length > 0 : form[field]) && !form.processing);

const updateSelectedTables = (event) => {
    hasChangedTables.value = true;
    newSelectedTables.value = 
        props.tables.map(table => event.find((value) => table.id === value) ? table.table_no : null)
                    .filter((table) => table !== null)
                    .join(', ');
};

const unsaved = (status) => {
    emit('close', status);
}

watch(form, (newValue) => emit('isDirty', newValue.isDirty));
</script>

<template>
    <form novalidate @submit.prevent="submit">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col justify-between gap-y-6">
                <p class="text-xl font-bold">
                    <span 
                        v-if="!hasChangedTables"
                        v-for="(table, index) in selectedTables" 
                        :key="table.text"
                        :class="{
                            'text-primary-900': !table.disabled,
                            'text-grey-200': table.disabled
                        }"
                    >
                        {{ table.text }}
                        <span v-if="index < selectedTables.length - 1">, </span>
                    </span>
                    <span class="text-primary-900" v-else>{{ newSelectedTables }}</span>
                </p>
                <p class="text-primary-800 text-xs font-normal self-stretch" v-if="selectedTables.some((table) => table.disabled === true)">Some selected table/room are currently unavailable, please replace with another one.</p>
                <div class="flex flex-col gap-6 items-center self-stretch">
                    <MultiSelect 
                        v-if="selectedTables.some((table) => table.disabled === true)"
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
                        inputName="pax"
                        :inputType="'number'"
                        labelText="No. of pax"
                        placeholder="No. of pax"
                        disabled
                        :errorMessage="form.errors?.pax || ''"
                        v-model="form.pax"
                    />
                    <Dropdown
                        inputName="assigned_waiter"
                        labelText="Assign Waiter"
                        imageOption
                        :inputArray="waiters"
                        :errorMessage="form.errors?.assigned_waiter || ''"
                        v-model="form.assigned_waiter"
                    />
                </div>
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
                    Check in
                </Button>
            </div>
        </div>
    </form>
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
</template>
