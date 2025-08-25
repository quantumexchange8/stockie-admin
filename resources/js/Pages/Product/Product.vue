<script setup>
import axios from 'axios';
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3';
import ProductTable from './Partials/ProductTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TotalProductChart from './Partials/TotalProductChart.vue';
import TopSellingProductTable from './Partials/TopSellingProductTable.vue';
import Toast from '@/Components/Toast.vue';
import { useCustomToast } from '@/Composables/index.js';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps({
    products: Array,
    inventories: Array,
    categories: Array,
});

const home = ref({
    label: wTrans('public.menu_management_header'),
});

// only for 'list' variant of table component
const productColumns = ref([
    // For row group options, the groupRowsBy set inside the rowType, will have its width set to be the left most invisible column width
    {field: 'availability', header: wTrans('public.menu.availability'), width: '13', sortable: false},
    {field: 'product_name', header: wTrans('public.product_name'), width: '50', sortable: false},
    {field: 'price', header: wTrans('public.price'), width: '15', sortable: false},
    {field: 'stock_left', header: wTrans('public.left_header'), width: '15', sortable: false},
    {field: 'action', header: '', width: '7', sortable: false, edit: true}
]);

const topSellingProductColumns = ref([
    // For row group options, the groupRowsBy set inside the rowType, will have its width set to be the left most invisible column width
    {field: 'product', header: wTrans('public.product'), width: '45', sortable: false},
    {field: 'category', header: wTrans('public.category'), width: '18', sortable: false},
    {field: 'sold', header: wTrans('public.sold'), width: '15', sortable: false},
    {field: 'status', header: wTrans('public.status'), width: '22', sortable: false},
]);

const { flashMessage } = useCustomToast();

const initialProducts = ref(props.products);
const products = ref(props.products);
const categoryArr = ref(props.categories);
const inventoriesArr = ref(props.inventories);
const productRowsPerPage = ref(8);
const selectedCategory = ref(0);
const isLoading = ref(false);
const checkedFilters = ref({
    itemCategory: [],
    stockLevel: [],
});

// Define row type with its options for 'list' variant
const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
}

// When declaring the actions, make sure to set the column property with the same action name to true to display the action button ('list' variant) 
const actions = [
    {
        view: (productId) => `/menu-management/products_details/${productId}`,
        replenish: () => ``,
        // For 'grid' variant only has below two
        edit: () => ``,
        delete: (productId) => `/menu-management/deleteProduct/${productId}`,
    },
    {
        view: (productId) => `/menu-management/products_details/${productId}`,
        replenish: () => ``,
        edit: () => ``,
        delete: () => ``,
    }
];

// Get table data
const getProducts = async (filters = {}, selectedCategory = 0) => {
    isLoading.value = true;
    try {
        const response = await axios.get('/menu-management/products/getProducts', {
            method: 'GET',
            params: {
                checkedFilters: filters,
                selectedCategory: selectedCategory,
            }
        });
        products.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const applyCategoryFilter = (category) => {
    selectedCategory.value = category;
    getProducts(checkedFilters.value, category);
};

const applyCheckedFilters = (filters) => {
    checkedFilters.value = filters;
    getProducts(filters, selectedCategory.value);
};

const productsTotalPages = computed(() => {
    return Math.ceil(products.value.length / productRowsPerPage.value);
})

const calcTotalProductSaleQty = (saleHistories) => {
    return saleHistories.reduce((total, sale) => total + sale.qty, 0);
};

// Get the top 4 selling products
const topSellingProducts = computed(() => {
    return initialProducts.value
        .map(product => {
            return {
                ...product,
                totalProductSaleQty: calcTotalProductSaleQty(product.sale_histories)
            };
        })
        .sort((a, b) => b.totalProductSaleQty - a.totalProductSaleQty)
        .slice(0, 4);
});

onMounted(() => {
    flashMessage();
});

</script>

<template>
    <Head :title="$t('public.menu_management_header')" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
            />
        </template>

        <Toast />
        
        <div class="flex flex-col justify-center gap-5">
            <div class="grid grid-cols-1 md:grid-cols-12 justify-center gap-5">
                <TotalProductChart 
                    :products="initialProducts"
                    :categoryArr="categoryArr"
                    :isLoading="isLoading"
                    @isLoading="isLoading=$event"
                    class="col-span-full md:col-span-4"
                />
                <TopSellingProductTable
                    :columns="topSellingProductColumns"
                    :rows="topSellingProducts"
                    :rowType="rowType"
                    :actions="actions[1]"
                    class="col-span-full md:col-span-8"
                />
            </div>
            <ProductTable
                :columns="productColumns"
                :rows="products"
                :categoryArr="categoryArr"
                :inventoriesArr="inventoriesArr"
                :rowType="rowType"
                :actions="actions[0]"
                :totalPages="productsTotalPages"
                :rowsPerPage="productRowsPerPage"
                @applyCategoryFilter="applyCategoryFilter"
                @applyCheckedFilters="applyCheckedFilters"
                @update:categories="categoryArr = $event"
            />
        </div>
    </AuthenticatedLayout>
</template>
