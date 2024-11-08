<script setup>
import { ref, computed, onMounted, reactive, watch } from 'vue'
import { useForm } from '@inertiajs/vue3';
import NumberCounter from '@/Components/NumberCounter.vue';
import Button from '@/Components/Button.vue'
import DragDropImage from '@/Components/DragDropImage.vue'
import Label from '@/Components/Label.vue';

const props = defineProps({
    errors: Object,
    selectedGroup: {
        type: Object,
        required: true,
    },
    selectedGroupItems: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close'])

const form = useForm({
    name: props.selectedGroup.name,
    // category_id: props.selectedGroup.category_id,
    image: props.selectedGroup.image,
    items: [],
});

watch(() => props.selectedGroupItems, () => {
    form.items = props.selectedGroupItems.map((item) => reactive({ ...item, add_stock_qty: 0 }));
}, { immediate: true });

const formSubmit = () => { 
    let hasAddValue = false;
    form.items.forEach(item => {
        if (item.add_stock_qty > 0 || item.add_stock_qty < 0) {
            hasAddValue = true;
        }
    });

    if (hasAddValue === true) {
        form.put(route('inventory.updateInventoryItemStock', props.selectedGroup.id), {
            preserveScroll: true,
            preserveState: 'errors',
            onSuccess: () => {
                form.reset();
                emit('close');
            },
        })
    }
};

const cancelForm = () => {
    emit('close');
    setTimeout(() =>form.reset(), 200);
}

const requiredFields = ['name', 'image', 'items'];

const isFormValid = computed(() => {
    let staticFields = requiredFields.every(field => form[field]);

    let hasAddValue = false;
    form.items.forEach(item => {
        if (item.add_stock_qty > 0 || item.add_stock_qty < 0) {
            hasAddValue = true;
        }
    });

    return staticFields && hasAddValue;
});

</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pl-1 pr-2 py-1 max-h-[700px] overflow-y-scroll scrollbar-thin scrollbar-webkit">
            <div class="col-span-full md:col-span-4 h-[372px] w-full flex items-center justify-center rounded-[5px] bg-grey-50 outline-dashed outline-2 outline-grey-200"></div>
            <div class="col-span-full md:col-span-8 flex flex-col items-start gap-6 flex-[1_0_0] self-stretch">
                <div class="grid grid-cols-12 items-center self-stretch" v-for="(item, i) in form.items" :key="i">
                    <div class="col-span-10 flex flex-col justify-between items-start">
                        <Label
                            :value="item.item_name"
                            :for="'item_'+ i +'_stock_qty'"
                            class="text-base !font-medium text-grey-900 whitespace-nowrap"
                        />
                        <Label
                            :for="'item_'+ i +'_stock_qty'"
                            class="text-sm !font-medium text-grey-900 whitespace-nowrap"
                        >
                            Remaining stock: <span class="text-green-700">{{ item.stock_qty }}</span>
                        </Label>
                    </div>
                    <NumberCounter
                        :inputName="'item_'+ i +'add_stock_qty'"
                        :errorMessage="(form.errors) ? form.errors['items.' + i + '.add_stock_qty']  : ''"
                        v-model="item.add_stock_qty"
                        class="col-span-2 items-end justify-center"
                    />
                </div>
            </div>
        </div>
        <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="cancelForm"
            >
                Cancel
            </Button>
            <Button
                :size="'lg'"
                :disabled="!isFormValid"
            >
                Save
            </Button>
        </div>
    </form>
</template>