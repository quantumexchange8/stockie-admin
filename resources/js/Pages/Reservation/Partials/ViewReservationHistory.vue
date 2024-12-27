<script setup>
import { computed, onMounted, ref } from "vue";
import { useCustomToast } from "@/Composables/index";
import Toast from "@/Components/Toast.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ReservationHistoryTable from "./ReservationHistoryTable.vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";

const home = ref({
    label: 'Reservation',
    route: '/reservation'
});

const items = ref([
    { label: 'Reservation History' },
]);

const props = defineProps({
    reservations: Array,
    tables: Array,
})

const { flashMessage } = useCustomToast();

onMounted(() => flashMessage());

const reservations = ref(props.reservations);
const rowsPerPage = ref(10);

const columns = ref([
    { field: 'reservation_no', header: 'No.', width: '9', sortable: false},
    { field: 'res_date', header: 'Date', width: '10', sortable: false},
    { field: 'res_time', header: 'Time', width: '7', sortable: false},
    { field: 'name', header: 'Name', width: '25', sortable: false},
    { field: 'pax', header: 'Pax', width: '6', sortable: false},
    { field: 'table_no', header: 'Table / Room', width: '18', sortable: false},
    { field: 'phone', header: 'Contact No.', width: '15', sortable: false},
    { field: 'status', header: 'Status', width: '10', sortable: false},
])

const totalPages = computed(() => Math.ceil(props.reservations.length / rowsPerPage.value))

const actions = {
    edit: () => ``,
    delete: () => ``,
};

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

</script>

<template>
    <Head title="Reservation History" />
    
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
                :items="items"
            />
        </template>

        <Toast />

        <ReservationHistoryTable
            :tables="tables"
            :columns="columns" 
            :rows="reservations" 
            :actions="actions" 
            :rowType="rowType" 
            :totalPages="totalPages" 
            :rowsPerPage="rowsPerPage"
        />
    </AuthenticatedLayout>
</template>
