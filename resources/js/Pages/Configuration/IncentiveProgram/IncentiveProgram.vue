<script setup>
import Button from "@/Components/Button.vue";
import { EmployeeIncentProgIllust } from "@/Components/Icons/illus";
import { DeleteIcon, EditIcon, PlusIcon } from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";
import SearchBar from "@/Components/SearchBar.vue";
import Table from "@/Components/Table.vue";
import Toast from "@/Components/Toast.vue";
import { FilterMatchMode } from "primevue/api";
import { computed, onMounted, ref, watch } from "vue";
import AddAchievement from "./Partials/AddAchievement.vue";
import EditAchievement from "./Partials/EditAchievement.vue";
import { transactionFormat } from "@/Composables";
import { useForm } from "@inertiajs/vue3";
import Dropdown from "@/Components/Dropdown.vue";
import { useCustomToast } from '@/Composables/index.js';

const isLoading = ref(false);
const isEditIncentOpen = ref(false);
const isDeleteIncentOpen = ref(false);
const isAddAchievementOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const selectedIncent = ref(null);
const rows = ref([]);
const waiters = ref([]);
const filteredRows = ref([]);
const searchQuery = ref('');
const incentiveRecurringDay = ref(0);
const { formatDate, formatAmount } = transactionFormat();
const { showMessage } = useCustomToast();

const form = useForm({
    recurring_on: incentiveRecurringDay.value,
    // recurring_on: recurringDates.value.find(item => item.value === selectedIncent.value.recurring_on) || recurringDates.value[0],
});

const actions = {
    view: (incentive) => `/configurations/configurations/incentCommDetail/${incentive}`,
    edit: () => ``,
    delete: () => ``,
};

const recurringDates = ref([...Array(7)].map((_, i) => {
    const day = i + 1;
    let suffix;

    if (day % 10 === 1 && day % 100 !== 11) {
        suffix = 'st';
    } else if (day % 10 === 2 && day % 100 !== 12) {
        suffix = 'nd';
    } else if (day % 10 === 3 && day % 100 !== 13) {
        suffix = 'rd';
    } else {
        suffix = 'th';
    }

    return {
        text: `${day}${suffix}`,
        value: day
    };
}));

const submit = async () => {
    form.processing = true;
    try {
        const response = await axios.post('/configurations/configurations/updateIncentiveRecurringDay', form);

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Changes saved.',
            });
        }, 200);

        incentiveRecurringDay.value = response.data;
        form.reset();
    } catch (error) {
        console.error(error);
    } finally {
        form.processing = false;
    }
}

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

const getEmployeeIncent = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/configurations/configurations/incentive');
        waiters.value = response.data.waiters;
        incentiveRecurringDay.value = response.data.incentiveRecurringDay;
        rows.value = response.data.incentiveProg.map(incentive => {
            return {
                ...incentive, 
                isRate: incentive.type !== 'fixed',
                hiddenEntitled: incentive.entitled.length > 4 ? incentive.entitled.length - 4 : null,
                entitled: incentive.entitled.slice(0, 4),
            };
        });
        filteredRows.value = rows.value;
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
    {field: 'entitled', header: 'Entitled Employee', width: '20', sortable: true},
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

watch(incentiveRecurringDay, (newValue) => (form.recurring_on = newValue));

watch(() => searchQuery.value, (newValue) => {
    if(newValue === '') {
        filteredRows.value = rows.value;
        return;
    }

    const query = newValue.toLowerCase();

    filteredRows.value = rows.value.filter(row => {
        const monthlySale = row.monthly_sale.toString().toLowerCase();
        const rowType = row.type === 'fixed' ? 'fixed amount'.toLowerCase() : 'percentage of monthly sales'.toLowerCase();
        const incentiveRate = row.rate.toString().toLowerCase();
        const effectiveDate = row.effective_date.toString().toLowerCase();
        const entitled = Array.isArray(row.entitled) ? row.entitled.some(entitled => entitled.name.toLowerCase().includes(query)) : false;
        const hidden = Array.isArray(row.hiddenEntitled) ? row.hiddenEntitled.some(entitled => entitled.name.toLowerCase().includes(query)) : false;

        return  monthlySale.includes(query) ||
                rowType.includes(query) ||
                incentiveRate.includes(query) ||
                effectiveDate.includes(query) ||
                entitled ||
                hidden;
    })
}, { immediate: true })
</script>

<template>
    <div class="w-full flex flex-col gap-y-5">
        <form class="w-full flex flex-col p-6 items-start self-stretch gap-6 border border-primary-100 rounded-[5px]" @submit.prevent="submit">
            <div class="w-full flex items-center justify-between gap-[10px]">
                <span class="text-md font-medium text-primary-900">
                    Recurring Settings
                </span>
                <Button
                    :size="'lg'"
                    class="!w-fit flex items-center gap-2"
                    :disabled="form.recurring_on === incentiveRecurringDay"
                >
                    Save Changes
                </Button>
            </div>
            
            <div class="flex items-center gap-x-4">
                <p class="text-base text-grey-950 font-medium whitespace-nowrap">Incentive will recur on</p>
                <Dropdown
                    :inputName="'recurring_on'"
                    :inputArray="recurringDates"
                    :dataValue="form.recurring_on"
                    :loading="!form.recurring_on"
                    v-model="form.recurring_on"
                />
                <p class="text-base text-grey-950 font-medium whitespace-nowrap">of every month</p>
            </div>
        </form>

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
                    v-model="searchQuery"
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

            <div class="w-full">
                <Toast 
                    inline
                    severity="info"
                    actionLabel="OK"
                    summary="Set different commission for different level of achievement"
                    detail="Employee can earn certain amount of commission for accomplishing a specific achievement/sales target."
                    :closable="false"
                />
            </div>

            <Table
                :columns="incentProgColumn"
                :rows="filteredRows"
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
                <template #monthly_sale="filteredRows">
                    <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">RM {{ formatAmount(filteredRows.monthly_sale) }}</span>
                </template>
                <template #type="filteredRows">
                    <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">{{ formatType(filteredRows.type) }}</span>
                </template>
                <template #rate="filteredRows">
                    <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">
                        <template v-if="filteredRows.type == 'fixed'">
                            RM {{ filteredRows.rate }}
                        </template>
                        <template v-else>
                            {{ filteredRows.rate * 100 }} %
                        </template>
                    </span>
                </template>
                <template #effective_date="filteredRows">
                    <span class="line-clamp-1 flex-[1_0_0] text-ellipsis text-sm font-medium">{{ formatDate(filteredRows.effective_date) }}</span>
                </template>
                <template #entitled="filteredRows">
                    <div class="flex items-center gap-[13px] overflow-hidden">
                        <div class="flex items-start gap-1">
                            <template v-for="image in filteredRows.entitled">
                                <img 
                                    :src="image.image ? image.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="size-6 rounded-full"
                                />
                            </template>
                        </div>
                        <div v-if="filteredRows.hiddenEntitled !== null" class="flex items-start pr-1">
                            <span class="text-grey-900 text-sm font-medium">+{{ filteredRows.hiddenEntitled }}</span>
                        </div>
                    </div>
                </template>
                <template #editAction="filteredRows">
                    <EditIcon
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="openEditIncent($event, filteredRows)"
                    />
                </template>
                <template #deleteAction="filteredRows">
                    <DeleteIcon
                        class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                        @click="openDeleteIncent($event, filteredRows.id)"
                    />
                </template>
            </Table>
        </div>
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
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        >
        </Modal>
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
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        >
        </Modal>

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
</template>
