<script setup>
import Button from '@/Components/Button.vue';
import TextInput from '@/Components/TextInput.vue';
import Toast from '@/Components/Toast.vue';
import Label from "@/Components/Label.vue";
import { useCustomToast } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { wTrans } from 'laravel-vue-i18n';

const point = ref({});
const pointExpirationSettings = ref({});

const { showMessage } = useCustomToast();

const form = useForm({
    value: point.value.respectiveValue ?? '',
    point: point.value.existingPoint ?? '',
})

const expirationSettingsForm = useForm({
    days_to_expire: '',
    days_to_notify: '',
})

const getCurrentPoint = async () => {
    form.processing = true;
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
        form.processing = false;
    }
}

const getPointExpirationSettings = async () => {
    expirationSettingsForm.processing = true;
    try {
        const response = await axios.get('/configurations/getPointExpirationSettings');
        pointExpirationSettings.value = response.data;

        if (pointExpirationSettings.value) {
            expirationSettingsForm.days_to_expire = Math.floor(pointExpirationSettings.value.daysToExpire).toString();
            expirationSettingsForm.days_to_notify = Math.floor(pointExpirationSettings.value.daysToNotify).toString();
        }

    } catch (error) {
        console.error(error);
    } finally {
        expirationSettingsForm.processing = false;
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
                    summary: wTrans('public.toast.update_point_calculation')
                });
            }, 200)
            getCurrentPoint();
        },
        onError: (error) => {
            console.error(error);
        },
    });
};

const setPointExpirationSettings = () => {
    expirationSettingsForm.post(route('configuration.setPointExpiration'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            expirationSettingsForm.reset();
            setTimeout(() => {
                showMessage({
                    severity: 'success', 
                    summary: wTrans('public.toast.update_point_expiration_settings')
                });
            }, 200)
            getPointExpirationSettings();
        },
        onError: (error) => {
            console.error(error);
        },
    });
};

const isFormValid = computed(() => {
    return ['value', 'point'].every(field => form[field]);
})

const isExpirationSettingsFormValid = computed(() => {
    return ['days_to_expire', 'days_to_notify'].every(field => expirationSettingsForm[field]);
})

onMounted(() => {
    getCurrentPoint();
    getPointExpirationSettings();
})

</script>

<template>

    <Toast />

    <div class="flex flex-col items-start gap-5">
    <div class="w-full flex flex-col p-6 rounded-[5px] border border-solid border-primary-100">
        <form @submit.prevent="submit">
            <div class="w-full flex justify-between items-center self-stretch pb-6">
                <div class="w-full flex flex-col justify-center flex-[1_0_0]">
                    <span class="w-full text-primary-900 text-md font-medium">{{ $t('public.config.point_calculation') }}</span>
                </div>
                <Button
                    :type="'submit'"
                    :size="'lg'"
                    :disabled="!isFormValid || form.processing"
                    class="!w-fit"
                >
                    {{ $t('public.action.save_changes') }}
                </Button>
            </div>
        
            <div class="flex items-center gap-4">
                <span>{{ $t('public.loyalty.spend') }}</span>
                <TextInput
                    :inputId="'value'"
                    :inputType="'number'"
                    withDecimal
                    :errorMessage="form.errors?.value"
                    :iconPosition="'left'"
                    :placeholder="'0'"
                    v-model="form.value"
                    class="max-w-[191px] [&>div:nth-child(1)>input]:text-right  [&>div:nth-child(1)>input]:pr-4"
                >
                    <template #prefix>
                        <span class="text-grey-700 text-base font-normal">RM</span>
                    </template>
                </TextInput>
                <span>{{ $t('public.equal_to') }}</span>
                <TextInput
                    :inputId="'point'"
                    :inputType="'number'"
                    withDecimal
                    :errorMessage="form.errors?.point"
                    :iconPosition="'right'"
                    :placeholder="'0'"
                    v-model="form.point"
                    class="max-w-[218px] [&>div:nth-child(1)>input]:text-left [&>div:nth-child(1)>input]:pr-4 [&>div:nth-child(1)>input]:pl-4"
                >
                    <template #prefix>
                        <span class="text-grey-700 text-base font-normal">{{ $tChoice('public.point_lowercase', 0) }}</span>
                    </template>
                </TextInput>
            </div>
        </form>
    </div>

        <div class="w-full flex flex-col p-6 rounded-[5px] border border-solid border-primary-100">
            <form @submit.prevent="setPointExpirationSettings">
                <div class="w-full flex justify-between items-center self-stretch pb-6">
                    <div class="w-full flex flex-col justify-center flex-[1_0_0]">
                        <span class="w-full text-primary-900 text-md font-medium">{{ $t('public.config.point_expiration_behaviour') }}</span>
                    </div>
                    <Button
                        :type="'submit'"
                        :size="'lg'"
                        :disabled="!isExpirationSettingsFormValid || expirationSettingsForm.processing"
                        class="!w-fit"
                    >
                        {{ $t('public.action.save_changes') }}
                    </Button>
                </div>
        
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="flex col-span-full lg:col-span-1 items-end gap-x-1">
                        <TextInput
                            :inputId="'days_to_expire'"
                            :inputType="'number'"
                            :errorMessage="expirationSettingsForm.errors?.days_to_expire"
                            :labelText="$t('public.config.points_expiration')"
                            required
                            :placeholder="'0'"
                            v-model="expirationSettingsForm.days_to_expire"
                            class="max-w-[120px] [&>div:nth-child(1)>input]:text-left  [&>div:nth-child(1)>input]:pr-4"
                        />
                        <TextInput
                            disabled
                            :modelValue="$t('public.config.days_after_added')"
                        />
                    </div>

                    <div class="flex flex-col col-span-full lg:col-span-1 items-start gap-x-1">
                        <Label
                            :for="'days_to_notify'"
                            required
                            class="mb-1 text-xs !font-medium text-grey-900"
                        >
                           {{ $t('public.config.expiration_notification') }}
                        </Label>
                        <div class="flex items-end gap-x-1">
                            <TextInput
                                :inputId="'days_to_notify'"
                                :inputType="'number'"
                                :errorMessage="expirationSettingsForm.errors?.days_to_notify"
                                :placeholder="'0'"
                                v-model="expirationSettingsForm.days_to_notify"
                                class="max-w-[120px] [&>div:nth-child(1)>input]:text-left  [&>div:nth-child(1)>input]:pr-4"
                            />
                            <TextInput
                                disabled
                                :modelValue="$t('public.config.days_before_expiration')"
                            />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

