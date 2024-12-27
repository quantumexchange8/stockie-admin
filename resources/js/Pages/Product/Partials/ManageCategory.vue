<script setup>
import TextInput from "@/Components/TextInput.vue";
import Button from "@/Components/Button.vue";
import { useForm } from "@inertiajs/vue3";
import { nextTick, reactive, ref, watch } from "vue";
import { DeleteIcon, Menu2Icon, PlusIcon } from '@/Components/Icons/solid.jsx';
import Modal from '@/Components/Modal.vue';
import { useCustomToast } from "@/Composables";
import OverlayPanel from "@/Components/OverlayPanel.vue";
import ReassignedProductCategory from "./ReassignedProductCategory.vue";

const props = defineProps({
    categoryArr: {
        type: Array,
        default: [],
    },
});

const emit = defineEmits(["close", 'isDirty', 'update:categories']);
const { showMessage } = useCustomToast();

const selectedCategory = ref(null);
const isEditing = ref(false);
const currentCategoryId = ref(null);
const initialCategoryName = ref(null);
const inputRefs = reactive({});
const isEdited = ref(false);
const categories = ref();
const newCategoriesCounter = ref(0);
const op = ref(null);
const reassignCategoryFormIsOpen = ref(false);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);
const isAfterAction = ref(false);

const form = useForm({
    name: "",
    edit_name: "",
    id: "",
});

const newCategories = useForm({
    categories: [],
})

const addCategory = () => {
    isEditing.value = false;
    newCategoriesCounter.value++;
    newCategories.categories.push({name: '', index: newCategoriesCounter.value})
    
    //when clicked, auto focus the latest added input
    nextTick(() => {
        const inputRefName = `categories${newCategoriesCounter.value}`;
        if (inputRefs[inputRefName]) {
            inputRefs[inputRefName].focus();
        }
    });
}

const removeAddCategory = (id) => { 
    newCategories.categories = id !== 'clear' ? newCategories.categories.filter(category => category.index !== id) : []; 
    newCategoriesCounter.value = id !== 'clear' ? newCategoriesCounter.value - 1 : 0;
}

const submit = async () => {
    //only submit if its edited, else just return default state
    if(isEdited.value){
        if(isEditing.value){
            form.processing = true;

            try {
                const { data } = await axios.put(`/menu-management/products/category/updateCategory/${form.id}`, form);
                isEditing.value = false;
                form.reset();

                setTimeout(() => {
                    showMessage({
                        severity: 'success',
                        summary: 'Category name has been edited successfully.'
                    });
                }, 200);

                emit('update:categories', data);
                emit('isDirty', false);
            } catch (error) {
                console.error(error);
            } finally {
                form.processing = false;
            }

            // form.post(route('products.category.update', form.id), {
            //     preserveScroll: true,
            //     preserveState: true,
            //     onSuccess: () => {
            //         isEditing.value = false;
            //         form.reset();
            //         setTimeout(() => {
            //             showMessage({
            //                 severity: 'success',
            //                 summary: 'Category name has been edited successfully.'
            //             });
            //         }, 200);
            //         emit('update:categories', );
            //     }
            // })
        } else {
            newCategories.processing = true;

            try {
                const { data } = await axios.post('/menu-management/products/category/storeCategory', newCategories);
                newCategories.reset();

                setTimeout(() => {
                    showMessage({
                        severity: 'success',
                        summary: 'Category has been added successfully.'
                    });
                }, 200);

                emit('update:categories', data);
                emit('isDirty', false);
            } catch (error) {
                console.error(error);
            } finally {
                newCategories.processing = false;
            }

            // newCategories.post(route("products.category.store"), {
            //     preserveScroll: true,
            //     preserveState: 'errors',
            //     onSuccess: () => {
            //         form.reset();
            //         emit('close', 'leave');
            //         showMessage({
            //             severity: 'success',
            //             summary: 'Category has been added successfully.'
            //         });
            //     }
            // });
        }
    } else {
        isEditing.value = false;
    }
};

const handleDefaultClick = (event) => {
    event.stopPropagation();  
    event.preventDefault();   
};

const startEditing = (categoryArr) => {
    if (newCategoriesCounter.value == 0) {
        isEditing.value = true;
        form.edit_name = categoryArr.text;
        form.id = categoryArr.value;
        currentCategoryId.value = categoryArr.value;
    
        nextTick(() => {
            const inputRefName = `categories${categoryArr.value}`;
            if (inputRefs[inputRefName]) {
                inputRefs[inputRefName].focus();
            }
        });
    
        initialCategoryName.value = categoryArr.text;
    }
}

const openModal = (category) => {
    isEditing.value = false;
    isDirty.value = false;
    selectedCategory.value = category;
    reassignCategoryFormIsOpen.value = true;
    removeAddCategory('clear');
};

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                selectedCategory.value = null;  
                reassignCategoryFormIsOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            selectedCategory.value = null;  
            isUnsavedChangesOpen.value = false;
            reassignCategoryFormIsOpen.value = false;
            isDirty.value = false;
        }
    }
}

const updateCategories = (event) => {
    categories.value = event;
    emit('update:categories', event);
};

watch(() => props.categoryArr, (newValue) => {
    categories.value = newValue ? newValue : {};
}, { immediate: true });

watch(() => form.edit_name, (newVal) => {
    isEdited.value = newVal !== initialCategoryName.value; 
}, { immediate: true });

watch([newCategories, isEdited], ([newFormValue, newIsEdited]) => {
    emit('isDirty', newFormValue.isDirty ?? newIsEdited);
});

</script>

<template>
    <!-- <form novalidate spellcheck="false" @submit.prevent="submit"> -->
        <div class="max-h-[calc(100dvh-18rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
            <div class="w-full flex flex-col ">
                <div class="flex flex-col gap-[16px]">
                    <!-- existing categories -->
                    <div v-for="categoryArr in categories" :key="categoryArr.id" class="w-full flex flex-row text-grey-600 h-auto p-3 gap-4">
                        <div v-if="!isEditing || currentCategoryId !== categoryArr.value" class="w-full flex flex-row justify-between">
                            <div class="flex flex-row gap-[12px] w-full">
                                <svg 
                                    width="25" 
                                    height="25" 
                                    viewBox="0 0 25 25" 
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path 
                                        d="M3.0918 12.5H21.0918M3.0918 6.5H21.0918M3.0918 18.5H21.0918"
                                        stroke="currentColor" 
                                        stroke-width="2" 
                                        stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                <span class="w-full" @click="startEditing(categoryArr)">{{ categoryArr.text }}</span>
                            </div>
                            <div>
                                <DeleteIcon 
                                    class="w-6 h-6 text-primary-600 hover:text-primary-700 cursor-pointer" 
                                    @click="openModal(categoryArr)" 
                                />
                            </div>
                        </div>
                        <template v-if="isEditing && currentCategoryId === categoryArr.value">
                            <TextInput
                                :errorMessage="form.errors.edit_name"
                                :id="'category'"
                                input-name="edit_zone_name"
                                inputId="edit_name"
                                v-model="form.edit_name"
                                :ref="el => (inputRefs[`categories${categoryArr.value}`] = el)"
                                @keydown.enter.prevent.stop="submit()"
                            />
                        </template>
                    </div>

                    <!-- new categories -->
                    <div v-if="newCategories.categories.length" v-for="categories in newCategories.categories" class="flex w-full px-3 gap-3 justify-center items-center">
                        <TextInput 
                            :placeholder="'eg: Beer'" 
                            inputId="name" 
                            type="'text'" 
                            v-model="categories.name"
                            :ref="el => (inputRefs[`categories${categories.index}`] = el)" 
                            :errorMessage="newCategories.errors ? newCategories.errors['categories.' + categories.index + '.name'] : null"
                        />
                        <div class="size-6">
                            <DeleteIcon 
                                class="w-6 h-6 text-primary-600 hover:text-primary-700 cursor-pointer" 
                                @click="removeAddCategory(categories.index)" 
                            />
                        </div>
                    </div>

                    <!-- buttons -->
                    <div class="flex flex-col gap-4 p-2">
                        <Button
                            :type="'button'"
                            :variant="'secondary'" 
                            :size="'lg'" 
                            :iconPosition="'left'"
                            :class="{ 'opacity-25': newCategories.processing }"
                            :disabled="newCategories.processing"
                            v-if="!isEditing"
                            @click="addCategory"
                        >
                            <template #icon>
                                <PlusIcon class="size-5"/>
                            </template>
                            New Category
                        </Button>

                        <Button
                            :type="'submit'"
                            :variant="'primary'" 
                            :size="'lg'" 
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                            v-if="isEditing"
                            @click="submit()"
                        >
                            {{ isEdited ? 'Save changes' : 'Discard' }}
                        </Button>

                        <Button
                            :type="'button'"
                            :variant="'primary'" 
                            :size="'lg'" 
                            :class="{ 'opacity-25': newCategories.processing }"
                            :disabled="newCategories.processing"
                            v-if="newCategories.categories.length"
                            @click="submit()"
                        >
                            Add Category
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    <!-- </form> -->

    <Modal 
        :title="'Reassign Category'"
        :show="reassignCategoryFormIsOpen" 
        :maxWidth="'sm'" 
        :closeable="true" 
        @close="closeModal('close')"
    >
        <ReassignedProductCategory 
            :selectedCategory="selectedCategory" 
            :categoryArr="categories" 
            @close="closeModal"
            @isDirty="isDirty = $event"
            @update:categories="updateCategories($event)"
        />
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>
</template>
