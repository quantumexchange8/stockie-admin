<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import Button from '@/Components/Button.vue';
import { useCustomToast } from '@/Composables/index.js';
import Tag from '@/Components/Tag.vue';
import Checkbox from '@/Components/Checkbox.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import Textarea from '@/Components/Textarea.vue';
import TextInput from '@/Components/TextInput.vue';
import RadioButton from '@/Components/RadioButton.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DateInput from "@/Components/Date.vue";
import Toggle from '@/Components/Toggle.vue';
import { Calendar } from "@/Components/Icons/solid";
import dayjs from 'dayjs';

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
const userId = computed(() => page.props.auth.user.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close']);

const keepTypes = [
    { text: 'in quantity', value: 'qty' },
    { text: 'in cm', value: 'cm' },
];

const expiryPeriodOptions = [
    { text: "1 month starting from today", value: 1 },
    { text: "3 months starting from today", value: 3 },
    { text: "6 months starting from today", value: 6 },
    { text: "12 months starting from today", value: 12 },
    { text: "Customise range...", value: 0 },
];

const form = useForm({
    user_id: userId.value,
    customer_id: props.order.customer_id,
    order_id: props.order.id,
    items: [],
});
    
const formSubmit = () => { 
    form.post(route('orders.items.keep'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'Item kept successfully.',
                    detail: "You can always refer back the keep item in 'Customer detail'.",
                });
            }, 200);
            form.reset();
            emit('close');
        },
    })
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
    });
});

const getKeptQuantity = (subItem) => {
    return subItem.keep_items?.reduce((totalKeeps, keepItem) => totalKeeps + parseInt(keepItem.oldest_keep_history.qty) + (parseFloat(keepItem.oldest_keep_history.cm) > 0 ? 1 : 0), 0) ?? 0;
};

const getTotalKeptQuantity = (item) => {
    return item.type === 'Normal' 
            ? item.sub_items?.reduce((total, subItem) => total + getKeptQuantity(subItem), 0 ) ?? 0
            : item.keep_item.keep_histories?.reduce((total, history) => total + parseInt(history.qty) + (parseFloat(history.cm) > 0 ? 1 : 0), 0) ?? 0;
};

const getTotalServedQty = (item) => {
    let count = 0;
    const totalServedQty = [];

    item.sub_items.forEach((subItem) => {
        const servedQty = Math.ceil(subItem.serve_qty / subItem.item_qty);
        count++;

        return servedQty > 0 || count > 1 ? totalServedQty.push(servedQty) : totalServedQty.push(0);
    });

    return totalServedQty.length > 0 ? Math.max(...totalServedQty) : 0;
};

const getLeftoverQuantity = (item) => {
    if (!item.sub_items || item.sub_items.length === 0) return item.item_qty;
    
    const untouchedQty = item.item_qty - getTotalServedQty(item);

    return untouchedQty > 0 ? untouchedQty : 0;
};

const addItemToList = (item) => {
    const index = form.items.findIndex(i => i.order_item_id === item.id);

    if (index !== -1) {
        // Remove all subitems if order item is unchecked
        form.items = form.items.filter(i => i.order_item_id !== item.id);
    } else {
        item.sub_items.forEach((subItem) => {
            form.items.push({
                order_item_id: item.id,
                order_item_subitem_id: subItem.id,
                type: item.type === 'Normal' 
                        ? 'qty' 
                        : parseFloat(item.keep_item.oldest_keep_history.qty) > parseFloat(item.keep_item.oldest_keep_history.cm) 
                            ? 'qty'
                            : 'cm',
                amount: item.type === 'Normal' 
                        ? (subItem.item_qty * item.item_qty) - getKeptQuantity(subItem) 
                        : parseFloat(item.keep_item.oldest_keep_history.qty) > parseFloat(item.keep_item.oldest_keep_history.cm) 
                            ? (subItem.item_qty * item.item_qty) - getTotalKeptQuantity(item)
                            : item.keep_item.oldest_keep_history.cm,
                remark: item.type === 'Normal' ? '' : item.keep_item.remark ? item.keep_item.remark : '',
                expiration: item.type === 'Normal' ? false : item.keep_item.expired_from ? true : false,
                expired_period: item.type === 'Normal' ? '' : item.keep_item.expired_from ? 0 : '',
                date_range: item.type === 'Normal' ? '' : item.keep_item.expired_from ? [dayjs(item.keep_item.expired_from).toDate(), dayjs(item.keep_item.expired_to).toDate()] : '',
                expired_from: item.type === 'Normal' ? '' : item.keep_item.expired_from ? dayjs(item.keep_item.expired_from).format('YYYY-MM-DD') : '',
                expired_to: item.type === 'Normal' ? '' : item.keep_item.expired_to ? dayjs(item.keep_item.expired_to).format('YYYY-MM-DD') : '',
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

const getKeepItemName = (item) => {
    var itemName = '';
    item.sub_items.forEach(subItem => {
        itemName = item.product.product_items.find(productItem => productItem.id === subItem.product_item_id).inventory_item.item_name;
    });
    if (itemName) return itemName;
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
</script>

<template>
    <form novalidate @submit.prevent="formSubmit">
        <div class="flex flex-col gap-y-6 items-start rounded-[5px]">
            <div class="flex flex-col justify-center items-start gap-y-3 px-6 py-3 w-full">
                <div class="flex flex-col gap-y-3 py-6 justify-center items-center w-full bg-[rgba(255,_249,_249,_0.90)] rounded-[5px]">
                    <div class="bg-primary-700 rounded-full size-20"></div>
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
                                        <div class="p-2 size-[60px] bg-primary-100 rounded-[1.5px] border-[0.3px] border-grey-100"></div>
                                        <div class="flex flex-col gap-y-2 items-start justify-center self-stretch">
                                            <div class="flex flex-nowrap gap-x-2 items-center">
                                                <Tag value="Set" v-if="item.product.bucket === 'set' && item.type === 'Normal'"/>
                                                <p class="text-base font-medium text-grey-900 truncate flex-shrink">{{ item.type === 'Normal' ? item.product.product_name : getKeepItemName(item) }}</p>
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
                                                    :maxValue="totalSubItemQty(item, subItem) - (item.type === 'Normal' ? getKeptQuantity(subItem) : getTotalKeptQuantity(item))"
                                                    :errorMessage="form.errors ? form.errors['items.' + index + '.amount']  : ''"
                                                    :disabled="totalSubItemQty(item, subItem) === (item.type === 'Normal' ? getKeptQuantity(subItem) : getTotalKeptQuantity(item))"
                                                    v-model="form.items.find(i => i.order_item_subitem_id === subItem.id).amount"
                                                    class="!w-36"
                                                />
                                                <TextInput
                                                    v-if="form.items.find(i => i.order_item_subitem_id === subItem.id).type === 'cm'"
                                                    :inputName="`item_${subItem.id}.amount`"
                                                    :iconPosition="'right'"
                                                    :errorMessage="form.errors ? form.errors['items.' + index + '.amount']  : ''"
                                                    :disabled="totalSubItemQty(item, subItem) === (item.type === 'Normal' ? getKeptQuantity(subItem) : getTotalKeptQuantity(item))"
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
        </div>
    </form>
</template>
    