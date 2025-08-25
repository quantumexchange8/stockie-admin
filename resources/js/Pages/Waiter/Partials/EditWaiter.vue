<script setup>
import Button from "@/Components/Button.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import { useForm } from "@inertiajs/vue3";
import DragDropImage from "@/Components/DragDropImage.vue";
import { computed, onMounted, ref, watch } from "vue";
import { useCustomToast, usePhoneUtils } from "@/Composables";
import Modal from "@/Components/Modal.vue";
import RadioButton from "@/Components/RadioButton.vue";
import Label from "@/Components/Label.vue";
import { employementTypeOptions } from "@/Composables/constants";
import { wTrans } from "laravel-vue-i18n";

const props = defineProps({
    waiters: {
        type: Object,
        required: true
    },
})
const { showMessage } = useCustomToast();
const { formatPhone, transformPhone, formatPhoneInput } = usePhoneUtils();

const emit = defineEmits(["close", "isDirty"]);

const isUnsavedChangesOpen = ref(false);
const employmentTypes = ref([
    { text: 'Full-time', value: true },
    { text: 'Part-time', value: false },
]);
const waiterPositionOptions = ref([]);

const unsaved = (status) => {
    emit('close', status);
}

const form = useForm({
    id: props.waiters.id,
    full_name: props.waiters.full_name,
    phone: props.waiters.phone,
    phone_temp: formatPhone(props.waiters.phone, true, true),
    email: props.waiters.email,
    role_id: props.waiters.role_id,
    employment_type: props.waiters.employment_type,
    position_id: props.waiters.position_id,
    salary: props.waiters.salary,
    stockie_email: props.waiters.worker_email,
    password: '',
    passcode: props.waiters.passcode?.toString(),
    image: props.waiters.image ? props.waiters.image : '',
});

const fetchWaiterPositions = async () => {
    form.processing = true;
    try {
        const response = await axios.get(route('waiter.getWaiterPositions'));
        waiterPositionOptions.value = response.data ?? [];
        
    } catch (error) {
        console.error(error);
    } finally {
        form.processing = false;
    }
}

const submit = () => {
    form.phone = form.phone_temp ? transformPhone(form.phone_temp) : '';
    form.post(route("waiter.edit-waiter"), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            unsaved('leave');
            setTimeout(() => {
                showMessage({
                    severity: 'success',
                    summary: 'Changes saved',
                });
            }, 200)
        },
        onError: (error) => {
            console.error(error); // Log the error details
        },
    });
};

const requiredFields = ['full_name', 'phone_temp', 'email', 'role_id', 'employment_type', 'position_id',  'salary', 'stockie_email'];

const isFormValid = computed(() => {
    return requiredFields.every(field => form[field]);
})

const getEmployementTypeOptions = computed(() => {
    return employementTypeOptions.map((opt) => ({
        ...opt,
        text: wTrans(opt.text).value,
    }));
});

onMounted(() => fetchWaiterPositions());

watch(form, (newValue) => emit('isDirty', newValue.isDirty));
</script>
<template>
    <div class="w-full flex flex-col max-h-[calc(100dvh-18rem)] overflow-y-auto scrollbar-thin scrollbar-webkit pl-1 pt-1">
        <form @submit.prevent="submit">
            <div class="w-full flex flex-col md:gap-9">
                <div class="w-full flex flex-col gap-6 md:flex-row justify-center">
                    <DragDropImage
                        :inputName="'image'"
                        :errorMessage="form.errors.image"
                        v-model="form.image"
                        class="h-[373px] !w-[373px] !md:w-full"
                    />
                    <div class="flex flex-grow flex-col gap-[48px]">
                        <div class="flex flex-col md:gap-6">
                            <div class="md:text-[20px] text-[#48070A]">
                                Personal Detail
                            </div>

                            <div class="flex flex-col gap-4">
                                <!-- <div class="flex md:gap-4">
                                    <TextInput
                                        label-text="User name"
                                        :placeholder="'eg: johndoe'"
                                        inputId="username"
                                        type="'text'"
                                        v-model="form.username"
                                        :errorMessage="form.errors.username"
                                    >
                                    </TextInput>
                                </div> -->

                                <TextInput
                                    label-text="Full name"
                                    :placeholder="'eg: John Doe'"
                                    inputId="full_name"
                                    type="'text'"
                                    required
                                    :errorMessage="form.errors.full_name"
                                    v-model="form.full_name"
                                />

                                <div class="flex gap-4">
                                    <TextInput
                                        labelText="Phone number"
                                        inputId="phone"
                                        :inputType="'number'"
                                        :errorMessage="form.errors?.phone || ''"
                                        :iconPosition="'left'"
                                        class="[&>div:nth-child(2)>input]:text-left"
                                        required
                                        v-model="form.phone_temp"
                                        @input="formatPhoneInput"
                                    >
                                        <template #prefix>
                                            <span class="text-grey-900">+60</span>
                                        </template>
                                    </TextInput>

                                    <TextInput
                                        label-text="Email address"
                                        :placeholder="'eg: johndoe@gmail.com'"
                                        inputId="email"
                                        type="'email'"
                                        required
                                        :errorMessage="form.errors?.email || ''"
                                        v-model="form.email"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col md:gap-6 w-full">
                            <div class="md:text-[20px] text-[#48070A]">
                                Work Detail
                            </div>
                            <div class="flex gap-4">
                                <TextInput
                                    :label-text="'Staff ID'"
                                    :placeholder="'eg: J8192'"
                                    :inputId="'role_id'"
                                    required
                                    disabled
                                    :errorMessage="form.errors?.role_id || ''"
                                    v-model="form.role_id"
                                />
                                <TextInput
                                    :label-text="`Salary per ` + (form.employment_type === 'Full-time' ? 'month (basic)' : 'hour')"
                                    inputId="salary"
                                    :inputType="'number'"
                                    withDecimal
                                    :iconPosition="'left'"
                                    required
                                    :errorMessage="form.errors?.email || ''"
                                    v-model="form.salary"
                                >
                                    <template #prefix>
                                        <span class="text-grey-900">RM</span>
                                    </template>
                                </TextInput>
                            </div>

                            <div class="flex gap-4">
                                <div class="w-full flex flex-col gap-y-1 items-start self-stretch">
                                    <Label required class="mb-1 text-xs !font-medium text-grey-900">
                                        Employment type
                                    </Label>
                                    <RadioButton
                                        :optionArr="getEmployementTypeOptions"
                                        :checked="form.employment_type"
                                        :errorMessage="form.errors?.employment_type || ''"
                                        v-model:checked="form.employment_type"
                                    />
                                </div>
                                <div class="w-full flex flex-col gap-y-1 items-start self-stretch">
                                    <Label required class="mb-1 text-xs !font-medium text-grey-900">
                                        Waiter Position
                                    </Label>
                                    <RadioButton
                                        :optionArr="waiterPositionOptions"
                                        :checked="form.position_id"
                                        :errorMessage="form.errors?.position_id || ''"
                                        v-model:checked="form.position_id"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col md:gap-6 w-full">
                            <div class="md:text-[20px] text-[#48070A]">
                                Account Detail
                            </div>
                            <div class="flex gap-4">
                                <TextInput
                                    label-text="Email address"
                                    :placeholder="'for STOXPOS account log-in'"
                                    inputId="stockie_email"
                                    type="'email'"
                                    required
                                    :errorMessage="form.errors?.stockie_email || ''"
                                    v-model="form.stockie_email"
                                />
                                <TextInput
                                    label-text="Password"
                                    :placeholder="'for STOXPOS account log-in'"
                                    inputId="password"
                                    :inputType="'password'"
                                    required
                                    :errorMessage="form.errors?.password || ''"
                                    v-model="form.password"
                                />
                            </div>
                            
                            <TextInput
                                inputId="passcode"
                                :inputType="'number'"
                                labelText="Clock in passcode"
                                placeholder="eg: 123456"
                                class="!w-1/2"
                                :maxlength="6"
                                required
                                :errorMessage="form.errors?.passcode || ''"
                                v-model="form.passcode"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex md:gap-4">
                    <Button
                        variant="tertiary"
                        type="button"
                        @click="unsaved('close')"
                        :size="'lg'"
                    >
                        Discard
                    </Button>
                    <Button
                        variant="primary"
                        type="submit"
                        :size="'lg'"
                        :disabled="!isFormValid || form.processing"
                        :class="{ 'opacity-25': form.processing }"
                    >
                        Save Changes
                    </Button>
                </div>
            </div>
            <Modal
                :unsaved="true"
                :maxWidth="'2xs'"
                :withHeader="false"
                :show="isUnsavedChangesOpen"
                @close="unsaved('stay')"
                @leave="unsaved('leave')"
            />
        </form>
    </div>
</template>
