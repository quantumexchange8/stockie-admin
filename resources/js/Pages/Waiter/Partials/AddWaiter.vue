<script setup>
import Button from "@/Components/Button.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import { useForm } from "@inertiajs/vue3";
import DragDropImage from "@/Components/DragDropImage.vue";
import { computed, ref, watch, onMounted } from "vue";
import { useCustomToast, useInputValidator, usePhoneUtils } from "@/Composables";
import Modal from "@/Components/Modal.vue";
import RadioButton from "@/Components/RadioButton.vue";
import { employementTypeOptions } from "@/Composables/constants";
import Label from "@/Components/Label.vue";

const { showMessage } = useCustomToast();
const { transformPhone, formatPhoneInput } = usePhoneUtils();
const { isValidNumberKey } = useInputValidator();

const emit = defineEmits(["close", "isDirty"]);

const isUnsavedChangesOpen = ref(false);
const waiterPositionOptions = ref([]);

const unsaved = (status) => {
    emit('close', status)
};

const form = useForm({
    full_name: "",
    phone: "",
    phone_temp: "",
    email: "",
    role_id: "",
    employment_type: "",
    position_id: "",
    salary: "",
    stockie_email: "",
    password: "",
    passcode: "",
    image: "",
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
    form.post(route("waiter.add-waiter"), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            unsaved('leave');
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'New waiter has been successfully added.',
                });
            }, 200)
        },
        onError: (error) => {
            console.error(error);
        },
    });
};

const requiredFields = ['full_name', 'phone_temp', 'email', 'role_id', 'employment_type', 'position_id', 'salary', 'stockie_email', 'password'];

const isFormValid = computed(() => {
    return requiredFields.every(field => form[field]);
});

onMounted(() => fetchWaiterPositions());

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>
<template>
    <div class="w-full flex flex-col overflow-y-auto scrollbar-thin scrollbar-webkit px-1 pt-1">
        <form @submit.prevent="submit" autocomplete="off">
            <div class="w-full flex flex-col max-h-[calc(100dvh-18rem)] md:gap-9">
                <div class="w-full flex md:gap-6">
                    <DragDropImage
                        :inputName="'image'"
                        :errorMessage="form.errors.image"
                        class="!h-[373px] !w-[373px] "
                        v-model="form.image"
                    />
                    <div class="flex flex-grow flex-col md:gap-[48px]">
                        <div class="flex flex-col md:gap-6">
                            <div class="md:text-[20px] text-[#48070A]">
                                Personal Detail
                            </div>

                            <div class="flex flex-col md:gap-4">
                                <!-- <div class="flex md:gap-4">
                                    <TextInput
                                        :labelText="'User name'"
                                        :placeholder="'eg: johndoe'"
                                        inputId="full_name"
                                        v-model="form.full_name"
                                        :errorMessage="form.errors.full_name"
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

                                <div class="flex md:gap-4">
                                    <TextInput
                                        inputName="phone"
                                        labelText="Phone number"
                                        placeholder="11 1234 5678"
                                        :iconPosition="'left'"
                                        :errorMessage="form.errors?.phone || ''"
                                        class="col-span-full sm:col-span-6 [&>div:nth-child(2)>input]:text-left"
                                        required
                                        v-model="form.phone_temp"
                                        @keypress="isValidNumberKey($event, false)"
                                        @input="formatPhoneInput"
                                    >
                                        <template #prefix> +60 </template>
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
                            <div class="flex md:gap-4">
                                <TextInput
                                    label-text="Staff ID"
                                    :placeholder="'eg: J8192'"
                                    inputId="role_id"
                                    type="'text'"
                                    required
                                    :errorMessage="form.errors?.role_id || ''"
                                    v-model="form.role_id"
                                />
                                <TextInput
                                    label-text="Salary per month (basic)"
                                    inputId="salary"
                                    type="'text'"
                                    required
                                    :iconPosition="'left'"
                                    :errorMessage="form.errors?.salary || ''"
                                    v-model="form.salary"
                                    @keypress="isValidNumberKey($event, true)"
                                >
                                    <!-- class="!w-1/2" -->
                                    <template #prefix>
                                        <span class="text-grey-900">RM</span>
                                    </template>
                                </TextInput>
                            </div>

                            <div class="flex md:gap-4">
                                <div class="w-full flex flex-col gap-y-1 items-start self-stretch">
                                    <Label required class="mb-1 text-xs !font-medium text-grey-900">
                                        Employment type
                                    </Label>
                                    <RadioButton
                                        :optionArr="employementTypeOptions"
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
                            <div class="flex md:gap-4">
                                <TextInput
                                    labelText="Email address"
                                    :placeholder="'for Stockie account log-in'"
                                    inputId="stockie_email"
                                    type="'email'"
                                    required
                                    :errorMessage="form.errors?.stockie_email || ''"
                                    v-model="form.stockie_email"
                                />
                                <TextInput
                                    labelText="Password"
                                    :placeholder="'for Stockie account log-in'"
                                    inputId="password"
                                    :inputType="'password'"
                                    required
                                    :errorMessage="form.errors?.password || ''"
                                    v-model="form.password"
                                />
                            </div>
                            
                            <TextInput
                                inputId="passcode"
                                labelText="Clock in passcode"
                                placeholder="eg: 123456"
                                class="!w-1/2"
                                :maxlength="6"
                                required
                                :errorMessage="form.errors?.passcode || ''"
                                v-model="form.passcode"
                                @keypress="isValidNumberKey($event, false)"
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
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        type="submit"
                        :size="'lg'"
                        :disabled="!isFormValid || form.processing"
                        :class="{ 'opacity-25': form.processing }"
                    >
                        Add
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
