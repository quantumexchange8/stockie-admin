<script setup>
import Button from "@/Components/Button.vue";
import { EmployeeIncentProgIllust } from "@/Components/Icons/illus";
import { DeleteIcon, EditIcon, PlusIcon } from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";
import SearchBar from "@/Components/SearchBar.vue";
import Table from "@/Components/Table.vue";
import Toast from "@/Components/Toast.vue";
import { Head } from "@inertiajs/vue3";
import { FilterMatchMode } from "primevue/api";
import { onMounted, ref } from "vue";
import AddAchievement from "./Partials/AddAchievement.vue";
import EditAchievement from "./Partials/EditAchievement.vue";

const isLoading = ref(false);
const rows = ref([]);
const isEditIncentOpen = ref(false);
const isDeleteIncentOpen = ref(false);
const isAddAchievementOpen = ref(false);
const selectedIncent = ref(null);
const waiters = ref([]);

const actions = {
    // view: (incentive) => `/configurations/configurations/incentCommDetail/${incentive}`,
    edit: () => ``,
    delete: () => ``,
};

const formatNumbers = (number) => {
    number = number.substring(0, number.length - 3);
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

const formatType = (type) => {
    if (type == 'fixed'){
        return 'Fixed Amount';
    } else {
        return 'Percentage of monthly sales';
    }
}

const getInitials = (fullName) => {
    const allNames = fullName.trim().split(' ');
    const initials = allNames.reduce((acc, curr, index) => {
        if(index === 0 || index === allNames.length - 1){
        acc = `${acc}${curr.charAt(0).toUpperCase()}`;
        }
        return acc;
    }, '');
    return initials;
}

const formatDate = (date) => {
    return date.slice(0,-9);
}

const openEditIncent = (event, rows) => {
    handleDefaultClick(event);
    isEditIncentOpen.value = true;
    selectedIncent.value = rows;
}

const openDeleteIncent = (event, id) => {
    handleDefaultClick(event);
    isDeleteIncentOpen.value = true;
    selectedIncent.value = id;
}

const openAddAchieve = () => {
    isAddAchievementOpen.value = true;
}

const closeModal = () => {
    isAddAchievementOpen.value = false;
    isDeleteIncentOpen.value = false;
    isEditIncentOpen.value = false;
}

const getEmployeeIncent = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/configurations/configurations/incentive');
        rows.value = response.data.incentiveProg;
        waiters.value = response.data.waiters;
        rows.value = response.data.incentiveProg.map(incentive => {
            return {
                ...incentive, 
                isRate: incentive.type !== 'fixed' 
            };
        });
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const incentProgColumn = ref([
    {field: 'monthly_sale', header: 'Monthly Sales Hits', width: '20', sortable: true},
    {field: 'type', header: 'Commission Type', width: '20', sortable: true},
    {field: 'rate', header: 'Rate', width: '10', sortable: true},
    {field: 'effective_date', header: 'Effective Date', width: '20', sortable: true},
    {field: 'name', header: 'Entitled Employee', width: '20', sortable: true},
    {field: 'action', header: '', width: '10', sortable: false},
])



const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const handleDefaultClick = (event) => {
    event.stopPropagation();
    event.preventDefault();
}

const isOverflown = ({ clientWidth, clientHeight, scrollWidth, scrollHeight }) => {
    return scrollHeight > clientHeight || scrollWidth > clientWidth;
}

onMounted (() => {
    getEmployeeIncent();
})
</script>

<template>
    <Head title="Configuration" />
        <div class="flex flex-col p-6 items-start self-stretch gap-6 border border-primary-100 rounded-[5px]">
            <div class="flex flex-col justify-center gap-[10px]">
                <span class="text-md font-medium text-primary-900">
                    Employee Incentive Programme
                </span>
            </div>

            <div class="flex items-start gap-5 self-stretch grid-cols-12">
                <SearchBar 
                    placeholder="Search"
                    :showFilter="false"
                    v-model="filters['global'].value"
                />

                <Button
                    :type="'button'"
                    :size="'lg'"
                    :iconPosition="'left'"
                    class="!w-fit flex items-center gap-2"
                    @click="openAddAchieve"
                >
                    <template #icon>
                        <PlusIcon />
                    </template>
                    Incentive Commission
                </Button>
            </div>

            <Toast 
                inline
                severity="info"
                actionLabel="OK"
                summary="Set different commission for different level of achievement"
                detail="Employee can earn certain amount of commission for accomplishing a specific achievement/sales target."
                :closable="false"
            />

            <Table
                :columns="incentProgColumn"
                :rows="rows"
                :actions="actions"
                :variant="'list'"
                :searchFilter="true"
                :filters="filters"
            >
                <template #empty>
                    <div class="flex w-full flex-col items-center justify-center gap-5 h-fit">
                        <EmployeeIncentProgIllust />
                        <span class="text-primary-900 text-sm font-medium">You haven’t added any incentive commission yet...</span>
                    </div>
                </template>
                <template #monthly_sale="rows">
                    <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">RM {{ formatNumbers(rows.monthly_sale) }}</span>
                </template>
                <template #type="rows">
                    <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">{{ formatType(rows.type) }}</span>
                </template>
                <template #rate="rows">
                    <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">
                        <template v-if="rows.type == 'fixed'">
                            RM {{ rows.rate }}
                        </template>
                        <template v-else>
                            {{ rows.rate * 100 }} %
                        </template>
                    </span>
                </template>
                <template #effective_date="rows">
                    <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">{{ formatDate(rows.effective_date) }}</span>
                </template>
                <template #name="rows">
                    <div class="flex items-start pr-1" :class="'overflow-hidden'">
                        <div v-for="(names, index) in rows.entitled" :key="index" class="flex items-start pr-1">
                            <span class="size-6 rounded-full bg-primary-900 text-white text-center self-stretch">{{ getInitials(names.name) }}</span>
                        </div>
                    </div>
                </template>
                <template #editAction="rows">
                    <EditIcon
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="openEditIncent($event, rows)"
                    />
                </template>
                <template #deleteAction="rows">
                    <DeleteIcon
                        class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                        @click="openDeleteIncent($event, rows.id)"
                    />
                </template>
            </Table>
        </div>
    
    <Modal
        :show="isAddAchievementOpen"
        :maxWidth="'md'"
        :closeable="true"
        :title="'Add Achievement'"
        @close="closeModal"
    >
        <AddAchievement 
            :waiters="waiters"
            @closeModal="closeModal"
            @getEmployeeIncent="getEmployeeIncent"
        />
    </Modal>

    <Modal
        :show="isEditIncentOpen"
        :maxWidth="'md'"
        :closeable="true"
        :title="'Edit Achievement'"
        @close="closeModal"
    >
        <EditAchievement 
            :selectedIncent="selectedIncent"
            @closeModal="closeModal"
            @getEmployeeIncent="getEmployeeIncent"
        />

    </Modal>

    <Modal 
        :show="isDeleteIncentOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/configurations/configurations/deleteAchievement/${selectedIncent}`"
        :confirmationTitle="'Delete achievement?'"
        :confirmationMessage="'Are you sure you want to delete this achievement? All the data in this achievement will be lost.'"
        @close="closeModal"
        v-if="selectedIncent"
    />

</template>
