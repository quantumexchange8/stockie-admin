<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue'
import Button from '@/Components/Button.vue'
import SearchBar from '@/Components/SearchBar.vue'
import { PlusIcon, TimesIcon } from '@/Components/Icons/solid';
import CreatePromotionForm from './Partials/CreatePromotionForm.vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import Active from "@/Pages/Configuration/Promotion/Partials/Active.vue"
import Inactive from "@/Pages/Configuration/Promotion/Partials/Inactive.vue"

const props = defineProps({
    ActivePromotions: Array,
    InactivePromotions: Array,
})
const activePromotionsCount = computed(() => {
    return props.ActivePromotions.filter(promotion => promotion.status === 'Active').length
})
const InactivePromotionsCount = computed(() => {
    return props.InactivePromotions.filter(promotion => promotion.status === 'Inactive').length
})

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

        <div class=" w-full">
            <TabGroup>
                <TabList class="flex space-x-1 rounded-xl bg-transparent p-1">
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                            'w-[90px] p-3 text-sm font-medium leading-5',
                            'focus:outline-none focus:ring-0',
                            selected
                                ? 'bg-white text-primary-900 border-b-2 border-primary-900'
                                : 'text-blue-100 hover:text-primary-500',
                            ]"
                        >
                            Active ({{ activePromotionsCount }})
                        </button>
                    </Tab>
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                            'w-[90px] py-2.5 text-sm font-medium leading-5',
                            'focus:outline-none focus:ring-0',
                            selected
                                ? 'bg-white text-primary-900 border-b-2 border-primary-900'
                                : 'text-blue-100 hover:text-primary-800',
                            ]"
                        >
                            Inactive ({{ InactivePromotionsCount }})
                        </button>
                    </Tab>
                </TabList>

                <TabPanels class="mt-2">
                    <TabPanel
                        class="py-3"
                    >
                        <Active :ActivePromotions="ActivePromotions"/>
                    </TabPanel>
                    <TabPanel
                        class="py-3"
                    >
                        <Inactive :InactivePromotions="InactivePromotions"/>
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </div>

        <div></div>
    </div>
</template>
