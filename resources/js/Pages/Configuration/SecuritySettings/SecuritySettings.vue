<script setup>
import Button from '@/Components/Button.vue';
import Dropdown from '@/Components/Dropdown.vue';
import TextInput from '@/Components/TextInput.vue';
import Toast from '@/Components/Toast.vue';
import { useCustomToast, useInputValidator } from '@/Composables';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const isLoading = ref(false);
const alDuration = ref(null);
const timerDurationTypes = ref([
    { text: 'seconds of inactivity', value: 'seconds' },
    { text: 'minutes of inactivity', value: 'minutes' },
]);

const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const form = useForm({
    value_type: '',
    value: '0',
})

const getCurrentAutoUnlockDuration = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/configurations/getAutoUnlockDuration');
        alDuration.value = response.data;

        if (alDuration.value) {
            form.value_type = alDuration.value.value_type;
            form.value = Math.floor(alDuration.value?.value ?? 0).toString();
        }

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const unlockAllTables = async () => {
    isLoading.value = true;
    router.post('/order-management/orders/handleTableLock', { action: 'unlock-all' }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showMessage({ 
                severity: 'info',
                summary: 'All tables have been unlocked'
            });
        },
        onError: (errors) => {
            showMessage({ 
                severity: 'error',
                summary: errors,
            });
        }
    });

    isLoading.value = false;
}

const submit = () => {
    form.post(route('configuration.updateAutoLockDuration'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            setTimeout(() => {
                showMessage({
                    severity: 'success', 
                    summary: 'Auto-unlock durations successfully updated.'
                });
            }, 200)
            getCurrentAutoUnlockDuration();
        },
        onError: (error) => {
            console.error(error);
        },
    });
};

const isFormValid = computed(() => {
    return ['value_type', 'value'].every(field => form[field]) && form.value > 0;
})

onMounted(() => {
    getCurrentAutoUnlockDuration();
})

</script>

<template>

    <Toast />

    <div class="w-full flex flex-col p-6 rounded-[5px] border border-solid border-primary-100">
        <form @submit.prevent="submit">
            <div class="w-full flex justify-between items-center self-stretch pb-6">
                <div class="w-full flex flex-col justify-center flex-[1_0_0]">
                    <span class="w-full text-primary-900 text-md font-medium">Auto-Lock Settings</span>
                </div>
                <div class="flex items-end gap-x-3">
                    <Button
                        :type="'button'"
                        :variant="'secondary'"
                        :size="'lg'"
                        :disabled="form.processing"
                        class="!w-fit"
                        @click="unlockAllTables"
                    >
                        Unlock All Table
                    </Button>
                    <Button
                        :type="'submit'"
                        :size="'lg'"
                        :disabled="!isFormValid || form.processing"
                        class="!w-fit"
                    >
                        Save Changes
                    </Button>
                </div>
            </div>
        
            <div class="flex items-end gap-x-1">
                <TextInput
                    :inputId="'value'"
                    :errorMessage="form.errors?.value"
                    :labelText="'Unlock Table After'"
                    required
                    :placeholder="'0'"
                    @keypress="isValidNumberKey($event, true)"
                    v-model="form.value"
                    class="max-w-[120px] [&>div:nth-child(1)>input]:text-left  [&>div:nth-child(1)>input]:pr-4"
                />
                <Dropdown
                    :inputName="'value_type'"
                    :inputArray="timerDurationTypes"
                    :dataValue="form.value_type"
                    :errorMessage="form.errors?.value_type || ''"
                    v-model="form.value_type"
                    class="max-w-[218px]"
                />
            </div>
        </form>
    </div>
</template>

