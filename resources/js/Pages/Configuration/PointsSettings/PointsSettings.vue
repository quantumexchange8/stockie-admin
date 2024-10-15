<script setup>
import Button from '@/Components/Button.vue';
import TextInput from '@/Components/TextInput.vue';
import Toast from '@/Components/Toast.vue';
import { useCustomToast } from '@/Composables';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    value: "",
    point: "",
})

const { showMessage } = useCustomToast();

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
        },
        onError: (error) => {
            console.error(error);
        },
    });
};

// Validate input to only allow numeric value to be entered
const isNumber = (e, withDot = true) => {
    const { key, target: { value } } = e;
    
    if (/^\d$/.test(key)) return;

    if (withDot && key === '.' && /\d/.test(value) && !value.includes('.')) return;
    
    e.preventDefault();
};

</script>

<template>

    <Toast />

    <div class="w-full flex flex-col p-6 gap-6 rounded-[5px] border border-solid border-primary-100">
        <form @submit.prevent="submit">
            <div class="w-full flex justify-between items-center self-stretch">
                <div class="w-full flex flex-col justify-center flex-[1_0_0]">
                    <span class="w-full text-primary-900 text-md font-medium">Point Calculation</span>
                </div>
                <Button
                    :type="'submit'"
                    :size="'lg'"
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
                    @keypress="isNumber($event)"
                    v-model="form.value"
                    class="max-w-[91px] [&>div:nth-child(1)>input]:text-right  [&>div:nth-child(1)>input]:pr-4"
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
                    @keypress="isNumber($event)"
                    v-model="form.point"
                    class="max-w-[118px] [&>div:nth-child(1)>input]:text-left  [&>div:nth-child(1)>input]:pr-4"
                >
                    <template #prefix>
                        <span class="text-grey-700 text-base font-normal">point</span>
                    </template>
                </TextInput>
            </div>
        </form>
    </div>
</template>

