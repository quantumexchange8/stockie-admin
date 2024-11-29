<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Toast from '@/Components/Toast.vue';
import { useCustomToast } from '@/Composables';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import SalesProductOrder from './Partials/SalesProductOrder.vue';
import OrderSummary from './Partials/OrderSummary.vue';
import axios from 'axios';
import SalesCategory from './Partials/SalesCategory.vue';

const home = ref({
    label: 'Summary Reports'
});

const props = defineProps({
    totalSales: {
        type: [String, Number],
        default: 0,
    },
    totalProducts: {
        type: Number,
        default: 0,
    },
    totalOrders: {
        type: Number,
        default: 0,
    },
    ordersArray: {
        type: Array,
        required: true,
    },
    salesCategory: {
        type: Array,
        required: true,
    },
    lastPeriodSales: {
        type: Array,
        required: true,
    }
})
const salesCategory = ref(props.salesCategory);
const lastPeriodSales = ref(props.lastPeriodSales)
const categories = ['Beer', 'Wine', 'Liquor', 'Others'];
const selectedCategory = ref(categories[0]);
const ordersArray = ref(props.ordersArray);
const isOrderLoading = ref(false);
const isSalesLoading = ref(false);

const getNextFiveYears = () => {
    const currentYear = new Date().getFullYear();
    const years = [];

    for (let i = 0; i < 5; i++) {
        years.push(currentYear - i);
    }

    return years;
}

const years = ref(getNextFiveYears());
const selectedYear = ref(years.value[0]);
const filterOrder = async (filters = {}) => {
    isOrderLoading.value = true;
    try {
        const response = await axios.get('/summary-report/filterOrder', {
            method: 'GET',
            params: {
                selected: filters,
            }
        });
        ordersArray.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isOrderLoading.value = false;
    }
}

const filterSales = async (category, filters) => {
    isSalesLoading.value = true;
    try {
        const filteredSales = await axios.get('/summary-report/filterSales',{
            method:'GET',
            params: {
                selectedYear: filters.value,
                selectedCategory: category.value,
            }
        });
        salesCategory.value = filteredSales.data.thisPeriod;
        lastPeriodSales.value = filteredSales.data.lastPeriod;

    } catch (error) {
        console.error(error);
    } finally {
        isSalesLoading.value = false;
    }
};

const applyYearFilter = (filter) => {
    selectedYear.value = filter;
    filterOrder(selectedYear.value);
}

const applySalesFilter = (filter, category) => {
    selectedYear.value = filter;
    selectedCategory.value = category;
    filterSales(selectedYear, selectedCategory);
}

const { flashMessage } = useCustomToast();

onMounted(async()=> {
    flashMessage();
})
</script>

<template>

    <Head title="Summary Reports" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home"
            />
        </template>

        <Toast />

        <div class="flex flex-col w-full gap-5">

            <div class="gap-5 grid grid-cols-12">
                <div class="flex flex-col gap-5 col-span-5">
                    <SalesProductOrder 
                        :totalSales="totalSales" 
                        :totalProducts="totalProducts"
                        :totalOrders="totalOrders"
                    />
                </div>
                <div class="flex flex-col col-span-7 justify-between">
                    <OrderSummary 
                        :ordersArray="ordersArray"
                        :isLoading="isOrderLoading"
                        @isLoading="isOrderLoading=$event"
                        @applyYearFilter="applyYearFilter"
                    />
                </div>
            </div>

            <SalesCategory 
                :salesCategory="salesCategory"
                :lastPeriodSales="lastPeriodSales"
                :isLoading="isSalesLoading"
                @isLoading="isSalesLoading=$event"
                @applySalesFilter="applySalesFilter"
            />

        </div>
    </AuthenticatedLayout>
</template>

