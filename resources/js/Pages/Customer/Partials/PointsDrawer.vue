<script setup>
import { PointsIllust } from '@/Components/Icons/illus';
import { HistoryIcon } from '@/Components/Icons/solid';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import Toast from '@/Components/Toast.vue';
import { computed, onMounted, ref } from 'vue';
import RedeemHistory from './RedeemHistory.vue';
import Button from '@/Components/Button.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import Textarea from '@/Components/Textarea.vue';
import axios from 'axios';
import { useCustomToast } from '@/Composables';

const props = defineProps({
    customer:{
        type: Object,
        default: () => {},
    }
})

const emit = defineEmits(['update:customerPoints']);
const { showMessage } = useCustomToast();

const customer = ref(props.customer);
const redeemables = ref([]);
const isPointHistoryDrawerOpen = ref(false);
const isAdjustOpen = ref(false);
const isUnsavedChangesOpen = ref(false);

const form = useForm({
    id: '',
    point: '',
    reason: '',
    addition: false,
})

const openHistoryDrawer = (id) => {
    isPointHistoryDrawerOpen.value = true;
}

const closeDrawer = () => {
    isPointHistoryDrawerOpen.value = false;
}

const formatPoints = (points) => {
    const pointsStr = points.toString();
    return pointsStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};


const getRedeemables = async () => {
    try {
        const response = await axios.get('/customer/getRedeemableItems');
        redeemables.value = response.data;
    } catch(error) {
        console.error(error);
    } finally {

    }
}

const openAdjust = () => {
    isAdjustOpen.value = true;
}

const closeAdjust = (status) => {
    switch (status) {
        case 'close': {
            if(form.isDirty) isUnsavedChangesOpen.value = true;
            else isAdjustOpen.value = close;
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isAdjustOpen.value = false;
            form.errors = [];
            break;
        }
    }
}

const submit = async (addition) => {
    form.id = props.customer.id;
    form.addition = addition;
    
    try {
        const response = await axios.post(route('customer.adjustPoint'), form);
        emit('update:customerPoints', response.data);
        showMessage({ 
            severity: 'success',
            summary: 'Customer point adjusted.',
        });
        closeAdjust('leave');
        form.reset();
    } catch (error) {
        if (error.response && error.response.data.errors) {
            form.errors = error.response.data.errors; 
        }
        console.error(error);
    }
}
const isFormValid = computed(() => ['point', 'reason'].every(field => form[field]));

onMounted(() => getRedeemables());
</script>

<template>
    <div class="flex flex-col max-h-[calc(100dvh-4rem)] p-6 items-center gap-6 shrink-0 overflow-y-auto scrollbar-thin scrollbar-webkit">
        <!-- current points -->
        <div class="flex flex-col p-6 justify-center items-center gap-2 self-stretch rounded-[5px] bg-primary-25">
            <div class="flex flex-col justify-center items-center gap-4 relative">
                <span class="self-stretch text-grey-900 text-base text-center font-medium">Current Points</span>
                <div class="flex flex-col justify-center items-center gap-2">
                    <div class="flex justify-center items-center gap-2 self-stretch">
                        <span class="bg-gradient-to-br from-primary-900 to-[#5E0A0E] text-transparent bg-clip-text text-[40px] font-normal">{{ formatPoints(customer.point) }}</span>
                        <Button
                            :variant="'primary'"
                            :type="'button'"
                            :size="'md'"
                            class="!w-fit z-[1101] inset-0"
                            @click="openAdjust"
                        >
                            Adjust
                        </Button>
                    </div>
                    <span class="text-primary-950 text-base font-medium">pts</span>
                </div>
                <PointsIllust class="absolute"/>
            </div>
        </div>

        <!-- Redeem product -->
         <div class="flex flex-col items-center gap-3 self-stretch">
            <div class="flex py-3 justify-center items-center gap-[10px] self-stretch">
                <span class="flex-[1_0_0] text-primary-900 text-md font-semibold ">Redeem Product</span>
                <div class="flex items-center gap-2 cursor-pointer" @click="openHistoryDrawer(customer.id)">
                    <HistoryIcon class="w-4 h-4" />
                    <div class="text-primary-900 text-sm font-medium">View History</div>
                </div>
            </div>

            <Toast 
                inline
                severity="info"
                summary="You can redeem the product only when the customer has checked-in to one table/room."
                :closable="false"
            />

            <div class="flex flex-col items-center self-stretch divide-y-[0.5px] divide-grey-200">
                <div class="flex flex-col justify-end items-start self-stretch" v-for="item in redeemables" :key="item.id">
                    <div class="flex items-center p-3 gap-3 self-stretch">
                        <div class="flex items-center gap-3">
                            <img 
                                :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt=""
                                class="size-[60px] bg-primary-25 rounded-[1.5px] border-[0.3px] border-solid border-grey-100"
                            >
                            <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0] self-stretch">
                                <span class="line-clamp-1 overflow-hidden text-grey-900 ellipsis text-base font-medium">{{ item.product_name }}</span>
                                <span class="overflow-hidden text-red-950 text-base font-medium">{{ formatPoints(item.point) }} pts</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
         </div>
    </div>

    <!-- history drawer -->
     <RightDrawer
        :header="'History'"
        :previousTab='true'
        :show="isPointHistoryDrawerOpen"
        @close="closeDrawer"
    >
        <template v-if="customer">
            <RedeemHistory :customerId="customer.id"/>
        </template>
    </RightDrawer>

    <!-- Adjust Point -->
    <Modal
        :title="'Adjust Point'"
        :maxWidth="'xs'"
        :show="isAdjustOpen"
        @close="closeAdjust('close')"
    >
        <form novalidate @submit.prevent="submit">
            <div class="flex flex-col items-start gap-6">
                <div class="flex flex-col items-start gap-4 self-stretch">
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <span class="self-stretch text-grey-950 text-base font-bold">Point</span>
                        <span class="self-stretch text-grey-950 text-sm font-normal">Please enter the amount of point you want to add to or substract from this customer's account.</span>
                    </div>
                    <TextInput 
                        :errorMessage="form.errors.point ? form.errors.point[0] : ''"
                        :iconPosition="'right'"
                        :inputName="'point'"
                        :placeholder="'0'"
                        v-model="form.point"
                    >
                        <template #prefix>
                            <span class="text-grey-900 text-base font-normal">pts</span>
                        </template>
                    </TextInput>
                </div>

                <Textarea 
                    :inputName="'reason'"
                    :labelText="'Reason of adjust'"
                    :errorMessage="form.errors.reason ? form.errors.reason[0] : ''"
                    :placeholder="'Enter'"
                    :rows="3"
                    v-model="form.reason"
                />

                <div class="flex pt-3 items-start gap-3 self-stretch">
                    <Button
                        :variant="'red'"
                        :type="'button'"
                        :size="'lg'"
                        :disabled="form.processing || !isFormValid"
                        @click="submit(false)"
                    >
                        Substract
                    </Button>
                    <Button
                        :variant="'green'"
                        :type="'button'"
                        :size="'lg'"
                        :disabled="form.processing || !isFormValid"
                        @click="submit(true)"
                    >
                        Add
                    </Button>
                </div>
            </div>
        </form>
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeAdjust('stay')"
            @leave="closeAdjust('leave')"
        />
    </Modal>
</template>
