<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminUserList from './Partials/AdminUserList.vue';
import Toast from '@/Components/Toast.vue';

const home = ref({
    label: 'Admin User',
});

const props = defineProps({
    user: {
        type: Array,
        default: () => [],
    }
})

const users = ref(props.user);

const refetchAdminUsers = async () => {
    try {
        const response = axios.get(route('refetch-admin-users'));
        users.value = response.data;
    } catch (error) {
        console.error(error);
    }
}
</script>

<template>
    <Head title="Admin User" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <Toast />

        <div class="flex flex-col p-6 items-start gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
            <span class="flex flex-col justify-center text-primary-900 text-md font-medium">Admin User List</span>
            
            <AdminUserList 
                :users="users"
                @update:users.value="users.value=$event"
            />
        </div>

    </AuthenticatedLayout>      
</template>

