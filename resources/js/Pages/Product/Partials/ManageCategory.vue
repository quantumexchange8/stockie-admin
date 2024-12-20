<script setup>
import TextInput from "@/Components/TextInput.vue";
import Button from "@/Components/Button.vue";
import { useForm } from "@inertiajs/vue3";
import { nextTick, reactive, ref, watch } from "vue";
import { DeleteIcon, Menu2Icon, PlusIcon } from '@/Components/Icons/solid.jsx';
import Modal from '@/Components/Modal.vue';
import { useCustomToast } from "@/Composables";

const props = defineProps({
    categoryArr: {
        type: Array,
        default: [],
    },
});

const emit = defineEmits(["close", 'getZoneDetails', 'isDirty']);
const { showMessage } = useCustomToast();

const deleteCategoryFormIsOpen = ref(false);
const selectedCategory = ref(null);
const isEditing = ref(false);
const currentCategoryId = ref(null);
const initialCategoryName = ref(null);
const inputRefs = reactive({});
const isEdited = ref(false);
const categories = ref();
const newCategoriesCounter = ref(0);

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
    newCategories.categories = newCategories.categories.filter(category => category.index !== id); 
}

const submit = () => {
    //only submit if its edited, else just return default state
    if(isEdited.value){
        if(isEditing.value){
            form.post(route('tableroom.edit-zone'), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    isEditing.value = false;
                    form.reset();
                    setTimeout(() => {
                    showMessage({
                        severity: 'success',
                        summary: 'Zone name has been edited successfully.'
                    });
                }, 200);
                    emit('getZoneDetails');
                }
            })
        }
        else{
            newCategories.post(route("tableroom.add-zone"), {
                preserveScroll: true,
                preserveState: 'errors',
                onSuccess: () => {
                    form.reset();
                    emit('close', 'leave');
                    showMessage({
                        severity: 'success',
                        summary: 'Zone has been added successfully.'
                    });
                }
            });
        }
    } else {
        isEditing.value = false;
        form.reset();
    }
};

const handleDefaultClick = (event) => {
    event.stopPropagation();  
    event.preventDefault();   
};

const showDeleteGroupForm = (event, id) => {
    handleDefaultClick(event);
    selectedCategory.value = id;
    deleteCategoryFormIsOpen.value = true;
}

const hideDeleteCategoryForm = () => {
    deleteCategoryFormIsOpen.value = false;
}


const startEditing = (categoryArr) => {
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

watch(() => props.categoryArr, (newValue) => {
    categories.value = newValue ? newValue : {};
}, { immediate: true });

watch(
    () => form.edit_name, 
    (newVal) => {
        isEdited.value = newVal !== initialCategoryName.value; 
    },
    { immediate: true } 
);

watch([newCategories, isEdited], ([newFormValue, newIsEdited]) => {
    emit('isDirty', newFormValue.isDirty || newIsEdited);
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
                            <div class="flex flex-row gap-[12px]">
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
                                <span
                                    @click="startEditing(categoryArr)"
                                    >
                                    {{ categoryArr.text }}
                                </span>
                            </div>
                            <div>
                            <DeleteIcon class="w-6 h-6 text-primary-600 hover:text-primary-700 cursor-pointer"
                                @click="showDeleteGroupForm($event, categoryArr.value)" /></div>
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
        :show="deleteCategoryFormIsOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/table-room/table-room/deleteZone/${selectedCategory}`"
        :confirmationTitle="'Delete this category?'"
        :confirmationMessage="'Are you sure you want to delete this category? This action cannot be undone.'"
        @close="hideDeleteCategoryForm" 
        v-if="selectedCategory" 
    />
</template>
