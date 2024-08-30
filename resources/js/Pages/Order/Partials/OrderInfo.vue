<script setup>
import { ref } from 'vue';
import TabView from '@/Components/TabView.vue';
import OrderDetail from './OrderDetail.vue';
import Button from '@/Components/Button.vue';

const props = defineProps({
    errors: Object,
    selectedTable: {
        type: Object,
        default: () => {},
    },
})

const emit = defineEmits(['close']);

const tabs = ref(['Order Detail', 'Customer Detail']);

</script>

<template>
    <div class="flex flex-col gap-6 items-start rounded-[5px]">
        <div class="px-6 py-2 w-full">
            <TabView :tabs="tabs">
                <template #order-detail>
                    <OrderDetail :selectedTable="selectedTable" />
                </template>
                <template #customer-detail>
                    cuscus
                </template>
            </TabView>
        </div>

        <div class="fixed bottom-0 w-full flex flex-col px-6 pt-6 pb-12 justify-center gap-6 self-stretch bg-white">
            <p class="self-stretch text-grey-900 text-right text-md font-medium">Total: RM{{ '223' }}</p>
            <div class="flex flex-col gap-3">
                <Button
                    size="lg"
                    @click="$emit('close')"
                >
                    Complete Order
                </Button>
                <Button
                    type="button"
                    variant="tertiary"
                    size="lg"
                    @click="$emit('close')"
                >
                    Cancel Order
                </Button>
            </div>
        </div>
    </div>
</template>
