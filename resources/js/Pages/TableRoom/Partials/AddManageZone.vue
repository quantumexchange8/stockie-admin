<script setup>
import TextInput from "@/Components/TextInput.vue";
import Button from "@/Components/Button.vue";
import { useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { DeleteIcon, PlusIcon } from '@/Components/Icons/solid.jsx';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    zonesArr: {
        type: Array,
        default: [],
    },
});
const emit = defineEmits(["close"]);
const zones = ref();
watch(() => props.zonesArr, (newValue) => {
    zones.value = newValue ? newValue : {};
}, { immediate: true });


const deleteProductFormIsOpen = ref(false);
const selectedZone = ref(null);
const isTextInputVisible = ref(false);
const turnToTextField = ref(false);
const form = useForm({
    name: "",
});

const addZone = () => {
    isTextInputVisible.value = true;
}
const submit = () => {
    form.post(route("tableroom.add-zone"), {
        onSuccess: () => {
            console.log('Zone created successfully!');
            form.reset();
        },
        onError: (errors) => {
            console.error('Form submission error:', errors);
        }
    });
};

const handleDefaultClick = (event) => {
    event.stopPropagation();  // Prevent the row selection event
    event.preventDefault();   // Prevent the default link action
};

const showDeleteGroupForm = (event, id) => {
    handleDefaultClick(event);
    selectedZone.value = id;
    deleteProductFormIsOpen.value = true;
}

const hideDeleteProductForm = () => {
    deleteProductFormIsOpen.value = false;
}

</script>

<template>
    <form @submit.prevent="submit">
        <div class="max-h-[454px] overflow-y-scroll scrollbar-thin scrollbar-webkit">
            <div class="w-full flex flex-col gap-[16px]">
                <div>
                    <div v-for="zonesArr in zones" :key="zonesArr.id" class="w-full flex flex-row justify-between text-grey-600 h-[49px] px-[12px] gap-4">
                        <div class="flex flex-row gap-[12px] justify-content">
                            <!-- <Menu2Icon
                            class="size-5 text-grey-600"
                            /> -->
                            <svg width="25" height="25" viewBox="0 0 25 25" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path 
                                    d="M3.0918 12.5H21.0918M3.0918 6.5H21.0918M3.0918 18.5H21.0918"
                                    stroke="currentColor" 
                                    stroke-width="2" 
                                    stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <div
                            >
                                {{ zonesArr.text }}
                            </div>
                        </div>
                        <DeleteIcon class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                            @click="showDeleteGroupForm($event, zonesArr.value)" />
                    </div>
                </div>
                <div v-if="isTextInputVisible" class="flex flex-col gap-4 px-[12px]" >
                    <TextInput 
                        :placeholder="'eg: Main Area'" 
                        inputId="name" 
                        type="'text'" 
                        v-model="form.name"
                        :errorMessage="form.errors.name" />
                </div>
                <div class="flex gap-4 pt-3">
                    <Button
                        :type="isTextInputVisible ? 'submit' : 'button'"
                        :variant="'secondary'" 
                        :size="'lg'" 
                        :iconPosition="'left'"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="addZone"
                    >
                        <template #icon>
                            <PlusIcon
                                class="w-[20px] h-[20px]"
                             />
                        </template>
                        New Zone
                    </Button>
                </div>
            </div>
        </div>
    </form>
    <Modal 
        :show="deleteProductFormIsOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/table-room/table-room/deleteZone/${selectedZone}`"
        :confirmationTitle="'Delete this zone?'"
        :confirmationMessage="'Table and room in this zone will be deleted altogether. Are you sure you want to delete this zone?'"
        @close="hideDeleteProductForm" 
        v-if="selectedZone" />
</template>
