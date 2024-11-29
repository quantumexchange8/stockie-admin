<script setup>
import Button from '@/Components/Button.vue';
import { TimesIcon } from '@/Components/Icons/solid';
import NumberCounter from '@/Components/NumberCounter.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useCustomToast } from '@/Composables/index.js';

const props = defineProps({
    item: Object
})

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'update:keepList']);

const form = useForm({
    customer_id: props.item.customer_id,
    type: props.item.qty > props.item.cm ? 'qty' : 'cm',
    return_qty: props.item.qty < props.item.cm ? 1 : 0,
});

const submit = async () => {
    form.processing = true;

    try {
        const returnItemResponse = await axios.post(`/customer/returnKeepItem/${props.item.id}`, form);

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: 'Item has been returned to the customer.',
            });
        }, 200);

        emit('update:keepList', returnItemResponse.data);
        emit('close');
        form.reset();
    } catch (error) {
        console.error(error);
    } finally {
        form.processing = false;
    }

    // form.post(route('/customer/returnKeepItem/${props.item.id}', props.item.id), {
    //     preserveScroll: true,
    //     preserveState: true,
    //     onSuccess: () => {
    //         form.reset();
    //         emit('close');
    //     },
    // })
};

const isFormValid = computed(() => ['type', 'return_qty'].every(field => form[field]) && !form.processing);
</script>

<template>
    <form class="flex flex-col items-center gap-9 rounded-[5px]" novalidate @submit.prevent="submit">
        <div class="flex flex-col gap-6 w-96">
            <div class="flex justify-between items-start self-stretch">
                <span class="text-md font-medium text-primary-950 text-center whitespace-nowrap">Select Quantity</span>
                <TimesIcon
                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                    @click="$emit('close')"
                />
            </div>

            <div class="flex flex-col items-center gap-5 self-stretch">
                <div class="flex items-center justify-between gap-3 self-stretch">
                    <div class="flex gap-x-3 items-center justify-between">
                        <img 
                            :src="props.item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="KeepItemImage"
                            class="rounded-[1.5px] size-[60px]"
                        >
                        <p class="text-base text-grey-900 font-medium"> {{ item.item_name }}</p>
                    </div>
                    <p class="text-primary-800 text-md font-medium pr-3">{{ item.qty > item.cm ? `x ${item.qty}` : `${item.cm} cm`  }}</p>
                    <!-- <div class="flex justify-between pr-3 items-center gap-2 flex-[1_0_0]">
                        <div class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-base font-medium">{{ item.item_name }}</div>
                        <div class="flex flex-col justify-center self-stretch text-primary-800 text-right text-md font-medium">x{{ initialAmount }}</div>
                    </div> -->
                </div>
                
                <NumberCounter
                    v-if="item.qty > 0"
                    :maxValue="item.qty"
                    v-model="form.return_qty"
                    class="!w-full whitespace-nowrap"
                />

                <div class="flex justify-center items-start gap-3 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="emit('close')"
                    >
                        Cancel
                    </Button>
                    <Button
                        :type="'submit'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="!isFormValid"
                    >
                        Return
                    </Button>
                </div>
            </div>
        </div>
    </form>
</template>
