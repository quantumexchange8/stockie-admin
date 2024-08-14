<script setup>
import { ref, onMounted } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Tier from "./Partial/Tier.vue";
import TabView from "@/Components/TabView.vue";
import Breadcrumb from '@/Components/Breadcrumb.vue';

const home = ref({
    label: 'Loyalty Programme',
});

const tabs = ref(["Points", "Tier"]);
const inventoryItems = ref([]);
const tiers = ref([]);
const tiersTotalPages = ref(1);
const tiersRowsPerPage = ref(8);

const tiersColumns = ref([
    { field: "icon", header: "Icon", width: "9", sortable: false },
    { field: "name", header: "Tier Name", width: "15", sortable: true },
    { field: "min_amount", header: "Amount spend to achive", width: "24", sortable: true },
    { field: "merged_reward_type", header: "Entry Rewards", width: "29", sortable: true },
    { field: "member", header: "Member", width: "13", sortable: true },
    { field: "action", header: "", width: "10", sortable: false, edit: true, delete: true },
]);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const actions = [
    {
        view: (id) => `/loyalty-programme/tier_details/${id}`,
        replenish: () => '',
        edit: () => '',
        delete: (id) => `/loyalty-programme/products/${id}/delete`,
    }
];

const getAllTiers = async () => {
    try {
        const tiersResponse = await axios.get('/loyalty-programme/getShowRecords');
        tiers.value = tiersResponse.data;
        tiersTotalPages.value = Math.ceil(tiers.value.length / tiersRowsPerPage.value);
    } catch (error) {
        console.log("Error fetching data:", error);
    } finally {

    }
};

const getAllInventoryItems = async () => {
    try {
        const inventoryItemsResponse = await axios.get('/loyalty-programme/getAllInventoryWithItems');
        inventoryItems.value = inventoryItemsResponse.data;
    } catch (error) {
        console.log("Error fetching data:", error);
    } finally {

    }
};

onMounted(() => {
    getAllTiers();
    getAllInventoryItems();
});

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
                Points
            </template>
            <template #tab-1>
                <Tier 
                    :inventoryItems="inventoryItems"
                    :columns="tiersColumns"
                    :rows="tiers"
                    :rowType="rowType"
                    :actions="actions[0]"
                    :totalPages="tiersTotalPages"
                    :rowsPerPage="tiersRowsPerPage"
                />
            </template>
        </TabView>
    </AuthenticatedLayout>
</template>
