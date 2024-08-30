<script setup>
import axios from 'axios';
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import Button from '@/Components/Button.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DragDropImage from '@/Components/DragDropImage.vue'
import { PlusIcon } from '@/Components/Icons/solid';

const props = defineProps({
    errors: Object,
    itemCategoryArr: {
        type: Array,
        default: () => [],
    },
});

const categoryArr = ref([]);

const getAllCategories = async () => {
    try {
        const response = await axios.get('/inventory/inventory/getAllCategories');
        categoryArr.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => {
    getAllCategories()
});

const emit = defineEmits(['close'])

const form = useForm({
    name: '',
    category_id: '',
    image: '',
    items: [
        {
            item_name: '',
            item_code: '',
            item_cat_id: '',
            stock_qty: 0,
            status: 'In stock',
        },
    ],
});

const formSubmit = () => { 
    form.keep = form.keep ? 'Active': 'Inactive';

    form.post(route('inventory.store'), {
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
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pl-1 pr-2 py-1 max-h-[700px] overflow-y-scroll scrollbar-thin scrollbar-webkit">
            <DragDropImage
                :inputName="'image'"
                :errorMessage="form.errors.image"
                v-model="form.image"
                class="col-span-full md:col-span-4 h-[372px]"
            />
            <div class="col-span-full md:col-span-8 flex flex-col items-start gap-6 flex-[1_0_0] self-stretch">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 self-stretch">
                    <TextInput
                        :inputName="'name'"
                        :labelText="'Group name'"
                        :placeholder="'eg: Heineken, Carlsberg etc'"
                        :errorMessage="form.errors?.name || ''"
                        v-model="form.name"
                        class="col-span-full md:col-span-8"
                    />
                    <Dropdown
                        :inputName="'category_id'"
                        :labelText="'Category'"
                        :inputArray="categoryArr"
                        :errorMessage="form.errors?.category_id || ''"
                        v-model="form.category_id"
                        class="col-span-full md:col-span-4"
                    />
                </div>
                <div class="flex flex-col items-end gap-4 self-stretch">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 self-stretch" v-for="(item, i) in form.items" :key="i">
                        <TextInput
                            :inputName="'item_'+ i +'_name'"
                            :labelText="'Item '+ (i + 1) +' name'"
                            :placeholder="'eg: Heineken Bottle 500ml'"
                            :errorMessage="(form.errors) ? form.errors['items.' + i + '.item_name']  : ''"
                            v-model="item.item_name"
                            class="col-span-full md:col-span-5"
                        />
                        <TextInput
                            :inputName="'item_'+ i +'_code'"
                            :labelText="'Item code'"
                            :placeholder="'eg: H001'"
                            :errorMessage="(form.errors) ? form.errors['items.' + i + '.item_code']  : ''"
                            v-model="item.item_code"
                            class="col-span-full md:col-span-2"
                        />
                        <Dropdown
                            :inputName="'item_'+ i +'_cat_id'"
                            :labelText="'Unit'"
                            :inputArray="props.itemCategoryArr"
                            :errorMessage="(form.errors) ? form.errors['items.' + i + '.item_cat_id']  : ''"
                            v-model="item.item_cat_id"
                            class="col-span-full md:col-span-2"
                        />
                        <NumberCounter
                            :inputName="'item_'+ i +'_stock_qty'"
                            :labelText="'Stock (per unit)'"
                            :errorMessage="(form.errors) ? form.errors['items.' + i + '.stock_qty']  : ''"
                            v-model="item.stock_qty"
                            class="col-span-full md:col-span-3"
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
                Cancel
            </Button>
            <Button
                :size="'lg'"
                :disabled="!isFormValid"
            >
                Add
            </Button>
        </div>
    </form>
</template>
