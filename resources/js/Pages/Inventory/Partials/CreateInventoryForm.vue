<script setup>
import axios from 'axios';
import { ref, computed, onMounted, watch } from 'vue'
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import Button from '@/Components/Button.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DragDropImage from '@/Components/DragDropImage.vue'
import { DeleteIcon, PlusIcon } from '@/Components/Icons/solid';
import { keepOptions, defaultInventoryItem } from '@/Composables/constants';
import RadioButton from '@/Components/RadioButton.vue';
import { useCustomToast, useInputValidator } from '@/Composables/index.js';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    errors: Object,
    itemCategoryArr: {
        type: Array,
        default: () => [],
    },
    categoryArr: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['addAsProducts', 'close', 'isDirty', 'update:rows']);

const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const groupCreatedModalIsOpen = ref(false);
const addAsProductModalIsOpen = ref(false);
const newInventoryData = ref({});
const isUnsavedChangesOpen = ref(false);
// const inventoryToAdd = ref({});

const form = useForm({
    name: '',
    // category_id: '',
    image: '',
    items: [{ ...defaultInventoryItem }],
});

const showGroupCreatedModal = () => groupCreatedModalIsOpen.value = true;

const hideGroupCreatedModal = () => groupCreatedModalIsOpen.value = false;

// const showAddAsProductModal = () => {
//     // inventoryToAdd.value = {
//     //     inventory_name: form.name,
//     //     inventory_items: form.items
//     // };
//     setTimeout(() => addAsProductModalIsOpen.value = true, 200);
//     setTimeout(() => emit('close'), 1000);
// };

// const hideAddAsProductModal = () => addAsProductModalIsOpen.value = false;

// const validateItemCodes = () => {
//     // Reset item_code errors
//     form.errors = { ...form.errors }; // Ensure reactivity

//     const codeMap = new Map();

//     // Build a map of item_code occurrences with their indices
//     form.items.forEach((item, index) => {
//         const code = item.item_code?.trim();
//         if (code) {
//             if (!codeMap.has(code)) {
//                 codeMap.set(code, []);
//             }
//             codeMap.get(code).push(index);
//         }
//     });

//     // Update errors for duplicates except the first occurrence
//     codeMap.forEach((indices) => {
//         if (indices.length > 1) {
//             indices.slice(1).forEach((index) => {
//                 form.errors[`items.${index}.item_code`] = 'This field must have a unique code.';
//             });
//         }
//     });
// };

// const validateForm = () => {
//     // Clear all previous errors
//     form.errors = {};

//     // Run validations
//     validateItemCodes();

//     // Return whether the form is valid
//     return Object.keys(form.errors).length === 0;
// };

const close = (status) => {
    emit('close', 'create', status);
}

const formSubmit = async () => { 
    // if (!validateForm()) return;

    form.post(route('inventory.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: async () => {
            try {
                // Fetch the latest inventory
                const response = await axios.get('/inventory/inventory/getLatestInventory');

                // Display success message
                showMessage({
                    severity: 'success',
                    summary: 'Group added successfully.',
                    detail: 'You can always add new stock to this group.',
                });

                // Reset the form and emit necessary events
                form.reset();
                // form.value = {
                //     name: '',
                //     image: '',
                //     items: [],
                // };
                close('leave');
                emit('update:rows', response.data.inventories);
                emit('addAsProducts', response.data.latestInventory);
            } catch (error) {
                if (error.response) {
                    console.error('An unexpected error occurred:', error.response.data.errors);
                }
            }
        },
    });

    
    // try { 
    //     const response = await axios.post(route('inventory.store'), {
    //             name: form.name,
    //             image: form.image,
    //             items: form.items
    //         }, { headers: { 'Content-Type': 'multipart/form-data' } }
    //     );

    //     // let inventoryToAdd = {
    //     //     inventory_name: form.name,
    //     //     inventory_image: form.image,
    //     //     inventory_items: form.items.map((item) => {
    //     //         item.unit = props.itemCategoryArr.find((itemCat) => item.item_cat_id === itemCat.value).text;
    //     //         return item;
    //     //     })
    //     // };

    //     setTimeout(() => {
    //         showMessage({ 
    //             severity: 'success',
    //             summary: 'Group added successfully.',
    //             detail: 'You can always add new stock to this group.',
    //         });
    //     }, 200);

    //     emit('close');
    //     form.reset();
    //     emit('addAsProducts', response.data);
    // } catch (error) {
    //     // Check for validation errors in the response
    //     if (error.response) {
    //         form.errors = error.response.data.errors;
    //     } else {
    //         console.error('An unexpected error occurred:', error);
    //     }
    // } finally {

    // }
};

// const cancelForm = () => {
//     emit('close');
//     setTimeout(() => form.reset(), 200);
// }


const requiredFields = ['name', 'image', 'items'];

const isFormValid = computed(() => requiredFields.every(field => form[field]) && !form.processing);

const addItem = () => form.items.push({ ...defaultInventoryItem });

const removeItem = (index) => {
    if (form.items.length === 1) {
        Object.assign(form.items[0], { ...defaultInventoryItem });
    } else {
        form.items.splice(index, 1);
    }
}

// watch(
//     () => form.items.map(item => item.item_code),
//     () => validateForm(), // Runs all validations, not just item codes
//     { deep: true }
// );

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pl-1 pr-2 py-1 max-h-[calc(100dvh-18rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
            <DragDropImage
                :inputName="'image'"
                :errorMessage="form.errors?.image || ''"
                v-model="form.image"
                class="col-span-full md:col-span-4 h-[372px]"
            />
            <div class="col-span-full md:col-span-8 flex flex-col items-start gap-8 flex-[1_0_0] self-stretch">
                <TextInput
                    :inputName="'name'"
                    :labelText="'Group name'"
                    :placeholder="'e.g. Beer'"
                    :errorMessage="form.errors?.name || ''"
                    v-model="form.name"
                />
                <!-- <div class="grid grid-cols-1 md:grid-cols-12 gap-3 self-stretch">
                    <Dropdown
                        :inputName="'category_id'"
                        :labelText="'Category'"
                        :inputArray="categoryArr"
                        :errorMessage="form.errors?.category_id || ''"
                        v-model="form.category_id"
                        class="col-span-full md:col-span-4"
                    />
                </div> -->
                <div class="flex flex-col items-end gap-y-8 self-stretch">
                    <div class="flex flex-col gap-4 self-stretch" v-for="(item, i) in form.items" :key="i">
                        <div class="flex flex-row items-center justify-between self-stretch">
                            <p class="text-grey-950 font-semibold text-md">Item {{ i + 1 }}</p>
                            <DeleteIcon class="size-6 self-center flex-shrink-0 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer" @click="removeItem(i)" />
                        </div>

                        <div class="flex flex-col items-start gap-y-3 self-stretch">
                            <div class="flex flex-row items-start gap-x-3 self-stretch">
                                <TextInput
                                    :inputName="'item_'+ i +'_name'"
                                    :labelText="'Name'"
                                    :placeholder="'e.g. Danish Pilsner'"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.item_name'] : ''"
                                    v-model="item.item_name"
                                />
                                <Dropdown
                                    :inputName="'item_'+ i +'_cat_id'"
                                    :labelText="'Unit'"
                                    :inputArray="props.itemCategoryArr"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.item_cat_id'] : ''"
                                    v-model="item.item_cat_id"
                                />
                            </div>
                            <div class="flex flex-row items-start justify-around gap-x-3 self-stretch">
                                <TextInput
                                    :inputName="'item_'+ i +'_code'"
                                    :labelText="'Code'"
                                    :placeholder="'e.g. C0001'"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.item_code'] : ''"
                                    v-model="item.item_code"
                                />
                                <TextInput
                                    :inputName="'item_'+ i +'_stock_qty'"
                                    :labelText="'Current stock'"
                                    :placeholder="'e.g. 100'"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.stock_qty'] : ''"
                                    v-model="item.stock_qty"
                                    @keypress="isValidNumberKey($event, false)"
                                />
                                <TextInput
                                    :inputName="'item_'+ i +'_low_stock_qty'"
                                    :labelText="'Show low stock at'"
                                    :placeholder="'e.g. 25'"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.low_stock_qty'] : ''"
                                    v-model="item.low_stock_qty"
                                    @keypress="isValidNumberKey($event, false)"
                                />
                            </div>
                            <RadioButton
                                :optionArr="keepOptions"
                                :checked="item.keep"
                                :errorMessage="form.errors ? form.errors['items.' + i + '.keep'] : ''"
                                v-model:checked="item.keep"
                            />
                        </div>
                    </div>
                </div>
                <Button
                    :type="'button'"
                    :variant="'secondary'"
                    :iconPosition="'left'"
                    :size="'lg'"
                    class="col-span-full"
                    @click="addItem"
                    >
                    <template #icon>
                        <PlusIcon class="size-6 flex-shrink-0" />
                    </template>
                    New Item
                </Button>
            </div>
        </div>
        <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="close('close')"
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
            @close="close('create', 'stay')"
            @leave="close('create', 'leave')"
        />
    </form>
</template>
