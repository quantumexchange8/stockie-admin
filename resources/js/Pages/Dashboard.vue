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

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
