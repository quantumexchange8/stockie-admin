<script setup>
import { computed } from 'vue';


const props = defineProps({
    redeemedProducts: Array,
});

const mostRedeemedProduct = computed(() => {
    return props.redeemedProducts.sort((a, b) => b.point_histories.qty - a.point_histories.qty).slice(0, 5);
});

const getTotalRedemption = (histories) => {
    let count = 0;

    histories.forEach(record => {
        count += record.qty;
    });

    return count;
}

</script>

<template>
    <div class="flex flex-col justify-start items-center gap-6 border border-primary-100 p-6 rounded-[5px]">
        <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Most Redeemed Product</span>
        <div class="h-full flex flex-col items-start self-stretch gap-4">
            <div 
                class="w-full flex flex-nowrap items-center gap-7"
                v-for="(product, index) in mostRedeemedProduct" :key="index"
            >
                <span class="text-sm text-primary-900 font-medium">{{ index + 1 }}</span>
                <div class="flex flex-nowrap items-start gap-4">
                    <!-- <div class="bg-primary-25 border-[0.2px] border-grey-100 rounded-[1px] size-10"></div> -->
                    <img 
                        :src="product.image ? product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt="" 
                        class="bg-primary-25 border-[0.2px] border-grey-100 rounded-[1px] size-10"
                    />
                    <div class="flex flex-col items-start gap-1">
                        <span class="text-grey-900 text-sm font-medium self-stretch overflow-hidden text-ellipsis"> {{ product.name }}</span>
                        <span class="text-grey-500 text-xs font-medium">{{ getTotalRedemption(product.point_histories) }} redemption</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
