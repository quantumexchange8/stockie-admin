<script setup>
import { UndetectableIllus } from '@/Components/Icons/illus';
import { computed } from 'vue';

const props = defineProps({
    redeemedProducts: Array,
});

const mostRedeemedProduct = computed(() => {
    return props.redeemedProducts
        .filter((product) => product.point_histories.length > 0)
        .sort((a, b) => {
            const totalQtyA = a.point_histories.reduce((total, record) => total + record.qty, 0);
            const totalQtyB = b.point_histories.reduce((total, record) => total + record.qty, 0);
            return totalQtyB - totalQtyA;
        })
        .slice(0, 5);
});

const getTotalRedemption = (histories) => histories.reduce((total, record) => total + record.qty, 0);

</script>

<template>
    <div class="flex flex-col justify-start items-center gap-6 border border-primary-100 p-6 rounded-[5px]">
        <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Most Redeemed Product</span>
        <div class="h-full flex flex-col items-start self-stretch gap-4" v-if="mostRedeemedProduct.length > 0">
            <div class="w-full flex flex-nowrap items-center gap-7" v-for="(product, index) in mostRedeemedProduct" :key="index" >
                <span class="text-sm text-primary-900 font-medium">{{ index + 1 }}</span>
                <div class="flex flex-nowrap items-start gap-4">
                    <img 
                        :src="product.image ? product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt="" 
                        class="bg-primary-25 border-[0.2px] border-grey-100 rounded-[1px] size-10"
                    />
                    <div class="flex flex-col items-start gap-1">
                        <span class="text-grey-900 text-sm font-medium self-stretch overflow-hidden text-ellipsis"> {{ product.product_name }}</span>
                        <span class="text-grey-500 text-xs font-medium">{{ getTotalRedemption(product.point_histories) }} redemption</span>
                    </div>
                </div>
            </div>
        </div>
        <template v-else>
            <div class="flex w-full flex-col items-center justify-center gap-5">
                <UndetectableIllus />
                <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
            </div>
        </template>
    </div>
</template>
