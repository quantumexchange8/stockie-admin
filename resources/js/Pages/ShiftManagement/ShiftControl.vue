<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Button from '@/Components/Button.vue';
import Tag from '@/Components/Tag.vue';
import Toast from '@/Components/Toast.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const home = ref({
    label: 'Shift Control',
})

const spoiler = ref(false);
const showSpoiler = () => {
    spoiler.value = !spoiler.value;
}
</script>

<template>
    <Head title="Shift Control" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <Toast />
        
        <div class="grid grid-cols-1 md:grid-cols-12 items-center gap-5 h-full">
            <div class="col-span-full md:col-span-5 lg:col-span-4 gap-y-4 p-5 flex flex-col self-stretch items-center border border-primary-100 rounded-[5px] max-h-[calc(100dvh-20rem)] md:max-h-[calc(100dvh-12rem)] shrink-0 overflow-y-auto scrollbar-thin scrollbar-webkit">
                <div class="flex flex-col w-full items-start self-stretch gap-y-5 p-4 rounded-[5px] bg-white border border-grey-100 shadow-sm">
                    <div class="flex w-full flex-col gap-y-3 items-start">
                        <div class="flex w-full items-center justify-between gap-x-3 self-stretch">
                            <p class="truncate text-sm font-bold text-grey-950">Shift 5</p>
                            <Tag value="Active" />
                        </div>        

                        <p class="truncate font-bold text-md text-grey-950 self-stretch">2024-12-23 13:00</p>
                    </div>

                    <div class="flex gap-x-3 items-start self-stretch">
                        <p class="truncate font-normal text-xs text-grey-500">by</p>

                        <div class="flex gap-x-1 items-center">
                            <p class="truncate font-bold text-xs text-grey-950">Catherine Tan</p>
                            <div class="rounded-full border borer-grey-100 size-4 bg-primary-100"></div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col w-full items-start self-stretch gap-y-5 p-4 rounded-[5px] bg-white border border-grey-100 shadow-sm">
                    <div class="flex w-full flex-col gap-y-3 items-start">
                        <div class="flex w-full items-center justify-between gap-x-3 self-stretch">
                            <p class="truncate text-sm font-bold text-grey-950">Shift 4</p>
                            <Tag value="Closed" variant="grey" />
                        </div>        

                        <p class="truncate font-bold text-md text-grey-950 self-stretch">2024-12-23 13:00</p>
                    </div>

                    <div class="flex gap-x-3 items-start self-stretch">
                        <p class="truncate font-normal text-xs text-grey-500">by</p>

                        <div class="flex gap-x-1 items-center">
                            <p class="truncate font-bold text-xs text-grey-950">Catherine Tan</p>
                            <div class="rounded-full border borer-grey-100 size-4 bg-primary-100"></div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col w-full items-start self-stretch gap-y-5 p-4 rounded-[5px] bg-white border border-grey-100 shadow-sm">
                    <div class="flex w-full flex-col gap-y-3 items-start">
                        <div class="flex w-full items-center justify-between gap-x-3 self-stretch">
                            <p class="truncate text-sm font-bold text-grey-950">Shift 3</p>
                            <Tag value="Closed" variant="grey" />
                        </div>        

                        <p class="truncate font-bold text-md text-grey-950 self-stretch">2024-12-23 13:00</p>
                    </div>

                    <div class="flex gap-x-3 items-start self-stretch">
                        <p class="truncate font-normal text-xs text-grey-500">by</p>

                        <div class="flex gap-x-1 items-center">
                            <p class="truncate font-bold text-xs text-grey-950">Catherine Tan</p>
                            <div class="rounded-full border borer-grey-100 size-4 bg-primary-100"></div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col w-full items-start self-stretch gap-y-5 p-4 rounded-[5px] bg-white border border-grey-100 shadow-sm">
                    <div class="flex w-full flex-col gap-y-3 items-start">
                        <div class="flex w-full items-center justify-between gap-x-3 self-stretch">
                            <p class="truncate text-sm font-bold text-grey-950">Shift 2</p>
                            <Tag value="Closed" variant="grey" />
                        </div>        

                        <p class="truncate font-bold text-md text-grey-950 self-stretch">2024-12-23 13:00</p>
                    </div>

                    <div class="flex gap-x-3 items-start self-stretch">
                        <p class="truncate font-normal text-xs text-grey-500">by</p>

                        <div class="flex gap-x-1 items-center">
                            <p class="truncate font-bold text-xs text-grey-950">Catherine Tan</p>
                            <div class="rounded-full border borer-grey-100 size-4 bg-primary-100"></div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col w-full items-start self-stretch gap-y-5 p-4 rounded-[5px] bg-white border border-grey-100 shadow-sm">
                    <div class="flex w-full flex-col gap-y-3 items-start">
                        <div class="flex w-full items-center justify-between gap-x-3 self-stretch">
                            <p class="truncate text-sm font-bold text-grey-950">Shift 1</p>
                            <Tag value="Closed" variant="grey" />
                        </div>        

                        <p class="truncate font-bold text-md text-grey-950 self-stretch">2024-12-23 13:00</p>
                    </div>

                    <div class="flex gap-x-3 items-start self-stretch">
                        <p class="truncate font-normal text-xs text-grey-500">by</p>

                        <div class="flex gap-x-1 items-center">
                            <p class="truncate font-bold text-xs text-grey-950">Catherine Tan</p>
                            <div class="rounded-full border borer-grey-100 size-4 bg-primary-100"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-full md:col-span-7 lg:col-span-8 flex flex-col self-stretch items-center justify-start bg-white shadow-sm border border-grey-100 rounded-[5px] max-h-[calc(100dvh-12rem)] shrink-0 overflow-y-auto scrollbar-thin scrollbar-webkit">
                <div class="flex flex-col gap-y-5 p-5 justify-center items-center self-stretch border-b border-grey-100">
                    <div class="flex flex-col gap-y-2 items-center self-stretch">
                        <p class="truncate self-stretch text-grey-950 text-lg font-bold text-center">Shift #5</p>
                        <p class="truncate self-stretch text-grey-950 text-sm font-normal text-center">2024-12-23 13:00</p>
                    </div>

                    <div class="flex flex-col gap-y-3 items-start self-stretch">
                        <div class="flex gap-x-3 items-center self-stretch">
                            <Button
                                :type="'button'"
                                :variant="'red-tertiary'"
                                :size="'lg'"
                            >
                                Delete shift
                            </Button>
                            <Button
                                :type="'button'"
                                :variant="'secondary'"
                                :size="'lg'"
                            >
                                Pay in/out
                            </Button>
                        </div>

                        <Button
                            :type="'button'"
                            :size="'lg'"
                        >
                            Close shift
                        </Button>
                    </div>
                </div>

                <div class="flex flex-col items-start self-stretch gap-y-6 p-5">
                    <div class="flex flex-col items-start self-stretch gap-y-2">
                        <div class="flex justify-between items-start self-stretch">
                            <p class="text-grey-700 font-normal text-base">Opening date</p>
                            <p class="text-grey-950 font-semibold text-base text-right">2024-12-23 13:00</p>
                        </div>
                        <div class="flex justify-between items-start self-stretch">
                            <p class="text-grey-700 font-normal text-base">Opened by</p>
                            <div class="flex gap-x-1 items-center">
                                <p class="truncate font-semibold text-base text-grey-950">Catherine Tan</p>
                                <div class="rounded-full border borer-grey-100 size-4 bg-primary-100 text-right"></div>
                            </div>
                        </div>
                    </div>

                    <hr class="w-full h-[1px] bg-grey-100">

                    <div class="flex flex-col items-start self-stretch gap-y-2">
                        <div class="flex justify-between items-start self-stretch">
                            <p class="text-grey-700 font-normal text-base">Starting cash</p>
                            <p class="text-grey-950 font-semibold text-base text-right">RM 500.00</p>
                        </div>
                        <div class="flex justify-between items-start self-stretch">
                            <p class="text-grey-700 font-normal text-base">Paid in</p>
                            <p class="text-grey-950 font-semibold text-base text-right">N/A</p>
                        </div>
                        <div class="flex justify-between items-start self-stretch">
                            <p class="text-grey-700 font-normal text-base">Paid out</p>
                            <p class="text-grey-950 font-semibold text-base text-right">N/A</p>
                        </div>
                        <div class="flex justify-between items-start self-stretch">
                            <p class="text-grey-700 font-normal text-base">Closing cash</p>
                            <p class="text-grey-950 font-semibold text-base text-right">N/A</p>
                        </div>
                        <div class="flex justify-between items-start self-stretch">
                            <p class="text-grey-700 font-normal text-base">Cash difference</p>
                            <p class="text-grey-950 font-semibold text-base text-right">N/A</p>
                        </div>
                    </div>

                    <hr class="w-full h-[1px] bg-grey-100">

                    <div class="flex flex-col items-start self-stretch gap-y-2">
                        <div class="flex justify-between items-start self-stretch">
                            <p class="text-grey-700 font-normal text-base">Closing date</p>
                            <p class="text-grey-950 font-semibold text-base text-right">N/A</p>
                        </div>
                        <div class="flex justify-between items-start self-stretch">
                            <p class="text-grey-700 font-normal text-base">Closed by</p>
                            <p class="text-grey-950 font-semibold text-base text-right">N/A</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>        
</template>

