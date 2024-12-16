<script setup>
import Button from '@/Components/Button.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import SearchBar from '@/Components/SearchBar.vue';
import Tapbar from '@/Components/Tapbar.vue';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    selectedProducts: {
        type: Array,
        default: () => [],
    },
    action: String,
    discountId: Number,
    dateFilter: [Date, Array],
})

const categories = ref([]);
const products = ref([]);
const filteredProducts = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');

const selectedCategory = ref(0);
const selectedProducts = ref(props.selectedProducts);
const tempArray = ref(props.selectedProducts);
const emit = defineEmits(['closeSelectProduct', 'cancelSelectProduct', 'dateFilter']);

const close = () => {
    selectedProducts.value = tempArray.value;
    emit('closeSelectProduct', selectedProducts.value);
    emit('dateFilter', selectedProducts.value);
}

const cancel = () => {
    emit('cancelSelectProduct');
}

const getData = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/configurations/getDiscount`, {
            method: 'GET',
            params: {
                id: props.discountId,
                date: props.dateFilter,
            }
        });
        categories.value = response.data.categories;
        products.value = response.data.productsAvailable;
        filteredProducts.value = products.value;
        categories.value = [...categories.value];
        categories.value.unshift({
            text: 'All',
            value: 0
        });
        selectedCategory.value = categories.value[0].value;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

const categoryHeader = (category) => {
    switch(category) {
        case 0: return 'All Product';
        case 1: return 'Beer';
        case 2: return 'Wine';
        case 3: return 'Liquor';
        case 4: return 'Others';
    };
}

const selectAllHeader = (category) => {
    if (category === 0)
        return 'Select All';
    else {
        return `Select all in '${categoryHeader(category)}'`
    }
}

const handleFilterChange = (filter) => {
    selectedCategory.value = filter;
    if(filter === 0){
        filteredProducts.value = [...products.value];
    } else {
        filteredProducts.value = products.value.filter(product => product.category_id === selectedCategory.value)
    }
}

const selectProduct = (product) => {
    const foundIndex = tempArray.value.findIndex(p => p.id === product.id);
    
    if (foundIndex !== -1) {
        tempArray.value.splice(foundIndex, 1);
    } else {
        tempArray.value.push(product);
    }
};

const selectAllProduct = (category) => {
    let categoryProducts;

    // If passed in is 0, select all products
    if (category === 0) { 
        categoryProducts = filteredProducts.value;
    } else {
        categoryProducts = filteredProducts.value.filter(product => product.category_id === category);
    }

    // Check if all products in the category are already selected
    const allSelected = categoryProducts.every(product => 
        tempArray.value.some(selected => selected.id === product.id)
    );

    if (allSelected) {
        // If all are selected, remove them
        tempArray.value = tempArray.value.filter(
            selected => !categoryProducts.some(product => product.id === selected.id)
        );
    } else {
        // Otherwise, add only those not already in tempArray
        const newProducts = categoryProducts.filter(product => 
            !tempArray.value.some(selected => selected.id === product.id)
        );
        tempArray.value = [...tempArray.value, ...newProducts];
    }
};

const isAllSelected = (category) => {
    if (category === 0) {
        return filteredProducts.value.every(product => 
            tempArray.value.some(selected => selected.id === product.id)
        );
    } else {
        // check if all products in the category are selected
        const categoryProducts = filteredProducts.value.filter(product => product.category_id === category);
        return categoryProducts.every(product => 
            tempArray.value.some(selected => selected.id === product.id)
        );
    }
};

const isFormValid = computed(() => {
    return tempArray.value.length > 0;
});

watch(() => searchQuery.value, (newValue) => {
    if (newValue === '') {
        if(tempArray.value === 0){
            filteredProducts.value = [...products.value];
        } else {
            filteredProducts.value = products.value.filter(product => product.category_id === tempArray.value)
        }
        return;
    }

    const query = newValue.toLowerCase();

    filteredProducts.value = filteredProducts.value.filter(product => {
        const productName = product.product_name.toLowerCase();
        const productPrice = product.price.toString().toLowerCase();

        return productName.includes(query) || productPrice.includes(query);
    });
}, { immediate: true });

watch(() => props.selectedProducts, (newValue) => {
    selectedProducts.value = newValue;
}, { immediate: true });

onMounted(() => {
    getData()
})
</script>

<template>
    <div class="flex flex-col items-end gap-5 rounded-[5px] bg-white" v-if="categories.length > 0">
        <SearchBar 
            placeholder="Search"
            :showFilter="false"
            v-model="searchQuery"
        />

        <Tapbar
            :optionArr="categories"
            :checked="selectedCategory"
            @update:checked="handleFilterChange"
        />

        <div class="flex flex-col items-start gap-3 self-stretch">
            <div class="flex justify-between items-center self-stretch px-2">
                <span class="text-grey-950 text-base font-bold">
                    {{ categoryHeader(selectedCategory) }}
                </span>
                <div class="flex items-center gap-3">
                    {{ selectAllHeader(selectedCategory) }}
                    <Checkbox 
                        :checked="isAllSelected(selectedCategory)"
                        @update:checked="selectAllProduct(selectedCategory)"
                    />
                </div>
            </div>
            <div class="flex flex-col items-start max-h-[316px] gap-0.5 self-stretch divide-y divide-grey-50 overflow-auto scrollbar-webkit">
                <template v-for="items in filteredProducts">
                    <div class="flex p-2 items-center gap-3 self-stretch">
                        <!-- <div class="size-10 bg-primary-200"></div> -->
                        <img 
                            :src="items.image ? items.image 
                                            : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt=""
                            class="size-10 object-contain"
                        />
                        <span class="line-clamp-1 flex-[1_0_0] text-grey-900 text-ellipsis text-sm font-medium">{{ items.product_name }}</span>
                        <Checkbox 
                            :checked="tempArray.some(product => product.id === items.id)"
                            :disabled="items.overlap && !(tempArray.some(product => product.id === items.id))"
                            @update:checked="selectProduct(items)"
                        />
                    </div>
                </template>
            </div>
        </div>
        <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="cancel"
            >
                Cancel
            </Button>
            <Button
                :type="'button'"
                :size="'lg'"
                :disabled="!isFormValid"
                @click="close"
            >
                Done
            </Button>
        </div>
    </div>
    <div class="w-full flex flex-col items-center" v-else>
        <UndetectableIllus class="w-44 h-44" />
        <span class="text-sm font-medium text-primary-900">No data can be shown yet...</span>
    </div>
</template>

