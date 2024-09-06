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

const home = ref({
    label: 'Waiter',
});

const props = defineProps({
    waiters: {
        type: Object,
        required: true
    },

})

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const waitersRowsPerPage = ref(6);

const waitersTotalPages = computed(() => {
    return Math.ceil(props.waiters.length / waitersRowsPerPage.value);
})

const { flashMessage } = useCustomToast();

const waiterColumns = ref([
    {field: 'staffid', header: 'ID', width: '11.5', sortable: true},
    {field: 'name', header: 'Waiter', width: '21.5', sortable: true},
    {field: 'phone', header: 'Phone', width: '20', sortable: true},
    {field: 'stockie_email', header: 'Email', width: '35', sortable: true},
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

onMounted(() => {
    flashMessage();
});

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});
</script>

<template>
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
                        class="w-full p-4 sm:p-8 bg-white sm:rounded-lg border border-solid border-primary-100 min-w-[360px]"
                    >
                        <SalesPerformance class="w-full" />
                    </div>
                    <div
                        class="w-full p-4 sm:p-8 bg-white sm:rounded-lg border border-solid border-primary-100 min-w-[360px]"
                    >
                        <CommissionEarned />
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
