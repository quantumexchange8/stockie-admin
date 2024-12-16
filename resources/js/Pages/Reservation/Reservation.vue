<script setup>
import { computed, ref } from "vue";
import Toast from "@/Components/Toast.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import ReservationTable from "./Partials/ReservationTable.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";

const home = ref({
    label: 'Reservation',
});

const props = defineProps({
    reservations: Array,
    customers: Array,
    tables: Array,
    occupiedTables: Array,
    waiters: Array,
})

const reservations = ref(props.reservations);
const rowsPerPage = ref(10);
const columns = ref([
    { field: 'reservation_no', header: 'No.', width: '9', sortable: false},
    { field: 'res_date', header: 'Date', width: '10', sortable: false},
    { field: 'res_time', header: 'Time', width: '7', sortable: false},
    { field: 'name', header: 'Name', width: '20', sortable: false},
    { field: 'pax', header: 'Pax', width: '6', sortable: false},
    { field: 'merged_table_no', header: 'Table / Room', width: '15', sortable: false},
    { field: 'phone', header: 'Contact No.', width: '15', sortable: false},
    { field: 'status', header: 'Status', width: '10', sortable: false},
    { field: 'action', header: 'Action', width: '8', edit: false, delete: false, sortable: false},
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

const fetchReservations = async () => {
    try {
        const response = await axios.get(route('reservations.getReservations'));
        reservations.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {

    }
};

</script>

<template>
    <Head title="Reservation" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <Toast />

        <ReservationTable 
            :customers="customers" 
            :tables="tables" 
            :occupiedTables="occupiedTables" 
            :waiters="waiters" 
            :columns="columns" 
            :rows="reservations" 
            :actions="actions" 
            :rowType="rowType" 
            :totalPages="totalPages" 
            :rowsPerPage="rowsPerPage"
            @fetchReservations="fetchReservations"
        />
    </AuthenticatedLayout>
</template>
