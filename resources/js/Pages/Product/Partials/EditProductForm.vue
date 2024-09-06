<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import Button from '@/Components/Button.vue'
import Dropdown from '@/Components/Dropdown.vue'
import RadioButton from '@/Components/RadioButton.vue'
import Toggle from '@/Components/Toggle.vue'
import NumberCounter from '@/Components/NumberCounter.vue';
import InputError from "@/Components/InputError.vue";
import { DeleteIcon } from '@/Components/Icons/solid';
import { keepOptions } from '@/Composables/constants';

const props = defineProps({
    errors: Object,
    product: {
        type: Object,
        default: () => {},
    },
    categoryArr: {
        type: Array,
        default: () => [],
    },
    inventoriesArr: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const categoryArr = ref(props.categoryArr);
const inventoriesArr = ref(props.inventoriesArr);

const form = useForm({
    image:'',
    bucket: props.product.bucket === 'set' ? true : false,
    product_name: props.product.product_name,
    price: props.product.price,
    point: props.product.point,
    category_id: props.product.category_id,
    keep: props.product.keep,
    itemsDeletedBasket: [],
    items: props.product.product_items ? props.product.product_items : [],
});

const formSubmit = () => { 
    form.put(route('products.updateProduct', props.product.id), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            form.reset();
            emit('close');
            
        },
    })
};

const cancelForm = () => {
    form.reset();
    emit('close');
}

// need to update this to delete the actual item from db
const deleteItem = (index, itemId) => {
    form.itemsDeletedBasket.push(itemId);

    form.items.splice(index, 1);
}

const updateInventoryStockCount = async (index, id) => {
    if (id != undefined) {
        try {
            const { data } = await axios.get(`/menu-management/products/getInventoryItemStock/${id}`);
            const item = form.items[index];
            item.inventory_stock_qty = data.stock_qty;
            item.qty = data.stock_qty >= 2 ? 2 : 0;
        } catch (error) {
            console.error(error);
        }
    }
}

// Check and allows only the following keypressed
const isNumber = (e, withDot = true) => {
    const keysAllowed = withDot ? '0123456789.' : '0123456789';
    if (!keysAllowed.includes(e.key)) {
        e.preventDefault();
    }
};

const isFormValid = computed(() => {
    return ['product_name', 'price', 'point', 'category_id', 'keep'].every(field => form[field]) && form.items.length > 0;
});

watch(() => form.items, (newValue) => {
    newValue.forEach(item => {
        item.inventory_stock_qty = item.inventory_item.stock_qty;
        item.qty = parseInt(item.qty);
    });
}, { immediate: true });
</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 max-h-[650px] pl-1 pr-2 py-1 overflow-y-scroll scrollbar-thin scrollbar-webkit">
            <div class="col-span-full md:col-span-4 h-[372px] w-full flex items-center justify-center rounded-[5px] bg-grey-50 outline-dashed outline-2 outline-grey-200"></div>
            <div class="col-span-full md:col-span-8 flex flex-col items-start gap-6 flex-[1_0_0] self-stretch">
                <div class="flex items-start gap-6 self-stretch">
                    <p class="text-grey-900 font-normal text-base">This product comes in a set (Bucket Product)</p>
                    <Toggle
                        :inputName="'bucket'"
                        :checked="form.bucket"
                        :disabled="true"
                        v-model:checked="form.bucket"
                        class="col-span-full xl:col-span-2"
                    />
                </div>
                <div class="flex flex-col items-start self-stretch gap-4">
                    <div class="flex flex-col items-start gap-6 self-stretch">
                        <div 
                            v-for="(item, i) in form.items" :key="i"
                            class="flex items-start justify-center gap-6 self-stretch" 
                        >
                            <div class="col-span-full xl:col-span-4 flex flex-col w-full">
                                <Dropdown
                                    :inputName="'inventory_item_id_' +  i"
                                    :labelText="'Select item'"
                                    :inputArray="inventoriesArr"
                                    :grouped="true"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.inventory_item_id']  : ''"
                                    :dataValue="item.inventory_item_id"
                                    v-model="item.inventory_item_id"
                                    @onChange="updateInventoryStockCount(i, $event)"
                                />
                                <InputError :message="form.errors ? form.errors['items.' + i + '.qty']  : ''" v-if="form.errors" />
                            </div>
                            <NumberCounter
                                v-if="form.bucket"
                                :labelText="'Quantity of item in this set'"
                                :inputName="'qty_' + i"
                                :minValue="form.bucket ? 2 : 1"
                                :maxValue="item.inventory_stock_qty"
                                v-model="item.qty"
                                class="!w-fit whitespace-nowrap"
                            />
                            <DeleteIcon
                                v-if="form.bucket && form.items.length > 1"
                                class="w-6 h-6 self-center flex-shrink-0 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                                @click="deleteItem(i, item.id)"
                            />
                        </div>
                    </div>
                    <div class="flex flex-col items-start gap-4 self-stretch">
                        <TextInput
                            :inputId="'product_name'"
                            :labelText="form.items.length > 1 ? 'Set Name' : 'Product Name'"
                            :placeholder="'eg: Heineken Light 500ml'"
                            :errorMessage="form.errors?.product_name || ''"
                            v-model="form.product_name"
                            class="col-span-full xl:col-span-4"
                        />

                        <div class="w-full grid grid-cols-1 sm:grid-cols-12 gap-4">
                            <TextInput
                                :inputId="'price'"
                                :labelText="'Price'"
                                :iconPosition="'left'"
                                :errorMessage="form.errors?.price || ''"
                                v-model="form.price"
                                @keypress="isNumber($event)"
                                class="col-span-full sm:col-span-4 [&>div>input]:text-center"
                            >
                                <template #prefix>RM</template>
                            </TextInput>
                            <TextInput
                                :inputId="'point'"
                                :labelText="'Points can be earned'"
                                :iconPosition="'right'"
                                :errorMessage="form.errors?.point || ''"
                                v-model="form.point"
                                @keypress="isNumber($event)"
                                class="col-span-full sm:col-span-4 [&>div>input]:text-center"
                            >
                                <template #prefix>pts</template>
                            </TextInput>
                            <Dropdown
                                :inputName="'category_id'"
                                :labelText="'Select category'"
                                :inputArray="categoryArr"
                                :errorMessage="form.errors?.category_id || ''"
                                :dataValue="form.category_id"
                                v-model="form.category_id"
                                class="col-span-full sm:col-span-4"
                            />
                        </div>
                        <div class="flex items-start gap-10">
                            <RadioButton
                                :optionArr="keepOptions"
                                :checked="form.keep"
                                v-model:checked="form.keep"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="cancelForm"
            >
                Cancel
            </Button>
            <Button
                :size="'lg'"
                :disabled="!isFormValid"
            >
                Save Changes
            </Button>
        </div>
    </form>
</template>
