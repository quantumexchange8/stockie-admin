<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import Button from '@/Components/Button.vue';
import { useCustomToast, useInputValidator, usePhoneUtils } from '@/Composables/index.js';
import Tag from '@/Components/Tag.vue';
import Checkbox from '@/Components/Checkbox.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import Textarea from '@/Components/Textarea.vue';
import TextInput from '@/Components/TextInput.vue';
import RadioButton from '@/Components/RadioButton.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DateInput from "@/Components/Date.vue";
import Toggle from '@/Components/Toggle.vue';
import { Calendar, DefaultIcon, TimesIcon, CircledArrowHeadDownIcon } from "@/Components/Icons/solid";
import dayjs from 'dayjs';
import { UndetectableIllus } from '@/Components/Icons/illus';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import NotAllowedToKeep from './NotAllowedToKeep.vue';

const props = defineProps({
    errors: Object,
    selectedTable: {
        type: Object,
        default: () => {}
    },
    order: {
        type: Object,
        default: () => {}
    }
})

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)
const items = ref([]);
const notAllowToKeep = ref([]);
const isLoading = ref(false);
const allCustomers = ref([]);
const notAllowedOverlay = ref(null);
const collapsedSections = ref({}); // Object to keep track of each section's collapsed state

const fetchProducts = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('orders.getTableKeepHistories', props.selectedTable.id));
        items.value = response.data;

        // allCustomers.value = response.data.allCustomers.map(customer => ({
        //     text: customer.full_name,
        //     value: customer.id,
        //     image: customer.image,
        //     details: [
        //         { key: 'email', value: customer.email },
        //         { key: 'phone', value: formatPhone(customer.phone) }
        //     ]
        // }));

    } catch (error) {
        console.error(error);
        items.value = [];
        notAllowToKeep.value = [];
    } finally {
        isLoading.value = false;
    }
};

const { formatPhone } = usePhoneUtils();

const getKeptQuantity = (subItem) => {
    return subItem.keep_items?.reduce((totalKeeps, keepItem) => totalKeeps + parseInt(keepItem.oldest_keep_history.qty) + (parseFloat(keepItem.oldest_keep_history.cm) > 0 ? 1 : 0), 0) ?? 0;
};

const getTotalKeptQuantity = (item) => {
    return item.sub_items?.reduce((total, subItem) => total + getKeptQuantity(subItem), 0 ) ?? 0;
};

const getTotalServedQty = (item) => {
    let count = 0;
    item.sub_items.forEach((subItem) => {
        if(subItem.product_item.inventory_item.keep === 'Active') {
            count+= subItem.item_qty * item.item_qty;
        }
    });
    return count;
};

const getLeftoverQuantity = (item) => {
    if (!item.sub_items || item.sub_items.length === 0) return item.item_qty;
    
    const untouchedQty = item.item_qty - getTotalServedQty(item);

    return untouchedQty > 0 ? untouchedQty : 0;
};

const openNotAllowOverlay = (event) => {
    notAllowedOverlay.value.show(event);
} 

const closeNotAllowOverlay = () => {
    notAllowedOverlay.value.hide();
}

const totalSubItemQty = (item, subItem) => {
    if (!item) return 0;
    return subItem.item_qty * item.item_qty;  // Multiply subitem qty by the order item's qty
};

const getKeepItemName = (item) => {
    var itemName = '';
    item.sub_items.forEach(subItem => {
        itemName = item.product.product_items.find(productItem => productItem.id === subItem.product_item_id).inventory_item.item_name;
    });
    if (itemName) return itemName;
};

const toggleCollapse = (orderNo) => collapsedSections.value[orderNo] = !collapsedSections.value[orderNo];


onMounted(() => {
    fetchProducts();
});

</script>

<template>
    <div class="flex flex-col p-5 items-start gap-5 self-stretch max-h-[calc(100dvh-5rem)] overflow-y-auto scrollbar-thin scrollbar-webkit" v-if="items.length">
        <template v-if="items.filter((group) => group.keep_histories.length > 0).length > 0">
            <template v-for="item in items.filter((group) => group.keep_histories.length > 0)" :key="item.id">
                <div class="flex flex-col pt-4 pb-2 items-start gap-4 self-stretch rounded-[5px] bg-white shadow-[0_4px_15.8px_0_rgba(13,13,13,0.08)] ">
                    <div class="flex px-4 justify-center items-center gap-2.5 self-stretch">
                        <div class="w-1.5 h-[53px] bg-red-800"></div>
                        <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                            <span class="line-clamp-1 self-stretch text-grey-950 text-ellipsis text-lg font-bold">{{ item.order_no }}</span>
                            <div class="flex items-center gap-3 self-stretch">
                                <div class="flex items-center gap-2">
                                    <img
                                        :src="item.customer.image ? item.customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                        alt="CustomerIcon"
                                        class="size-5 rounded-[20px]"
                                        v-if="item.customer"
                                    >
                                    <DefaultIcon class="size-5" v-else />
                                    <span class="text-grey-900 text-base font-normal">{{ item.customer ? item.customer.full_name : $t('public.guest') }}</span>
                                    <span class="text-grey-200">&#x2022;</span>
                                    <span class="text-grey-900 text-base font-normal">{{ item.order_time }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col w-full px-4 items-start self-stretch">
                        <template v-for="record in item.keep_histories">
                            <div class="flex flex-col items-start gap-2 self-stretch divide-b-[0.5px] divide-grey-200">
                                <div class="flex py-3 items-center gap-8 self-stretch">
                                    <div class="flex w-full items-center gap-3 !justify-between self-stretch cursor-pointer" @click="toggleCollapse(record.id)">
                                        <div class="flex w-full items-start gap-3 self-stretch">
                                            <img
                                                :src="record.keep_item?.image ? record.keep_item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                                alt="ItemImage"
                                                class="size-[60px] object-contain"
                                            >
                                            <div class="flex flex-col justify-start items-start gap-y-1">
                                                <span class="text-md font-semibold text-primary-900" >x{{ record.qty }}</span>
                                                <span class="line-clamp-1 self-stretch text-ellipsis text-base font-medium text-grey-900">{{ record.keep_item.inventory_item_name }}</span>
                                            </div>
                                        </div>
                                        <CircledArrowHeadDownIcon :class="{ 'rotate-180': !collapsedSections[record.id] }" class="text-primary-700 transition-transform duration-300" />
                                    </div>
                                </div>

                                <transition
                                    enter-active-class="transition duration-100 ease-out"
                                    enter-from-class="transform scale-95 opacity-0"
                                    enter-to-class="transform scale-100 opacity-100"
                                    leave-active-class="transition duration-100 ease-in"
                                    leave-from-class="transform scale-100 opacity-100"
                                    leave-to-class="transform scale-95 opacity-0"
                                >
                                    <div class="grid grid-cols-10 w-full items-start" v-show="collapsedSections[record.id]">
                                        <span class="col-span-2 text-grey-500 text-sm font-normal">{{ $t('public.order.kept_for') }}</span>
                                        <div class="col-span-8 flex items-center gap-1.5 flex-[1_0_0]">
                                            <img
                                                :src="record.keep_item.customer?.image ? record.keep_item.customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                                alt="WaiterImage"
                                                class="rounded-full size-3 object-contain"
                                            >
                                            <span class="flex-[1_0_0] text-grey-950 text-sm font-normal">{{ record.keep_item.customer?.full_name ?? '-' }}</span>
                                        </div>
                                        <span class="col-span-2 text-grey-500 text-sm font-normal">{{ $t('public.expire_on') }}</span>
                                        <span class="col-span-8 flex-[1_0_0] text-grey-950 text-sm font-normal">{{ record.keep_item.expired_to ? dayjs(record.keep_item.expired_to).format('DD/MM/YYYY') : '-' }}</span>
                                        <span class="col-span-2 text-grey-500 text-sm font-normal">{{ $t('public.remark') }}</span>
                                        <span class="col-span-8 flex-[1_0_0] text-grey-950 text-sm font-normal">{{ record.keep_item.remark ? record.keep_item.remark : '-' }}</span>
                                        <div class="col-span-full flex items-center py-2">
                                            <hr class="w-full border-grey-100"></hr>
                                        </div>
                                        <span class="col-span-2 text-grey-500 text-sm font-normal">{{ $t('public.kept_by') }}</span>
                                        <div class="col-span-8 flex items-center gap-1.5 flex-[1_0_0]">
                                            <img
                                                :src="record.keep_item.waiter.image ? record.keep_item.waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                                alt="WaiterImage"
                                                class="rounded-full size-3 object-contain"
                                            >
                                            <span class="flex-[1_0_0] text-grey-950 text-sm font-normal">{{ record.keep_item.waiter.full_name }}</span>
                                        </div>
                                        <span class="col-span-2 text-grey-500 text-sm font-normal">{{ $t('public.order.kept_on') }}</span>
                                        <span class="col-span-8 flex-[1_0_0] text-grey-950 text-sm font-normal">{{ record.keep_date ? dayjs(record.keep_date).format('DD/MM/YYYY') : '-' }}</span>
                                    </div>
                                </transition>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </template>
        
        <div class="flex w-full flex-col items-center justify-center gap-5" v-else>
            <UndetectableIllus />
            <span class="text-primary-900 text-sm font-medium">{{ $t('public.empty.no_data') }}</span>
        </div>
    </div>
    <div class="flex w-full flex-col items-center justify-center gap-5" v-else>
        <UndetectableIllus />
        <span class="text-primary-900 text-sm font-medium">{{ $t('public.empty.no_data') }}</span>
    </div>
</template>
    