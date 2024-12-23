<script setup>
import { TimesIcon } from '@/Components/Icons/solid';
import Tag from '@/Components/Tag.vue';

const props = defineProps({
    item: {
        type: Object,
        default: () => {}
    },
})
const emit = defineEmits(['close']);
</script>

<template>
    <!-- header -->
    <div class="flex justify-between items-start self-stretch min-w-[370px]">
        <span class="text-primary-950 text-center text-md font-medium">Item not allowed to keep</span>
        <TimesIcon class="text-primary-900 cursor-pointer" @click="emit('close')"/>
    </div>

    <!-- item list -->
    <div class="flex flex-col items-start self-stretch divide-y-[0.5px] divide-grey-200">
        <div class="flex py-3 items-center gap-8 self-stretch" v-for="item in props.item">
            <div class="flex items-center gap-3 flex-[1_0_0]">
                <img
                    :src="item.product.image ? item.product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                    alt="NotAllowedToKeep"
                    class="bg-white object-contain size-[60px]"
                >

                <!-- item info -->
                <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0]">
                    <!-- item info header -->
                    <div class="flex items-center gap-2 self-stretch">
                        <Tag 
                            :variant="'default'"
                            :value="'Set'"
                            v-if="item.product.bucket === 'set'"
                        />
                        <span class="line-clamp-1 self-stretch text-ellipsis text-grey-900 text-base font-medium">{{ item.product.product_name }}</span>
                    </div>

                    <!-- item info details -->
                    <ul class="flex flex-col items-start gap-1 self-stretch" v-for="sub_items in item.sub_items" v-if="item.product.bucket === 'set'">
                        <li class="self-stretch text-grey-900 text-base font-md list-disc list-inside">
                            {{ sub_items.product_item.inventory_item.item_name }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

