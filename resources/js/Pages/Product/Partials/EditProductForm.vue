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
import { redeemOptions } from '@/Composables/constants';
import { useInputValidator } from '@/Composables';
import DragDropImage from '@/Components/DragDropImage.vue';
import Modal from '@/Components/Modal.vue';

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
const emit = defineEmits(['close', 'isDirty']);
const { isValidNumberKey } = useInputValidator();

const categoryArr = ref(props.categoryArr);
const inventoriesArr = ref(props.inventoriesArr);
const isUnsavedChangesOpen = ref(false);
const initialData = ref();
const isDirty = ref(false);

const form = useForm({
    bucket: props.product.bucket === 'set' ? true : false,
    product_name: props.product.product_name,
    price: props.product.price,
    is_redeemable: !!props.product.is_redeemable,
    point: props.product.point,
    category_id: props.product.category_id,
    itemsDeletedBasket: [],
    items: props.product.product_items 
            ?   props.product.product_items.map((item) => {
                    item.formatted_item_name = `${item.inventory_item.inventory.name} - ${item.inventory_item.item_name}`;
                    return item;
                }) 
            :   [],
    image: props.product.image ? props.product.image : '',

});

initialData.value = ({
    bucket: form.bucket,
    items: props.product.product_items 
            ?   props.product.product_items.map((item) => {
                    item.formatted_item_name = `${item.inventory_item.inventory.name} - ${item.inventory_item.item_name}`;
                    item.inventory_stock_qty = item.inventory_item.stock_qty;
                    item.qty = parseInt(item.qty);
                    return item;
                }) 
            :   [],
    product_name: form.product_name,
    price: form.price,
    category_id: form.category_id,
    is_redeemable: form.is_redeemable,
})

const formSubmit = () => { 
    form.post(route('products.updateProduct', props.product.id), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            emit('close');
            form.reset();
        },
    })
};

const unsaved = (status) => {
    emit('close', status)
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
            item.qty = data.stock_qty >= 1 ? 1 : 0;
            item.formatted_item_name = data.formattedName;
        } catch (error) {
            console.error(error);
        }
    }
}

const isFormValid = computed(() => ['product_name', 'price', 'category_id'].every(field => form[field]) && form.items.length > 0);

watch(form.items, (newValue) => {
    newValue.forEach(item => {
        item.inventory_stock_qty = item.inventory_item.stock_qty;
        item.qty = parseInt(item.qty);
    });
}, { immediate: true });

watch(
    form,
    () => {
        const currentData = ({
            bucket: form.bucket,
            items: form.items,
            product_name: form.product_name,
            price: form.price,
            category_id: form.category_id,
            is_redeemable: form.is_redeemable,
        })
        isDirty.value = JSON.stringify(currentData) !== JSON.stringify(initialData.value);
        emit('isDirty', isDirty.value);

    }, { deep: true }
);
</script>

<template>
    <form class="flex flex-col gap-6" @submit.prevent="formSubmit">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 max-h-[calc(100dvh-18rem)] pl-1 pr-2 py-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
            <DragDropImage 
                :inputName="'image'"
                :remarks="'Suggested image size: 1200 x 1200 pixel'"
                :modelValue="form.image"
                :errorMessage="form.errors.image"
                v-model="form.image"
                class="col-span-full md:col-span-4 h-[372px] w-full flex items-center justify-center rounded-[5px] bg-grey-50 outline-dashed outline-2 outline-grey-200"
            />

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
                                    :labelText="'Select an item'"
                                    :inputArray="inventoriesArr"
                                    :grouped="true"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.inventory_item_id']  : ''"
                                    :dataValue="item.inventory_item_id"
                                    :hintText="item.inventory_item.status !== 'In stock' ? item.inventory_item.status : ''"
                                    v-model="item.inventory_item_id"
                                    class="[&>div:nth-child(3)]:!text-primary-700"
                                    @onChange="updateInventoryStockCount(i, $event)"
                                >
                                    <template #value>
                                        {{ item.inventory_item_id ? item.formatted_item_name : 'Select' }}
                                    </template>
                                    <template #optionGroup="group">
                                        <div class="flex flex-nowrap items-center gap-3">
                                            <!-- <div class="bg-grey-50 border border-grey-200 h-6 w-6"></div> -->
                                            <img 
                                                :src="group.group_image ? group.group_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt=""
                                                class="bg-grey-50 border border-grey-200 h-6 w-6"
                                            >
                                            <span class="text-grey-400 text-base font-bold">{{ group.group_name }}</span>
                                        </div>
                                    </template>
                                </Dropdown>
                                <InputError :message="form.errors ? form.errors['items.' + i + '.qty']  : ''" v-if="form.errors" />
                            </div>
                            <NumberCounter
                                v-if="form.bucket"
                                :labelText="'Quantity of item in this set'"
                                :inputName="'qty_' + i"
                                :minValue="1"
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

                        <div class="w-full flex flex-row items-center justify-around gap-x-4">
                            <TextInput
                                :inputId="'price'"
                                :labelText="'Price'"
                                :iconPosition="'left'"
                                :errorMessage="form.errors?.price || ''"
                                v-model="form.price"
                                @keypress="isValidNumberKey($event, true)"
                                class="[&>div>input]:text-center"
                            >
                                <template #prefix>RM</template>
                            </TextInput>
                            <Dropdown
                                :inputName="'category_id'"
                                :labelText="'Select category'"
                                :inputArray="categoryArr"
                                :errorMessage="form.errors?.category_id || ''"
                                :dataValue="form.category_id"
                                v-model="form.category_id"
                            />
                        </div>
                        <RadioButton
                            :optionArr="redeemOptions"
                            :checked="form.is_redeemable"
                            v-model:checked="form.is_redeemable"
                        />
                        <TextInput  
                            v-if="form.is_redeemable"
                            :inputId="'point'"
                            :labelText="'Redeemed with'"
                            :iconPosition="'right'"
                            class="!w-1/3 [&>div>input]:text-center"
                            :errorMessage="form.errors?.point || ''"
                            v-model="form.point"
                            @keypress="isValidNumberKey($event, false)"
                        >
                            <template #prefix>pts</template>
                        </TextInput>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="unsaved('close')"
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
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="unsaved('stay')"
            @leave="unsaved('leave')"
        >
        </Modal>
    </form>
</template>
