<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue'
import Button from '@/Components/Button.vue'
import SearchBar from '@/Components/SearchBar.vue'
import { PlusIcon, TimesIcon } from '@/Components/Icons/solid';
import CreatePromotionForm from './Partials/CreatePromotionForm.vue'

const createFormIsOpen = ref(false);

const showCreateForm = () => {
    createFormIsOpen.value = true;
}

const hideCreateForm = () => {
    createFormIsOpen.value = false;
}

</script>

<template>
    <Head title="Configuration" />

    <AuthenticatedLayout>
        <template #header>
            Configuration
        </template>

        <div class="flex flex-col h-[12px] py-6 gap-15 justify-center items-center rounded-[5px] border border-primary-100"></div>
        <div class="flex flex-col p-6 items-start self-stretch gap-6 border border-primary-100 rounded-[5px]">
            <div class="flex flex-col justify-center flex-[1_0_0] h-6">
                <p class="text-md font-medium text-primary-900">Promotion List</p>
            </div>
            <div class="flex w-full gap-5 self-stretch flex-wrap md:flex-nowrap">
                <SearchBar
                    :inputName="'searchbar'"
                    :placeholder="'Search'"
                />
                <Button
                    :type="'button'"
                    :size="'lg'"
                    :iconPosition="'left'"
                    class="md:!w-fit"
                    @click="showCreateForm"
                >
                    <template #icon>
                        <PlusIcon
                            class="w-6 h-6"
                        />
                    </template>
                    New Promotion
                </Button>
                <Modal 
                    :title="'Add New Promotion'"
                    :show="createFormIsOpen" 
                    :maxWidth="'lg'" 
                    :closeable="true" 
                    @close="hideCreateForm"
                >
                    <CreatePromotionForm 
                        @close="hideCreateForm"
                    />
                </Modal>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
