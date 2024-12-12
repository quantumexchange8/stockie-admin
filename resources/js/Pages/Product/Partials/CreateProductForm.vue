<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import Button from '@/Components/Button.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DragDropImage from '@/Components/DragDropImage.vue'
import RadioButton from '@/Components/RadioButton.vue'
import Toggle from '@/Components/Toggle.vue'
import NumberCounter from '@/Components/NumberCounter.vue';
import InputError from "@/Components/InputError.vue";
import { PlusIcon, DeleteIcon } from '@/Components/Icons/solid';
import { redeemOptions, defaultProductItem } from '@/Composables/constants';
import { useInputValidator } from '@/Composables';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    errors: Object,
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
const isUnsavedChangesOpen = ref(false)
const open = ref(false);
const updateProductNameCounter = ref(0);

const form = useForm({
    image:'',
    bucket: false,
    product_name: '',
    price: '',
    is_redeemable: false,
    point: '0',
    category_id: '',
    items: [{ ...defaultProductItem }],
});

const formSubmit = () => { 
    form.post(route('products.store'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            form.reset();
            unsaved('leave');
        },
    })
};

const unsaved = (status) => {
    // form.reset();
    emit('close', status);
}

const addItem = () => form.items.push({ ...defaultProductItem });

const removeItem = (index) => {
    if (form.items.length === 1) {
        Object.assign(form.items[0], { ...defaultProductItem });
    } else {
        form.items.splice(index, 1);
    }
}

const updateInventoryStockCount = async (index, id) => {
    if (id != undefined) {
        try {
            const { data } = await axios.get(`/menu-management/products/getInventoryItemStock/${id}`);
            const item = form.items[index];
            item.inventory_stock_qty = data.stock_qty;
            item.status = data.status;
            item.formatted_item_name = data.formattedName;

            if (updateProductNameCounter.value === 0) {
                form.product_name = data.formattedProductName;
                updateProductNameCounter.value++;
            }
            
            if (item.bucket) {
                item.qty = data.stock_qty >= 1 ? 1 : 0;
            }
        } catch (error) {
            console.error(error);
        }
    }
}

const isFormValid = computed(() => ['product_name', 'price', 'category_id'].every(field => form[field]) && form.items.length > 0);

watch(() => form.bucket, (newValue) => {
    if (!newValue) {
        if (form.items.length > 1) {
            form.items.splice(0, 1);
        }
        form.items[0].qty = 1;
    } else {
        form.items[0].qty = 1;
    }
}, { immediate: true });

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 max-h-[calc(100dvh-18rem)] pl-1 pr-2 py-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
            <DragDropImage
                :inputName="'image'"
                :errorMessage="form.errors.image"
                v-model="form.image"
                class="col-span-full md:col-span-4 h-[372px]"
            />
            <div class="col-span-full md:col-span-8 flex flex-col items-start gap-6 flex-[1_0_0] self-stretch">
                <div class="flex items-start gap-6 self-stretch">
                    <p class="text-grey-900 font-normal text-base">This product comes in a set (Bucket Product)</p>
                    <Toggle
                        :inputName="'bucket'"
                        :checked="form.bucket"
                        v-model:checked="form.bucket"
                        class="col-span-full xl:col-span-2"
                    />
                </div>
                <div class="flex flex-col items-start self-stretch" :class="form.bucket ? 'gap-12' : 'gap-4'">
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
                                    :hintText="item.status !== 'In stock' ? item.status : ''"
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
                                v-if="form.bucket"
                                class="w-6 h-6 self-center flex-shrink-0 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                                @click="removeItem(i)"
                            />
                        </div>
                        <Button
                            v-if="form.bucket"
                            :type="'button'"
                            :variant="'secondary'"
                            :iconPosition="'left'"
                            :size="'lg'"
                            class="col-span-full"
                            @click="addItem"
                        >
                            <template #icon>
                                <PlusIcon class="size-6" />
                            </template>
                            Another Item
                        </Button>
                    </div>
                    <div class="flex flex-col items-start gap-4 self-stretch">
                        <TextInput
                            :inputId="'product_name'"
                            :labelText="form.items.length > 1 ? 'Set name' : 'Product name'"
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
                Add
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
