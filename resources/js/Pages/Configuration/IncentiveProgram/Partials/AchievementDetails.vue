<script setup>
import { CalendarIcon, CommissionIcon, DeleteIcon, EditIcon, RecurringIcon, TargetIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';
import EditAchievement from './EditAchievement.vue';

const props = defineProps ({
    achievementDetails: {
        type: Object,
        required: true,
    },
    waiterName: {
        type: Array,
        required: true,
    }
})
const isDeleteWaiterOpen = ref(false);
const isEditAchievementOpen = ref(false);
const isDeleteAchievementOpen = ref(false);
const selectedAchievement = ref(null);
const selectedWaiter = ref(null);
const rows = ref([]);
const waiters = ref([]);
const isLoading = ref(false);

const formatNumbers = (number) => {
    number = number.substring(0, number.length - 3);
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

const formatRate = (rate) => {
    rate = rate * 100;
    return rate;
}

const formatDate = (date, locale = 'en-GB', options = {}) => {
    if (!(date instanceof Date)) {
        date = new Date(date);
    }

    const defaultOptions = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    };

    return date.toLocaleDateString(locale, { ...defaultOptions, ...options });
};

const getSuffix = (day) => {
    switch(day)
    {
        case 1:
        case 21:
        case 31:
            return day+"st";
        case 2:
        case 22:
            return day+"nd";
        case 3:
        case 23:
            return day+"rd";
        default:
            return day+"th";
    }
}

const showEditAchievement = (achievement) => {
    isEditAchievementOpen.value = true;
    selectedAchievement.value = achievement;
}

const showDeleteAchievement = (id) => {
    isDeleteAchievementOpen.value = true;
    selectedAchievement.value = id;
}

const showDeleteWaiter = (id) => {
    isDeleteWaiterOpen.value = true;
    selectedWaiter.value = id;
}

const closeModal = () => {
    isEditAchievementOpen.value = false;
    isDeleteWaiterOpen.value = false;
    isDeleteAchievementOpen.value = false;
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
</script>

<template>
    <div class="w-full flex flex-col p-6 items-center gap-6 rounded-[5px] border border-solid border-primary-100 min-w-[300px]">
        <div class="w-full flex flex-col items-end gap-6 flex-[1_0_0] self-stretch">
            <div class="w-full flex items-center justify-between gap-2.5 self-stretch">
                <span class="text-primary-900 text-md font-medium">Achievement Detail</span>
                <div class="flex flex-nowrap gap-2">
                    <EditIcon 
                        class="w-5 h-5 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="showEditAchievement(props.achievementDetails)"
                    />
                    <DeleteIcon 
                        class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                        @click="showDeleteAchievement(props.achievementDetails.id)"
                    />
                </div>
            </div>

            <div class="flex flex-col items-center gap-4 self-stretch">
                <div class="w-full flex p-3 items-center gap-4 rounded-[5px] border border-solid border-primary-100 bg-white">
                    <div class="bg-primary-50 size-6 justify-center items-center flex">
                        <CommissionIcon />
                    </div>
                    <template v-if="props.achievementDetails.type === 'fixed'">
                        <span class="flex-[1_0_0] text-grey-900 text-base font-medium">RM {{ props.achievementDetails.rate }} / monthly sales</span>
                    </template>

                    <template v-else> 
                        <span class="flex justify-center items-center gap-2.5 shrink-0 rounded-[2px] bg-primary-25">{{ formatRate(props.achievementDetails.rate) }}% / monthly sales</span>
                    </template>
                </div>

                <div class="w-full flex p-3 items-center gap-4 rounded-[5px] border border-solid border-primary-100 bg-white">
                    <div class="bg-primary-50 size-6 justify-center items-center flex">
                        <TargetIcon/>
                    </div>
                    <span class="flex-[1_0_0] text-grey-900 text-base font-medium">Sales hits 
                        <span class="text-primary-900 text-base font-medium">RM{{ formatNumbers(props.achievementDetails.monthly_sale) }}</span>
                    </span>
                </div>

                <div class="w-full flex p-3 items-center gap-4 rounded-[5px] border border-solid border-primary-100 bg-white">
                    <div class="bg-primary-50 size-6 justify-center items-center flex">
                        <CalendarIcon />
                    </div>
                    <span class="flex-[1_0_0] text-grey-900 text-base font-medium">Starting from
                        <span class="text-primary-900 text-base font-medium">{{ formatDate(props.achievementDetails.effective_date) }}</span>
                    </span>
                </div>

                <div class="w-full flex p-3 items-center gap-4 rounded-[5px] border border-solid border-primary-100 bg-white">
                    <div class="bg-primary-50 size-6 justify-center items-center flex">
                        <RecurringIcon />
                    </div>
                    <span class="flex-[1_0_0] text-grey-900 text-base font-medium">Recurring on
                        <span class="text-primary-900 text-base font-medium">{{ getSuffix(props.achievementDetails.recrurring_on) }} of every month</span>
                    </span>
                </div>


            </div>

            <div class="flex flex-col items-end gap-3 self-stretch">
                <div class="flex flex-col items-end gap-3 self-stretch">
                    <div class="flex px-3 py-2 justify-between items-center self-stretch rounded-[5px] bg-primary-50">
                        <div class="flex items-center gap-[10px] flex-[1_0_0]">
                            <span class="flex-[1_0_0] text-primary-900 text-sm font-semibold">Entitled Employees</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <template v-for="(waiters,id) in props.waiterName" :key="id">
                        <template v-for="(waiter, index) in waiters" :key="index">
                            <div class="flex px-3 py-2 justify-between items-center self-stretch rounded-[5px]">
                                <div class="flex items-center gap-2.5 flex-[1_0_0]">
                                    <div class="size-7 rounded-full bg-primary-700"></div>
                                    <span class="text-grey-900 text-sm font-medium">{{ waiter.name }}</span>
                                </div>
                                <DeleteIcon 
                                    class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                                    @click="showDeleteWaiter(waiter.id)"
                                />
                            </div>
                        </template>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <Modal
        :show="isEditAchievementOpen"
        :maxWidth="'md'"
        :closeable="true"
        :title="'Edit Achievement'"
        @close="closeModal"
    >
        <EditAchievement
            :selectedIncent="selectedAchievement"
            @closeModal="closeModal"
            @getEmployeeIncent="getEmployeeIncent"
            />
    </Modal>

    <Modal 
        :show="isDeleteAchievementOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/configurations/configurations/deleteAchievement/${selectedAchievement}`"
        :confirmationTitle="'Delete achievement?'"
        :confirmationMessage="'Are you sure you want to delete this achievement? All the data in this achievement will be lost.'"
        @close="closeModal"
        v-if="selectedAchievement"
    />

    <Modal 
        :show="isDeleteWaiterOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/configurations/configurations/deleteEntitled/${props.achievementDetails.id}/${selectedWaiter}`"
        :confirmationTitle="'Delete this entitled employee?'"
        :confirmationMessage="'Starting from next recurring date, the selected employee will no longer be entitled to this incentive commission.'"
        :toastMessage="'Selected employee has been removed from this achievement. '"
        @close="closeModal"
        v-if="selectedWaiter"
    />
</template>

