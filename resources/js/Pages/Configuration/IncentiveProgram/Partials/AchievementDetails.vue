<script setup>
import { CalendarIcon, CommissionIcon, DeleteIcon, EditIcon, PlusIcon, RecurringIcon, TargetIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import { onMounted, ref, watch } from 'vue';
import EditAchievement from './EditAchievement.vue';
import { transactionFormat, useCustomToast } from '@/Composables';
import dayjs from 'dayjs';
import Button from '@/Components/Button.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import { useForm } from '@inertiajs/vue3';
import AddEntitledEmployees from './AddEntitledEmployees.vue';
import { DeleteIllus } from '@/Components/Icons/illus';

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
const { formatAmount } = transactionFormat();
const { showMessage } = useCustomToast();

const emit = defineEmits(['getEmployeeIncent', 'getIncentDetail']);

const isDeleteWaiterOpen = ref(false);
const isEditAchievementOpen = ref(false);
const isDeleteAchievementOpen = ref(false);
const isAddEntitledEmployeesOpen = ref(false);
// const selectedAchievement = ref(null);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
// const selectedWaiter = ref(null);
const waiters = ref(props.waiterName);
const achievement = ref({
    ...props.achievementDetails,
    isRate: props.achievementDetails.type !== 'fixed'
});

const form = useForm({
    entitled_employee_id: null
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

const openModal = (action, waiter = null) => {
    switch(action) {
        case 'edit-achievement': 
            isDirty.value = false;
            isEditAchievementOpen.value = true;
            break;

        case 'add-entitled-employees': 
            isDirty.value = false;
            isAddEntitledEmployeesOpen.value = true;
            break;

        case 'delete-achievement': 
            isDeleteAchievementOpen.value = true;
            break;

        case 'delete-waiter' : 
            isDeleteWaiterOpen.value = true;
            form.entitled_employee_id = waiter.id;
            break;
    }
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isEditAchievementOpen.value = false;
                isDeleteWaiterOpen.value = false
                isDeleteAchievementOpen.value = false;
                isAddEntitledEmployeesOpen.value = false;
            }
            break;
        };
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        };
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isEditAchievementOpen.value = false;
            isDeleteWaiterOpen.value = false
            isDeleteAchievementOpen.value = false;
            isAddEntitledEmployeesOpen.value = false;
            break;
        }
    }
}

const submit = async () => {
    form.processing = true;
    try {
        const response = await axios.put(`/configurations/configurations/deleteEntitled/${props.achievementDetails.id}`, form);
        waiters.value = response.data;

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Selected employee has been removed from this achievement.',
            });
        }, 200);
        
        closeModal('leave');
        form.reset();
    } catch (error) {
        form.setError(error.response?.data?.errors);
        console.log(error);
    } finally {
        form.processing = false;
    }
}


watch(() => props.achievementDetails, (newValue) => {
    achievement.value = {
        ...newValue,
        isRate: newValue.type !== 'fixed'
    };
})

watch(() => form.isDirty, (newValue) => isDirty.value = newValue)

</script>

<template>
    <div class="w-full h-full flex flex-col p-6 items-center gap-6 rounded-[5px] border border-solid border-primary-100 min-w-[300px]">
        <div class="w-full flex flex-col items-end gap-6 flex-[1_0_0] self-stretch">
            <div class="w-full flex items-center justify-between gap-2.5 self-stretch">
                <span class="text-primary-900 text-md font-medium">Achievement Detail</span>
                <div class="flex flex-nowrap gap-2">
                    <EditIcon 
                        class="w-5 h-5 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click="openModal('edit-achievement')"
                    />
                    <DeleteIcon 
                        class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                        @click="openModal('delete-achievement')"
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
                        <span class="text-primary-900 text-base font-medium">{{ getSuffix(props.achievementDetails.recurring_on) }} of every month</span>
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
                    <template v-for="(waiter, index) in Object.values(waiters).sort((a, b) => a.name.localeCompare(b.name))" :key="index">
                        <div
                            :class="[
                                'flex px-3 py-2 justify-between items-center self-stretch rounded-[5px]',
                                (index + 1) % 2 === 0 ? 'bg-primary-25' : 'bg-white'
                            ]"
                        >
                            <div class="flex items-center gap-2.5 flex-[1_0_0]">
                                <img 
                                    :src="waiter.image ? waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                    alt="WaiterProfileImage"
                                    class="size-7 rounded-full"
                                >
                                <span class="text-grey-900 text-sm font-medium">{{ waiter.name }}</span>
                            </div>
                            <DeleteIcon 
                                class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                                @click="openModal('delete-waiter', waiter)"
                            />
                        </div>
                    </template>
                </div>
            </div>

            <Button
                :type="'button'"
                :size="'lg'"
                :variant="'tertiary'"
                :iconPosition="'left'"
                class="flex items-center gap-2"
                @click="openModal('add-entitled-employees')"
            >
                <template #icon>
                    <PlusIcon />
                </template>
                Add Entitled Employees
            </Button>
        </div>
    </div>

    <Modal
        :show="isEditAchievementOpen"
        :maxWidth="'md'"
        :closeable="true"
        :title="'Edit Achievement'"
        @close="closeModal('close')"
    >
        <EditAchievement
            :selectedIncent="achievement"
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
        />
    </Modal>

    <Modal
        :show="isAddEntitledEmployeesOpen"
        :maxWidth="'md'"
        :closeable="true"
        :title="'Add Entitled Employees'"
        @close="closeModal('close')"
    >
        <AddEntitledEmployees
            :achievement="achievement"
            @update:waiters="waiters = $event"
            @isDirty="isDirty = $event"
            @closeModal="closeModal"
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

    <Modal 
        :show="isDeleteAchievementOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/configurations/configurations/deleteAchievement/${achievement.id}`"
        :confirmationTitle="'Delete achievement?'"
        :confirmationMessage="'Are you sure you want to delete this achievement? All the data in this achievement will be lost.'"
        @close="closeModal('close')"
        v-if="achievement.id"
    />

    <Modal 
        maxWidth="2xs" 
        :closeable="true"
        :show="isDeleteWaiterOpen"
        :withHeader="false"
        class="z-[1106] [&>div:nth-child(2)>div>div]:p-0"
        @close="closeModal('leave')"
    >
        <form novalidate @submit.prevent="submit">
            <div class="flex flex-col justify-center gap-9">
                <div class="bg-primary-50 pt-6 flex items-center justify-center rounded-t-[5px]">
                    <slot name="deleteimage">
                        <DeleteIllus />
                    </slot>
                </div>
                <div class="flex flex-col justify-center items-center self-stretch gap-1 px-6">
                    <p class="text-center text-primary-900 text-lg font-medium self-stretch">Delete this entitled employee?</p>
                    <p class="text-center text-grey-900 text-base font-medium self-stretch">Starting from next recurring date, the selected employee will no longer be entitled to this incentive commission.</p>
                </div>
                <div class="flex px-6 pb-6 justify-center items-end gap-4 self-stretch">
                    <Button
                        type="button"
                        variant="tertiary"
                        size="lg"
                        @click="closeModal('leave')"
                    >
                        Keep
                    </Button>
                    <Button
                        variant="red"
                        size="lg"
                        :disabled="form.processing"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </form>
    </Modal>

    <!-- <Modal 
        :show="isDeleteWaiterOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/configurations/configurations/deleteEntitled/${props.achievementDetails.id}/${selectedWaiter}`"
        :confirmationTitle="'Delete this entitled employee?'"
        :confirmationMessage="'Starting from next recurring date, the selected employee will no longer be entitled to this incentive commission.'"
        :toastMessage="'Selected employee has been removed from this achievement. '"
        @close="closeModal('close')"
        v-if="selectedWaiter"
    /> -->

</template>

