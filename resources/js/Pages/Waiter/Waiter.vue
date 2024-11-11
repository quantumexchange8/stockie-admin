<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Button from "@/Components/Button.vue";
import SearchBar from "@/Components/SearchBar.vue";
import SalesPerformance from "./Partials/SalesPerformance.vue";
import AddWaiter from "./Partials/AddWaiter.vue";
import { PlusIcon } from "@/Components/Icons/solid";
import { computed, onMounted, ref } from "vue";
import Modal from "@/Components/Modal.vue";
import Breadcrumb from '@/Components/Breadcrumb.vue';
import WaiterTable from "./Partials/WaiterTable.vue";
import CommissionEarned from "./Partials/CommissionEarned.vue";
import Toast from "@/Components/Toast.vue";
import { useCustomToast } from "@/Composables";
import { FilterMatchMode } from "primevue/api";
import axios from "axios";
import { Head } from "@inertiajs/vue3";

const home = ref({
    label: 'Waiter',
});

const props = defineProps({
    waiters: {
        type: Object,
        required: true
    },
    waiterIds: {
        type: Array,
        default: () => {},
    },
    waiterSales: {
        type: Array,
        default: () => {},
    },
    image: {
        type: Array,
        default: () => {},
    },
    waiterNames: {
        type: Array,
        default: () => {},
    },
    waiterCommission: {
        type: Array,
        default: () => {},
    }

})

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const waiterIds = ref(props.waiterIds);
const waiterSales = ref(props.waiterSales);
const waiterImages = ref(props.image);
const waiterNames = ref(props.waiterNames);
const waiterCommission = ref(props.waiterCommission);
const selected = ref('This month');
const selectedFilter = ref('This month');
const waitersRowsPerPage = ref(6);

const waitersTotalPages = computed(() => {
    return Math.ceil(props.waiters.length / waitersRowsPerPage.value);
})

const { flashMessage } = useCustomToast();

const waiterColumns = ref([
    {field: 'role_id', header: 'ID', width: '11.5', sortable: true},
    {field: 'full_name', header: 'Waiter', width: '21.5', sortable: true},
    {field: 'phone', header: 'Phone', width: '20', sortable: true},
    {field: 'worker_email', header: 'Email', width: '35', sortable: true},
    {field: 'action', header: '', width: '20', sortable: false},
]);

const actions = {
    view: (waiterId) => `/waiter/waiterDetails/${waiterId}`,
    edit: () => ``,
    delete: () => ``,
};

const isModalOpen = ref(false);

const openModal = () => {
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const filterSalesPerformance = async (filters) => {
    // console.log(filters);
    try {
        const response = await axios.get('/waiter/filterSalesPerformance', {
            method: 'GET',
            params: {
                selected: filters,
            }
        });
        waiterIds.value = response.data.waiterIds;
        waiterSales.value = response.data.waiterSales;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

const filterCommEarned = async (selectedFilter) => {
    try {
        const response = await axios.get('/waiter/filterCommEarned', {
            method: 'GET',
            params: {
                selectedFilter: selectedFilter,
            }
        });
        waiterNames.value = response.data.waiterNames;
        waiterCommission.value = response.data.waiterCommission;

    } catch (error) {
        console.error(error);
    } finally {

    }
}

const applyFilter = (filter) => {
    selected.value = filter;
    filterSalesPerformance(selected.value);
}

const applyCommFilter = (filter) => {
    selectedFilter.value = filter;
    filterCommEarned(selectedFilter.value);
}

onMounted(() => {
    flashMessage();
});

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});
</script>

<template>
    <Head title="Waiter" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
            />
        </template>

        <Toast />

        <div class="w-full py-6">
            <div class="w-full flex flex-col gap-5 justify-center items-center">
                <div class="w-full flex gap-[21px] flex-col md:flex-row justify-center">
                    <div
                        class="w-full p-6 bg-white sm:rounded-lg border border-solid border-primary-100 rounded-[5px] min-h-[360px]"
                    >
                        <SalesPerformance 
                            :waiterName="waiterIds"
                            :waiterSales="waiterSales"
                            :waiterImages="waiterImages"
                            @applyFilter="applyFilter"
                        />
                    </div>
                    <div
                        class="w-full p-6  bg-white sm:rounded-lg border border-solid border-primary-100 min-h-[360px]"
                    >
                        <CommissionEarned 
                            :waiterNames="waiterNames"
                            :waiterCommission="waiterCommission"
                            @applyCommFilter="applyCommFilter"  
                        />
                    </div>
                </div>

                <div
                    class="w-full flex flex-col gap-4 p-4 sm:p-6 bg-white sm:rounded-lg border-[#ffe1e2] border-solid border-[1px]"
                >
                    <div class="w-full flex items-center gap-5">
                        <SearchBar
                            placeholder="Search"
                            :show-filter="false"
                            v-model="filters['global'].value"
                        >
                        </SearchBar>

                        <Button
                            :type="'button'"
                            :size="'lg'"
                            :iconPosition="'left'"
                            class="md:w-[166px] flex items-center gap-2"
                            @click="openModal"
                            ><template #icon>
                                <PlusIcon />
                            </template>

                            New Waiter
                            <Modal
                                :show="isModalOpen"
                                @close="closeModal"
                                :title="'Add New Waiter'"
                                :maxWidth="'lg'"
                            >
                                <AddWaiter @close="closeModal" :waiters="waiters" />
                            </Modal>
                        </Button>
                    </div>

                    <div>
                        <WaiterTable 
                            :rows="waiters"
                            :columns="waiterColumns"
                            :actions="actions"
                            :rowType="rowType"
                            :totalPages="waitersTotalPages"
                            :rowsPerPage="waitersRowsPerPage"
                            :searchFilter="true"
                            :filters="filters"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
