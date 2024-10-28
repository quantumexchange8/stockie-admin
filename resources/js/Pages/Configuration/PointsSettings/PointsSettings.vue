<script setup>
import Button from '@/Components/Button.vue';
import TextInput from '@/Components/TextInput.vue';
import Toast from '@/Components/Toast.vue';
import { useCustomToast, useInputValidator } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const isLoading = ref(false);
const point = ref({});


const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const form = useForm({
    value: point.value.respectiveValue ?? '',
    point: point.value.existingPoint ?? '',
})
const getCurrentPoint = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/configurations/getPoint');
        point.value = response.data;
        if (point.value) {
            form.value = point.value.respectiveValue;
            form.point = point.value.existingPoint;
        }
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}


const submit = () => {
    form.post(route('configuration.pointCalculate'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            setTimeout(() => {
                showMessage({
                    severity: 'success', 
                    summary: 'Point calculation updated.'
                });
            }, 200)
            getCurrentPoint();
        },
        onError: (error) => {
            console.error(error);
        },
    });
};

const isFormValid = computed(() => {
    return ['value', 'point'].every(field => form[field]);
})

onMounted(() => {
    getCurrentPoint();
})

</script>

<template>

    <Toast />

    <div class="w-full flex flex-col p-6 rounded-[5px] border border-solid border-primary-100">
        <form @submit.prevent="submit">
            <div class="w-full flex justify-between items-center self-stretch pb-6">
                <div class="w-full flex flex-col justify-center flex-[1_0_0]">
                    <span class="w-full text-primary-900 text-md font-medium">Point Calculation</span>
                </div>
                <Button
                    :type="'submit'"
                    :size="'lg'"
                    :disabled="!isFormValid || form.processing"
                    class="!w-fit"
                >
                    Save Changes
                </Button>
            </div>
        
            <div class="flex items-center gap-4">
                <span>Spend</span>
                <TextInput
                    :inputId="'value'"
                    :errorMessage="form.errors?.value"
                    :iconPosition="'left'"
                    :placeholder="'0'"
                    @keypress="isValidNumberKey($event, true)"
                    v-model="form.value"
                    class="max-w-[191px] [&>div:nth-child(1)>input]:text-right  [&>div:nth-child(1)>input]:pr-4"
                >
                    <template #prefix>
                        <span class="text-grey-700 text-base font-normal">RM</span>
                    </template>
                </TextInput>
                <span>equal to</span>
                <TextInput
                    :inputId="'point'"
                    :errorMessage="form.errors?.point"
                    :iconPosition="'right'"
                    :placeholder="'0'"
                    @keypress="isValidNumberKey($event, false)"
                    v-model="form.point"
                    class="max-w-[218px] [&>div:nth-child(1)>input]:text-left [&>div:nth-child(1)>input]:pr-4 [&>div:nth-child(1)>input]:pl-4"
                >
                    <template #prefix>
                        <span class="text-grey-700 text-base font-normal">point</span>
                    </template>
                </TextInput>
            </div>
        </form>
    </div>
</template>

