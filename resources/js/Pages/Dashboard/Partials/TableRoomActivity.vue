<script setup>
import { TableRoomActivityIllust } from '@/Components/Icons/illus';
import { CircledArrowHeadRightIcon2 } from '@/Components/Icons/solid';
import { Link } from '@inertiajs/vue3';

const activeTable = (n) => {
    console.log('t' + n + ' button clicked')
}
</script>

<template>
    <div class="w-full flex flex-col py-6 item-start gap-6 shrink-0 rounded-[5px] border border-solid border-primary-100">
        <div class="w-full flex flex-col items-start gap-[10px] self-stretch">
            <div class="w-full flex justify-between px-6 items-center self-stretch ">
                <span class="flex flex-col justify-between flex-[1_0_0] text-md font-medium text-primary-900 w-full">Table / Room Activity</span>
                <Link :href="route('orders')">
                        <CircledArrowHeadRightIcon2  
                            class="w-6 h-6 text-primary-25 [&>rect]:fill-primary-900 [&>rect]:hover:fill-primary-800 hover:cursor-pointer"
                        />
                </Link>
            </div>
        </div>

        <div class="flex pl-6 items-end gap-[10px] self-stretch overflow-x-auto relative">
            <div 
                class="flex p-3 pt-0 items-end gap-1 flex-[1_0_0] h-[102.231px] w-[400px] absolute
                        rounded-tl-[45px] rounded-tr-[5px] rounded-bl-[5px] rounded-br-[5px] 
                        bg-gradient-to-r from-[rgba(255,147,147,0.20)] from-[0.08%] via-[rgba(249,213,213,0.58)] via-[21.54%] to-[#FFF] to-[99.89%]"
            >
            </div>
            <div class="flex p-3 items-end gap-1 flex-[1_0_0] w-[400px] z-10">
                <TableRoomActivityIllust class="flex-shrink-0"/>
                <div class="flex flex-col justify-center items-start gap-[5px] flex-[1_0_0]">
                    <span class="text-primary-950 text-xs font-normal">Active Table / Room: </span>
                    <div class="flex items-center gap-3 self-stretch">
                        <template v-for="n in 10" :key="n">
                            <div class="flex w-9 h-9 px-[9px] py-2 flex-col justify-center items-center gap-[10px] rounded-full bg-primary-800 hover:bg-primary-900 hover:cursor-pointer"
                                    @click="activeTable(n)">
                                <span class="text-primary-25 text-base font-medium">T{{ n }}</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full flex flex-col px-6 items-end gap-[10px] flex-[1_0_0] self-stretch min-h-[300px] overflow-y-auto scrollbar-webkit">
            <div class="w-full flex flex-col items-end self-stretch divide-y-[0.5px] divide-solid divide-grey-200">
                    <template v-for="n in 6" :key="n">
                <div class="w-full h- flex flex-col p-3 items-end gap-3 self-stretch rounded-[5px]">
                    <div class="w-full flex items-start gap-[10px] self-stretch">
                        <div class="h-9 w-9 rounded-full bg-primary-900"></div>
                        <div class="w-full flex flex-col justify-center items-start gap-1 flex-[1_0_0]">
                            <div class="flex items-center gap-1 self-stretch">
                                <div class="w-2 h-2 rounded-full bg-green-600"></div>
                                <span class="w-full line-clamp-1 overflow-hidden text-primary-900 text-ellipsis text-xs font-medium">Waiter name</span>
                                <span class="text-grey-300 text-2xs font-normal">15m</span>
                            </div>
                            <span class="text-grey-900 text-sm font-normal">Placed an order for <span class="text-grey-900 text-sm font-semibold">T1.</span></span>
                        </div>
                    </div>
                </div>
                </template>
            </div>
        </div>
    </div>
</template>

