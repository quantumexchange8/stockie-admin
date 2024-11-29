<script setup>
import Breadcrumb from "@/Components/Breadcrumb.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { computed, onMounted, ref } from "vue";
import CustomerTable from "./Partials/CustomerTable.vue";
import axios from "axios";
import Toast from "@/Components/Toast.vue";
import { useCustomToast } from "@/Composables/index";
import { Head } from "@inertiajs/vue3";

const home = ref({
    label: 'Customer',
});

const props = defineProps({
    customers: Array,
    rankingArr: Array
})

const { flashMessage } = useCustomToast();

const initialCustomers = ref(props.customers);
const customers = ref(props.customers);
const customerRowsPerPage = ref(10);
const customerTotalPages = computed(() => {
    return Math.ceil(props.customers.length / customerRowsPerPage.value);
})
const highestPoints = computed(() => {
    return Math.max(...initialCustomers.value.map(customer => customer.point));
});

const customerColumn = ref([
    { field: 'ranking', header: 'Tier', width: '13', sortable: true},
    { field: 'full_name', header: 'Customer', width: '28', sortable: true},
    { field: 'point', header: 'Points', width: '13', sortable: true},
    { field: 'keep_items_count', header: 'Keep', width: '13', sortable: true},
    { field: 'created_at', header: 'Joined date', width: '20', sortable: true},
    { field: 'action', header: '', width: '13', sortable: false},
])

const actions = {
    edit: () => ``,
    delete: () => ``,
};

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const checkedFilters = ref({
    tier: [],
    points: [0, 800],
    keepItems: [],
})

const getFilteredCustomers = (filteredCustomers) => {
    let filteredArr = props.customers.filter(({id}) => {
        let filteredCustomerIds = filteredCustomers.map(({ id }) => id);

        return filteredCustomerIds.includes(id);
    });

    return filteredArr;
};

const getCustomers = async (filters = {}) => {
    try {
        const response = await axios.get('/customer/filterCustomer', {
            method: 'GET',
            params: {
                checkedFilters: filters,
            }
        });
        customers.value = getFilteredCustomers(response.data);
    } catch (error) {
        console.error(error);
    } finally {
        
    }
};

const applyCheckedFilters = (filters) => {
    checkedFilters.value = filters;
    getCustomers(filters);
}

onMounted(() => flashMessage());

</script>

<template>
    <Head title="Customer" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
            />
        </template>

        <Toast />

        <CustomerTable 
            :columns="customerColumn" 
            :customers="customers" 
            :actions="actions" 
            :rowType="rowType" 
            :totalPages="customerTotalPages" 
            :rowsPerPage="customerRowsPerPage"
            :highestPoints="highestPoints"
            :rankingArr="rankingArr"
            @applyCheckedFilters="applyCheckedFilters"
        />
        
    </AuthenticatedLayout>
</template>
