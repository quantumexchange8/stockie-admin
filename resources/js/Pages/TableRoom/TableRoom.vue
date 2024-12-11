<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { onMounted, ref, watch, computed } from 'vue'
import { Head } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Button from '@/Components/Button.vue';
import { GearIcon, PlusIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import ManageZone from './Partials/ManageZone.vue';
import AddTable from './Partials/AddTable.vue';
import ZoneAll from './Partials/ZoneAll.vue';
import axios from 'axios';
import ZoneTabs from './Partials/ZoneTabs.vue';
import { EmptyTableIllus } from '@/Components/Icons/illus.jsx';
import TabView from '@/Components/TabView.vue';
import { FilterMatchMode } from 'primevue/api';
import Toast from '@/Components/Toast.vue';
import { useCustomToast } from '@/Composables';

const home = ref({
    label: 'Table & Room',
});

const props = defineProps({
  zones: {
    type: Array,
    required: true
  },
});

const { flashMessage } = useCustomToast();

const zones = ref(props.zones);
const zonesManage = ref(props.zones);
const tables = ref([]);
const tabs = ref([]);
const isModalOpen = ref(false);
const createFormIsOpen = ref(false);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);
const isLoading = ref(false);

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

const getZoneDetails = async () => {
    try {
        const zoneResponse = await axios.get('/table-room/get-zonedetails');
        zones.value = zoneResponse.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => {
    getTableDetails();
    flashMessage();
});

const showCreateForm = () => {
    createFormIsOpen.value = true;
}

const openModal = () => {
    isModalOpen.value = true;
};

const closeModal = (status) => {
    switch(status){
        case 'close':{
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isModalOpen.value = false;
                createFormIsOpen.value = false;
            }
            break;
        }
        case 'stay':{
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave':{
            isUnsavedChangesOpen.value = false;
            createFormIsOpen.value = false;
            isModalOpen.value = false;
            isDirty.value = false;
            break;
        }
    }
};

// Transform the zones instance's zone text to be lower case and separated by hyphens (-) instead
const tranformedZones = computed(() => {
    if (zones.value) {
        return zones.value.map((zone) => {
            zone.name = zone.text.toLowerCase().replace(/ /g,"-");
            
            return zone;
        });
    }

    return [];
});

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

        <Toast />

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
                @close="closeModal('close')"
            >
                <AddTable
                    :zonesArr="zones"
                    @close="closeModal"
                    @isDirty="isDirty = $event"
                />
                <Modal
                    :unsaved="true"
                    :maxWidth="'2xs'"
                    :withHeader="false"
                    :show="isUnsavedChangesOpen"
                    @close="closeModal('stay')"
                    @leave="closeModal('leave')"
                />
            </Modal>
        </div>
        
        <TabView :tabs="tabs">
            <template #endheader>
                <Button
                    :type="'button'"
                    :size="'lg'"
                    :iconPosition="'left'"
                    @click="openModal"
                    variant="tertiary"
                    class="md:!w-fit !border-0 text-wrap"
                >
                    <template #icon>
                        <GearIcon
                            class="w-[20px] h-[20px]"
                        />
                    </template>
                    <span class="hidden sm:flex">Manage Zone</span>
                </Button>
                <Modal
                    :show="isModalOpen"
                    @close="closeModal('close')"
                    :title="'Manage Zone'"
                    :maxWidth="'md'"
                >
                    <ManageZone 
                        @getZoneDetails="getZoneDetails"
                        @close="closeModal('close')" 
                        @isDirty="isDirty=$event"
                        :zonesArr="zones"
                    />
                    <Modal
                        :unsaved="true"
                        :maxWidth="'2xs'"
                        :withHeader="false"
                        :show="isUnsavedChangesOpen"
                        @close="closeModal('stay')"
                        @leave="closeModal('leave')"
                    />
                </Modal>
            </template>
            <template #all>
                <ZoneAll :zones="filteredZones"/>
            </template>
            <template 
                v-for="zone in tranformedZones" 
                :key="zone.id" 
                v-slot:[`${zone.name}`]
            >
                <ZoneTabs 
                    :zones="filteredZones" 
                    :activeTab="zone.value" 
                    v-if="zone.tables.length > 0"
                />
                <div class="flex flex-col items-center text-center" v-else>
                    <EmptyTableIllus />
                    <span class="text-primary-900 text-sm font-medium">
                        You haven't added any table/room yet...
                    </span>
                </div>
            </template>
        </TabView>
    </AuthenticatedLayout>
</template>
