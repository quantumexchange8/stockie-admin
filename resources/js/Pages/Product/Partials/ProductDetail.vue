<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
const props = defineProps({
    id: String,
})

</script>

<template>
    <Head title="Product Detail" />

    <AuthenticatedLayout>
        <template #header>
            Menu Management > Product Detail
        </template>

        <div class="grid grid-cols-1 md:grid-cols-12 justify-center gap-5">
            <div class="col-span-full md:col-span-5 flex flex-col h-[404px] py-6 gap-15 items-center rounded-[5px] border border-red-100"></div>
            <div class="col-span-full md:col-span-7 flex flex-col h-[404px] py-6 gap-6 items-end rounded-[5px] border border-red-100"></div>
        </div>
    </AuthenticatedLayout>
</template>
