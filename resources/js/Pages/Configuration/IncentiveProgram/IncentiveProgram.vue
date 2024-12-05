<script setup>
import Button from "@/Components/Button.vue";
import { EmployeeIncentProgIllust } from "@/Components/Icons/illus";
import { DeleteIcon, EditIcon, PlusIcon } from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";
import SearchBar from "@/Components/SearchBar.vue";
import Table from "@/Components/Table.vue";
import Toast from "@/Components/Toast.vue";
import { FilterMatchMode } from "primevue/api";
import { computed, onMounted, ref } from "vue";
import AddAchievement from "./Partials/AddAchievement.vue";
import EditAchievement from "./Partials/EditAchievement.vue";
import { transactionFormat } from "@/Composables";

const isLoading = ref(false);
const rows = ref([]);
const isEditIncentOpen = ref(false);
const isDeleteIncentOpen = ref(false);
const isAddAchievementOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const selectedIncent = ref(null);
const waiters = ref([]);
const { formatDate, formatAmount } = transactionFormat();

const actions = {
    view: (incentive) => `/configurations/configurations/incentCommDetail/${incentive}`,
    edit: () => ``,
    delete: () => ``,
};

const formatType = (type) => {
    if (type == 'fixed'){
        return 'Fixed Amount';
    } else {
        return 'Percentage of monthly sales';
    }
}

const openEditIncent = (event, rows) => {
    handleDefaultClick(event);
    isDirty.value = false;
    isEditIncentOpen.value = true;
    selectedIncent.value = rows;
}

const openDeleteIncent = (event, id) => {
    handleDefaultClick(event);
    isDeleteIncentOpen.value = true;
    selectedIncent.value = id;
}

const openAddAchieve = () => {
    isDirty.value = false;
    isAddAchievementOpen.value = true;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isAddAchievementOpen.value = false;
                isEditIncentOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave' :{
            isUnsavedChangesOpen.value = false;
            isAddAchievementOpen.value = false;
            isEditIncentOpen.value = false;
        }
    }
} 

const closeDeleteAchivementModal = () => {
    isDeleteIncentOpen.value = false;
}

const closeEditAchievementModal = () => {
    isUnsavedChangesOpen.value = isDirty.value ? true : false;
    isEditIncentOpen.value = !isDirty.value ? false : true;
}

const stayModal = () => {
    isUnsavedChangesOpen.value = false;
}

const leaveModal = () => {
    isUnsavedChangesOpen.value = false;
    isAddAchievementOpen.value = false;
    isEditIncentOpen.value = false;
}

const getEmployeeIncent = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/configurations/configurations/incentive');
        waiters.value = response.data.waiters;
        rows.value = response.data.incentiveProg.map(incentive => {
            return {
                ...incentive, 
                isRate: incentive.type !== 'fixed',
                hiddenEntitled: incentive.entitled.length > 4 ? incentive.entitled.length - 4 : null,
                entitled: incentive.entitled.slice(0, 4),
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

onMounted (() => {
    getEmployeeIncent();
})
</script>

<template>
        <div class="w-full flex flex-col p-6 items-start self-stretch gap-6 border border-primary-100 rounded-[5px]">
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
                minWidth="min-w-[963px]"
            >
                <template #empty>
                    <div class="flex w-full flex-col items-center justify-center gap-5 h-fit">
                        <EmployeeIncentProgIllust />
                        <span class="text-primary-900 text-sm font-medium">You haven’t added any incentive commission yet...</span>
                    </div>
                </template>
                <template #monthly_sale="rows">
                    <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">RM {{ formatAmount(rows.monthly_sale) }}</span>
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
                    <div class="flex items-center gap-[13px] overflow-hidden">
                        <div class="flex items-start gap-1">
                            <template v-for="image in rows.entitled">
                                <img 
                                    :src="image.image ? image.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="size-6 rounded-full"
                                />
                            </template>
                        </div>
                        <div v-if="rows.hiddenEntitled !== null" class="flex items-start pr-1">
                            <span class="text-grey-900 text-sm font-medium">+{{ rows.hiddenEntitled }}</span>
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
        @close="closeModal('close')"
    >
        <AddAchievement 
            :waiters="waiters"
            @closeModal="closeModal"
            @isDirty="isDirty = $event"
            @getEmployeeIncent="getEmployeeIncent"
        />
    </Modal>

    <Modal
        :show="isEditIncentOpen"
        :maxWidth="'md'"
        :closeable="true"
        :title="'Edit Achievement'"
        @close="closeModal('close')"
    >
        <EditAchievement 
            :selectedIncent="selectedIncent"
            @isDirty="isDirty = $event"
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
        :toastMessage="'Achievement has been deleted.'"
        @close="closeDeleteAchivementModal"
        v-if="selectedIncent"
    />

    <Modal
        :unsaved="true"
        :maxWidth="'2xs'"
        :withHeader="false"
        :show="isUnsavedChangesOpen"
        @close="stayModal"
        @leave="leaveModal"
    >
    </Modal>
</template>
