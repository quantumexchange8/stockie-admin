<script setup>
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import Textarea from '@/Components/Textarea.vue';
import DateInput from '@/Components/Date.vue';
import TextInput from '@/Components/TextInput.vue';
import DragDropImage from '@/Components/DragDropImage.vue'

defineProps({
    errors:Object
})

const emit = defineEmits(['close'])

const form = useForm({
    title: '',
    description: '',
    promotionPeriod: '',
    promotion_from: '',
    promotion_to: '',
    image: '',
});

const formSubmit = () => { 
    if (form.promotionPeriod !== '') {
        const startDate = new Date(form.promotionPeriod[0]);
        const endDate = new Date(form.promotionPeriod[1]);

        startDate.setHours(0, 0, 0, 0);
        endDate.setHours(0, 0, 0, 0);
        
        form.promotion_from = (startDate.getFullYear() < 10 ? '0' + startDate.getFullYear() : startDate.getFullYear()) + '/' + ((startDate.getMonth() + 1) < 10 ? '0' + (startDate.getMonth() + 1) : (startDate.getMonth() + 1)) + '/' + (startDate.getDate()  < 10 ? '0' + startDate.getDate() : startDate.getDate());
        form.promotion_to = (endDate.getFullYear() < 10 ? '0' + endDate.getFullYear() : endDate.getFullYear()) + '/' + ((endDate.getMonth() + 1) < 10 ? '0' + (endDate.getMonth() + 1) : (endDate.getMonth() + 1)) + '/' + (endDate.getDate()  < 10 ? '0' + endDate.getDate() : endDate.getDate());
    }

    form.post(route('configurations.promotions.store'), {
        preserveScroll: true,
        onSuccess: () => closeForm(),
    })
};

const closeForm = () => {
    form.reset();
    emit('close');
}

</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="flex flex-col items-start self-stretch gap-6 max-h-[450px] pl-1 pr-2 py-1 overflow-auto scrollbar-thin scrollbar-webkit">
            <DragDropImage
                :inputName="'image'"
                :errorMessage="form.errors.image"
                v-model="form.image"
            />
            <div class="flex flex-col items-start gap-4 flex-[1_0_0] self-stretch">
                <div class="flex flex-col items-start gap-4 self-stretch">
                    <TextInput
                        :inputName="'title'"
                        :labelText="'Title'"
                        :placeholder="'eg: Heineken B1F1 Promotion'"
                        :errorMessage="form.errors.title"
                        v-model="form.title"
                    />
                    <DateInput
                        :inputName="'product_name'"
                        :labelText="'Promotion Active Period'"
                        :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                        :range="true"
                        class="col-span-full xl:col-span-4"
                        :errorMessage="form.errors.promotionPeriod"
                        v-model="form.promotionPeriod"
                    />
                    <Textarea
                        :inputName="'description'"
                        :labelText="'Description'"
                        :placeholder="'eg: promotion date and time, detail of the promotion and etc'"
                        :rows="5"
                        class="col-span-full xl:col-span-4"
                        :errorMessage="form.errors.description" 
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
                @click="closeForm"
            >
                Cancel
            </Button>
            <Button
                :size="'lg'"
            >
                Add
            </Button>
        </div>
    </form>
</template>
