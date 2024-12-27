<script setup>
import { ref, computed, watch, onMounted } from 'vue'
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
import Message from '@/Components/Message.vue';
import Tag from '@/Components/Tag.vue';
import { DeleteIllus, UndetectableIllus } from '@/Components/Icons/illus';
import { useCustomToast } from '@/Composables/index.js';

const props = defineProps({
    selectedCategory: Object,
    categoryArr: Array
});

const emit = defineEmits(['close', 'isDirty', 'update:categories']);
const { isValidNumberKey } = useInputValidator();
const { showMessage } = useCustomToast();

const categories = ref();
const products = ref([]);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const deleteCategoryFormIsOpen = ref(false);

const form = useForm({
    items: [],
});

const showDeleteGroupForm = () => {
    deleteCategoryFormIsOpen.value = true;
};

const hideDeleteCategoryForm = () => {
    deleteCategoryFormIsOpen.value = false;
};

const unsaved = (status) => {
    emit('close', status);
};

const submit = async () => {
    form.processing = true;

    try {
        const { data } = await axios.post(`/menu-management/products/category/reassignProductsCategory/${props.selectedCategory.value}`, form);
    
        // hideDeleteCategoryForm();
        emit('update:categories', data);
        emit('isDirty', false);
        unsaved('leave');

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Selected product category has been deleted.',
                detail: 'Products in this category have been moved to different categories.',
            });
        }, 200);
    } catch (error) {
        console.error(error);
    } finally {
        form.processing = false;
    }

    // form.post(route('products.store'), {
    //     preserveScroll: true,
    //     preserveState: 'errors',
    //     onSuccess: () => {
    //         form.reset();
    //         unsaved('leave');
    //     },
    // })
};

const getCategoryProducts = async () => {
    try {
        const { data } = await axios.get(`/menu-management/products/category/getCategoryProducts/${props.selectedCategory.value}`);
        products.value = data;

        // Clear form items before pushing new ones
        form.items = [];

        products.value.forEach(product => {
            form.items.push({
                product_id: product.id,
                new_category_id: null
            });
        });
    } catch (error) {
        console.error(error);
    }
}

// const addEditedProduct = (product_id, category_id) => {
//     form.items.push({
//         product_id: product_id,
//         new_category_id: category_id
//     });
// };

// const getFormItemByProductId = (id) => {
//     console.log(form.items);
//     return form.items.find((item) => item.product_id === id);
// };

onMounted(() => getCategoryProducts());

const isFormValid = computed(() => {
    if (!products.value.length) return true; // If no products, form is valid
    if (form.processing) return false; // If form is processing, it's not valid
    
    // Check if we have all required selections
    return form.items.every(item => item.new_category_id !== null);
});

// Watch for form changes only after initial data is loaded
watch(() => form.items, (newValue) => {
    if (newValue.some((item) => item.new_category_id != null)) {
        emit('isDirty', form.isDirty);
    }
}, { deep: true });

watch(() => props.categoryArr, (newValue) => {
    categories.value = newValue && props.selectedCategory ? newValue.filter((item) => item.text !== props.selectedCategory.text && item.text !== 'All') : [];
}, { immediate: true });
// watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <div class="flex flex-col gap-6">
        <template v-if="products.length > 0">
            <Message 
                v-if="products.length > 0"
                severity="warn" 
                :title="`${products.length} items need to be re-assigned to another category`" 
            >
                <template #content>
                    <p class="text-sm font-normal text-yellow-950">To proceed with removing this category, please assign a new category to each product.</p>
                </template>
            </Message>

            <div class="flex flex-col max-h-[calc(100dvh-24rem)] divide-y divide-grey-100 pl-1 pr-2 py-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
                <div class="grid grid-cols-12 gap-3 items-center py-3" v-for="(product, index) in products" :key="index">
                    <div class="col-span-7 grid grid-cols-12 gap-x-3 items-start">
                        <img 
                            :src="product.image ? product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="OrderItemImage"
                            class="col-span-3 size-[60px] rounded-[1.5px] border-[0.3px] border-grey-100 object-contain"
                        >
                        <div class="col-span-9 flex flex-col gap-y-1 items-start justify-center self-stretch">
                            <Tag value="Set" v-if="product.bucket === 'set'"/>
                            <p class="text-base font-medium text-grey-900 self-stretch">
                                {{ product.product_name }}
                            </p>
                        </div>
                    </div>
                    <Dropdown
                        placeholder="Select Category"
                        :inputName="'new_category_id_' +  index"
                        :inputArray="categories"
                        :errorMessage="form.errors ? form.errors['items.' + index + '.new_category_id']  : ''"
                        class="col-span-5"
                        v-model="form.items.find((item) => item.product_id === product.id).new_category_id"
                    />
                </div>
            </div>
        </template>

        <template v-else>
            <div class="flex flex-col items-center justify-center gap-5">
                <UndetectableIllus />
                <span class="text-primary-900 text-sm font-medium">There are no products to reassign...</span>
            </div>
        </template>

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
                :type="'button'"
                :size="'lg'"
                :disabled="!isFormValid"
                @click="showDeleteGroupForm"
            >
                Save & Delete Category
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
    </div>
    
    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="deleteCategoryFormIsOpen"
        @close="hideDeleteCategoryForm"
        :withHeader="false"
    >
        <form novalidate @submit.prevent="submit">
            <div class="w-full flex flex-col gap-9" >
                <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] pt-6 mx-[-24px] mt-[-24px]">
                    <DeleteIllus />
                </div>
                <div class="flex flex-col gap-1" >
                    <span class="text-primary-900 text-lg font-medium text-center self-stretch" >
                        Delete this category?
                    </span>
                    <span class="text-grey-900 text-center text-base font-medium self-stretch" >
                        Are you sure you want to delete this category? This action cannot be undone.
                    </span>
                </div>
                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="hideDeleteCategoryForm"
                    >
                        Keep
                    </Button>
                    <Button
                        variant="red"
                        size="lg"
                        type="submit"
                        :disabled="form.processing"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
</template>
