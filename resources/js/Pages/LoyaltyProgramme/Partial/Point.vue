<script setup>
import { computed } from "vue";
import { PointsBoxIcon, PointsIcon, RedeemPointsIcon } from '@/Components/Icons/solid';
import PointTable from "./PointTable.vue";
import MostRedeemedProductSection from "./MostRedeemedProductSection.vue";
import RecentRedemptionSection from "./RecentRedemptionSection.vue";

const props = defineProps({
    columns: Array,
    rows: Array,
    inventoryItems: Array,
    totalPointsGivenAway: Number,
    rowType: Object,
    actions: {
        type: Object,
        default: () => {},
    },
    totalPages: Number,
    rowsPerPage: Number,
});

const totalRedemptionCount = computed(() => {
    return props.rows
        .flatMap(row => row.point_histories)
        .reduce((total, record) => total + record.qty, 0);
});


</script>

<template>
    <div class="flex flex-col gap-5 rounded-[5px] overflow-y-auto">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">
            <div class="col-span-full sm:col-span-4 flex justify-center md:justify-between gap-3 border border-primary-100 p-5 rounded-[5px]">
                <div class="flex flex-col gap-2 items-center md:items-start">
                    <span class="text-sm font-medium text-grey-900 whitespace-nowrap">Points Given Away</span>
                    <span class="text-lg font-medium text-primary-900">{{ totalPointsGivenAway }} pts</span>
                </div>
                <div class="hidden bg-primary-50 rounded-[5px] md:flex items-center justify-center gap-2.5 w-16 h-16">
                    <PointsIcon class="text-primary-900 size-8"/>
                </div>
            </div>
            <div class="col-span-full sm:col-span-4 flex justify-center md:justify-between gap-3 border border-primary-100 p-5 rounded-[5px]">
                <div class="flex flex-col gap-2 items-center md:items-start">
                    <span class="text-sm font-medium text-grey-900 whitespace-nowrap">Counts of Redemption</span>
                    <span class="text-lg font-medium text-primary-900">{{ totalRedemptionCount }}</span>
                </div>
                <div class="hidden bg-primary-50 rounded-[5px] md:flex items-center justify-center gap-2.5 w-16 h-16">
                    <RedeemPointsIcon class="text-primary-900 size-8"/>
                </div>
            </div>
            <div class="col-span-full sm:col-span-4 flex justify-center md:justify-between gap-3 border border-primary-100 p-5 rounded-[5px]">
                <div class="flex flex-col gap-2 items-center md:items-start">
                    <span class="text-sm font-medium text-grey-900 whitespace-nowrap">Redeemable Item</span>
                    <span class="text-lg font-medium text-primary-900">{{ rows.length }}</span>
                </div>
                <div class="hidden bg-primary-50 rounded-[5px] md:flex items-center justify-center gap-2.5 w-16 h-16">
                    <PointsBoxIcon class="text-primary-900 size-8"/>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
            <MostRedeemedProductSection
                :redeemedProducts="rows"
                class="col-span-full sm:col-span-5"
            />

            <RecentRedemptionSection
                :redeemableItems="rows"
                class="col-span-full sm:col-span-7"
            />
        </div>

        <PointTable
            :inventoryItems="inventoryItems"
            :columns="columns"
            :rows="rows"
            :rowType="rowType"
            :actions="actions"
            :totalPages="totalPages"
            :rowsPerPage="rowsPerPage"
        />

    </div>
</template>
