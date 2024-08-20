<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { onMounted, ref, watch, computed } from 'vue'
import { Head } from '@inertiajs/vue3';
import RightSidebar from '@/Components/RightSidebar/RightSidebar.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Button from '@/Components/Button.vue';
import { GearIcon, PlusIcon } from '@/Components/Icons/solid';
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import Modal from '@/Components/Modal.vue';
import AddManageZone from './Partials/AddManageZone.vue';
import AddTable from './Partials/AddTable.vue';
import ZoneAll from './Partials/ZoneAll.vue';
import axios from 'axios';
import ZoneTabs from './Partials/ZoneTabs.vue';
import { EmptyTableIllus } from '@/Components/Icons/illus.jsx';
import TabView from '@/Components/TabView.vue';
import { FilterMatchMode } from 'primevue/api';

const home = ref({
    label: 'Table & Room',
});

const props = defineProps({
  zones: {
    type: Array,
    required: true
  },
});

const zones = ref(props.zones);
const tables = ref([]);
const tabs = ref([]);
const isModalOpen = ref(false);
const createFormIsOpen = ref(false);

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const populateTabs = () => {
    tabs.value = ['All'];
    for (const zone of zones.value) {
        if (zone.text) { 
            tabs.value.push(zone.text);
        }
    }
};

watch(() => zones.value, populateTabs, {immediate: true});

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

const filteredZones = computed(() => {
    // If search is empty then return initial zones
    if (!filters.value['global'].value) {
        return zones.value;
    }

    const searchValue = filters.value['global'].value.toLowerCase();
    
    // Filter by zone tables' table_no
    return zones.value
        .map(zone => {
            const matchingTables = zone.tables.filter(table => 
                table.table_no.toLowerCase().includes(searchValue)
            );
            return matchingTables.length > 0 ? { ...zone, tables: matchingTables } : null;
        })
        .filter(zone => zone !== null);
});

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
                :placeholder="'Search'"
                :inputName="'searchbar'" 
                :showFilter="false"
                v-model="filters['global'].value"
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
                    <template v-for="zone in zones" :key="zone.id">
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
                    </template>
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
                </TabList>

                <TabPanels class="mt-2">
                    <TabPanel
                        :class="[
                            'rounded-xl bg-white p-3',
                            'focus:outline-none',
                        ]"
                    >
                        <ZoneAll :zones="filteredZones"/>
                    </TabPanel>
                    <template v-for="zone in zones" :key="zone.id">
                        <TabPanel
                            :class="[
                                'rounded-xl bg-white p-3',
                                'focus:outline-none',
                            ]"
                        >
                        <div v-if="zone.tables.length > 0">
                            <ZoneTabs :zones="zones" :activeTab="zone.value"/>
                        </div>
                        <div v-else>
                            <div class="flex flex-col items-center text-center ">
                                <EmptyTableIllus />
                                <span class="text-primary-900 text-sm font-medium">You haven’t added any table / room yet...</span>
                        </div>
                        </div>
                        </TabPanel>
                    </template>
                </TabPanels>
            </TabGroup>
            <!-- <TabView :tabs="tabs">
                <template v-for="(tab, index) in tabs" :key="index" #[`tab-${index}`]>
                    
                    <div v-if="tab === 'All'">
                        <ZoneAll :zones="zones" :tabs="tabs"/>
                    </div>
                    <div v-else>
                        Tier
                    </div>
                </template>
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
            </TabView> -->
                <!-- <Modal
                    :show="isModalOpen"
                    @close="closeModal"
                    :title="'Manage Zone'"
                    :maxWidth="'md'"
                >
                    <AddManageZone @close="closeModal" :zonesArr="zones"/>
                </Modal> -->
    </AuthenticatedLayout>
</template>
