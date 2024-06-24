<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import RightSidebar from '@/Components/RightSidebar/RightSidebar.vue'
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            Dashboard
        </template>
        
        <RightSidebar
            :title="'Title'"
            :previousTab="true"
        >
        </RightSidebar>

        <div></div>
    </AuthenticatedLayout>
</template>
