<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Modal from '@/Components/Modal.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Tag from '@/Components/Tag.vue';
import { useForm } from '@inertiajs/vue3';
import { FilterMatchMode } from 'primevue/api';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    id: Number,
    productsToAdd: {
        type: Array,
        default: () => {},
    }
    
})
const emit = defineEmits(['closeModal', 'isDirty']);
const products = ref(props.productsToAdd);
const isAddingProductEmpty = ref(true);
const isUnsavedChangesOpen = ref(false);
const addingProduct = ref([]);
const form = useForm({
    addingProduct: addingProduct.value,
    comm_id: props.id
})
const toggleAddProduct = (id) => {
    const index = addingProduct.value.indexOf(id);

    if (index === -1) {
        addingProduct.value.push(id);
    } else {
        addingProduct.value.splice(index, 1);
    }

    if (addingProduct.value.length === 0) {
        isAddingProductEmpty.value = true;
    } else {
        isAddingProductEmpty.value = false;
    }
};

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const submit = () => {
    form.addingProduct = addingProduct;
    form.post(route('configurations.addProducts', form.addingProduct), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            unsaved('leave');
        },
        onError: (error) => {
            console.error(error);
        }
    })
}

const unsaved = (status) => {
    emit('closeModal', status);
}

const filterProducts = computed(() => {
    if (!filters.value['global'].value) {
        return products.value;
    }

    const searchQuery = filters.value['global'].value.toLowerCase();

    return products.value.filter(product => {
        return product.product_name.toLowerCase().includes(searchQuery);
    });
});

watch(
    addingProduct,
    (newValue) => {
        // Update `isDirty` by emitting the status of `addingProduct`
        emit('isDirty', newValue.length > 0); // `isDirty` is true if array is not empty
    },
    { deep: true } // Watch deeply to track changes in array content
);

</script>

<template>
    <form @submit.prevent="submit">
        <div class="flex flex-col items-start gap-6 rounded-[5px] bg-white">
            <SearchBar 
                :placeholder="'Search'"
                :showFilter="false"
                v-model="filters['global'].value"
            >
            </SearchBar>

            <div class="flex flex-col items-start self-stretch divide-y divide-grey-100 max-h-[300px] overflow-auto scrollbar-webkit scrollbar-thin">
                <template v-for="product in filterProducts" :key="product.index">
                    <div class="flex justify-between items-center self-stretch py-2.5">
                        <div class="flex items-center gap-3">
                            <img 
                                :src="product.image ? product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt=""
                                class="object-contain w-[60px] h-[60px] flex-shrink-0"
                            >
                            <div class="flex items-center gap-2 flex-[1_0_0]">
                                <Tag
                                    :variant="'default'"
                                    :value="'Set'"
                                    v-if="product.bucket === 'set'"
                                />
                                <span class="flex flex-col justify-center flex-[1_0_0] overflow-hidden text-grey-900 text-ellipsis whitespace-nowrap text-sm font-medium">{{ product.product_name }}</span>
                            </div>
                        </div>
                        
                        <Checkbox
                            :checked="addingProduct.includes(product.id)"
                            @click=toggleAddProduct(product.id);
                        />
                    </div>
                </template>
            </div>

            <div class="flex flex-col justify-end items-center gap-5 self-stretch">
                <span class="self-stretch text-grey-900 text-right text-base font-medium">Total selected: {{ addingProduct.length }}</span>
                <div class="flex justify-center items-end gap-4 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="unsaved('close')"
                    >
                        Cancel
                    </Button>
                    <Button
                        :type="'submit'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="isAddingProductEmpty || form.processing"
                    >
                        Add
                    </Button>
                </div>
            </div>
        </div>
    </form>
    <Modal
        :unsaved="true"
        :maxWidth="'2xs'"
        :withHeader="false"
        :show="isUnsavedChangesOpen"
        @close="unsaved('stay')"
        @leave="unsaved('leave')"
    >
    </Modal>

</template>
