<script setup>
import axios from 'axios';
import { ref, computed, onMounted, watch, reactive } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue'
import TextInput from '@/Components/TextInput.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import Button from '@/Components/Button.vue'
import Dropdown from '@/Components/Dropdown.vue'
// import Dropdown from 'primevue/dropdown';
import DragDropImage from '@/Components/DragDropImage.vue'
import { PlusIcon } from '@/Components/Icons/solid';

const props = defineProps({
    errors: Object,
    group: {
        type: Object,
        required: true,
    },
    itemCategoryArr: {
        type: Array,
        default: () => [],
    },
});

const inventoryItemsArr = reactive([]);
const categoryArr = reactive([]);
// const itemCategoryArr = reactive([]);

const emit = defineEmits(['close'])

onMounted(async () => {
    try {
        // const itemCategoryResponse = await axios.get('/inventory/inventory/getAllItemCategories');
        // itemCategoryArr.value = itemCategoryResponse.data;

        const categoryResponse = await axios.get('/inventory/inventory/getAllCategories');
        categoryArr.value = categoryResponse.data;

        const inventoryItemsResponse = await axios.get(`/inventory/inventory/getInventoryItems/${props.group.id}`);
        inventoryItemsArr.value = inventoryItemsResponse.data.inventory_items;
    } catch (error) {
        console.error('Error fetching data:', error);
    }
});


const form = useForm({
    id: props.group.id,
    name: props.group.name,
    category_id: parseInt(props.group.category_id),
    image: props.group.image,
    items: inventoryItemsArr.value ? inventoryItemsArr.value : [],
});

const updatedInventoryItemsArr = computed(() => {
  return form.items = inventoryItemsArr.value;
})

const formSubmit = () => { 
    form.put(route('inventory.updateInventoryAndItems', props.group.id), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            form.reset();
            emit('close');
            
        },
    })
};

const cancelForm = () => {
    form.reset();
    emit('close');
}

const requiredFields = ['name', 'category_id', 'image', 'items'];

const isFormValid = computed(() => {
    return requiredFields.every(field => form[field]);
});

const addItem = () => {
    form.items.push(
        {
            inventory_id: props.group.id,
            item_name: '',
            item_code: '',
            item_cat_id: '',
            stock_qty: 0,
            status: 'In stock',
        }
    );
}
</script>

<template>
    <form class="flex flex-col gap-6 min-h-full max-h-screen" novalidate @submit.prevent="formSubmit">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pl-1 pr-2 py-1 max-h-[60vh] overflow-y-scroll scrollbar-thin scrollbar-webkit">
            <div class="col-span-full md:col-span-4 h-[372px] w-full flex items-center justify-center rounded-[5px] bg-grey-50 outline-dashed outline-2 outline-grey-200"></div>
            <div class="col-span-full md:col-span-8 flex flex-col items-start gap-6 self-stretch">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 self-stretch">
                    <TextInput
                        :inputName="'name'"
                        :labelText="'Group name'"
                        :placeholder="'eg: Heineken, Carlsberg etc'"
                        :errorMessage="form.errors?.name || ''"
                        v-model="form.name"
                        class="col-span-full md:col-span-9"
                    />
                    <Dropdown
                        :inputName="'category_id'"
                        :labelText="'Category'"
                        :inputArray="categoryArr.value"
                        :dataValue="parseInt(props.group.category_id)"
                        :errorMessage="form.errors?.category_id || ''"
                        v-model="form.category_id"
                        class="col-span-full md:col-span-3"
                    />
                </div>
                <div class="flex flex-col items-end gap-4 self-stretch">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 self-stretch" v-for="(item, i) in updatedInventoryItemsArr" :key="i">
                        <TextInput
                            :inputName="'items.'+ i +'.item_name'"
                            :labelText="'Item '+ (i + 1) +' name'"
                            :placeholder="'eg: Heineken Bottle 500ml'"
                            :errorMessage="(form.errors) ? form.errors['items.' + i + '.item_name']  : ''"
                            v-model="item.item_name"
                            class="col-span-full md:col-span-8"
                        />
                        <TextInput
                            :inputName="'items.'+ i +'.item_code'"
                            :labelText="'Item code'"
                            :placeholder="'eg: H001'"
                            :errorMessage="(form.errors) ? form.errors['items.' + i + '.item_code']  : ''"
                            v-model="item.item_code"
                            class="col-span-full md:col-span-2"
                        />
                        <Dropdown
                            :inputName="'items.'+ i +'.item_cat_id'"
                            :labelText="'Unit'"
                            :inputArray="props.itemCategoryArr"
                            :dataValue="item.item_cat_id"
                            :errorMessage="(form.errors) ? form.errors['items.' + i + '.item_cat_id']  : ''"
                            v-model="item.item_cat_id"
                            class="col-span-full md:col-span-2"
                        />
                    </div>
                </div>
                <Button
                    :type="'button'"
                    :variant="'secondary'"
                    :iconPosition="'left'"
                    :size="'lg'"
                    class="col-span-full"
                    @click="addItem"
                    >
                    <template #icon>
                        <PlusIcon
                            class="w-6 h-6"
                        />
                    </template>
                    New Item
                </Button>
            </div>
        </div>
        <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="cancelForm"
            >
                Dicard
            </Button>
            <Button
                :size="'lg'"
                :disabled="!isFormValid"
            >
                Save Changes
            </Button>
        </div>
    </form>
</template>
