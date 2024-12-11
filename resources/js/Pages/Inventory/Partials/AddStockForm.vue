<script setup>
import { ref, computed, onMounted, reactive, watch } from 'vue'
import { useForm } from '@inertiajs/vue3';
import NumberCounter from '@/Components/NumberCounter.vue';
import Button from '@/Components/Button.vue'
import DragDropImage from '@/Components/DragDropImage.vue'
import Label from '@/Components/Label.vue';
import Modal from '@/Components/Modal.vue';
import { useCustomToast } from '@/Composables/index.js';

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
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const initialData = ref();

const { showMessage } = useCustomToast();
const emit = defineEmits(['close', 'isDirty'])

const form = useForm({
    name: props.selectedGroup.name,
    // category_id: props.selectedGroup.category_id,
    image: props.selectedGroup.inventory_image ? props.selectedGroup.inventory_image : '',
    items: [],
});


watch(() => props.selectedGroupItems, () => {
    form.items = props.selectedGroupItems.map((item) => reactive({ ...item, add_stock_qty: 0 }));
    initialData.value = JSON.parse(
        JSON.stringify({
            name: form.name,
            image: form.image,
            items: form.items,
        })
    );
}, { immediate: true });

const formSubmit = () => { 
    let hasAddValue = false;
    form.items.forEach(item => {
        if (item.add_stock_qty > 0 || item.add_stock_qty < 0) {
            hasAddValue = true;
        }
    });

    let replenishedItem = form.items.map(item => {
        if (item.add_stock_qty > 0) return item.item_name;
    }).filter(item => item != null).join(', ');

    if (hasAddValue === true) {
        form.post(route('inventory.updateInventoryItemStock', props.selectedGroup.id), {
            preserveScroll: true,
            preserveState: 'errors',
            onSuccess: () => {
                showMessage({
                    severity: 'success',
                    summary: 'Stock replenished successfully.',
                    detail: `You've replenished stock for ${replenishedItem}`,
                });

                form.reset();
                unsaved('leave');
            },
        })
    }
};

const unsaved = (status) => {
    emit('close', status);
}

const requiredFields = ['name', 'items'];

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

watch(
    form,
    () => {
        const currentData = ({
            name: form.name,
            image: form.image,
            items: form.items,
        })
        isDirty.value = JSON.stringify(currentData) !== JSON.stringify(initialData.value);
        emit('isDirty', isDirty.value)
    },
    { deep: true }
);
</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pl-1 pr-2 py-1 max-h-[calc(100dvh-18rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
            <div class="col-span-full md:col-span-4 h-[372px] w-full flex items-center justify-center rounded-[5px] bg-grey-50 outline-dashed outline-2 outline-grey-200">
                <img :src="selectedGroup.inventory_image ? selectedGroup.inventory_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" alt="">
            </div>
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
                @click="unsaved('close')"
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
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="unsaved('stay')"
            @leave="unsaved('leave')"
        />
    </form>
</template>