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
import { wTrans, wTransChoice } from "laravel-vue-i18n";

const props = defineProps({
    tiers: Array,
    redeemableItems: Array,
    products: Array,
    totalPointsGivenAway: Number,
    logos: Array,
});
const home = ref({
    label: wTrans('public.loyalty_programme_header'),
});

const { flashMessage } = useCustomToast();

const tabs = ref([
    { key: 'Points', title: wTransChoice('public.point', 1), disabled: false },
    { key: 'Tier', title: wTrans('public.tier'), disabled: false },
]);
const tiersRowsPerPage = ref(3);
const redeemableItemsRowsPerPage = ref(8);

const tiersColumns = ref([
    { field: "icon", header: wTrans('public.loyalty.icon'), width: "9", sortable: false },
    { field: "name", header: wTrans('public.loyalty.tier_name'), width: "15", sortable: true },
    { field: "min_amount", header: wTrans('public.loyalty.achieve_spend_amount'), width: "24", sortable: true },
    { field: "merged_reward_type", header: wTrans('public.entry_rewards'), width: "29", sortable: true },
    { field: "member", header: wTrans('public.loyalty.member'), width: "13", sortable: true },
    { field: "action", header: "", width: "10", sortable: false, edit: true },
]);

const redeemableItemsColumns = ref([
    { field: "product_name", header: wTrans('public.product_name'), width: "58", sortable: true },
    { field: "point", header: wTrans('public.redeemed_with'), width: "25", sortable: true },
    { field: "stock_left", header: wTrans('public.left_header'), width: "17", sortable: true },
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

onMounted(() => flashMessage());

</script>

<template>
    <Head :title="$t('public.loyalty_programme_header')" />

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
                    :rowsPerPage="tiersRowsPerPage"
                />
            </template>
        </TabView>
    </AuthenticatedLayout>
</template>
