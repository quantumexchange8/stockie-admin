<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const home = ref({
    label: 'Menu Management',
    route: '/menu-management/products'
});
const items = ref([
    { label: 'Product Detail' },
]);

const props = defineProps({
    id: String,
})

</script>

<template>
    <Head title="Product Detail" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
                :items="items"
            />
        </template>

        <div class="grid grid-cols-1 md:grid-cols-12 justify-center gap-5">
            <div class="col-span-full md:col-span-5 flex flex-col h-[404px] py-6 gap-15 items-center rounded-[5px] border border-red-100"></div>
            <div class="col-span-full md:col-span-7 flex flex-col h-[404px] py-6 gap-6 items-end rounded-[5px] border border-red-100"></div>
        </div>
    </AuthenticatedLayout>
</template>
