<script setup>
import Button from "@/Components/Button.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import { useForm } from "@inertiajs/vue3";
import DragDropImage from "@/Components/DragDropImage.vue";
import { computed } from "vue";

const props = defineProps({
    waiters: {
        type: Object,
        required: true
    },
})

const emit = defineEmits(["close"]);
const closeModal = () => {
    form.reset(); 
    form.errors = {}; 
    emit("close");
};

function formatPhone(phone) {
    if (!phone || phone.length < 10) 
        return phone; 
    

    if (phone.startsWith('+6')) 
        phone = phone.slice(3);
      

    return phone;
}

function formatSalary(salary) {
    if(salary.endsWith('.00'))
        salary = salary.slice(0, -3);

    return salary;
}

const form = useForm({
    id: props.waiters.id,
    name: props.waiters.name,
    phone: formatPhone(props.waiters.phone),
    email: props.waiters.email,
    staffid: props.waiters.staffid,
    salary: formatSalary(props.waiters.salary),
    stockie_email: props.waiters.stockie_email,
    stockie_password: '',
});

const submit = () => {
    form.post(route("waiter.edit-waiter"), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            closeModal();
        },
        onError: (error) => {
            console.error(error); // Log the error details
        },
    });
};

const requiredFields = ['name', 'phone', 'email', 'staffid', 'salary', 'stockie_email', 'stockie_password'];

const isFormValid = computed(() => {
    return requiredFields.every(field => form[field]);
})
</script>
<template>
    <div class="w-full flex flex-col max-h-[650px] overflow-y-scroll scrollbar-thin scrollbar-webkit pl-1 pt-1">
        <form novalidate @submit.prevent="submit">
            <div class="w-full flex flex-col md:gap-9">
                <div class="w-full flex flex-col gap-6 md:flex-row justify-center">
                    <DragDropImage
                        :inputName="'image'"
                        :errorMessage="form.errors.image"
                        v-model="form.image"
                        class="h-[373px] !w-[373px] !md:w-full "
                    />
                    <div class="flex flex-grow flex-col gap-[48px]">
                        <div class="flex flex-col md:gap-6">
                            <div class="md:text-[20px] text-[#48070A]">
                                Personal Detail
                            </div>

                            <div class="flex flex-col gap-4">
                                <div class="flex md:gap-4">
                                    <TextInput
                                        label-text="Full name"
                                        :placeholder="'eg: John Doe'"
                                        inputId="name"
                                        type="'text'"
                                        v-model="form.name"
                                        :errorMessage="form.errors.name"
                                    >
                                    </TextInput>
                                </div>

                                <div class="flex gap-4">
                                    <div class="w-full flex flex-col">
                                        <TextInput
                                            label-text="Phone number"
                                            inputId="phone"
                                            type="'tel'"
                                            v-model="form.phone"
                                            :errorMessage="form.errors.phone"
                                            :iconPosition="'left'"
                                        >
                                            <template #prefix>
                                                <span class="text-grey-900"
                                                    >+60</span
                                                >
                                            </template>
                                        </TextInput>
                                    </div>

                                    <div class="w-full flex flex-col">
                                        <TextInput
                                            label-text="Email address"
                                            :placeholder="'eg: johndoe@gmail.com'"
                                            inputId="email"
                                            type="'email'"
                                            v-model="form.email"
                                            :errorMessage="form.errors.email"
                                        >
                                        </TextInput>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col md:gap-6">
                            <div class="md:text-[20px] text-[#48070A]">
                                Work Detail
                            </div>
                            <div class="flex gap-4">
                                <div class="w-full flex flex-col">
                                    <TextInput
                                        label-text="Staff ID"
                                        :placeholder="'eg: J8192'"
                                        inputId="staffid"
                                        type="'text'"
                                        v-model="form.staffid"
                                    ></TextInput>
                                    <InputError
                                        :message="form.errors.staffid"
                                    />
                                </div>
                                <div class="w-full flex flex-col">
                                    <TextInput
                                        label-text="Basic salary (per month)"
                                        inputId="salary"
                                        type="'text'"
                                        v-model="form.salary"
                                        :iconPosition="'left'"
                                    >
                                        <template #prefix>
                                            <span class="text-grey-900"
                                                >RM</span
                                            >
                                        </template>
                                    </TextInput>
                                    <InputError :message="form.errors.salary" />
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col md:gap-6">
                            <div class="md:text-[20px] text-[#48070A]">
                                Account Detail
                            </div>
                            <div class="flex gap-4">
                                <div class="w-full flex flex-col">
                                    <TextInput
                                        label-text="Email address"
                                        :placeholder="'for Stockie account log-in'"
                                        inputId="stockie_email"
                                        type="'email'"
                                        v-model="form.stockie_email"
                                    ></TextInput>
                                    <InputError
                                        :message="form.errors.stockie_email"
                                    />
                                </div>
                                <div class="w-full flex flex-col">
                                    <TextInput
                                        label-text="Password"
                                        :placeholder="'for Stockie account log-in'"
                                        inputId="stockie_password"
                                        :inputType="'password'"
                                        v-model="form.stockie_password"
                                    ></TextInput>
                                    <InputError
                                        :message="form.errors.stockie_password"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex md:gap-4">
                    <Button
                        variant="tertiary"
                        type="button"
                        @click="form.reset()"
                        :size="'lg'"
                        >Discard</Button
                    >
                    <Button
                        variant="primary"
                        type="submit"
                        :size="'lg'"
                        :disabled="!isFormValid || form.processing"
                        :class="{ 'opacity-25': form.processing }"
                        >Save Changes</Button
                    >
                </div>
            </div>
        </form>
    </div>
</template>
