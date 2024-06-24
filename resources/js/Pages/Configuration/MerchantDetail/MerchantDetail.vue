<script setup>
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <Head title="Configuration" />
    <div class="flex flex-col gap-5">
        <div
            class="flex flex-col h-[12px] py-6 gap-15 justify-center items-center rounded-[5px] border border-primary-100"
        ></div>
        <div
            class="flex flex-col p-6 items-start self-stretch gap-6 border border-primary-100 rounded-[5px]"
        >
            <div class="flex flex-col justify-center flex-[1_0_0] h-6">
                <p class="text-md font-medium text-primary-900">
                    Merchant Detail
                </p>
            </div>
            <div
                class="flex w-full gap-5 self-stretch flex-wrap md:flex-nowrap"
            ></div>
        </div>
    </div>
</template>
