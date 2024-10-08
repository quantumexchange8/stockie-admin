<script setup>
import {transactionFormat} from "@/Composables/index.js";
import Button from "@/Components/Button.vue";
import { DeleteIcon, EditIcon } from "@/Components/Icons/solid";
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";
import Label from "@/Components/Label.vue";
import { useForm } from "@inertiajs/vue3";
import TextInput from "@/Components/TextInput.vue";
import DateInput from '@/Components/Date.vue';
import Textarea from '@/Components/Textarea.vue';
import { PromotionsNoVal } from "@/Components/NoDatas/Images";

const props = defineProps({
    ActivePromotions: Array
}) 

const editModal = ref(false);
const actionVal = ref('');
const modalDetails = ref();

const openEditModal = (promotion, actionType) => {
    editModal.value = true;
    actionVal.value = actionType;
    modalDetails.value = promotion;
    form.title = modalDetails.value.title;
    form.description = modalDetails.value.description;
    form.id = modalDetails.value.id;
    form.promotion_from = modalDetails.value.promotion_from;
    form.promotion_to = modalDetails.value.promotion_to;
    
    form.promotionPeriod = [
        new Date(modalDetails.value.promotion_from),
        new Date(modalDetails.value.promotion_to)
    ];
}

const closeModal = () => {
    editModal.value = false;
}

const form = useForm({
    id: '',
    title: '',
    description: '',
    promotionPeriod: '',
    promotion_from: '',
    promotion_to: '',
})

const { formatDate, formatAmount } = transactionFormat();

const submit = () => {

    const startDate = new Date(form.promotionPeriod[0]);
    const endDate = new Date(form.promotionPeriod[1]);

    startDate.setHours(0, 0, 0, 0);
    endDate.setHours(0, 0, 0, 0);
    
    form.promotion_from = (startDate.getFullYear() < 10 ? '0' + startDate.getFullYear() : startDate.getFullYear()) + '/' + ((startDate.getMonth() + 1) < 10 ? '0' + (startDate.getMonth() + 1) : (startDate.getMonth() + 1)) + '/' + (startDate.getDate()  < 10 ? '0' + startDate.getDate() : startDate.getDate());
    form.promotion_to = (endDate.getFullYear() < 10 ? '0' + endDate.getFullYear() : endDate.getFullYear()) + '/' + ((endDate.getMonth() + 1) < 10 ? '0' + (endDate.getMonth() + 1) : (endDate.getMonth() + 1)) + '/' + (endDate.getDate()  < 10 ? '0' + endDate.getDate() : endDate.getDate());

    if (actionVal.value === 'edit') {
        form.post(route('configurations.promotion.edit'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                form.reset();
            },
        })
    } else {
        form.post(route('configurations.promotion.delete'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                form.reset();
            },
        })
    }
}
</script>


<template>
    <div v-if="ActivePromotions.length === 0" class="flex flex-col items-center justify-center gap-5">
        <PromotionsNoVal />
        <div class="text-primary-900 text-sm font-medium">
            You haven’t added any promotion yet...
        </div>
    </div>
    <div v-else class="grid grid-cols-3 gap-5 select-none">
        <div v-for="promotion in ActivePromotions" >
            <div class="flex flex-col" >
                <div class="p-3">
                    <img :src="promotion.promotion_image ? promotion.promotion_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" alt="">
                </div>
                <div class="pb-2 px-3 flex flex-col gap-1">
                    <div class="text-gray-500 text-2xs font-medium leading-tight" >
                        Active Period: {{ formatDate(promotion.promotion_from) }} - {{ formatDate(promotion.promotion_to) }}
                    </div>
                    <div class="text-base text-gray-900 font-semibold leading-tight">
                        {{ promotion.title }}
                    </div>
                    <div class="h-9 text-gray-700 text-xs font-medium overflow-hidden" >
                        {{ promotion.description }}
                    </div>
                </div>
                <div class="flex items-center">
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

    <Modal 
        :title="'Edit Promotion'"
        :maxWidth="'md'" 
        :closeable="true"
        :show="editModal"
        @close="closeModal"
        v-if="actionVal === 'edit'"
    >
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-6">
                <div class=" w-full h-56">
                    <img src="" alt="" >
                </div>
                <div class="flex flex-col gap-4">
                    <div class="space-y-1">
                        <Label value="Title"/>
                        <TextInput 
                            v-model="form.title"
                        />
                    </div>
                    <div class="space-y-1">
                        <Label value="Promotion Active Period"/>
                        <DateInput
                            :inputName="'product_name'"
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
                        @click="closeModal"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        size="lg"
                        type="submit"
                    >
                        Save Changes
                    </Button>
                </div>
            </div>
        </form>
    </Modal>

    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="editModal"
        @close="closeModal"
        v-if="actionVal === 'delete'"
        :withHeader="false"
    >
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-9" >
                <div></div>
                <div class="flex flex-col gap-1" >
                    <div class="text-primary-900 text-2xl font-medium text-center" >
                        Delete promotion?
                    </div>
                    <div class="text-gray-900 text-base font-medium text-center leading-tight" >
                        Are you sure you want to delete the selected promotion? This action cannot be undone.
                    </div>
                </div>
                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeModal"
                    >
                        Keep
                    </Button>
                    <Button
                        variant="red"
                        size="lg"
                        type="submit"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
</template>