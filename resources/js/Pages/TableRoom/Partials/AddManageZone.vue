<script setup>
import TextInput from "@/Components/TextInput.vue";
import Button from "@/Components/Button.vue";
import { useForm } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { Menu2Icon, DeleteIcon } from '@/Components/Icons/solid.jsx';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    zones: {
        type: Array,
        default: [],
    },
});
console.log('Zones in TableRoom:', props.zones);


const deleteProductFormIsOpen = ref(false);
const emit = defineEmits(["close"]);
const form = useForm({
    name: "",
});

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

const closeModal = () => {
    form.reset();
    form.errors = {};
    emit("close");
};

const showDeleteGroupForm = () => {
    deleteProductFormIsOpen.value = true;
}

const hideDeleteProductForm = () => {
    deleteProductFormIsOpen.value = false;
}

</script>

<template>
    <form @submit.prevent="submit">
        <div class="max-h-[773px]">
            <div class="w-full flex flex-col gap-6">
                <div>
                    <div v-for="zone in zones" :key="zone.id" class="w-full flex flex-row justify-between text-grey-600">
                        <div class="flex flex-row gap-[12px]">
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
                            <span>
                                {{ zone.name }}
                            </span>
                        </div>
                        <DeleteIcon class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                            @click="showDeleteGroupForm" />
                    </div>
                </div>
                <div class="flex flex-col gap-4">
                    <TextInput 
                        :placeholder="'eg: Main Area'" 
                        inputId="name" 
                        type="'text'" 
                        v-model="form.name"
                        :errorMessage="form.errors.name" />
                </div>

                <div class="flex gap-4 pt-3">
                    <Button 
                        type="button" 
                        variant="tertiary" 
                        :size="'lg'" 
                        @click="closeModal">
                        Cancel
                    </Button>
                    <Button 
                        variant="primary" 
                        :size="'lg'" 
                        :type="'submit'">
                        Add
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
        :confirmationTitle="'Delete this zone?'"
        :confirmationMessage="'Table and room in this zone will be deleted altogether. Are you sure you want to delete this zone?'"
        @close="hideDeleteProductForm" />
</template>
