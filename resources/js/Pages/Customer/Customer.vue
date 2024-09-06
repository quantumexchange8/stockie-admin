<script setup>
import Breadcrumb from "@/Components/Breadcrumb.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { computed, onMounted, ref } from "vue";
import CustomerTable from "./Partials/CustomerTable.vue";
import axios from "axios";
import Toast from "@/Components/Toast.vue";
import { useCustomToast } from "@/Composables/index";

const home = ref({
    label: 'Customer',
});

const props = defineProps({
    customers: {
        type:Array,
        required: true,
    }
})

const { flashMessage } = useCustomToast();

const customers = ref(props.customers);
const customerRowsPerPage = ref(10);
const customerTotalPages = computed(() => {
    return Math.ceil(props.customers.length / customerRowsPerPage.value);
})
const highestPoints = computed(() => {
    return Math.max(...customers.value.map(customer => customer.points));
});

const customerColumn = ref([
    { field: 'tier', header: 'Tier', width: '13', sortable: true},
    { field: 'name', header: 'Customer', width: '28', sortable: true},
    { field: 'points', header: 'Points', width: '13', sortable: true},
    { field: 'keep', header: 'Keep', width: '13', sortable: true},
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

const getCustomers = async (filters = {}) => {
    try {
        const response = await axios.get('/customer/filterCustomer', {
            method: 'GET',
            params: {
                checkedFilters: filters,
            }
        });
        customers.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        
    }
};

const applyCheckedFilters = (filters) => {
    checkedFilters.value = filters;
    getCustomers(filters);
}

onMounted(() => {
    flashMessage();
});


</script>

<template>
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
            @applyCheckedFilters="applyCheckedFilters"
        />
        
    </AuthenticatedLayout>
</template>
