<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import Button from '@/Components/Button.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DragDropImage from '@/Components/DragDropImage.vue'
import { DeleteIcon, PlusIcon } from '@/Components/Icons/solid';
import { keepOptions, defaultInventoryItem } from '@/Composables/constants';
import RadioButton from '@/Components/RadioButton.vue';
import { useCustomToast, useInputValidator } from '@/Composables/index.js';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    errors: Object,
    group: {
        type: Object,
        required: true,
    },
    selectedGroup: {
        type: Array,
        default: () => [],
    },
    // categoryArr: {
    //     type: Array,
    //     default: () => [],
    // },
    itemCategoryArr: {
        type: Array,
        default: () => [],
    },
});

const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const emit = defineEmits(['close', 'isDirty'])

const isUnsavedChangesOpen = ref(false);

const cancelForm = (status) => {
    emit('close', 'edit', status)
}

const form = useForm({
    id: props.group.id,
    name: props.group.name,
    // category_id: parseInt(props.group.category_id),
    image: props.group.inventory_image ? props.group.inventory_image : '',
    items: props.selectedGroup 
            ?   props.selectedGroup.map((item) => {
                    item.low_stock_qty = item.low_stock_qty.toString();
                    item.stock_qty = item.stock_qty.toString();
                    return item;
                })
            :   [],
});

const formSubmit = () => { 
    form.post(route('inventory.updateInventoryAndItems', props.group.id), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            showMessage({
                severity: 'success',
                summary: 'Changes saved.',
            });

            form.reset();
            cancelForm('leave');
        },
    })
};

const requiredFields = ['name', 'image', 'items'];

const isFormValid = computed(() => requiredFields.every(field => form[field]) && !form.processing);

const addItem = () => form.items.push({ ...defaultInventoryItem, inventory_id: props.group.id });

const removeItem = (index) => {
    if (form.items.length === 1) {
        Object.assign(form.items[0], { ...defaultInventoryItem, inventory_id: props.group.id });
    } else {
        form.items.splice(index, 1);
    }
}

watch(form, (newValue) => emit('isDirty', newValue.isDirty));
</script>

<template>
    <form class="flex flex-col gap-6 min-h-full max-h-screen" novalidate @submit.prevent="formSubmit">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pl-1 pr-2 py-1 max-h-[calc(100dvh-18rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
            <!-- <div class="col-span-full md:col-span-4 h-[372px] w-full flex items-center justify-center rounded-[5px] bg-grey-50 outline-dashed outline-2 outline-grey-200"></div> -->
             <DragDropImage
                :inputName="'image'"
                :remarks="'Suggested image size: 1200 x 1200 pixel'"
                :model="form.image"
                :errorMessage="form.errors.image"
                v-model="form.image"
                class="col-span-full md:col-span-4 h-[372px] py-1"
             />
            <div class="col-span-full md:col-span-8 flex flex-col items-start gap-6 self-stretch">
                <TextInput
                    :inputName="'name'"
                    :labelText="'Group name'"
                    :placeholder="'eg: Beer'"
                    :errorMessage="form.errors?.name || ''"
                    v-model="form.name"
                />
                <!-- <div class="grid grid-cols-1 md:grid-cols-12 gap-3 self-stretch">
                    <Dropdown
                        :inputName="'category_id'"
                        :labelText="'Category'"
                        :inputArray="categoryArr"
                        :dataValue="parseInt(props.group.category_id)"
                        :errorMessage="form.errors?.category_id || ''"
                        v-model="form.category_id"
                        class="col-span-full md:col-span-3"
                    />
                </div> -->
                <div class="flex flex-col items-end gap-4 self-stretch">
                    <div class="flex flex-col gap-4 self-stretch" v-for="(item, i) in form.items" :key="i">
                        <div class="flex flex-row items-center justify-between self-stretch">
                            <p class="text-grey-950 font-semibold text-md">Item {{ i + 1 }}</p>
                            <DeleteIcon class="size-6 self-center flex-shrink-0 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer" @click="removeItem(i)" />
                        </div>

                        <div class="flex flex-col items-start gap-y-3 self-stretch">
                            <div class="flex flex-row items-start gap-x-3 self-stretch">
                                <TextInput
                                    :inputName="'items.'+ i +'.item_name'"
                                    :labelText="'Item '+ (i + 1) +' name'"
                                    :placeholder="'eg: Heineken Bottle 500ml'"
                                    :errorMessage="(form.errors) ? form.errors['items.' + i + '.item_name']  : ''"
                                    v-model="item.item_name"
                                />
                                <Dropdown
                                    :inputName="'items.'+ i +'.item_cat_id'"
                                    :labelText="'Unit'"
                                    :inputArray="props.itemCategoryArr"
                                    :dataValue="item.item_cat_id"
                                    :errorMessage="(form.errors) ? form.errors['items.' + i + '.item_cat_id']  : ''"
                                    v-model="item.item_cat_id"
                                />
                            </div>
                            <div class="flex flex-row items-start justify-around gap-x-3 self-stretch">
                                <TextInput
                                    :inputName="'items.'+ i +'.item_code'"
                                    :labelText="'Item code'"
                                    :placeholder="'eg: H001'"
                                    :errorMessage="(form.errors) ? form.errors['items.' + i + '.item_code']  : ''"
                                    v-model="item.item_code"
                                />
                                <TextInput
                                    :inputName="'item_'+ i +'_stock_qty'"
                                    :labelText="'Current stock'"
                                    :placeholder="'e.g. 100'"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.stock_qty'] : ''"
                                    v-model="item.stock_qty"
                                    @keypress="isValidNumberKey($event, false)"
                                />
                                <TextInput
                                    :inputName="'item_'+ i +'_low_stock_qty'"
                                    :labelText="'Show low stock at'"
                                    :placeholder="'e.g. 25'"
                                    :errorMessage="(form.errors) ? form.errors['items.' + i + '.low_stock_qty']  : ''"
                                    v-model="item.low_stock_qty"
                                    @keypress="isValidNumberKey($event, false)"
                                />
                           </div>
                            <div class="flex items-start gap-10">
                                <RadioButton
                                    :optionArr="keepOptions"
                                    :checked="item.keep"
                                    v-model:checked="item.keep"
                                />
                            </div>
                        </div>
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
                        <PlusIcon class="size-6 flex-shrink-0" />
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
                @click="cancelForm('close')"
            >
                Discard
            </Button>
            <Button
                :size="'lg'"
                :disabled="!isFormValid"
            >
                Save Changes
            </Button>
        </div>
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="close('create', 'stay')"
            @leave="close('create', 'leave')"
        />
    </form>
</template>
