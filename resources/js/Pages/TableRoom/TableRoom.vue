<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { onMounted, ref, watch } from 'vue'
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
import AddTable from './Partials/AddTable.vue';
import ZoneAll from './Partials/ZoneAll.vue';
import axios from 'axios';
import ZoneTabs from './Partials/ZoneTabs.vue';
import TabView from '@/Components/TabView.vue';

const home = ref({
    label: 'Table & Room',
});

const props = defineProps({
  zones: {
    type: Array,
    required: true
  },
});

// tabs need to pass to subpages // 
const tables = ref([]);
const tabs = ref([]);

const populateTabs = () => {
tabs.value = ['All'];
  for (const zone of props.zones) {
    if (zone.text) { 
      tabs.value.push(zone.text);
    }
  }
};

watch(() => props.zones, populateTabs, {immediate: true});

const getTableDetails = async () => {
    try {
        const response = await axios.get('/table-room/get-tabledetails');
        tables.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => {
    getTableDetails();
});

const isModalOpen = ref(false);
const createFormIsOpen = ref(false);

const showCreateForm = () => {
    createFormIsOpen.value = true;
}

const hideCreateForm = () => {
    createFormIsOpen.value = false;
}

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
            <SearchBar placeholder="Search" />
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
                Add Table / Room
            </Button>
            <Modal
                :title="'Add Table / Room'"
                :show="createFormIsOpen"
                :maxWidth="'md'"
                :closeable="true"
                @close="hideCreateForm"
            >
                <AddTable
                    :zonesArr="zones"
                    @close="hideCreateForm"
                />
            </Modal>
        </div>

            <!-- <TabGroup>
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
                    <div v-for="zone in zones" :key="zone.id">
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
                                {{ zone.text }}
                            </button>
                        </Tab>
                    </div>
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
                            <AddManageZone @close="closeModal" :zonesArr="zones"/>
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
                        <ZoneAll :zones="zones"/>
                    </TabPanel>
                    <TabPanel
                        :class="[
                            'rounded-xl bg-white p-3',
                            'focus:outline-none',
                        ]"
                    >
                        <ZoneTabs :zones="zones"/>
                    </TabPanel>
                </TabPanels>
            </TabGroup> -->
            <TabView :tabs="tabs">
                <template v-for="(tab, index) in tabs" :key="index" #[`tab-${index}`]>
                <div v-if="tab === 'All'">
                    <ZoneAll :zones="zones" :tabs="tabs"/>
                </div>
                <div v-else>
                    Tier
                </div>
                </template>
            </TabView>
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
                    <AddManageZone @close="closeModal" :zonesArr="zones"/>
                </Modal>
    </AuthenticatedLayout>
</template>
