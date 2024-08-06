<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3';
import RightSidebar from '@/Components/RightSidebar/RightSidebar.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue';

const home = ref({
    label: 'Dashboard',
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
            />
        </template>
        
        <RightSidebar
            :title="'Title'"
            :previousTab="true"
        >
        </RightSidebar>

        <div></div>
    </AuthenticatedLayout>
</template>
