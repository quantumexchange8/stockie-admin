<script setup>
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import Textarea from '@/Components/Textarea.vue';
import DateInput from '@/Components/Date.vue';
import TextInput from '@/Components/TextInput.vue';
import DragDropImage from '@/Components/DragDropImage.vue'
import { useCustomToast } from '@/Composables';
import dayjs from 'dayjs';
import { computed, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

defineProps({
    errors:Object
})

const emit = defineEmits(['isDirty', 'closeModal', 'update:promotions'])
const { showMessage } = useCustomToast();
const isUnsavedChangesOpen = ref(false);

const form = useForm({
    title: '',
    description: '',
    promotionPeriod: '',
    promotion_from: '',
    promotion_to: '',
    promotion_image: '',
});

const formSubmit = async () => { 
    if (form.promotionPeriod !== '') {
        let startDate, endDate;
        
        startDate = dayjs(form.promotionPeriod[0]);
        if (form.promotionPeriod[1] != null) {
            endDate = dayjs(form.promotionPeriod[1]);
        } else {
            endDate = startDate;
        }
            
        form.promotion_from = startDate.startOf('day').format('YYYY-MM-DD HH:mm:ss');
        form.promotion_to = endDate.endOf('day').format('YYYY-MM-DD HH:mm:ss');

        if (startDate.isAfter(endDate)) {
            let temp = form.promotion_from;
            form.promotion_from = form.promotion_to;
            form.promotion_to = temp;
        }
    }

    try {
        const { data } = await axios.post('/configurations/promotions/store', form, { headers: { 'Content-Type': 'multipart/form-data' } });

        emit('update:promotions', data);
        unsaved('leave');
        setTimeout(() => {
            form.reset();
            showMessage({ 
                severity: 'success',
                summary: 'Promotion has been added.',
                detail: 'Promotion will be visible to waiter and customer during the active period.',
            });
        }, 200);
    } catch (error) {
        form.errors = error.response.data.errors;
        console.error(error);
    }

    // form.post(route('configurations.promotions.store'), {
    //     preserveScroll: true,
    //     preserveState: 'errors',
    //     onSuccess: () => {
    //         unsaved('close');
    //         setTimeout(() => {
    //             showMessage({ 
    //                 severity: 'success',
    //                 summary: 'Promotion has been added.',
    //                 detail: 'Promotion will be visible to waiter and customer during the active period.',
    //             });
    //         }, 200);
    //     }
    // })
};

const unsaved = (status) => {
    emit('closeModal', status);
}

const isFormValid = computed(() => {
    return ['title', 'description', 'promotionPeriod'].every(field => form[field]) && !form.processing;
})

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 max-h-[calc(100dvh-18rem)] pl-1 pr-2 py-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
            <DragDropImage
                inputName="image"
                remarks="Suggested image size: 1920 x 1080 pixel"
                :errorMessage="form.errors.promotion_image ? form.errors.promotion_image[0] : ''"
                v-model="form.promotion_image"
                class="col-span-full h-[244px]"
            />
            <div class="col-span-full flex flex-col items-start gap-4 flex-[1_0_0] self-stretch">
                <div class="flex flex-col items-start gap-4 self-stretch">
                    <TextInput
                        :inputName="'title'"
                        :labelText="'Title'"
                        :placeholder="'eg: Heineken B1F1 Promotion'"
                        :errorMessage="form.errors.title ? form.errors.title[0] : ''"
                        v-model="form.title"
                    />
                    <DateInput
                        :inputName="'promotionPeriod'"
                        :labelText="'Promotion Active Period'"
                        :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                        :range="true"
                        :minDate="new Date()"
                        class="col-span-full xl:col-span-4"
                        :errorMessage="form.errors.promotionPeriod ? form.errors.promotionPeriod[0] : ''"
                        v-model="form.promotionPeriod"
                    />
                    <Textarea
                        :inputName="'description'"
                        :labelText="'Description'"
                        :placeholder="'eg: promotion date and time, detail of the promotion and etc'"
                        :rows="5"
                        class="col-span-full xl:col-span-4"
                        :errorMessage="form.errors.description ? form.errors.description[0] : ''" 
                        v-model="form.description"
                    />
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
    </form>
    
    <Modal
        :unsaved="true"
        :maxWidth="'2xs'"
        :withHeader="false"
        :show="isUnsavedChangesOpen"
        @close="unsaved('stay')"
        @leave="unsaved('leave')"
    />
</template>
