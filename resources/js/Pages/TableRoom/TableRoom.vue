<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3';
import RightSidebar from '@/Components/RightSidebar/RightSidebar.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Button from '@/Components/Button.vue';
import { GearIcon, PlusIcon } from '@/Components/Icons/solid';
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import ManageZoneMainArea from './Partials/ManageZoneMainArea.vue';
import ManageZoneAll from './Partials/ManageZoneAll.vue';
import ManageZone2ndFloor from './Partials/ManageZone2ndFloor.vue';
import ManageZoneA from './Partials/ManageZoneA.vue';
import Modal from '@/Components/Modal.vue';
import AddManageZone from './Partials/AddManageZone.vue';

const home = ref({
    label: 'Table & Room',
});

const props = defineProps({
  zones: {
    type: Array,
    required: true
  }
});
// console.log('Zones in TableRoom:', props.zones);

const isModalOpen = ref(false);
const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
};

const openModal = () => {
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};
</script>

<template>

    <Head title="Table & Room" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <RightSidebar 
            :title="'Title'" 
            :previousTab="true">
        </RightSidebar>

        <div class="flex flex-wrap md:flex-nowrap items-center justify-between gap-3 rounded-[5px]">
            <SearchBar 
                placeholder="Search" />
                <Button
                    :type="'button'"
                    :size="'lg'"
                    :iconPosition="'left'"
                    class="md:!w-fit"
                >
                    <template #icon>
                        <PlusIcon
                            class="w-6 h-6"
                        />
                    </template>
                    Add Table / Room
                </Button>
        </div>

            <TabGroup>
                <TabList class="flex border-b border-gray-200 pt-[20px]">
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                            'p-3 text-sm font-medium leading-none',
                            'focus:outline-none',
                            selected
                                ? 'text-primary-900 border-b-2 border-primary-900'
                                : 'text-grey-200 hover:bg-white/[0.12] hover:text-primary-800',
                            ]"
                        >
                            All
                        </button>
                    </Tab>
                    
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                            'p-3 text-sm font-medium leading-none',
                            'focus:outline-none',
                            selected
                                ? 'text-primary-900 border-b-2 border-primary-900'
                                : 'text-grey-200 hover:bg-white/[0.12] hover:text-primary-800',
                            ]"
                        >
                            Main Area
                        </button>
                    </Tab>
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                            'p-3 text-sm font-medium leading-none',
                            'focus:outline-none',
                            selected
                                ? 'text-primary-900 border-b-2 border-primary-900'
                                : 'text-grey-200 hover:bg-white/[0.12] hover:text-primary-800',
                            ]"
                        >
                            2nd Floor
                        </button>
                    </Tab>
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                            'p-3 text-sm font-medium leading-none',
                            'focus:outline-none',
                            selected
                                ? 'text-primary-900 border-b-2 border-primary-900'
                                : 'text-grey-200 hover:bg-white/[0.12] hover:text-primary-800',
                            ]"
                        >
                            Zone A
                        </button>
                    </Tab>
                    <div>
                        <Button
                            :type="'button'"
                            :size="'lg'"
                            :iconPosition="'left'"
                            @click="openModal"
                            variant="tertiary"
                            class="md:!w-fit !border-0"
                                >
                                    <template #icon>
                                        <GearIcon
                                            class="w-[20px] h-[20px]"
                                        />
                                    </template>
                                    Manage Zone
                        </Button>
                        <Modal
                            :show="isModalOpen"
                            @close="closeModal"
                            :title="'Manage Zone'"
                            :maxWidth="'md'"
                        >
                            <AddManageZone @close="closeModal"  :zones="zones"/>
                        </Modal>
                    </div>
                </TabList>
                <TabPanels class="mt-2">
                    <TabPanel
                        :class="[
                            'rounded-xl bg-white p-3',
                            'focus:outline-none',
                        ]"
                    >
                        <ManageZoneAll />
                    </TabPanel>
                    <TabPanel
                        :class="[
                            'rounded-xl bg-white p-3',
                            'focus:outline-none',
                        ]"
                    >
                        <ManageZoneMainArea />
                    </TabPanel>
                    <TabPanel
                        :class="[
                            'rounded-xl bg-white p-3',
                            'focus:outline-none',
                        ]"
                    >
                        <ManageZone2ndFloor />
                    </TabPanel>
                    <TabPanel
                        :class="[
                            'rounded-xl bg-white p-3',
                            'focus:outline-none',
                        ]"
                    >
                        <ManageZoneA />
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        <!-- </div> -->
    </AuthenticatedLayout>
    <!-- <AddManageZone :zones="zones" v-if="isModalOpen" @close="isModalOpen = false" /> -->
</template>
