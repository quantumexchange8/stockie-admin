<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import Button from '@/Components/Button.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DragDropImage from '@/Components/DragDropImage.vue'
import NumberCounter from '@/Components/NumberCounter.vue';
import InputError from "@/Components/InputError.vue";
import { DeleteIcon } from '@/Components/Icons/solid';
import { useInputValidator } from '@/Composables';

const props = defineProps({
    point: {
        type: Object,
        default: () => {},
    },
    inventoriesArr: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);
const { isValidNumberKey } = useInputValidator();
const inventoriesArr = ref(props.inventoriesArr);

const form = useForm({
    id: props.point.id,
    image: props.point.image ?? '',
    name: props.point.name,
    point: props.point.point.toString(),
    itemsDeletedBasket: [],
    items: props.point.point_items.map((item) => {
        return {
            ...item,
        }
    }) ?? [],
});

const formSubmit = () => { 
    form.post(route('loyalty-programme.points.update', form.id), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => cancelForm(),
    })
};

const cancelForm = () => {
    form.reset();
    emit('close');
}

// need to update this to delete the actual item from db
const deleteItem = (index, itemId) => {
    form.itemsDeletedBasket.push(itemId);

    form.items.splice(index, 1);
}

const updateInventoryStockCount = async (index, id) => {
    if (id != undefined) {
        try {
            const { data } = await axios.get(`/menu-management/products/getInventoryItemStock/${id}`);
            const item = form.items[index];
            item.inventory_stock_qty = data.stock_qty;
            item.item_qty = data.stock_qty >= 1 ? 1 : 0;
        } catch (error) {
            console.error(error);
        }
    }
}

const isFormValid = computed(() => {
    return ['name', 'point'].every(field => form[field]) && form.items.length > 0;
});

</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 max-h-[calc(100dvh-12rem)] pl-1 pr-2 py-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
            <DragDropImage
                :inputName="'image'"
                :errorMessage="form.errors?.image || ''"
                :modelValue="form.image"
                v-model="form.image"
                class="col-span-full md:col-span-4 h-[372px]"
            />
            <div class="col-span-full md:col-span-8 flex flex-col items-start gap-6 flex-[1_0_0] self-stretch">
                <div class="flex flex-col items-start self-stretch gap-4">
                    <div class="flex flex-col items-start gap-6 self-stretch">
                        <div class="w-full grid grid-cols-1 sm:grid-cols-12 gap-4">
                            <TextInput
                                :inputId="'name'"
                                :labelText="'Redeemable item name'"
                                :placeholder="'eg: Heineken Light 500ml'"
                                :errorMessage="form.errors?.name || ''"
                                v-model="form.name"
                                class="col-span-full sm:col-span-8"
                            />
                            <TextInput
                                :inputId="'point'"
                                :labelText="'Redeemed with'"
                                :iconPosition="'right'"
                                :errorMessage="form.errors?.point || ''"
                                v-model="form.point"
                                @keypress="isValidNumberKey($event, false)"
                                class="col-span-full sm:col-span-4 [&>div>input]:text-center"
                            >
                                <template #prefix>pts</template>
                            </TextInput>
                        </div>
                        <div 
                            v-for="(item, i) in form.items" :key="i"
                            class="flex items-start justify-center gap-6 self-stretch" 
                        >
                            <div class="flex flex-col w-full">
                                <Dropdown
                                    :inputName="'inventory_item_id_' +  i"
                                    :labelText="'Select item'"
                                    :inputArray="inventoriesArr"
                                    :grouped="true"
                                    :errorMessage="form.errors ? form.errors['items.' + i + '.inventory_item_id']  : ''"
                                    :dataValue="item.inventory_item_id"
                                    v-model="item.inventory_item_id"
                                    @onChange="updateInventoryStockCount(i, $event)"
                                >
                                    <template #optionGroup="group">
                                        <div class="flex flex-nowrap items-center gap-3">
                                            <!-- <div class="bg-grey-50 border border-grey-200 h-6 w-6"></div> -->
                                            <img 
                                                :src="group.group_image ? group.group_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt=""
                                                class="bg-grey-50 border border-grey-200 h-6 w-6"
                                            >
                                            <span class="text-grey-400 text-base font-bold">{{ group.group_name }}</span>
                                        </div>
                                    </template>
                                </Dropdown>
                                <InputError :message="form.errors ? form.errors['items.' + i + '.item_qty']  : ''" v-if="form.errors" />
                            </div>
                            <NumberCounter
                                :labelText="'Quantity of item in this set'"
                                :inputName="'item_qty_' + i"
                                :minValue="1"
                                :maxValue="item.inventory_stock_qty"
                                v-model="item.item_qty"
                                class="!w-fit whitespace-nowrap"
                            />
                            <DeleteIcon
                                class="w-6 h-6 self-center flex-shrink-0 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                                @click="deleteItem(i, item.id)"
                                v-if="form.items.length > 1"
                            />
                        </div>
                    </div>
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
                Save Changes
            </Button>
        </div>
    </form>
</template>
