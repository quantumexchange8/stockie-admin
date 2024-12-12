<script setup>
import {transactionFormat, useCustomToast} from "@/Composables/index.js";
import Button from "@/Components/Button.vue";
import { DeleteIcon, EditIcon } from "@/Components/Icons/solid";
import { computed, ref, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import Label from "@/Components/Label.vue";
import { useForm } from "@inertiajs/vue3";
import TextInput from "@/Components/TextInput.vue";
import DateInput from '@/Components/Date.vue';
import Textarea from '@/Components/Textarea.vue';
import { PromotionsNoVal } from "@/Components/NoDatas/Images";
import { DeleteIllus } from "@/Components/Icons/illus";
import DragDropImage from "@/Components/DragDropImage.vue";

const props = defineProps({
    ActivePromotions: Array
}) 
const { showMessage } = useCustomToast();
const { formatDate } = transactionFormat();

const editModal = ref(false);
const actionVal = ref('');
const modalDetails = ref();
const initialForm = ref(null);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);

const openEditModal = (promotion, actionType) => {
    editModal.value = true;
    actionVal.value = actionType;
    modalDetails.value = promotion;
    form.id = modalDetails.value.id;
    form.title = modalDetails.value.title;
    form.description = modalDetails.value.description;
    form.promotion_from = modalDetails.value.promotion_from;
    form.promotion_to = modalDetails.value.promotion_to;
    form.promotion_image = modalDetails.value.promotion_image;
    
    form.promotionPeriod = [
        new Date(modalDetails.value.promotion_from),
        new Date(modalDetails.value.promotion_to)
    ];

    initialForm.value = JSON.parse(JSON.stringify(form));

}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                editModal.value = false;
            }
            break;
        };
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        };
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            editModal.value = false;
            form.errors = {};
            break;
        }
    }
    
}

const form = useForm({
    id: '',
    title: '',
    description: '',
    promotionPeriod: '',
    promotion_from: '',
    promotion_to: '',
    promotion_image: '',
})


const submit = () => {
    const startDate = new Date(form.promotionPeriod[0]);
    const endDate = new Date(form.promotionPeriod[1]);

    startDate.setHours(0, 0, 0, 0);
    endDate.setHours(0, 0, 0, 0);
    
    form.promotion_from = (startDate.getFullYear() < 10 ? '0' + startDate.getFullYear() : startDate.getFullYear()) + '/' + ((startDate.getMonth() + 1) < 10 ? '0' + (startDate.getMonth() + 1) : (startDate.getMonth() + 1)) + '/' + (startDate.getDate()  < 10 ? '0' + startDate.getDate() : startDate.getDate());
    form.promotion_to = (endDate.getFullYear() < 10 ? '0' + endDate.getFullYear() : endDate.getFullYear()) + '/' + ((endDate.getMonth() + 1) < 10 ? '0' + (endDate.getMonth() + 1) : (endDate.getMonth() + 1)) + '/' + (endDate.getDate()  < 10 ? '0' + endDate.getDate() : endDate.getDate());

    const routeURL = actionVal.value === 'edit' ? 'configurations.promotion.edit' : 'configurations.promotion.delete';
    const toastSummary = actionVal.value === 'edit' ? 'Selected promotion has been edited successfully.' : 'Selected promotion has been deleted successfully.';

    form.post(route(routeURL), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            closeModal('leave');
            form.reset();
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: toastSummary,
                });
            }, 200)
        },
    })

    // if (actionVal.value === 'edit') {
    //     form.post(route('configurations.promotion.edit'), {
    //         preserveScroll: true,
    //         onSuccess: () => {
    //             closeModal('leave');
    //             form.reset();
    //             setTimeout(() => {
    //                 showMessage({ 
    //                     severity: 'success',
    //                     summary: 'Selected promotion has been edited successfully.',
    //                 });
    //             }, 200)
    //         },
    //     })
    // } else {
    //     form.post(route('configurations.promotion.delete'), {
    //         preserveScroll: true,
    //         preserveState: true,
    //         onSuccess: () => {
    //             closeModal('leave');
    //             form.reset();
    //             setTimeout(() => {
    //                 showMessage({ 
    //                     severity: 'success',
    //                     summary: 'Selected promotion has been deleted successfully.',
    //                 });
    //             }, 200)
    //         },
    //     })
    // }
}

const isFormValid = computed(() => {
    return ['title', 'description', 'promotionPeriod'].every(field => form[field]);
})

const excludeIsDirty = (formData) => {
    const { isDirty, ...rest } = formData;
    return rest;
}

watch(form, () => {
    isDirty.value = JSON.stringify(excludeIsDirty(form)) !== JSON.stringify(excludeIsDirty(initialForm.value));
}, { deep: true });

</script>


<template>
    <div v-if="ActivePromotions.length === 0" class="flex flex-col items-center justify-center gap-5">
        <PromotionsNoVal />
        <div class="text-primary-900 text-sm font-medium">
            You haven’t added any promotion yet...
        </div>
    </div>
    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-5 select-none h-full">
        <div v-for="promotion in ActivePromotions" >
            <div class="flex flex-col border border-grey-100 rounded-[5px]">
                <div class="p-3 flex justify-center items-center">
                    <img 
                        :src="promotion.promotion_image ? promotion.promotion_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt="PromotionImage"
                        class="object-contain h-[296px]"
                    >
                </div>
                <div class="">
                    <div class="pb-2 px-3 flex flex-col gap-1">
                        <div class="text-gray-500 text-2xs font-medium leading-tight" >
                            Active Period: {{ formatDate(promotion.promotion_from) }} - {{ formatDate(promotion.promotion_to) }}
                        </div>
                        <div class="text-base text-gray-900 font-semibold leading-tight">
                            {{ promotion.title }}
                        </div>
                        <div class="h-9 text-gray-700 text-xs font-medium overflow-hidden text-ellipsis whitespace-nowrap " >
                            {{ promotion.description }}
                        </div>
                    </div>
                    <div class="flex items-center p-0.5 gap-x-0.5 bg-primary-25">
                        <Button
                            class="bg-primary-100 hover:bg-primary-50"
                            variant="secondary"
                            @click="openEditModal(promotion, 'edit')"
                        >
                            <EditIcon class="text-primary-900"/>
                        </Button>
                        <Button
                            class="bg-primary-600 hover:bg-primary-700"
                            variant="secondary"
                            @click="openEditModal(promotion, 'delete')"
                        >
                            <DeleteIcon class="text-white"/>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Modal 
        :title="'Edit Promotion'"
        :maxWidth="'md'" 
        :closeable="true"
        :show="editModal"
        @close="closeModal('close')"
        v-if="actionVal === 'edit'"
    >
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-6 max-h-[calc(100dvh-12rem)] overflow-y-auto scrollbar-thin scrollbar-webkit pl-1 pr-2 py-1">
                <div class="w-full h-56">
                    <DragDropImage
                        inputName="image"
                        remarks="Suggested image size: 1920 x 1080 pixel"
                        :modelValue="form.promotion_image"
                        :errorMessage="form.errors.promotion_image"
                        v-model="form.promotion_image"
                        class="w-full h-full object-contain rounded-[5px]"
                    />
                </div>
                <div class="flex flex-col gap-4">
                    <div class="space-y-1">
                        <Label value="Title"/>
                        <TextInput 
                            :inputName="'title'"
                            v-model="form.title"
                        />
                    </div>
                    <div class="space-y-1">
                        <Label value="Promotion Active Period"/>
                        <DateInput
                            :inputName="'promotionPeriod'"
                            :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                            :range="true"
                            class="col-span-full xl:col-span-4"
                            :errorMessage="form.errors.promotionPeriod"
                            v-model="form.promotionPeriod"
                        />
                    </div>
                    <div class="space-y-1">
                        <Label value="Description"/>
                        <Textarea
                            :inputName="'description'"
                            :placeholder="'eg: promotion date and time, detail of the promotion and etc'"
                            :rows="5"
                            class="col-span-full xl:col-span-4"
                            :errorMessage="form.errors.description" 
                            v-model="form.description"
                        />
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeModal('close')"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        size="lg"
                        :disabled="form.processing || !isFormValid"
                        type="submit"
                    >
                        Save Changes
                    </Button>
                </div>
            </div>
        </form>
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>

    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="editModal"
        @close="closeModal('leave')"
        v-if="actionVal === 'delete'"
        :withHeader="false"
    >
        <form @submit.prevent="submit">
            <div class="w-full flex flex-col gap-9" >
                <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] pt-6 mx-[-24px] mt-[-24px]">
                    <DeleteIllus />
                </div>
                <div class="flex flex-col gap-1" >
                    <span class="text-primary-900 text-lg font-medium text-center self-stretch" >
                        Delete promotion?
                    </span>
                    <span class="text-grey-900 text-center text-base font-medium self-stretch" >
                        Are you sure you want to delete the selected promotion? This action cannot be undone.
                    </span>
                </div>
                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeDeleteModal()"
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