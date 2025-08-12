<script setup>
import Button from '@/Components/Button.vue';
import { CircledArrowHeadRightIcon2, GiftImage, HistoryIcon, MedalImage, TimesIcon, WarningIcon } from '@/Components/Icons/solid';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import { computed, onMounted, ref, watch } from 'vue';
import ViewHistory from './ViewHistory.vue';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import ReturnItem from './ReturnItem.vue';
import PointsDrawer from './PointsDrawer.vue';
import TierDrawer from './TierDrawer.vue';
import { useCustomToast, usePhoneUtils } from '@/Composables';
import dayjs from 'dayjs';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import NumberCounter from '@/Components/NumberCounter.vue';
import TextInput from '@/Components/TextInput.vue';
import DateInput from '@/Components/Date.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Textarea from '@/Components/Textarea.vue';
import { deleteReason, expiryDates } from '@/Composables/constants';
import { MovingIllus } from '@/Components/Icons/illus';

const props = defineProps ({
    customer: Object
})
const { showMessage } = useCustomToast();
const emit = defineEmits(['update:customerKeepItems', 'update:customerPoints']);
const initialEditForm = ref();
const selectedCustomer = ref(null);
const selectedItem = ref(null);
const returnItemOverlay = ref(null);
const isDrawerOpen = ref(false);
const isMoreActionOpen = ref(null);
const isEditKeptItemOpen = ref(false);
const isExtendExpirationOpen = ref(false);
const isExpireKeptItemOpen = ref(false);
const isDeleteKeptItemOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const viewType = ref('');
const isLoading = ref(false);
const expiringPointHistories = ref([]);
const isMessageShown = ref(false);

const editForm = useForm({
    id: '',
    kept_amount: '',
    remark: '',
    expired_to: '',
    keptIn: '',
    customer_id: props.customer.id,
})

const extendForm = useForm({
    id: '',
    customer_id: props.customer.id,
    expired_to: 2,
})

const deleteForm = useForm({
    id: '',
    customer_id: props.customer.id,
    remark: '',
    remark_description: '',
})


const { formatPhone } = usePhoneUtils();

const fetchExpiringPointHistories = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('customer.getExpiringPointHistories', props.customer.id));
        expiringPointHistories.value = response.data;

        if (expiringPointHistories.value.length > 0) (isMessageShown.value = true);
        
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const openDrawer = (action, customer) => {
    viewType.value = action;
    selectedCustomer.value = customer;
    isDrawerOpen.value = true;
}

const closeDrawer = () => {
    isDrawerOpen.value = false;
}

const openItemOverlay = (event, item) => {
    selectedItem.value = item;
    returnItemOverlay.value.show(event);
};

const closeItemOverlay = () => {
    returnItemOverlay.value.hide();
};

const openActionsOverlay = (event, item) => {
    selectedItem.value = item;
    isMoreActionOpen.value.show(event);

    editForm.id = selectedItem.value.id;
    editForm.expired_to = selectedItem.value.expired_to ? dayjs(selectedItem.value.expired_to).format('DD/MM/YYYY') : '';
    editForm.kept_amount = parseFloat(selectedItem.value.cm) > parseFloat(selectedItem.value.qty) ? (selectedItem.value.cm).toString() : selectedItem.value.qty;
    editForm.remark = selectedItem.value.remark ? selectedItem.value.remark : '';
    editForm.keptIn = parseFloat(selectedItem.value.cm) > parseFloat(selectedItem.value.qty) ? 'cm' : 'qty';
    editForm.customer_id = props.customer.id;

    initialEditForm.value = {...editForm};

    extendForm.id = selectedItem.value.id;

    deleteForm.id = selectedItem.value.id;
}

const closeActionsOverlay = () => {
    if(isMoreActionOpen.value) {
        isMoreActionOpen.value.hide();
    }
}

const openModal = (action) => {
    closeActionsOverlay();
    switch(action) {
        case 'edit': isEditKeptItemOpen.value = true; break;
        case 'extend': isExtendExpirationOpen.value = true; break;
        case 'expire' : isExpireKeptItemOpen.value = true; break;
        case 'delete' : isDeleteKeptItemOpen.value = true; break;
    }
}

const closeModal = (status) => {
    switch(status) {
        case 'close': {
            if (initialEditForm.isDirty) {
                isUnsavedChangesOpen.value = true;
            } else {
                isEditKeptItemOpen.value = false; 
                editForm.clearErrors();
            }

            if (extendForm.expired_to !== 2) {
                isUnsavedChangesOpen.value = true;
            } else {
                isExtendExpirationOpen.value = false; 
                extendForm.clearErrors();
            }

            if (deleteForm.remark !== '' || deleteForm.remark_description !== '') {
                isUnsavedChangesOpen.value = true;
            } else {
                isDeleteKeptItemOpen.value = false; 
                deleteForm.clearErrors();
            }

            isExpireKeptItemOpen.value = false;

            break;
        };
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        };
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isEditKeptItemOpen.value = false;
            isExtendExpirationOpen.value = false;
            isExpireKeptItemOpen.value = false;
            isDeleteKeptItemOpen.value = false;

            selectedItem.value = '';

            editForm.reset();
            extendForm.reset();
            deleteForm.reset();

            editForm.isDirty = false;
            extendForm.isDirty = false;
            deleteForm.isDirty = false;
            break;
        };
    }
}

const newExpiredTo = computed(() => {
    return dayjs(selectedItem.value.expired_to).add(extendForm.expired_to, 'month');
});

const editKeptItem = async () => {
    isLoading.value = true;

    try {
        const response = await axios.put(`/order-management/editKeptItemDetail`, editForm);
        emit('update:customerKeepItems', response.data);
        closeModal('leave');
        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Successfully edited.',
            });
        }, 200)
        editForm.reset();
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const extendKeptItem = async () => {
    try {
        const response = await axios.post(`/order-management/extendDuration`, extendForm);
        emit('update:customerKeepItems', response.data);
        closeModal('leave');
        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Expiration date extended successfully',
                detail: 'You’ve extended the expiration date for selected kept item.'
            });
        }, 200)
        extendForm.reset();
    } catch (error) {
        console.error(error);
    }
}

const expireKeptItem = async () => {
    isLoading.value = true;

    try {
        const response = await axios.post(`/order-management/expireKeepItem`, {
            id: selectedItem.value.id,
            customer_id: props.customer.id
        });

        emit('update:customerKeepItems', response.data);
        closeModal('leave');

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Kept item successfully expired',
                detail: 'Selected item has been expired and returned to inventory.'
            });
        }, 200)
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const deleteKeptItem = async () => {
    try {
        const response = await axios.post(`/order-management/deleteKeptItem`, deleteForm);
        emit('update:customerKeepItems', response.data);
        closeModal('leave');
        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Kept item successfully deleted',
                detail: 'Selected item has been deleted from this customer’s account.'
            });
        }, 200)
        deleteForm.reset();
    } catch (error) {
        if (error.response && error.response.data.errors) {
            deleteForm.errors = error.response.data.errors; 
        }
        console.error(error);
    }
}

const formatKeepItems = (keepItems) => {
    return keepItems
            .map((item) => {
                item.qty = parseFloat(item.qty);
                item.cm = parseFloat(item.cm);
                return item;
            })
            .sort((a, b) => dayjs(b.created_at).diff(dayjs(a.created_at)))
            .filter((item) => ((item.qty == 0 && item.cm > 0) || (item.qty > 0 && item.cm == 0)) && item.status === 'Keep');
};

const formatPoints = (points) => {
    const pointsStr = points.toString();
    return pointsStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const getKeepItemExpiryStatus = (keepItem) => {
    const expiredDate = dayjs(keepItem.expired_to).startOf('day');
    const today = dayjs().startOf('day');
    const daysDiff = expiredDate.diff(today, 'day');

    const expiredStatus = daysDiff <= 0
            ? 'now'
            : daysDiff <= 7 
                ? 'soon'
                : 'normal';

    return expiredStatus;
};

const totalPointsExpiringSoon = computed(() => {
    return expiringPointHistories.value.reduce((total, record) => total + Number(record.expire_balance), 0).toFixed(2);
});

onMounted(() => fetchExpiringPointHistories());

watch((editForm), (newValue) => {
    initialEditForm.isDirty = newValue.id !== initialEditForm.value.id ||
                            newValue.kept_amount !== initialEditForm.value.kept_amount ||
                            newValue.remark !== initialEditForm.value.remark ||
                            newValue.expired_to !== initialEditForm.value.expired_to;
})

</script>

<template>
    <div class="flex flex-col items-center gap-3 self-stretch max-h-[calc(100dvh-4rem)] p-6 overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="flex flex-col py-6 gap-6 self-stretch rounded-[5px]">
            <!-- avatar -->
            <div class="w-full flex flex-col items-center gap-3">
                <img 
                    :src="customer.image ? customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                    alt="" 
                    class="size-[80px] rounded-full"
                />
                <div class="w-full flex flex-col items-center gap-2">
                    <span class="text-primary-900 text-base font-semibold">{{ customer.full_name }}</span>
                    <div class="flex justify-center items-center gap-2" v-if="customer.email || customer.phone">
                        <span class="text-primary-950 text-sm font-medium">{{ customer.email }}</span>
                        <span class="w-1 h-1 rounded-full bg-grey-300" v-if="customer.email"></span>
                        <span class="text-primary-950 text-sm font-medium">({{ formatPhone(customer.phone) }})</span>
                    </div>
                </div>
            </div>

            <!-- message container -->
            <div class="flex p-3 justify-center items-start gap-3 self-stretch rounded-[5px] bg-[#FDFBED]"
                v-if="expiringPointHistories.length > 0 && isMessageShown"
                @click.prevent.stop=""
            >
                <WarningIcon />
                <div class="flex flex-col items-start gap-3 flex-[1_0_0]">
                    <span class="self-stretch text-[#A35F1A] text-base font-bold">{{`${totalPointsExpiringSoon} points expiring soon:` }}</span>
                    <ul class="list-disc pl-6">
                        <template v-for="record in expiringPointHistories" :key="record.id">
                            <li class="text-sm text-grey-950 font-normal"><span class="!font-bold">{{ `${record.expire_balance} points` }}</span> on {{ dayjs(record.expired_at).format('MMM D, YYYY') }}</li>
                        </template>
                    </ul>
                </div>
                <TimesIcon class="!text-[#6E3E19] cursor-pointer" @click.prevent.stop="isMessageShown = false"/>
            </div>

            <!-- current points and current tier -->
            <div class="w-full flex justify-center items-start gap-6 self-stretch">
                <div class="flex flex-col p-4 items-start gap-3 flex-[1_0_0] rounded-[5px] bg-gradient-to-br from-primary-900 to-[#5E0A0E] relative">
                    <div class="w-full flex justify-between items-center">
                        <span class="text-base font-semibold text-primary-25 whitespace-nowrap w-full">Current Points</span>
                        <CircledArrowHeadRightIcon2
                            class="w-6 h-6 text-primary-900 [&>rect]:fill-primary-25 [&>rect]:hover:fill-primary-800 cursor-pointer z-10"
                            @click="openDrawer('currentPoints', customer)" />
                    </div>
                    <div class="flex flex-col items-start gap-1">
                        <span class="text-white text-[36px] leading-normal font-light tracking-[-0.72px]">{{ formatPoints(customer.point) }}</span>
                        <span class="text-white text-lg font-normal">pts</span>
                    </div>
                    <div class="absolute bottom-0 right-0">
                        <GiftImage />
                    </div>
                </div>
                <div class="flex flex-col p-4 items-start gap-3 flex-[1_0_0] self-stretch rounded-[5px] border border-solid border-primary-50 
                            bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-[#FFFAFA] to-[#FFFFFF] relative">
                    <div class="w-full flex justify-between items-center">
                        <span class="text-base font-semibold text-primary-900 whitespace-nowrap w-full">Current Tier</span>
                        <CircledArrowHeadRightIcon2
                            class="w-6 h-6 text-primary-25 [&>rect]:fill-primary-900 [&>rect]:hover:fill-primary-800 hover:cursor-pointer z-10"
                            @click="openDrawer('currentTier', customer)" />
                    </div>
                    <div class="w-full flex flex-col justify-center items-start gap-1 self-stretch">
                        <template v-if="customer.rank && customer.rank.name !== 'No Tier'">
                            <div class="flex flex-col justify-center items-start gap-[10px]">
                                <img 
                                    :src="customer.rank && customer.rank.image ? customer.rank.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt="CustomerRankImage"
                                    class="size-[48px]"
                                />
                            </div>
                            <div class="flex flex-col justify-center items-center gap-2 !z-10">
                                <span class="text-primary-900 text-lg font-medium">{{ customer.rank.name }}</span>
                            </div>
                        </template>
                        <div v-else>
                            <span class="text-primary-900 text-lg font-medium"> - </span>
                        </div>
                        <div class="absolute bottom-0 right-0">
                            <MedalImage />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- keep item -->
        <div class="w-full flex flex-col items-center gap-3 self-stretch">
            <div class="flex py-3 justify-center items-center gap-[10px] self-stretch">
                <span class="flex-[1_0_0] text-primary-900 text-md font-semibold">Keep Item ({{ formatKeepItems(customer.keep_items).reduce((total, item) =>  total + (item.qty > item.cm ? item.qty : 1), 0) }})</span>
                <div class="flex items-center gap-2 cursor-pointer" @click="openDrawer('keepHistory', customer)">
                    <HistoryIcon class="w-4 h-4" />
                    <div class="bg-gradient-to-br from-primary-900 to-[#5E0A0E] text-transparent bg-clip-text text-sm font-medium">View History</div>
                </div>
            </div>
            <div class="flex flex-col justify-end items-start gap-2 self-stretch">
                <!-- <div class="flex py-3 items-center gap-3 self-stretch" v-for="item in formatKeepItems(customer.keep_items)" :key="item.id">
                    <div class="flex flex-col justify-center items-start gap-3 flex-[1_0_0]">
                        <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch rounded-sm bg-primary-25">
                            <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                        </div>
                        <div class="flex items-center gap-3 self-stretch">
                            <div class="flex flex-col items-start gap-3 flex-[1_0_0]">
                                <div class="flex items-start gap-3 self-stretch">
                                    <img 
                                        :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                        alt="CustomerKeepItem"
                                        class="flex rounded-[1.5px] overflow-x-hidden size-[60px] object-contain"
                                    />
                                    <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                                        <div class="flex items-center gap-1 self-stretch">
                                            <span class="text-grey-400 text-2xs font-normal" v-if="item.expired_to">{{ item.expired_to ? `Expire on ${dayjs(item.expired_to).format('DD/MM/YYYY')}` : '' }}</span>
                                            <span class="size-1 bg-grey-900 rounded-full" v-if="item.expired_to"></span>
                                            <span class="text-primary-900 text-2xs font-normal">Kept by</span>
                                            <img 
                                                :src="item.waiter && item.waiter.image ? item.waiter.image : 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bc/Unknown_person.jpg/434px-Unknown_person.jpg'" 
                                                alt="" 
                                                class="size-3 bg-red-900 rounded-full"
                                            />
                                            <span class="text-primary-900 text-2xs font-normal">{{ item.waiter.full_name }}</span>
                                        </div>
                                        <span class="text-grey-900 line-clamp-1 self-stretch overflow-hidden text-ellipsis text-sm font-medium">{{ item.item_name }}</span>
                                        <div class="flex flex-nowrap gap-x-1 items-start" v-if="item.remark">
                                            <CommentIcon class="flex-shrink-0 mt-1" />
                                            <span class="text-grey-900 text-sm font-normal">{{ item.remark }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col justify-center items-end gap-3">
                                <span class="text-primary-900 text-base font-medium">{{ item.qty > item.cm ? `x ${item.qty}` : `${item.cm} cm`  }}</span>
                                <Button 
                                    :type="'button'" 
                                    :size="'md'" 
                                    @click="openItemOverlay($event, item)"
                                >
                                    Return Item
                                </Button>
                            </div>
                        </div>
                    </div>
                </div> -->
                <div class="flex flex-col p-4 justify-center items-start gap-4 self-stretch rounded-[5px] bg-white shadow-[0_4px_15.8px_0_rgba(13,13,13,0.08)]"  v-for="item in formatKeepItems(customer.keep_items)" :key="item.id">
                    <!-- message container -->
                    <div class="flex p-3 justify-center items-start gap-3 self-stretch rounded-[5px] bg-[#FDFBED]"
                        v-if="getKeepItemExpiryStatus(item) !== 'normal'"
                    >
                        <!-- message header -->
                        <WarningIcon />
                        <div class="flex flex-col justify-between items-start flex-[1_0_0]">
                            <span class="self-stretch text-[#A35F1A] text-base font-bold">{{ getKeepItemExpiryStatus(item) === 'soon' ? 'Expiring Soon' : 'Pending Action'  }}</span>
                            <span class="self-stretch text-[#3E200A] text-sm font-normal">{{ getKeepItemExpiryStatus(item) === 'soon' ? `This kept item will be expired on ${dayjs(item.expired_to).format('DD/MM/YYYY')}.` : 'This item is expired. Please choose to expire it or extend the expiration date.'  }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div class="flex items-start gap-3 self-stretch">
                            <div class="flex flex-col items-start gap-0.5 flex-[1_0_0] self-stretch">
                                <span class="text-primary-800 text-lg font-bold">{{ parseFloat(item.qty) > parseFloat(item.cm) ? `x${item.qty}` : `${item.cm} cm` }}</span>
                                <span class="self-stretch text-grey-950 text-base font-medium">{{ item.item_name }}</span>
                            </div>
                            <img 
                                :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                alt="ProductImage"
                                class="size-[65px] object-contain"
                            >
                        </div>
                        <div class="grid grid-cols-10 w-full items-start">
                            <span class="col-span-2 text-grey-500 text-sm font-normal">Kept from</span>
                            <span class="col-span-8 flex-[1_0_0] text-grey-950 text-sm font-normal">{{ item.kept_from_table }}</span>
                            <span class="col-span-2 text-grey-500 text-sm font-normal">Expire on</span>
                            <span class="col-span-8 flex-[1_0_0] text-grey-950 text-sm font-normal">{{ item.expired_to ? dayjs(item.expired_to).format('DD/MM/YYYY') : '-' }}</span>
                            <span class="col-span-2 text-grey-500 text-sm font-normal">Kept by</span>
                            <div class="col-span-8 flex items-center gap-1.5 flex-[1_0_0]">
                                <img
                                    :src="item.waiter.image ? item.waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                    alt="WaiterImage"
                                    class="rounded-full size-3 object-contain"
                                >
                                <span class="flex-[1_0_0] text-grey-950 text-sm font-normal">{{ item.waiter.full_name }}</span>
                            </div>
                            <span class="col-span-2 text-grey-500 text-sm font-normal">Remark</span>
                            <span class="col-span-8 flex-[1_0_0] text-grey-950 text-sm font-normal">{{ item.remark ? item.remark : '-' }}</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 self-stretch">
                        <Button
                            :variant="'tertiary'"
                            :type="'button'"
                            :size="'md'"
                            @click="openActionsOverlay($event, item)"
                        >
                            More Action
                        </Button>
                        <Button
                            :variant="'primary'"
                            :type="'button'"
                            :size="'md'"
                            @click="openItemOverlay($event, item)"
                        >
                            Return Item
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <RightDrawer
        :header="viewType === 'keepHistory' ? 'History' : viewType === 'currentPoints' ? 'Points' : 'Tier'" 
        :previousTab="true"
        :show="isDrawerOpen"
        @close="closeDrawer"
    >
        <template v-if="viewType === 'keepHistory'"> <!-- customer keep history drawer -->
            <ViewHistory :customer="selectedCustomer" />
        </template>
        <template v-else-if="viewType === 'currentPoints'"> <!-- customer point drawer -->
            <PointsDrawer 
                :customer="selectedCustomer"
                @update:customerPoints="emit('update:customerPoints', $event)"
            />
        </template>
        <template v-else> <!-- customer tier drawer -->
            <TierDrawer :customer="selectedCustomer"/>
        </template>
    </RightDrawer>

    <OverlayPanel ref="returnItemOverlay">
        <ReturnItem
            :item="selectedItem"
            @update:keepList="customer.keep_items = $event"
            @close="closeItemOverlay"
        />
    </OverlayPanel>

    
    <!-- More Actions -->
    <OverlayPanel ref="isMoreActionOpen" @close="closeActionsOverlay" class="[&>div]:!p-1 [&>div]:!gap-0.5">
        <div class="flex p-3 items-center gap-2.5 self-stretch cursor-pointer" @click="openModal('edit')">
            <span class="text-grey-900 text-base font-medium">Edit kept detail</span>
        </div>
        <!-- <div class="flex p-3 items-center gap-2.5 self-stretch cursor-pointer" @click="openModal('extend')" v-if="selectedItem.expired_to">
            <span class="text-grey-900 text-base font-medium">Extend expiration date</span>
        </div>
        <div class="flex p-3 items-center gap-2.5 self-stretch cursor-not-allowed pointer-events-none bg-grey-50" @click="openModal('extend')" v-else>
            <span class="text-grey-300 text-base font-medium">Extend expiration date</span>
        </div> -->
        <div class="flex p-3 items-center gap-2.5 self-stretch cursor-pointer" v-if="getKeepItemExpiryStatus(selectedItem) === 'now'" @click="openModal('expire')">
            <span class="text-primary-800 text-base font-medium">Mark as expired</span>
        </div>
        <div class="flex p-3 items-center gap-2.5 self-stretch cursor-pointer" @click="openModal('delete')">
            <span class="text-primary-800 text-base font-medium">Delete kept item</span>
        </div>
    </OverlayPanel>

    <!-- Edit Kept Detail -->
    <Modal
        :show="isEditKeptItemOpen"
        :title="'Edit Kept Item Detail'"
        :maxWidth="'sm'"
        @close="closeModal('close')"
    >  
        <form novalidate @submit.prevent="editKeptItem">
            <div class="flex flex-col items-start gap-8 self-stretch">
                <div class="flex p-4 items-start gap-5 self-stretch rounded-[5px] bg-grey-50">
                    <div class="flex flex-col justify-between items-start flex-[1_0_0] self-stretch">
                        <span class="self-stretch text-md font-medium">{{ selectedItem.item_name }}</span>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <div class="flex items-center gap-3 self-stretch">
                                <span class="text-grey-500 text-sm font-normal">From order</span>
                                <span class="flex-[1_0_0] text-grey-950 text-sm font-normal">{{ selectedItem.order_no }}</span>
                            </div>
                        </div>
                    </div>
                    <img
                        :src="selectedItem.image ? selectedItem.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                        alt="ItemImage"
                        class="object-contain size-[74px]"
                    >
                </div>
                <div class="grid grid-cols-3 items-center self-stretch">
                    <span class="text-grey-900 text-base font-bold">Kept</span>
                    <NumberCounter
                        :errorMessage="editForm.errors?.kept_amount"
                        :minValue="1"
                        :maxValue="parseFloat(selectedItem.qty) > parseFloat(selectedItem.cm) ? parseFloat(selectedItem.qty) : parseFloat(selectedItem.cm)"
                        :inputName="'kept_amount'"
                        v-model="editForm.kept_amount"
                        class="!col-span-1"
                        disabled
                        v-if="parseFloat(selectedItem.qty) > parseFloat(selectedItem.cm)"
                    />
                    <TextInput
                        :errorMessage="editForm.errors?.kept_amount"
                        :inputName="'kept_amount'"
                        :iconPosition="'right'"
                        v-model="editForm.kept_amount"
                        class="!col-span-2 [&>div>input]:!text-start [&>div>input]:!pl-4"
                        v-else
                    >
                        <template #prefix>
                            cm
                        </template>
                    </TextInput>
                </div>
                <div class="grid grid-cols-3  items-center self-stretch">
                    <span class="text-grey-900 text-base font-bold">Remark</span>
                    <Textarea 
                        :rows="3"
                        :errorMessage="editForm.errors?.remark"
                        :inputName="'remark'"
                        class="!col-span-2"
                        v-model="editForm.remark"
                    />
                </div>
                <div class="grid grid-cols-3 items-center self-stretch">
                    <span class="text-grey-900 text-base font-bold">Expiration date</span>
                    <DateInput 
                        :errorMessage="editForm.errors?.expired_to"
                        :range="false"
                        :inputName="'expiration_date'"
                        :minDate="editForm.expired_to ? new Date(dayjs(editForm.expired_to).format('DD/MM/YYYY')) : new Date()"
                        v-model="editForm.expired_to"
                        class="col-span-2"
                    />
                </div>

                <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                    <Button
                        :variant="'tertiary'"
                        :type="'button'"
                        :size="'lg'"
                        @click="closeModal('close')"
                    >
                        Cancel
                    </Button>
                    <Button
                        :variant="'primary'"
                        :type="'submit'"
                        :size="'lg'"
                        :disabled="isLoading"
                    >
                        Save
                    </Button>
                </div>
            </div>
        </form>
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>

     <!-- Extend expiration date -->
    <!-- <Modal
        :title="'Extend Expiration Date'"
        :maxWidth="'xs'"
        :show="isExtendExpirationOpen"
        @close="closeModal('close')"
    >
        <form @submit.prevent="extendKeptItem">
            <div class="flex flex-col items-start gap-6">
                <Dropdown 
                    :inputArray="expiryDates"
                    :labelText="'Expiry Date'"
                    :dataValue="2"
                    v-model="extendForm.expired_to"
                />

                <div class="flex items-start gap-3 self-stretch">
                    <span class="text-grey-950 text-base font-normal">New expiration date:</span>
                    <span class="text-grey-950 text-base font-bold">{{ newExpiredTo ? dayjs(newExpiredTo).format('DD/MM/YYYY') : dayjs(selectedItem.expired_to).format('DD/MM/YYYY') }}</span>
                </div>

                <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                    <Button
                        :variant="'tertiary'"
                        :type="'button'"
                        :size="'lg'"
                        @click="closeModal('close')"
                    >
                        Cancel
                    </Button>
                    <Button
                        :variant="'primary'"
                        :type="'submit'"
                        :size="'lg'"
                    >
                        Confirm
                    </Button>
                </div>
            </div>
        </form>

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal> -->

    <!-- Delete kept item -->
    <Modal
        :title="'Delete Kept Item'"
        :show="isDeleteKeptItemOpen"
        :maxWidth="'sm'"
        @close="closeModal('close')"
    >
        <form @submit.prevent="deleteKeptItem">
            <div class="flex flex-col items-start gap-4 self-stretch">
                <Dropdown
                    :labelText="'Select Deletion Reason'"
                    :errorMessage="deleteForm.errors.remark ? deleteForm.errors.remark[0] : ''"
                    :inputName="'remark'"
                    :inputArray="deleteReason"
                    :placeholder="'Select'"
                    v-model="deleteForm.remark"
                />

                <Textarea
                    :labelText="'Remarks'"
                    :errorMessage="deleteForm.errors.remark_description ? deleteForm.errors.remark_description[0] : ''"
                    :inputName="'remark_description'"
                    :rows="5"
                    :placeholder="'Enter here'"
                    v-model="deleteForm.remark_description"
                />
            </div>
            <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                <Button
                    :variant="'tertiary'"
                    :size="'lg'"
                    :type="'button'"
                    @click="closeModal('close')"
                >
                    Cancel
                </Button>
                <Button
                    :variant="'primary'"
                    :size="'lg'"
                    :type="'submit'"
                >
                    Delete
                </Button>
            </div>
        </form>
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>

    <!-- Expire kept item -->
    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="isExpireKeptItemOpen"
        @close="closeModal('close')"
        :withHeader="false"
    >
        <form @submit.prevent="expireKeptItem">
            <div class="flex flex-col gap-9 pt-36">
                <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] fixed top-0 w-full left-0">
                    <MovingIllus class="mt-2.5"/>
                </div>
                <div class="flex flex-col gap-1" >
                    <div class="text-primary-900 text-lg font-medium text-center">
                        Mark as Expired?
                    </div>
                    <div class="text-gray-900 text-base font-medium text-center leading-tight" >
                        This kept item will be marked as expired and customer can no longer redeem back this item. Are you sure you want to proceed?
                    </div>
                </div>
                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        @click="closeModal('close')"
                        type="button"
                    >
                        Cancel
                    </Button>
                    <Button
                        size="lg"
                        :disabled="isLoading"
                    >
                        Confirm
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
</template>