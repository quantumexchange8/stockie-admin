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
import { Calendar, DefaultIcon, TimesIcon, WarningIcon } from "@/Components/Icons/solid";
import dayjs from 'dayjs';
import { UndetectableIllus } from '@/Components/Icons/illus';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import NotAllowedToKeep from './NotAllowedToKeep.vue';
import InputError from '@/Components/InputError.vue';

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
const isMessageShown = ref(true);
const allCustomers = ref([]);
const notAllowedOverlay = ref(null);
const { isValidNumberKey } = useInputValidator();

const fetchProducts = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('orders.getTableKeepItem', props.selectedTable.id));
        items.value = response.data.uniqueOrders;
        allCustomers.value = response.data.allCustomers.map(customer => ({
            text: customer.full_name,
            value: customer.id,
            image: customer.image,
            details: [
                { key: 'email', value: customer.email },
                { key: 'phone', value: formatPhone(customer.phone) }
            ]
        }));
        notAllowToKeep.value = items.value
            .flatMap(item => {
                // Map through all order items
                return item.order_items.map(orderItem => {
                    // Filter sub-items that are not 'Active'
                    const inactiveSubItems = orderItem.sub_items.filter(
                        subItem => subItem.product_item.inventory_item.keep !== 'Active'
                    );

                    // If there are any inactive sub-items, return a new order item with those sub-items
                    if (inactiveSubItems.length > 0) {
                        return {
                            ...orderItem,
                            sub_items: inactiveSubItems,
                        };
                    }

                    return null; // Return null if no inactive sub-items
                }).filter(orderItem => orderItem !== null); // Remove null entries
            });

    } catch (error) {
        console.error(error);
        items.value = [];
        notAllowToKeep.value = [];
    } finally {
        isLoading.value = false;
    }
};

const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();

const emit = defineEmits(['close', 'update:customerKeepItems']);

const keepTypes = [
    { text: 'in quantity', value: 'qty' },
    { text: 'in cm', value: 'cm' },
];

const expiryPeriodOptions = [
    { text: "1 month starting from today", value: 1 },
    { text: "2 months starting from today", value: 2 },
    { text: "3 months starting from today", value: 3 },
    { text: "4 months starting from today", value: 4 },
    { text: "5 months starting from today", value: 5 },
    { text: "6 months starting from today", value: 6 },
    { text: "Customise range...", value: 0 },
];

const form = useForm({
    user_id: userId.value,
    current_customer_id: props.order.customer_id,
    customer_id: props.order.customer_id,
    current_order_id: props.order.id,
    order_id: props.order.id,
    items: [],
});
    
const submit = async () => { 
    form.processing = true;
    try {
        const response = await axios.post(`/order-management/orders/addItemToKeep`, form);
        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Item kept successfully.',
                detail: "You can always refer back the keep item in 'Customer detail'.",
            });
        }, 200);

        emit('update:customerKeepItems', response.data);
        emit('close');
        form.clearErrors()
        form.reset();
    } catch (error) {
        form.setError(error.response.data.errors);
    } finally {
        form.processing = false;
    }
};

const pendingServeItems = computed(() => {
    if (!props.order || !props.order.order_items) return [];

    return props.order.order_items.map((item) => {
        // Filter and calculate `total_qty` and `total_served_qty` within a single pass
        const filteredSubItems = item.sub_items.filter((subItem) => 
            item.product.product_items.some((product_item) => 
                subItem.product_item_id === product_item.id && product_item.inventory_item.keep === 'Active'
            )
        );

        // Calculate total quantities
        const total_qty = filteredSubItems.reduce((total, sub_item) => total + (item.item_qty * sub_item.item_qty), 0);
        const total_served_qty = filteredSubItems.reduce((total, sub_item) => total + sub_item.serve_qty, 0);

        return {
            ...item,
            sub_items: filteredSubItems,
            total_qty,
            total_served_qty
        };
    })
    .filter((item) => item.sub_items.length > 0)
    .filter((undeleted) => undeleted.status !== 'Cancelled');
});


const getKeptQuantity = (subItem) => {
    return subItem.keep_items?.reduce((totalKeeps, keepItem) => totalKeeps + parseInt(keepItem.oldest_keep_history.qty) + (parseFloat(keepItem.oldest_keep_history.cm) > 0 ? 1 : 0), 0) ?? 0;
};

const getTotalKeptQuantity = (item) => {
    return item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' 
            ? item.sub_items?.reduce((total, subItem) => total + getKeptQuantity(subItem), 0 ) ?? 0
            : item.keep_item.keep_histories?.reduce((total, history) => total + parseInt(history.qty) + (parseFloat(history.cm) > 0 ? 1 : 0), 0) ?? 0;
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

const addItemToList = (item) => {
    const index = form.items.findIndex(i => i.order_item_id === item.id);

    if (index !== -1) {
        // Remove all subitems if order item is unchecked
        // form.items = form.items.filter(i => i.order_item_id !== item.id);
        form.items = [];
    } else {
        form.items = [];
        form.order_id = item.order_id;
        item.sub_items.forEach((subItem) => {
            form.items.push({
                order_item_id: item.id,
                order_item_subitem_id: subItem.id,
                type: item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward'
                        ? 'qty' 
                        : parseFloat(item.keep_item.oldest_keep_history.qty) > parseFloat(item.keep_item.oldest_keep_history.cm) 
                            ? 'qty'
                            : 'cm',
                // amount: item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' 
                //         ? (subItem.item_qty * item.item_qty) - getKeptQuantity(subItem) 
                //         : parseFloat(item.keep_item.oldest_keep_history.qty) > parseFloat(item.keep_item.oldest_keep_history.cm) 
                //             ? (subItem.item_qty * item.item_qty) - getTotalKeptQuantity(item)
                //             : item.keep_item.oldest_keep_history.cm,
                amount: subItem.product_item.inventory_item.keep === 'Active' ?
                        item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' 
                        ? (subItem.item_qty * item.item_qty) - getKeptQuantity(subItem) 
                        : parseFloat(item.keep_item.oldest_keep_history.qty) > parseFloat(item.keep_item.oldest_keep_history.cm) 
                            ? (subItem.item_qty * item.item_qty) - getTotalKeptQuantity(item)
                            : item.keep_item.oldest_keep_history.cm : 0,
                remark: item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' ? '' : item.keep_item.remark ? item.keep_item.remark : '',
                expiration: item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' ? false : item.keep_item.expired_from ? true : false,
                expired_period: item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' ? '' : item.keep_item.expired_from ? 0 : '',
                date_range: item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' ? '' : item.keep_item.expired_from ? [dayjs(item.keep_item.expired_from).toDate(), dayjs(item.keep_item.expired_to).toDate()] : '',
                expired_from: item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' ? '' : item.keep_item.expired_from ? dayjs(item.keep_item.expired_from).format('YYYY-MM-DD') : '',
                expired_to: item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' ? '' : item.keep_item.expired_to ? dayjs(item.keep_item.expired_to).format('YYYY-MM-DD') : '',
                keep_id: item.type === 'Keep' ? item.keep_item_id : null
            });
        });
    }
}

const totalSubItemQty = (item, subItem) => {
    if (!item) return 0;
    return subItem.item_qty * item.item_qty;  // Multiply subitem qty by the order item's qty
};

const updateKeepAmount = (item, type) => {
    item.amount = type === 'cm' ? (item.amount).toString() : parseInt(item.amount);
}

const isNumber = (e, withDot = true, item) => {
    const { key, target: { value } } = e;
    const maxAllowedValue = item.type === 'keep' || (item.type === 'Keep' && parseFloat(item.keep_item.oldest_keep_history.cm) > parseFloat(item.keep_item.oldest_keep_history.qty)) ? item.keep_item.oldest_keep_history.cm : null;
    
    if (/^\d$/.test(key)) {
        // Check if entering the next digit exceeds the max value
        const newValue = value + key;
        if (maxAllowedValue && parseFloat(newValue) > maxAllowedValue) {
            e.target.value = maxAllowedValue;
        }
        return;
    }

    if (withDot && key === '.' && /\d/.test(value) && !value.includes('.')) return;
    
    e.preventDefault();
};

// Function to check if the input exceeds max on change
const checkMaxValue = (value, item, subitem_id) => {
    const validatedValue = value.replace(/\D+/g, '').replace(/^0+/, '');
    const formItem = form.items.find(i => i.order_item_subitem_id === subitem_id);

    // Check if there is an old history and set maxValue accordingly
    const maxValue = item.keep_item?.oldest_keep_history?.cm ?? null;

    // If maxValue is null (no history), set formItem.amount to an empty string or some default value
    if (maxValue === null) {
        formItem.amount = validatedValue || '0'; // or any default value you want to set
        return; // Exit the function as no further checks are needed
    }
    
    const maxValueStr = maxValue.toString();

    // Remove leading zeros and trim to max digit length
    let sanitizedValue = validatedValue.slice(0, maxValueStr.length);

    // Set to maxValue if sanitizedValue exceeds maxValue numerically
    formItem.amount = parseInt(sanitizedValue, 10) > maxValue ? maxValueStr : sanitizedValue || '0';
};

// Function to update textarea value for all subitems
const updateRemarkForSubitems = (orderItemId, remark) => {
    form.items.forEach((item) => {
        if (item.order_item_id === orderItemId) {
            item.remark = remark;
        }
    });
};

const toggleExpiration = (orderItemId, expiration) => {
    form.items.forEach((item) => {
        if (item.order_item_id === orderItemId) {
            item.expiration = expiration;
            if (!expiration) {
                item.expired_period = '';
                item.date_range = '';
                item.expired_from = '';
                item.expired_to = '';
            }
        }
    });
};

const updateValidPeriod = (orderItemId, option) => {
    form.items.forEach((item) => {
        if (item.order_item_id === orderItemId) {
            item.expired_from = option === 0  || typeof option === 'object' 
                                ? dayjs(option[0]).format('YYYY-MM-DD') 
                                : dayjs().format('YYYY-MM-DD');
            item.expired_to = option === 0 || typeof option === 'object' 
                                ? dayjs(option[1]).format('YYYY-MM-DD') 
                                : dayjs().add(option, 'month').format('YYYY-MM-DD');
        }
    });
}

const isAllNotKept = (item) => {
    return item.sub_items.every((sub_item) => sub_item.product_item.inventory_item.keep === 'Inactive');
}

const getKeepItemName = (item) => {
    var itemName = '';
    item.sub_items.forEach(subItem => {
        itemName = item.product.product_items.find(productItem => productItem.id === subItem.product_item_id).inventory_item.item_name;
    });
    if (itemName) return itemName;
};

onMounted(() => {
    fetchProducts();
});

</script>

<template>
    <form novalidate @submit.prevent="submit">
        <!-- <div class="flex flex-col gap-y-6 items-start rounded-[5px]">
            <div class="flex flex-col justify-center items-start gap-y-3 px-6 py-3 w-full">
                <div class="flex flex-col gap-y-3 py-6 justify-center items-center w-full bg-[rgba(255,_249,_249,_0.90)] rounded-[5px]">
                    <img 
                        :src="order.customer.image ? order.customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt=""
                        class="rounded-full size-20 object-contain"
                    >
                    <div class="flex flex-col gap-y-2 items-center">
                        <p class="text-primary-900 text-base font-semibold">{{ order.customer.full_name }}</p>
                        <div class="flex items-center justify-center gap-x-2">
                            <p class="text-primary-950 text-sm font-medium">{{ order.customer.email }}</p>
                            <span class="text-grey-200">&#x2022;</span>
                            <p class="text-primary-950 text-sm font-medium">({{ formatPhone(order.customer.phone) }})</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-y-2 self-stretch max-h-[calc(100dvh-29rem)] pr-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
                    <template v-if="pendingServeItems.length > 0">
                        <div class="flex flex-col divide-y-[0.5px] divide-grey-200">
                            <div class="flex flex-col gap-y-2 py-3" v-for="(item, index) in pendingServeItems" :key="index">
                                <div class="flex justify-between items-center gap-x-3">
                                    <div class="flex flex-nowrap gap-x-3 items-center">
                                        <img 
                                            :src="item.product.image ? item.product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt="OrderItemImage"
                                            class="size-[60px] rounded-[1.5px] border-[0.3px] border-grey-100 object-contain"
                                        >
                                        <div class="flex flex-col gap-y-2 items-start justify-center self-stretch">
                                            <div class="flex flex-nowrap gap-x-2 items-center">
                                                <Tag value="Set" v-if="item.product.bucket === 'set' && item.type === 'Normal'"/>
                                                <p class="text-base font-medium text-grey-900 truncate flex-shrink">{{ item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' ? item.product.product_name : getKeepItemName(item) }}</p>
                                            </div>
                                            <div class="flex flex-nowrap gap-x-2 items-center">
                                                <p class="text-sm font-medium text-primary-950">{{ item.product.bucket === 'set' ? 'Quantity in set:' : 'Quantity:' }} {{ item.item_qty }}</p>
                                                <p class="text-sm font-medium text-green-800">Served: {{ getTotalServedQty(item) }}</p>
                                                <p class="text-sm font-medium text-primary-700">Left: {{ getLeftoverQuantity(item) }}</p>
                                                <p class="text-sm font-medium text-blue-600" v-if="item.product.bucket === 'single' || item.type === 'Keep'">Kept: {{ getTotalKeptQuantity(item) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <Checkbox 
                                        :checked="false"
                                        :disabled="getTotalKeptQuantity(item) >= item.sub_items.reduce((total, subItem) => total + totalSubItemQty(item, subItem), 0)"
                                        @change="addItemToList(item)"
                                    />
                                </div>
                                
                                <template v-if="form.items.find(i => i.order_item_id === item.id)">
                                    <template v-for="(subItem, index) in item.sub_items" :key="index">
                                        <div class="flex flex-col self-stretch gap-y-2 py-3">
                                            <div class="flex justify-between items-center self-stretch" v-if="item.product.bucket === 'set'">
                                                <p class="text-base text-grey-900 font-medium">
                                                    {{ item.product.product_items.find(i => i.id === subItem.product_item_id).inventory_item.item_name }}
                                                </p>
                                                <div class="flex flex-nowrap gap-x-9 items-center">
                                                    <p class="text-primary-900 text-xs font-medium">Served: {{ subItem.serve_qty }}/{{ subItem.item_qty * item.item_qty }}</p>
                                                    <p class="text-xs font-medium text-blue-600">Kept: {{ getKeptQuantity(subItem) }}</p>
                                                </div>
                                            </div>

                                            <div class="flex justify-between items-center">
                                                <div class="flex items-start gap-6">
                                                    <RadioButton
                                                        :optionArr="keepTypes"
                                                        :checked="form.items.find(i => i.order_item_subitem_id === subItem.id).type"
                                                        :disabled="totalSubItemQty(item, subItem) === getKeptQuantity(subItem) || item.type === 'Keep'"
                                                        v-model:checked="form.items.find(i => i.order_item_subitem_id === subItem.id).type"
                                                        @onChange="updateKeepAmount(form.items.find(i => i.order_item_subitem_id === subItem.id), $event)"
                                                    />
                                                </div>
    
                                                <NumberCounter
                                                    v-if="form.items.find(i => i.order_item_subitem_id === subItem.id).type === 'qty'"
                                                    :inputName="`item_${subItem.id}.amount`"
                                                    :maxValue="totalSubItemQty(item, subItem) - (item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' ? getKeptQuantity(subItem) : getTotalKeptQuantity(item))"
                                                    :errorMessage="form.errors ? form.errors['items.' + index + '.amount']  : ''"
                                                    :disabled="totalSubItemQty(item, subItem) === (item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' ? getKeptQuantity(subItem) : getTotalKeptQuantity(item))"
                                                    v-model="form.items.find(i => i.order_item_subitem_id === subItem.id).amount"
                                                    class="!w-36"
                                                />
                                                <TextInput
                                                    v-if="form.items.find(i => i.order_item_subitem_id === subItem.id).type === 'cm'"
                                                    :inputName="`item_${subItem.id}.amount`"
                                                    :iconPosition="'right'"
                                                    :errorMessage="form.errors ? form.errors['items.' + index + '.amount']  : ''"
                                                    :disabled="totalSubItemQty(item, subItem) === (item.type === 'Normal' || item.type === 'Redemption' || item.type === 'Reward' ? getKeptQuantity(subItem) : getTotalKeptQuantity(item))"
                                                    v-model="form.items.find(i => i.order_item_subitem_id === subItem.id).amount"
                                                    @keypress="isNumber($event, true, item)"
                                                    @update:modelValue="checkMaxValue($event, item, subItem.id)"
                                                    class="!w-36"
                                                >
                                                    <template #prefix>cm</template>
                                                </TextInput>
                                            </div>
                                        </div>
                                    </template>

                                    <Textarea
                                        inputName="remark"
                                        labelText="Remark"
                                        :rows="5"
                                        :maxCharacters="60" 
                                        :errorMessage="form.errors ? form.errors['items.' + index + '.remark']  : ''"
                                        :disabled="item.type === 'Keep'"
                                        class="col-span-full xl:col-span-4 [&>div>label:first-child]:text-xs [&>div>label:first-child]:font-medium [&>div>label:first-child]:text-grey-900"
                                        v-model="form.items.find(i => i.order_item_id === item.id).remark"
                                        @input="updateRemarkForSubitems(item.id, $event.target.value)"
                                    />
                                    
                                    <div class="justify-end flex gap-3">
                                        <Toggle
                                            :checked="form.items.find(i => i.order_item_id === item.id).expiration"
                                            :inputName="'expiration'"
                                            inputId="expiration"
                                            :disabled="item.type === 'Keep'"
                                            v-model="form.items.find(i => i.order_item_id === item.id).expiration"
                                            @change="toggleExpiration(item.id, $event.target.checked)"
                                        />
                                        <p class="text-base text-grey-900 font-normal">
                                            With Keep Expiration Date
                                        </p>
                                    </div>

                                    <template v-if="form.items.find(i => i.order_item_id === item.id).expiration">
                                        <div class="flex flex-col gap-3 items-start">
                                            <Dropdown
                                                :placeholder="'Select'"
                                                :inputArray="expiryPeriodOptions"
                                                :dataValue="form.items.find(i => i.order_item_id === item.id).expired_period"
                                                :inputName="'expired_period_' + index"
                                                :disabled="item.type === 'Keep'"
                                                labelText="Expire Date Range"
                                                inputId="expired_period"
                                                v-model="form.items.find(i => i.order_item_id === item.id).expired_period"
                                                @onChange="updateValidPeriod(item.id, $event)"
                                                :iconOptions="{
                                                    'Customise range...': Calendar,
                                                }"
                                            />

                                            <DateInput
                                                v-if="form.items.find(i => i.order_item_id === item.id).expired_period === 0"
                                                :labelText="''"
                                                :inputName="'date_range_' + index"
                                                :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                                :range="true"
                                                :errorMessage="form.errors ? form.errors['items.' + index + '.expired_to']  : ''"
                                                :disabled="item.type === 'Keep'"
                                                @onChange="updateValidPeriod(item.id, $event)"
                                                v-model="form.items.find(i => i.order_item_id === item.id).date_range"
                                            />
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-base font-medium text-grey-900">No pending item can be kept.</p>
                    </template>
                </div>
            </div>

            <div class="fixed bottom-0 w-full flex flex-col px-6 pt-6 pb-12 justify-center gap-y-6 self-stretch bg-white">
                <p class="self-stretch text-grey-900 text-right text-md font-medium">{{ form.items.length }} item selected</p>
                <Button
                    size="lg"
                    :disabled="form.items.length === 0 || form.processing"
                >
                    Done
                </Button>
            </div>
        </div> -->

        <div class="flex flex-col p-5 items-start gap-5 flex-[1_0_0] self-stretch max-h-[calc(100dvh-5rem)] overflow-y-auto scrollbar-thin scrollbar-webkit" v-if="items.length">
            <!-- message container -->
            <div class="flex flex-col p-3 justify-center items-start gap-3 self-stretch rounded-[5px] bg-[#FDFBED]"
                v-if="notAllowToKeep.length > 0 && isMessageShown"
                @click.prevent.stop="openNotAllowOverlay($event)"
            >
                <!-- message header -->
                <div class="flex items-start gap-3 self-stretch">
                    <div class="flex items-start gap-3 flex-[1_0_0]">
                        <WarningIcon />
                        <div class="flex flex-col justify-between items-start flex-[1_0_0]">
                            <span class="self-stretch text-[#A35F1A] text-base font-bold">Item(s) not allowed to keep.</span>
                            <span class="self-stretch text-[#3E200A] text-sm font-normal"><span class="!font-bold">{{ notAllowToKeep.length }} items</span> will be removed from keep item listing.</span>
                        </div>
                    </div>
                    <TimesIcon class="!text-[#6E3E19] cursor-pointer" @click.prevent.stop="isMessageShown = false"/>
                </div>

                <!-- image container -->
                <div class="flex mx-9 items-start gap-3 self-stretch overflow-x-auto">
                    <img 
                        :src="item.product?.image ? item.product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt=""
                        class="size-[60px] object-contain bg-white"
                        v-for="item in notAllowToKeep" :key="item.id"
                    >
                </div>
            </div>

            <!-- item container -->
             <template v-for="item in items" :key="item.id">
                <div class="flex flex-col pt-4 pb-2 items-start gap-4 self-stretch rounded-[5px] bg-white shadow-[0_4px_15.8px_0_rgba(13,13,13,0.08)] "
                v-if="item.order_items.some(orderItem =>
                        orderItem.sub_items.some(subItem => subItem.product_item.inventory_item.keep !== 'Inactive')
                    )"
                >
                    <!-- item header -->
                    <div class="flex px-4 justify-center items-center gap-2.5 self-stretch">
                        <div class="w-1.5 h-[53px] bg-red-800"></div>
                        <!-- item details -->
                        <div class="flex flex-col items-start gap-1 flex-[1_0_0]">
                            <span class="line-clamp-1 self-stretch text-grey-950 text-ellipsis text-lg font-bold">{{ item.order_no }}</span>
                            <!-- item info -->
                            <div class="flex items-center gap-3 self-stretch">
                                <div class="flex items-center gap-2">
                                    <img
                                        :src="item.customer.image ? item.customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                        alt="CustomerIcon"
                                        class="size-5 rounded-[20px]"
                                        v-if="item.customer"
                                    >
                                    <DefaultIcon class="size-5" v-else />
                                    <span class="text-grey-900 text-base font-normal">{{ item.customer ? item.customer.full_name : 'Guest' }}</span>
                                    <span class="text-grey-200">&#x2022;</span>
                                    <span class="text-grey-900 text-base font-normal">{{ item.order_time }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- item list -->
                    <div class="flex flex-col w-full px-4 items-start self-stretch divide-y-[0.5px] divide-grey-200">
                        <div class="flex flex-col items-start gap-2 self-stretch" v-for="(order_item, index) in item.order_items">
                            <template v-if="!isAllNotKept(order_item)">
                                <div class="flex py-3 items-center gap-8 self-stretch">
                                    <div class="flex items-center gap-3 !justify-between flex-[1_0_0]">
                                        <img
                                            :src="order_item.product?.image ? order_item.product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'"
                                            alt="ItemImage"
                                            class="size-[60px] object-contain"
                                        >
                                        <div class="flex flex-col justify-center items-start gap-2 flex-[1_0_0]">
                                            <div class="flex items-center gap-2 self-stretch">
                                                <Tag 
                                                    :variant="'default'"
                                                    :value="'Set'"
                                                    v-if="order_item.product.bucket === 'set'"
                                                />
                                                <span class="line-clamp-1 self-stretch text-ellipsis text-base font-medium gap-2">
                                                    {{ order_item.product.product_name }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 self-stretch">
                                                <span class="text-base font-semibold"
                                                    :class="getTotalKeptQuantity(order_item) === getTotalServedQty(order_item) ? '  text-green-500' : 'text-primary-900'"
                                                >
                                                    {{ getTotalKeptQuantity(order_item) }}/{{ getTotalServedQty(order_item) }} item kept
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <RadioButton 
                                        :name="'item'"
                                        :dynamic="false"
                                        :value="order_item"
                                        class="!w-fit"
                                        :errorMessage="''"
                                        v-model:checked="form.item"
                                    /> -->
                                    <Checkbox 
                                        :checked="form.items.find(i => i.order_item_id === order_item.id) !== undefined"
                                        v-tooltip.left="{ value: getTotalKeptQuantity(order_item) === getTotalServedQty(order_item) ?  'You’ve reached the max keep quantity.' : '' }"
                                        :disabled="getTotalKeptQuantity(order_item) === getTotalServedQty(order_item)"
                                        @change="addItemToList(order_item)"
                                    />
                                </div>
                            </template>
                            <div class="flex flex-col px-3 pb-5 items-start gap-3 self-stretch" v-if="form.items.find(i => i.order_item_id === order_item.id)">
                                <div class="flex flex-col py-2 justify-end items-start gap-3 self-stretch" v-for="sub_item in order_item.sub_items">
                                    <template v-if="sub_item.product_item.inventory_item.keep === 'Active'">
                                        <span class="self-stretch text-grey-900 text-base font-medium" v-if="order_item.product.bucket === 'set'">{{ sub_item.product_item.inventory_item.item_name }}</span>
                                        <div class="flex flex-col mx-[-12px] items-start gap-2.5 self-stretch">
                                            <div class="flex justify-between items-center self-stretch">
                                                <RadioButton
                                                    :optionArr="keepTypes"
                                                    :checked="form.items.find(i => i.order_item_subitem_id === sub_item.id).type"
                                                    :disabled="totalSubItemQty(order_item, sub_item) === getKeptQuantity(sub_item) || order_item.type === 'Keep'"
                                                    v-model:checked="form.items.find(i => i.order_item_subitem_id === sub_item.id).type"
                                                    @onChange="updateKeepAmount(form.items.find(i => i.order_item_subitem_id === sub_item.id), $event)"
                                                />

                                                <NumberCounter 
                                                    :minValue="0"
                                                    :maxValue="totalSubItemQty(order_item, sub_item) - (order_item.type === 'Normal' || order_item.type === 'Redemption' || order_item.type === 'Reward' ? getKeptQuantity(sub_item) : getTotalKeptQuantity(order_item))"
                                                    v-model="form.items.find(i => i.order_item_subitem_id === sub_item.id).amount"
                                                    v-if="form.items.find(i => i.order_item_subitem_id === sub_item.id).type === 'qty'"
                                                    class="!w-36"
                                                />

                                                <TextInput
                                                    :iconPosition="'right'"
                                                    v-model="form.items.find(i => i.order_item_subitem_id === sub_item.id).amount"
                                                    v-if="form.items.find(i => i.order_item_subitem_id === sub_item.id).type === 'cm'"
                                                    :disabled="totalSubItemQty(order_item, sub_item) === (order_item.type === 'Normal' || order_item.type === 'Redemption' || order_item.type === 'Reward' ? getKeptQuantity(sub_item) : getTotalKeptQuantity(order_item))"
                                                    @keypress="isValidNumberKey($event, true)"
                                                    @update:modelValue="checkMaxValue($event, order_item, sub_item.id)"
                                                    class="!w-36"
                                                />
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                
                                <Dropdown 
                                    :inputArray="allCustomers"
                                    :labelText="'Keep for'"
                                    :dataValue="props.order.customer_id"
                                    v-model="form.customer_id"
                                    imageOption
                                    withDescription
                                >
                                    <template #description="slotProps">
                                        <div class="flex items-center gap-2 self-stretch">
                                            <span class="text-grey-500 text-sm font-normal ">{{ allCustomers[slotProps.index].details[0].value }}</span>
                                            <span class="text-grey-300 ">&#x2022;</span>
                                            <span class="text-grey-500 text-sm font-normal ">{{ allCustomers[slotProps.index].details[1].value }}</span>
                                        </div>
                                    </template>
                                </Dropdown>

                                <Textarea
                                    inputName="remark"
                                    labelText="Remark"
                                    v-model="form.items.find(i => i.order_item_id === order_item.id).remark"
                                    :rows="5"
                                    :maxCharacters="60" 
                                    :disabled="order_item.type === 'Keep'"
                                    class="col-span-full xl:col-span-4 [&>div>label:first-child]:text-xs [&>div>label:first-child]:font-medium [&>div>label:first-child]:text-grey-900"
                                    @input="updateRemarkForSubitems(order_item.id, $event.target.value)"
                                />

                                <!-- <div class="flex justify-end items-center gap-3 self-stretch">
                                    <p class="text-base text-grey-900 font-normal">
                                        With Keep Expiration Date
                                    </p>
                                    <Toggle
                                        :checked="form.items.find(i => i.order_item_id === order_item.id).expiration"
                                        :inputName="'expiration'"
                                        inputId="expiration"
                                        :disabled="order_item.type === 'Keep'"
                                        v-model="form.items.find(i => i.order_item_id === order_item.id).expiration"
                                        @change="toggleExpiration(order_item.id, $event.target.checked)"
                                    />
                                </div> -->

                                <!-- <template v-if="form.items.find(i => i.order_item_id === order_item.id).expiration"> -->
                                    <div class="flex flex-col gap-3 w-full">
                                        <Dropdown
                                            :placeholder="'Select'"
                                            :inputArray="expiryPeriodOptions"
                                            :dataValue="form.items.find(i => i.order_item_id === order_item.id).expired_period"
                                            :inputName="'expired_period_' + index"
                                            :disabled="order_item.type === 'Keep'"
                                            labelText="Expire Date Range"
                                            inputId="expired_period"
                                            v-model="form.items.find(i => i.order_item_id === order_item.id).expired_period"
                                            @onChange="updateValidPeriod(order_item.id, $event)"
                                            :iconOptions="{
                                                'Customise range...': Calendar,
                                            }"
                                        />

                                        <DateInput
                                            v-if="form.items.find(i => i.order_item_id === order_item.id).expired_period === 0"
                                            :labelText="''"
                                            :inputName="'date_range_' + index"
                                            :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                            :range="true"
                                            :disabled="order_item.type === 'Keep'"
                                            @onChange="updateValidPeriod(order_item.id, $event)"
                                            v-model="form.items.find(i => i.order_item_id === order_item.id).date_range"
                                        />
                                        <InputError :message="form.errors ? form.errors['items.0.expired_from'][0]  : ''" v-if="form.errors && form.errors['items.0.expired_from']" />
                                    </div>
                                <!-- </template> -->
                            </div>
                            <Button
                                :variant="'primary'"
                                :size="'lg'"
                                :disabled="form.processing"
                                v-if="form.items.find(i => i.order_item_id === order_item.id)"
                            >
                                Keep
                            </Button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="flex w-full flex-col items-center justify-center gap-5" v-else>
            <UndetectableIllus />
            <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
        </div>
    </form>

    <OverlayPanel ref="notAllowedOverlay" class="!max-h-[calc(100dvh-18rem)] !overflow-y-auto !scrollbar-thin !scrollbar-webkit">
        <NotAllowedToKeep 
            :item="notAllowToKeep"
            @close="closeNotAllowOverlay()"
        />
    </OverlayPanel>
</template>
    