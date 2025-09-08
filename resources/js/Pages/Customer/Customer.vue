<script setup>
import Breadcrumb from "@/Components/Breadcrumb.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { computed, onMounted, ref } from "vue";
import CustomerTable from "./Partials/CustomerTable.vue";
import axios from "axios";
import Toast from "@/Components/Toast.vue";
import { useCustomToast } from "@/Composables/index";
import { Head } from "@inertiajs/vue3";
import { wTrans, wTransChoice } from "laravel-vue-i18n";

const home = ref({
    label: wTrans('public.customer_header'),
});

const props = defineProps({
    customers: Array,
    rankingArr: Array
})

const { flashMessage } = useCustomToast();

const initialCustomers = ref(props.customers);
const customers = ref(props.customers);
const customerRowsPerPage = ref(10);
const highestPoints = computed(() => {
    return Math.max(...initialCustomers.value.map(customer => customer.point));
});

const customerColumn = ref([
    { field: 'ranking', header: wTrans('public.tier'), width: '13', sortable: true},
    { field: 'full_name', header: wTrans('public.customer_header'), width: '28', sortable: true},
    { field: 'point', header: wTransChoice('public.point', 1), width: '13', sortable: true},
    { field: 'keep_items_count', header: wTrans('public.keep'), width: '13', sortable: true},
    { field: 'created_at', header: wTrans('public.customer.joined_date'), width: '20', sortable: true},
    { field: 'action', header: '', width: '13', sortable: false, edit: true},
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
    <Head :title="$t('public.customer_header')" />

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
            :rowsPerPage="customerRowsPerPage"
            :highestPoints="highestPoints"
            :rankingArr="rankingArr"
            @applyCheckedFilters="applyCheckedFilters"
        />
        
    </AuthenticatedLayout>
</template>
