<script setup>
import { ref, onMounted, watch, computed, getCurrentScope } from 'vue';
import Tag from '@/Components/Tag.vue';
import Button from '@/Components/Button.vue';
import AddOrderItems from './AddOrderItems.vue';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import { Calendar, CircledArrowHeadRightIcon2, CommentIcon, GiftImage, HistoryIcon, MedalImage, TimesIcon } from '@/Components/Icons/solid.jsx';
import axios from 'axios';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import KeepHistory from './KeepHistory.vue';
import CustomerPoint from './CustomerPoint.vue';
import { useCustomToast, usePhoneUtils } from '@/Composables/index.js';
import CustomerTier from './CustomerTier.vue';
import Modal from '@/Components/Modal.vue';
import Textarea from '@/Components/Textarea.vue';
import DateInput from '@/Components/Date.vue';
import TextInput from '@/Components/TextInput.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { deleteReason, expiryDates } from '@/Composables/constants';

const props = defineProps({
    customer: {
        type: Object,
        default: () => {}
    },
    matchingOrderDetails: {
        type: Object,
        default: () => {}
    },
    orderId: Number,
    tableStatus: String
})

const emit = defineEmits(['close', 'fetchZones', 'fetchOrderDetails', 'update:customerPoint', 'update:customerKeepItems']);

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)
const { formatPhone } = usePhoneUtils();
const { showMessage } = useCustomToast();


const drawerIsVisible = ref(false);
const viewType = ref(null);
const op = ref(null);
const isMoreActionOpen = ref(null);
const isEditKeptItemOpen = ref(false);
const isExtendExpirationOpen = ref(false);
const isDeleteKeptItemOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const selectedItem = ref();
const initialEditForm = ref();

const form = useForm({
    order_id: props.orderId,
    user_id: userId.value,
    customer_id: props.customer.id,
    type: '',
    return_qty: 0,
});

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

const openDrawer = (action) => {
    viewType.value = action;

    if (!drawerIsVisible.value) {
        drawerIsVisible.value = true;
    }
};

const closeDrawer = (redirect = false) => {
    drawerIsVisible.value = false;
    viewType.value = null;
    if (redirect) emit('close');
};

const openOverlay = (event, item) => {
    selectedItem.value = item;
    
    if (selectedItem.value) {
        form.type = selectedItem.value.qty > selectedItem.value.cm ? 'qty' : 'cm'
        form.return_qty = form.type === 'cm' ? 1 : 0;
        op.value.show(event);
    }
};

const closeOverlay = () => {
    selectedItem.value = null;
    
    if (op.value) {
        op.value.hide();
    }
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
        case 'delete' : isDeleteKeptItemOpen.value = true; break;
    }
}

const closeModal = (status) => {
    switch(status) {
        case 'close': {
            if(initialEditForm.isDirty) isUnsavedChangesOpen.value = true;
            else isEditKeptItemOpen.value = false; editForm.clearErrors();

            if(extendForm.expired_to !== 2) isUnsavedChangesOpen.value = true;
            else isExtendExpirationOpen.value = false; extendForm.clearErrors();

            if(deleteForm.remark !== '' || deleteForm.remark_description !== '') isUnsavedChangesOpen.value = true;
            else isDeleteKeptItemOpen.value = false; deleteForm.clearErrors();
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

const closeKeepItemDetails = () => {
    closeOverlay();
    setTimeout(() => {
        emit('fetchZones');
        emit('close');
    }, 300);
}

const submit = async () => { 
    form.processing = true;
    try {
        const response = await axios.post(`/order-management/orders/customer/keep/addKeptItemToOrder/${selectedItem.value.id}`, form);

        emit('update:customerKeepItems', response.data);
        closeKeepItemDetails();
        form.reset();
    } catch (error) {
        console.error(error);
    } finally {
        form.processing = false;
    }

    // form.post(route('orders.customer.keep.addKeptItemToOrder', selectedItem.value.id), {
    //     preserveScroll: true,
    //     preserveState: true,
    //     onSuccess: () => {
    //         form.reset();
    //         closeKeepItemDetails();
    //     },
    // })
};

const editKeptItem = async () => {
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
    return keepItems.map((item) => {
        item.qty = parseFloat(item.qty);
        item.cm = parseFloat(item.cm);
        return item;
    }).sort((a, b) => dayjs(b.created_at).diff(dayjs(a.created_at)));
};

const formatPoints = (points) => {
  const pointsStr = points.toString();
  
  return pointsStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

watch((editForm), (newValue) => {
    initialEditForm.isDirty = newValue.id !== initialEditForm.value.id ||
                            newValue.kept_amount !== initialEditForm.value.kept_amount ||
                            newValue.remark !== initialEditForm.value.remark ||
                            newValue.expired_to !== initialEditForm.value.expired_to;
})

const isFormValid = computed(() => ['type', 'return_qty'].every(field => form[field]) && !form.processing);

</script>

<template>
    <RightDrawer 
        :header="viewType === 'keepHistory' ? 'History' : viewType === 'currentPoints' ? 'Points' : 'Tier'" 
        previousTab
        v-model:show="drawerIsVisible"
        @close="closeDrawer"
    >
        <template v-if="viewType === 'keepHistory'">
            <KeepHistory :customer="customer" />
        </template>
        <template v-else-if="viewType === 'currentPoints'">
            <!-- :customerPoint="customerPoint" -->
            <CustomerPoint 
                :orderId="orderId"
                :customer="customer" 
                :tableStatus="tableStatus" 
                :matchingOrderDetails="matchingOrderDetails"
                @fetchZones="$emit('fetchZones')"
                @update:customerPoint="$emit('update:customerPoint', $event)"
                @close="closeDrawer"
            />
        </template>
        <template v-else>
            <CustomerTier
                :orderId="orderId"
                :customer="customer" 
                :tableStatus="tableStatus" 
                :matchingOrderDetails="matchingOrderDetails"
                @fetchZones="$emit('fetchZones')"
                @close="closeDrawer"
            />
        </template>
    </RightDrawer>

    <div class="w-full flex flex-col gap-6 items-start rounded-[5px] py-4 pr-1 max-h-[calc(100dvh-23rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="w-full flex flex-col items-center gap-3 pt-6">
            <img 
                :src="customer && customer.image ? customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                alt="CustomerProfilePic"
                class="size-20 rounded-full"
            >
            <div class="w-full flex flex-col items-center gap-2">
                <span class="text-primary-900 text-base font-semibold">{{ customer.full_name }}</span>
                <div class="flex justify-center items-center gap-2">
                    <span class="text-primary-950 text-sm font-medium">{{ customer.email }}</span>
                    <span class="w-1 h-1 rounded-full bg-grey-300"></span>
                    <span class="text-primary-950 text-sm font-medium">{{ formatPhone(customer.phone) }}</span>
                </div>
            </div>
        </div>

        <!-- current points and current tier -->
        <div class="w-full flex justify-center items-start gap-6 self-stretch">
            <div class="flex flex-col p-4 items-start gap-3 flex-[1_0_0] rounded-[5px] bg-gradient-to-br from-primary-900 to-[#5E0A0E] relative">
                <div class="w-full flex justify-between items-center">
                    <span class="text-base font-semibold text-primary-25 whitespace-nowrap w-full">Current Points</span>
                    <CircledArrowHeadRightIcon2
                        class="size-6 text-primary-900 [&>rect]:fill-primary-25 [&>rect]:hover:fill-primary-800 cursor-pointer z-10"
                        @click="openDrawer('currentPoints')" 
                    />
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
                        class="size-6 text-primary-25 [&>rect]:fill-primary-900 [&>rect]:hover:fill-primary-800 hover:cursor-pointer z-10"
                        @click="openDrawer('currentTier')" 
                    />
                </div>
                <div class="w-full flex flex-col justify-center items-start gap-1 self-stretch">
                    <template v-if="customer.rank && customer.rank.name !== 'No Tier'">
                        <div class="flex flex-col justify-center items-center gap-[10px]">
                            <img 
                                :src="customer.rank.image ? customer.rank.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt="CustomerRankIcon"
                                class="size-[48px]"
                            >
                        </div>
                        <div class="flex flex-col justify-center items-center gap-2 !z-10">
                            <span class="text-primary-900 text-lg font-medium">{{ customer.rank.name }}</span>
                        </div>
                    </template>
                    <template v-else>
                        <span class="text-primary-900 text-lg font-medium"> - </span>
                    </template>
                    <div class="absolute bottom-0 right-0">
                        <MedalImage />
                    </div>
                </div>
            </div>
        </div>

        <!-- Keep item -->
        <div class="w-full flex flex-col items-center gap-3 self-stretch">
            <div class="flex py-3 justify-center items-center gap-[10px] self-stretch">
                <span class="flex-[1_0_0] text-primary-900 text-md font-semibold">Keep Item ({{ customer.keep_items.length }})</span>
                <div class="flex items-center gap-2 cursor-pointer" @click="openDrawer('keepHistory')">
                    <HistoryIcon class="w-4 h-4" />
                    <div class="bg-gradient-to-br from-primary-900 to-[#5E0A0E] text-transparent bg-clip-text text-sm font-medium">View History</div>
                </div>
            </div>
            <div class="flex flex-col justify-end items-start gap-2 self-stretch">
                <!-- <div class="flex py-3 items-center gap-3 self-stretch" v-for="item in formatKeepItems(customer.keep_items)" :key="item.id">
                    <div class="flex flex-col justify-center items-start gap-3 flex-[1_0_0]">
                        <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch">
                            <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                        </div>
                        <div class="flex items-center gap-3 self-stretch">
                            <div class="w-full flex items-start gap-3 self-stretch">
                                <img 
                                    :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="rounded-[1.5px] size-[60px] object-cover"
                                >
                                <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                                    <div class="flex items-center gap-1 self-stretch">
                                        <span class="text-grey-400 text-2xs font-normal" v-if="item.expired_to">{{ item.expired_to ? `Expire on ${dayjs(item.expired_to).format('DD/MM/YYYY')}` : '' }}</span>
                                        <span class="w-1 h-1 bg-grey-900 rounded-full" v-if="item.expired_to"></span>
                                        <span class="text-primary-900 text-2xs font-normal">Kept by</span>
                                        <img 
                                            :src="item.waiter.image ? item.waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="size-4 rounded-full"
                                        >
                                        <span class="text-primary-900 text-2xs font-normal">{{ item.waiter.full_name }}</span>
                                    </div>
                                    <span class="text-grey-900 line-clamp-1 self-stretch overflow-hidden text-ellipsis text-sm font-medium">{{ item.item_name }}</span>
                                    <div class="flex flex-nowrap gap-x-1 items-start" v-if="item.remark">
                                        <CommentIcon class="flex-shrink-0 mt-1" />
                                        <span class="text-grey-900 text-sm font-normal">{{ item.remark }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col justify-center items-end gap-3">
                                <span class="text-primary-900 text-base font-medium">{{ item.qty > item.cm ? `x ${item.qty}` : `${item.cm} cm`  }}</span>
                                <Button 
                                    :type="'button'" 
                                    :size="'md'" 
                                    :disabled="tableStatus === 'Pending Clearance'"
                                    @click="openOverlay($event, item)"
                                >
                                    Add to Order
                                </Button>
                            </div>
                        </div>
                    </div>
                </div> -->
                <div class="flex flex-col p-4 justify-center items-start gap-4 self-stretch rounded-[5px] bg-white shadow-[0_4px_15.8px_0_rgba(13,13,13,0.08)]"  v-for="item in formatKeepItems(customer.keep_items)" :key="item.id">
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
                        <div class="flex flex-col items-start gap-0.5 self-stretch">
                            <div class="flex items-center gap-3 self-stretch">
                                <span class="text-grey-500 text-sm font-normal">Expire on</span>
                                <span class="flex-[1_0_0] text-grey-950 text-sm font-normal">{{ item.expired_to ? dayjs(item.expired_to).format('DD/MM/YYYY') : '-' }}</span>
                            </div>
                            <div class="flex items-center gap-3 self-stretch">
                                <span class="text-grey-500 text-sm font-normal">Kept by</span>
                                <div class="flex items-center gap-1.5 flex-[1_0_0]">
                                    <img
                                        :src="item.waiter.image ? item.waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                        alt="WaiterImage"
                                        class="rounded-full size-3 object-contain"
                                    >
                                    <span class="flex-[1_0_0] text-grey-950 text-sm font-normal">{{ item.waiter.full_name }}</span>
                                </div>
                            </div>
                            <div class="flex items-start gap-4 self-stretch">
                                <span class="text-grey-500 text-sm font-normal">Remark</span>
                                <span class="flex-[1_0_0] text-grey-950 text-sm font-normal">{{ item.remark ? item.remark : '-' }}</span>
                            </div>
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
                            @click="openOverlay($event, item)"
                        >
                            Add to Order
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Keep item to order -->
    <OverlayPanel ref="op" @close="closeOverlay">
        <template v-if="selectedItem">
            <form novalidate @submit.prevent="submit">
                <div class="flex flex-col gap-6 w-96">
                    <div class="flex items-center justify-between">
                        <span class="text-primary-950 text-center text-md font-medium">Select Quantity</span>
                        <TimesIcon
                            class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                            @click="closeOverlay"
                        />
                    </div>
                    <div class="flex flex-col gap-y-6">
                        <div class="w-full flex gap-x-2 items-center justify-between">
                            <div class="flex gap-x-3 items-center">
                                <img 
                                    :src="selectedItem.image ? selectedItem.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt="KeepItemImage"
                                    class="rounded-[1.5px] size-[60px] object-contain"
                                >
                                <p class="text-base text-grey-900 font-medium">
                                    {{ selectedItem.item_name }}
                                </p>
                            </div>
                            <p class="text-primary-800 text-md font-medium pr-3">{{ selectedItem.qty > selectedItem.cm ? `x ${selectedItem.qty}` : `${selectedItem.cm} cm`  }}</p>
                        </div>

                        <NumberCounter
                            v-if="selectedItem.qty > 0"
                            :inputName="`return_qty`"
                            :maxValue="selectedItem.qty"
                            v-model="form.return_qty"
                        />
                    </div>

                    <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                        <Button
                            :type="'button'"
                            :variant="'tertiary'"
                            :size="'lg'"
                            @click="closeOverlay"
                        >
                            Cancel
                        </Button>
                        <Button
                            :size="'lg'"
                            :disabled="!isFormValid"
                        >
                            Add
                        </Button>
                    </div>
                </div>
            </form>
        </template>
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
    <Modal
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
    </Modal>

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
</template>
