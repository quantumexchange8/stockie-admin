<script setup>
import Button from '@/Components/Button.vue';
import Modal from '@/Components/Modal.vue';
import Tag from '@/Components/Tag.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useInputValidator, useCustomToast } from '@/Composables';
import { UndetectableIllus } from '@/Components/Icons/illus';

const props = defineProps({
    shiftTransactions: Array,
    currentSelectedShift: {
        type: Object,
        default: () => {}
    },
});

const emit = defineEmits(['update:shift-listing', 'update:selected-shift']);

const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const shiftTransactionsList = ref(props.shiftTransactions);
const openShiftFormIsOpen = ref(false);
const selectedShift = ref(props.currentSelectedShift);

const form = useForm({
    starting_cash : ''
});

const submit = async () => { 
    form.processing = true;

    try {
        const response = await axios.post(`/shift-management/shift-control/open`, form);
        
        shiftTransactionsList.value = response.data; 
        emit('update:shift-listing', response.data);
        closeModal()

        setTimeout(() => {
            showMessage({ 
                severity: 'success', 
                summary: "Shift successfully opened. You're all set to start the day!", 
            }); 
        }, 200); 

    } catch (error) {
        form.setError(error.response.data.errors);
    } finally {
        form.processing = false;
    }
};

const openModal = () => {
    const currentOpenedShift = shiftTransactionsList.value.find((shift) => shift.status === 'opened');

    if (currentOpenedShift) {
        showMessage({ 
            severity: 'error', 
            summary: "You can't open more than one shift at the same time.", 
            detail: "To proceed, please close the current shift first.", 
        }); 

    } else {
        openShiftFormIsOpen.value = true;
    }
}

const closeModal = () => {
    openShiftFormIsOpen.value = false;
    setTimeout(() => {
        form.reset();
        form.clearErrors();
    }, 200);
}

const updateSelected = (shift) => {
    if (selectedShift.value === shift) {
        selectedShift.value = shiftTransactionsList.value.find((shift) => shift.status === 'opened') ?? null;
    } else {
        selectedShift.value = shift;
    }
    emit('update:selected-shift', selectedShift.value);
}

watch(() => props.shiftTransactions, (newValue) => {
    shiftTransactionsList.value = newValue;
});

watch(() => props.currentSelectedShift, (newValue) => {
    selectedShift.value = newValue;
});
</script>

<template>
    <div 
        class="col-span-full md:col-span-5 lg:col-span-4 gap-y-4 p-5 flex flex-col self-stretch items-center border border-primary-100 rounded-[5px] 
            h-[calc(100dvh-20rem)] md:h-[calc(100dvh-12rem)] shrink-0 overflow-y-auto scrollbar-thin scrollbar-webkit"
        :class="shiftTransactionsList.length > 0 ? 'justify-start' : 'justify-center'"
    >
        <template v-if="shiftTransactionsList.length > 0">
            <template v-for="(shift, index) in shiftTransactionsList" :key="index">
                <div 
                    class="flex flex-col w-full items-start self-stretch gap-y-5 p-4 rounded-[5px] border border-grey-100 shadow-sm cursor-pointer"
                    :class="shift.id === selectedShift?.id ? 'bg-primary-25' : 'bg-white'"
                    @click="updateSelected(shift)"
                >
                    <div class="flex w-full flex-col gap-y-3 items-start">
                        <div class="flex w-full items-center justify-between gap-x-3 self-stretch">
                            <p class="truncate text-sm font-bold text-grey-950">{{ `Shift #${shift.shift_no}` }}</p>
                            <Tag :value="shift.status === 'opened' ? 'Active' : 'Closed'" />
                        </div>        

                        <p class="truncate font-bold text-md text-grey-950 self-stretch">{{ shift.shift_opened }}</p>
                    </div>

                    <div class="flex gap-x-3 items-start self-stretch">
                        <p class="truncate font-normal text-xs text-grey-500">by</p>

                        <div class="flex gap-x-1 items-center">
                            <p class="truncate font-bold text-xs text-grey-950">{{ shift.opened_by.full_name }}</p>
                            <img 
                                :src="shift.opened_by.image ? shift.opened_by.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt="ProductImage" 
                                class="size-4 object-fit rounded-full border borer-grey-100"
                            />
                        </div>
                    </div>
                </div>
            </template>
            
            <Button
                :variant="'tertiary'"
                :size="'lg'"
                @click="openModal"
            >
                Open new shift
            </Button>
        </template>

        <template v-else>
            <div class="flex w-full flex-col items-center justify-center gap-5">
                <UndetectableIllus />
                <div class="flex flex-col gap-y-1 items-center">
                    <span class="text-grey-950 text-md font-semibold">No data yet</span>
                    <span class="text-grey-950 text-sm font-normal">Your shift record will show up here.</span>
                </div>

                <Button
                    :variant="'primary'"
                    :size="'lg'"
                    class="!w-fit"
                    @click="openModal"
                >
                    Open Shift
                </Button>
            </div>
        </template>
    </div>     

    <Modal 
        :title="'Open shift'"
        :show="openShiftFormIsOpen"
        :maxWidth="'2xs'" 
        :closeable="true"
        @close="closeModal"
    >
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-1" >
                    <p class="text-grey-950 text-base font-bold">
                        Starting cash amount
                    </p>
                    <div class="text-grey-950 text-sm font-normal self-stretch" >
                        Please enter the initial cash amount in the register to begin your shift
                    </div>
                </div>

                <TextInput
                    :inputId="'starting_cash'"
                    :iconPosition="'left'"
                    :placeholder="'0.00'"
                    :inputType="'number'"
                    :errorMessage="form.errors?.starting_cash?.[0] ??''"
                    v-model="form.starting_cash"
                    @keypress="isValidNumberKey($event, true)"
                    class="[&>div>input]:text-left"
                >
                    <template #prefix>RM</template>
                </TextInput>

                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeModal"
                    >
                        Cancel
                    </Button>
                    <Button
                        size="lg"
                        :disabled="form.processing || form.starting_cash == ''"
                    >
                        Open
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
</template>

