<script setup>
import { ref, onMounted, watch, computed, getCurrentScope } from 'vue';
import Tag from '@/Components/Tag.vue';
import Button from '@/Components/Button.vue';
import AddOrderItems from './AddOrderItems.vue';
import RightDrawer from '@/Components/RightDrawer/RightDrawer.vue';
import { CircledArrowHeadRightIcon2, CommentIcon, CrownImage, DeleteIcon, EmptyProfilePic, GiftImage, HistoryIcon, MedalImage, PlusIcon, TimesIcon } from '@/Components/Icons/solid.jsx';
import axios from 'axios';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import KeepHistory from './KeepHistory.vue';

const props = defineProps({
    customer: {
        type: Object,
        default: () => {}
    },
    orderId: Number,
    tableStatus: String
})

const emit = defineEmits(['close', 'fetchZones']);

const page = usePage();
const userId = computed(() => page.props.auth.user.id)

const drawerIsVisible = ref(false);
const viewType = ref(null);
const customer = ref(props.customer);
const categoryArr = ref([]);
const op = ref(null);
const selectedItem = ref();

const form = useForm({
    order_id: props.orderId,
    user_id: userId.value,
    type: '',
    return_qty: 0,
});

const openDrawer = (action) => {
    viewType.value = action;

    if (!drawerIsVisible.value) {
        drawerIsVisible.value = true;
    }
};

const closeDrawer = () => {
    drawerIsVisible.value = false;
    viewType.value = null;
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

const closeKeepItemDetails = () => {
    closeOverlay();
    setTimeout(() => {
        emit('fetchZones');
        emit('close');
    }, 300);
}

const formSubmit = () => { 
    form.post(route('orders.customer.keep.addKeptItemToOrder', selectedItem.value.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            closeKeepItemDetails();
        },
    })
};

onMounted(async() => {
});

const formatKeepItems = (keepItems) => {
    return keepItems.map((item) => {
        item.qty = parseFloat(item.qty);
        item.cm = parseFloat(item.cm);
        return item;
    }).sort((a, b) => dayjs(b.created_at).diff(dayjs(a.created_at)));
};

const formatPhone = (phone) => {
    if (!phone) return phone; 
    
    // Remove the '+6' prefix if it exists
    if (phone.startsWith('+6')) phone = phone.slice(2);

    // Slice the number into parts
    const totalLength = phone.length;
    const cutPosition = totalLength - 4;

    const firstPart = phone.slice(0, 2);
    const secondPart = phone.slice(2, cutPosition)
    const lastFour = phone.slice(cutPosition);

    return `${firstPart} ${secondPart} ${lastFour}`;
};

const formatPoints = (points) => {
  const pointsStr = points.toString();
  
  return pointsStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const isFormValid = computed(() => {
    return ['type', 'return_qty'].every(field => form[field]) && !form.processing;
});
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
        </template>
        <template v-else>
        </template>
    </RightDrawer>

    <div class="w-full flex flex-col gap-6 items-start rounded-[5px] py-4 pr-1 max-h-[calc(100dvh-23rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <div class="w-full flex flex-col items-center gap-3 pt-6">
            <img 
                :src="customer.image" 
                alt=""
                v-if="customer.image"
            >
            <EmptyProfilePic v-else/>
            <div class="w-full flex flex-col items-center gap-2">
                <span class="text-primary-900 text-base font-semibold">{{ customer.full_name }}</span>
                <div class="flex justify-center items-center gap-2">
                    <span class="text-primary-950 text-sm font-medium">{{ customer.email }}</span>
                    <span class="w-1 h-1 rounded-full bg-grey-300"></span>
                    <span class="text-primary-950 text-sm font-medium">(+60 {{ formatPhone(customer.phone) }})</span>
                </div>
            </div>
        </div>

        <!-- current points and current tier -->
        <div class="w-full flex justify-center items-start gap-6 self-stretch">
            <div class="flex flex-col p-4 items-start gap-3 flex-[1_0_0] rounded-[5px] bg-gradient-to-br from-primary-900 to-[#5E0A0E] relative">
                <div class="w-full flex justify-between items-center">
                    <span class="text-base font-semibold text-primary-25 whitespace-nowrap w-full">Current Points</span>
                    <CircledArrowHeadRightIcon2
                        class="w-6 h-6 text-primary-900 [&>rect]:fill-primary-25 [&>rect]:hover:fill-primary-800 cursor-pointer z-10"
                        @click="" />
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
                        @click="" />
                </div>
                <div class="w-full flex flex-col justify-center items-start gap-1 self-stretch">
                    <div v-if="customer.rank.name !== 'No Tier'">
                        <div class="flex flex-col justify-center items-center gap-[10px]">
                            <img 
                                :src="customer.rank.image ? customer.rank.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt=""
                                class="size-[48px]"
                            >
                        </div>
                        <div class="flex flex-col justify-center items-center gap-2">
                            <span class="text-primary-900 text-lg font-medium">{{ customer.rank.name }}</span>
                        </div>
                    </div>
                    <div v-else>
                        <span class="text-primary-900 text-lg font-medium"> - </span>
                    </div>
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
                <div class="flex py-3 items-center gap-3 self-stretch" v-for="item in formatKeepItems(customer.keep_items)" :key="item.id">
                    <div class="flex flex-col justify-center items-start gap-3 flex-[1_0_0]">
                        <div class="flex px-[10px] py-1 items-center gap-[10px] self-stretch">
                            <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                        </div>
                        <div class="flex items-center gap-3 self-stretch">
                            <div class="w-full flex items-start gap-3 self-stretch">
                                <!-- <div class="rounded-[1.5px] size-[60px] bg-primary-25"></div> -->
                                <img 
                                    :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="rounded-[1.5px] size-[60px]"
                                >
                                <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                                    <div class="flex items-center gap-1 self-stretch">
                                        <span class="text-grey-400 text-2xs font-normal">{{ item.expired_to ? `Expire on ${dayjs(item.expired_to).format('DD/MM/YYYY')}` : '' }}</span>
                                        <span class="w-1 h-1 bg-grey-900 rounded-full"></span>
                                        <span class="text-primary-900 text-2xs font-normal">Kept by</span>
                                        <!-- <span class="w-3 h-3 bg-red-900 rounded-full"></span> -->
                                        <img 
                                            :src="item.waiter.image ? item.waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="w-3 h-3 rounded-full"
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
                </div>
            </div>
        </div>
    </div>

    <!-- Add Keep item to order -->
    <OverlayPanel ref="op" @close="closeOverlay">
        <template v-if="selectedItem">
            <form novalidate @submit.prevent="formSubmit">
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
                                <!-- <div class="rounded-[1.5px] size-[60px] bg-primary-25"></div> -->
                                <img 
                                    :src="selectedItem.image ? selectedItem.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt=""
                                    class="rounded-[1.5px] size-[60px]"
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
</template>
