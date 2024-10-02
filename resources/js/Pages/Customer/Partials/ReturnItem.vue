<script setup>
import Button from '@/Components/Button.vue';
import { TimesIcon } from '@/Components/Icons/solid';
import NumberCounter from '@/Components/NumberCounter.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    }
})
const emit = defineEmits(['close']);
const initialAmount = ref(props.item.qty);

const form = useForm({
    id: props.item.id,
    name: props.item.item,
    qty: props.item.qty,
    initial_qty: initialAmount,
});

const formSubmit = () => {
    form.post(route('customer.return-item'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    })
};

const closeItemOverlay = () => {
    emit('close');
}
</script>

<template>
    <form class="flex flex-col items-center gap-9 rounded-[5px]" novalidate @submit.prevent="formSubmit">
        <div class="flex justify-between items-start self-stretch">
            <span class="text-md font-medium text-primary-950 text-center whitespace-nowrap">Select Quantity</span>
            <TimesIcon
                class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                @click="$emit('close')"
            />
        </div>

        <div class="flex flex-col items-center gap-[20px] self-stretch">
            <div class="flex items-center gap-3 self-stretch">
                <div class="flex w-10 h-10 bg-primary-900"></div>
                <div class="flex justify-between pr-3 items-center gap-2 flex-[1_0_0]">
                    <div class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-base font-medium">{{ item.item }}</div>
                    <div class="flex flex-col justify-center self-stretch text-primary-800 text-right text-md font-medium">x{{ initialAmount }}</div>
                </div>
            </div>
            
            <NumberCounter
                :minValue="0"
                :maxValue="initialAmount"
                v-model="form.qty"
                class="!w-full whitespace-nowrap"
            />

            <div class="flex justify-center items-start gap-3 self-stretch">
                <Button
                    :type="'button'"
                    :variant="'tertiary'"
                    :size="'lg'"
                    @click="closeItemOverlay()"
                >
                    Cancel
                </Button>
                <Button
                    :type="'submit'"
                    :variant="'primary'"
                    :size="'lg'"
                >
                    Return
                </Button>
            </div>
        </div>
    </form>
</template>
