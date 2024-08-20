<script setup>
import { ref, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Tier from "./Partial/Tier.vue";
import TabView from "@/Components/TabView.vue";
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Point from "./Partial/Point.vue";

const props = defineProps({
    tiers: Array,
    redeemableItems: Array,
    inventoryItems: Array,
    totalPointsGivenAway: Number
});

const home = ref({
    label: 'Loyalty Programme',
});

const tabs = ref(["Points", "Tier"]);
const tiersRowsPerPage = ref(8);
const redeemableItemsRowsPerPage = ref(8);

const tiersColumns = ref([
    { field: "icon", header: "Icon", width: "9", sortable: false },
    { field: "name", header: "Tier Name", width: "15", sortable: true },
    { field: "min_amount", header: "Amount spend to achive", width: "24", sortable: true },
    { field: "merged_reward_type", header: "Entry Rewards", width: "29", sortable: true },
    { field: "member", header: "Member", width: "13", sortable: true },
    { field: "action", header: "", width: "10", sortable: false, edit: true, delete: true },
]);

const redeemableItemsColumns = ref([
    { field: "name", header: "Product Name", width: "45", sortable: true },
    { field: "point", header: "Redeemed with", width: "25", sortable: true },
    { field: "stock_left", header: "Left", width: "15", sortable: true },
    { field: "action", header: "", width: "15", sortable: false, edit: true, delete: true },
]);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const actions = [
    {
        view: (id) => `/loyalty-programme/point_details/${id}`,
        replenish: () => '',
        edit: () => '',
        delete: (id) => `/loyalty-programme/points/${id}`,
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

</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
            />
        </template>

        <TabView :tabs="tabs">
            <template #tab-0>
                <Point
                    :inventoryItems="inventoryItems"
                    :totalPointsGivenAway="totalPointsGivenAway"
                    :columns="redeemableItemsColumns"
                    :rows="redeemableItems"
                    :rowType="rowType"
                    :actions="actions[0]"
                    :totalPages="redeemableItemsTotalPages"
                    :rowsPerPage="redeemableItemsRowsPerPage"
                />
            </template>
            <template #tab-1>
                <Tier 
                    :inventoryItems="inventoryItems"
                    :columns="tiersColumns"
                    :rows="tiers"
                    :rowType="rowType"
                    :actions="actions[1]"
                    :totalPages="tiersTotalPages"
                    :rowsPerPage="tiersRowsPerPage"
                />
            </template>
        </TabView>
    </AuthenticatedLayout>
</template>
