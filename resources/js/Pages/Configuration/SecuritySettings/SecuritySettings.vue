<script setup>
import Button from '@/Components/Button.vue';
import Dropdown from '@/Components/Dropdown.vue';
import TextInput from '@/Components/TextInput.vue';
import Toast from '@/Components/Toast.vue';
import { useCustomToast } from '@/Composables';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { wTrans } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

const isLoading = ref(false);
const alDuration = ref(null);
const timerDurationTypes = ref([
    { text: wTrans('public.config.secs_of_inactivity'), value: 'seconds' },
    { text: wTrans('public.config.mins_of_inactivity'), value: 'minutes' },
]);

const { showMessage } = useCustomToast();

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

const lockChannel = new BroadcastChannel('table-locks');

const unlockAllTables = async () => {
    isLoading.value = true;
    router.post('/order-management/orders/handleTableLock', { action: 'unlock-all' }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            const tabUid = sessionStorage.getItem('tab_uid');

            // Unlock all tables from session storage and other tabs' locked tables array
            lockChannel.postMessage({
                type: 'unlock-all',
                tableIds: [],
                sourceTabUid: tabUid,
            });

            sessionStorage.setItem('table_locks', JSON.stringify([]));

            showMessage({ 
                severity: 'info',
                summary: wTrans('public.toast.all_table_unlocked')
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
                    summary: wTrans('public.toast.update_auto_lock_duration_success')
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
                    <span class="w-full text-primary-900 text-md font-medium">{{ $t('public.config.auto_lock_settings') }}</span>
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
                        {{ $t('public.action.unlock_all_table') }}
                    </Button>
                    <Button
                        :type="'submit'"
                        :size="'lg'"
                        :disabled="!isFormValid || form.processing"
                        class="!w-fit"
                    >
                        {{ $t('public.action.save_changes') }}
                    </Button>
                </div>
            </div>
        
            <div class="flex items-end gap-x-1">
                <TextInput
                    :inputId="'value'"
                    :inputType="'number'"
                    :errorMessage="form.errors?.value"
                    :labelText="$t('public.field.unlock_table_after')"
                    required
                    :placeholder="'0'"
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

