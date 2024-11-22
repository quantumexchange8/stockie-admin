<script setup>
import { CalendarIcon, CommissionIcon, DeleteIcon, EditIcon, RecurringIcon, TargetIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import { onMounted, ref } from 'vue';
import EditAchievement from './EditAchievement.vue';
import { transactionFormat } from '@/Composables';
import dayjs from 'dayjs';

const props = defineProps ({
    achievementDetails: {
        type: Object,
        required: true,
    },
    waiterName: {
        type: Object,
        required: true,
    }
})
const { formatDate, formatAmount } = transactionFormat();
const emit = defineEmits(['getEmployeeIncent', 'getIncentDetail']);

const isDeleteWaiterOpen = ref(false);
const isEditAchievementOpen = ref(false);
const isDeleteAchievementOpen = ref(false);
const selectedAchievement = ref(null);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const selectedWaiter = ref(null);
const isRate = ref({
    ...props.achievementDetails,
    isRate: props.achievementDetails.type !== 'fixed'
});

const getSuffix = (day) => {
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

    return day+suffix;
}

const getEmployeeIncent = () => {
    emit('getEmployeeIncent', props.achievementDetails.id);
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

const closeEditModal = () => {
    isUnsavedChangesOpen.value = isDirty.value ? true : false;
    isEditAchievementOpen.value = !isDirty.value ? false : true;
}

const closeDeleteWaiter = () => {
    isDeleteWaiterOpen.value = false;
}

const closeDeleteAchievement = () => {
    isDeleteAchievementOpen.value = false;
}

const stayModal = () => {
    isUnsavedChangesOpen.value = false;
}

const leaveModal = () => {
    isUnsavedChangesOpen.value = false;
    isEditAchievementOpen.value = false;
}

</script>

<template>
    <div class="w-full h-full flex flex-col p-6 items-center gap-6 rounded-[5px] border border-solid border-primary-100 min-w-[300px]">
        <div class="w-full flex flex-col items-end gap-6 flex-[1_0_0] self-stretch">
            <div class="w-full flex items-center justify-between gap-2.5 self-stretch">
                <span class="text-primary-900 text-md font-medium">Achievement Detail</span>
                <div class="flex flex-nowrap gap-2">
                    <EditIcon 
                        class="w-5 h-5 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="showEditAchievement(isRate)"
                    />
                    <DeleteIcon 
                        class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                        @click="showDeleteAchievement(isRate.id)"
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
                        <span class="flex justify-center items-center gap-2.5 shrink-0 rounded-[2px] bg-primary-25">{{ parseInt(props.achievementDetails.rate * 100) }}% / monthly sales</span>
                    </template>
                </div>

                <div class="w-full flex p-3 items-center gap-4 rounded-[5px] border border-solid border-primary-100 bg-white">
                    <div class="bg-primary-50 size-6 justify-center items-center flex">
                        <TargetIcon/>
                    </div>
                    <span class="flex-[1_0_0] text-grey-900 text-base font-medium">Sales hits 
                        <span class="text-primary-900 text-base font-medium">RM{{ formatAmount(props.achievementDetails.monthly_sale) }}</span>
                    </span>
                </div>

                <div class="w-full flex p-3 items-center gap-4 rounded-[5px] border border-solid border-primary-100 bg-white">
                    <div class="bg-primary-50 size-6 justify-center items-center flex">
                        <CalendarIcon />
                    </div>
                    <span class="flex-[1_0_0] text-grey-900 text-base font-medium">Starting from
                        <span class="text-primary-900 text-base font-medium">{{ dayjs(props.achievementDetails.effective_date).format('DD/MM/YYYY') }}</span>
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
                    <template v-for="(waiter, index) in Object.values(props.waiterName).sort((a, b) => a.name.localeCompare(b.name))" :key="index">
                        <div class="flex px-3 py-2 justify-between items-center self-stretch rounded-[5px]"
                            :class="(index + 1) % 2 === 0 ? 'bg-primary-25' : 'bg-white'"
                        >
                            <div class="flex items-center gap-2.5 flex-[1_0_0]">
                                <!-- <div class="size-7 rounded-full bg-primary-700"></div> -->
                                <img 
                                    :src="waiter.image ? waiter.image 
                                                    : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="size-7 rounded-full"
                                >
                                <span class="text-grey-900 text-sm font-medium">{{ waiter.name }}</span>
                            </div>
                            <DeleteIcon 
                                class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                                @click="showDeleteWaiter(waiter.id)"
                            />
                        </div>
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
        @close="closeEditModal($event)"
    >
        <EditAchievement
            :selectedIncent="selectedAchievement"
            @stay="stayModal"
            @leave="leaveModal"
            @isDirty="isDirty = $event"
            @closeModal="closeEditModal($event)"
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
        @close="closeDeleteAchievement"
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
        @close="closeDeleteWaiter"
        v-if="selectedWaiter"
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

