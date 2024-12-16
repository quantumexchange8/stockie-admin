<script setup>
import { ref, computed, onMounted } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Tier from "./Partial/Tier.vue";
import TabView from "@/Components/TabView.vue";
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Point from "./Partial/Point.vue";
import Toast from '@/Components/Toast.vue'
import { useCustomToast } from '@/Composables/index.js';
import { Head } from "@inertiajs/vue3";

const props = defineProps({
    tiers: Array,
    redeemableItems: Array,
    products: Array,
    totalPointsGivenAway: Number,
    logos: Array,
});
const home = ref({
    label: 'Loyalty Programme',
});

const { flashMessage } = useCustomToast();

const tabs = ref(["Points", "Tier"]);
const tiersRowsPerPage = ref(8);
const redeemableItemsRowsPerPage = ref(8);

const tiersColumns = ref([
    { field: "icon", header: "Icon", width: "9", sortable: false },
    { field: "name", header: "Tier Name", width: "15", sortable: true },
    { field: "min_amount", header: "Amount spend to achive", width: "24", sortable: true },
    { field: "merged_reward_type", header: "Entry Rewards", width: "29", sortable: true },
    { field: "member", header: "Member", width: "13", sortable: true },
    { field: "action", header: "", width: "10", sortable: false, edit: true },
]);

const redeemableItemsColumns = ref([
    { field: "product_name", header: "Product Name", width: "58", sortable: true },
    { field: "point", header: "Redeemed with", width: "25", sortable: true },
    { field: "stock_left", header: "Left", width: "17", sortable: true },
]);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const actions = [
    {
        view: (id) => `/menu-management/products_details/${id}`,
        replenish: () => '',
        edit: () => '',
        delete: () => ``,
    },
    {
        view: (id) => `/loyalty-programme/tier_details/${id}`,
        replenish: () => '',
        edit: () => '',
        delete: (id) => `/loyalty-programme/tiers/destroy/${id}`,
    }
];

const tiersTotalPages = computed(() => {
    return Math.ceil(props.tiers.length / tiersRowsPerPage.value);
})

const redeemableItemsTotalPages = computed(() => {
    return Math.ceil(props.redeemableItems.length / redeemableItemsRowsPerPage.value);
})

onMounted(() => flashMessage());

</script>

<template>
    <Head title="Loyalty Programme" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
            />
        </template>

        <Toast />

        <TabView :tabs="tabs">
            <template #points>
                <Point
                    :products="products"
                    :totalPointsGivenAway="totalPointsGivenAway"
                    :columns="redeemableItemsColumns"
                    :rows="redeemableItems"
                    :rowType="rowType"
                    :actions="actions[0]"
                    :totalPages="redeemableItemsTotalPages"
                    :rowsPerPage="redeemableItemsRowsPerPage"
                />
            </template>
            <template #tier>
                <Tier 
                    :products="products"
                    :columns="tiersColumns"
                    :rows="tiers"
                    :rowType="rowType"
                    :logos="logos"
                    :actions="actions[1]"
                    :totalPages="tiersTotalPages"
                    :rowsPerPage="tiersRowsPerPage"
                />
            </template>
        </TabView>
    </AuthenticatedLayout>
</template>
