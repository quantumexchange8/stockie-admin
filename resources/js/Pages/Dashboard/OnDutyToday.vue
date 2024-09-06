<script setup>
import Tag from '@/Components/Tag.vue';


</script>

<template>
    <div class="w-full flex flex-col items-end gap-6 py-6 rounded-[5px] bg-gradient-to-br from-primary-900 from-[2.42%] to-[#5E0A0E] to-[102.62%]">
        <div class="flex flex-col pl-4 items-start gap-[10px] self-stretch">
            <div class="flex pl-3 pr-6 justify-between items-center self-stretch rounded-tl-[5px] rounded-bl-[5px]">
                <span class="flex flex-col justify-center flex-[1_0_0] text-primary-25 text-md font-medium">On Duty Today</span>
            </div>
        </div>
        <div class="flex pl-6 justify-end items-center gap-[10px] self-stretch">
            <div class="flex flex-col px-4 py-3 items-start flex-[1_0_0] rounded-tl-[5px] rounded-bl-[5px] bg-white shadow-[0_10px_11.2px_0px_rgba(75,12,14,0.52)]">
                <div class="flex flex-col items-start self-stretch rounded-[5px]">
                    <template v-for="n in 5" :key="n">
                        <div class="flex py-2 justify-between items-center self-stretch">
                            <div class="flex items-center gap-[10px]">
                                <div class="w-10 h-10 shrink-0 rounded-full bg-primary-900"></div>
                                <div class="flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                                    <span class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">Adeline Watson</span>
                                    <span class="text-grey-500 text-sm font-medium">at Today, 16:33</span>
                                </div>
                            </div>
                            <Tag
                                :variant="'green'"
                                :value="'Checked in'"
                            ></Tag>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>