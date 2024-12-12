<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import Button from '@/Components/Button.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DragDropImage from '@/Components/DragDropImage.vue'
import Toggle from '@/Components/Toggle.vue'
import NumberCounter from '@/Components/NumberCounter.vue';
import InputError from "@/Components/InputError.vue";
import { PlusIcon, DeleteIcon } from '@/Components/Icons/solid';
import { keepOptions, defaultProductItem } from '@/Composables/constants';
import { useInputValidator, useCustomToast } from '@/Composables';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    inventoryToAdd: Object,
    categoryArr: Array,
});

const emit = defineEmits(['close', 'isDirty']);
const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const categoryArr = ref(props.categoryArr);
const inventoryToAdd = ref(props.inventoryToAdd);
const open = ref(false);
const updateProductNameCounter = ref(0);
const isUnsavedChangesOpen = ref(false);
// const inventoriesArr = ref([{ text: , value: }]);

const getInventoryItemArr = (item) => {
    return [{text: '', value: item.inventory_item_id}];
};

const form = useForm({
    items: inventoryToAdd.value?.inventory_items?.map((item) => {
        return {
            bucket: false,
            formattedItemName: `${inventoryToAdd.value.name} - ${item.item_name}`,
            product_name: `${item.item_name} (${item.item_category.name})`,
            image: inventoryToAdd.value.inventory_image,
            price: '',
            is_redeemable: false,
            point: '0',
            category_id: '',
            qty: 1,
            inventory_item_id: item.id
        };
    }) ?? [],
});

const formSubmit = () => { 
    form.post(route('products.storeFromInventoryItems'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            showMessage({
                severity: 'success',
                summary: 'Products has been successfully added to your menu.',
            });
            form.reset();
            emit('close');
        },
    })
};

const cancelForm = () => {
    form.reset();
    emit('close');
}

const unsaved = (status) => {
    emit('close', 'add-as-product', status);
}

// const addItem = () => form.items.push({ ...defaultProductItem, qty: 2 });

const removeItem = (index) => {
    if (form.items.length > 1) form.items.splice(index, 1);
}


// const updateInventoryStockCount = async (index, id) => {
//     if (id != undefined) {
//         try {
//             const { data } = await axios.get(`/menu-management/products/getInventoryItemStock/${id}`);
//             const item = form.items[index];
//             item.inventory_stock_qty = data.stock_qty;
//             item.status = data.status;
//             item.formatted_item_name = data.formattedName;

//             if (updateProductNameCounter.value === 0) {
//                 form.product_name = data.formattedProductName;
//                 updateProductNameCounter.value++;
//             }
            
//             if (item.bucket) {
//                 item.qty = data.stock_qty >= 2 ? 2 : 0;
//             }
//         } catch (error) {
//             console.error(error);
//         }
//     }
// }

const isFormValid = computed(() => {
    return ['product_name', 'price', 'category_id'].every(field => form.items.every((item) => item[field])) 
            && form.items.length > 0
            && !form.processing;
});

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="flex flex-col items-start gap-6 self-stretch max-h-[650px] pl-1 pr-2 py-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
            <!-- <DragDropImage
                :inputName="'image'"
                :errorMessage="form.errors.image"
                v-model="form.image"
                class="col-span-full md:col-span-4 h-[372px]"
            />  -->
            <div class="flex flex-col items-start gap-y-6 self-stretch">
                <div class="flex flex-col gap-y-4 self-stretch" v-for="(item, i) in form.items" :key="i">
                    <div class="flex flex-row items-center justify-between self-stretch">
                        <p class="text-grey-950 font-semibold text-md">Item {{ i + 1 }}</p>
                        <DeleteIcon 
                            v-if="form.items.length > 1"
                            class="size-6 self-center flex-shrink-0 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer" 
                            @click="removeItem(i)" 
                        />
                    </div>

                    <div class="flex flex-row gap-x-6 self-stretch">
                        <img 
                            :src="inventoryToAdd.inventory_image ? inventoryToAdd.inventory_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="Inventory Image"
                            width="142"
                            height="142"
                            class="object-contain"
                        >
                        <div class="w-full flex flex-col gap-4 self-stretch">
                            <div class="w-full flex flex-row items-start gap-x-4 self-stretch">
                                <Dropdown
                                    :inputName="'inventory_item_id_' +  i"
                                    :labelText="'Select an item'"
                                    :inputArray="getInventoryItemArr(item)"
                                    grouped
                                    disabled
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.inventory_item_id']  : ''"
                                    :hintText="item.status !== 'In stock' ? item.status : ''"
                                    v-model="item.inventory_item_id"
                                    class="[&>div:nth-child(3)]:!text-primary-700"
                                    @onChange="updateInventoryStockCount(i, $event)"
                                >
                                    <template #value>
                                        {{ item.formattedItemName }}
                                    </template>
                                    <template #optionGroup="group">
                                        <div class="flex flex-nowrap items-center gap-3">
                                            <!-- <div class="bg-grey-50 border border-grey-200 h-6 w-6"></div> -->
                                            <!-- <img 
                                                :src="group.group_image ? group.group_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt=""
                                                class="bg-grey-50 border border-grey-200 h-6 w-6"
                                            > -->
                                            <span class="text-grey-400 text-base font-bold">{{ group }}</span>
                                        </div>
                                    </template>
                                </Dropdown>
                                <!-- <TextInput
                                    :inputName="'item_name_' +  i"
                                    :labelText="form.items.length > 1 ? 'Set name' : 'Product name'"
                                    :placeholder="'eg: Heineken Light 500ml'"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.item_name']  : ''"
                                    disabled
                                    v-model="item.item_name"
                                /> -->
                                <TextInput
                                    :inputId="'product_name_' +  i"
                                    :labelText="'Product name'"
                                    :placeholder="'eg: Heineken Light 500ml'"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.product_name']  : ''"
                                    v-model="item.product_name"
                                />
                            </div>
                            <div class="w-full flex flex-row items-start gap-x-4 self-stretch">
                                <TextInput
                                    :inputId="'price_' +  i"
                                    :labelText="'Price'"
                                    :iconPosition="'left'"
                                    :placeholder="'0.00'"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.price']  : ''"
                                    v-model="item.price"
                                    @keypress="isValidNumberKey($event, true)"
                                    class="[&>div>input]:text-left"
                                >
                                    <template #prefix>RM</template>
                                </TextInput>
                                <Dropdown
                                    :inputName="'category_id_' +  i"
                                    :labelText="'Select category'"
                                    :inputArray="categoryArr"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.category_id']  : ''"
                                    v-model="item.category_id"
                                />
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-span-full xl:col-span-4 flex flex-col w-full">
                        <Dropdown
                            :inputName="'inventory_item_id_' +  i"
                            :labelText="'Select an item'"
                            :inputArray="getInventoryItemArr(item)"
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
                        </Dropdown>
                        <InputError :message="form.errors ? form.errors['items.' + i + '.qty']  : ''" v-if="form.errors" />
                    </div>
                    <NumberCounter
                        v-if="form.bucket"
                        :labelText="'Quantity of item in this set'"
                        :inputName="'qty_' + i"
                        :minValue="form.bucket === true ? 2 : 1"
                        :maxValue="item.inventory_stock_qty"
                        v-model="item.qty"
                        class="!w-fit whitespace-nowrap"
                    /> -->
                </div>
                <!-- <Button
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
                </Button> -->
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
        />
    </form>
</template>
