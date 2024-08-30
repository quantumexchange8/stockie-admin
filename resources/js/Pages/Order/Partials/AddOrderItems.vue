<script setup>
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import Button from '@/Components/Button.vue';
import TabView from '@/Components/TabView.vue';
import SearchBar from '@/Components/SearchBar.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';

const props = defineProps({
    errors: Object,
    orderId: String,
    categoryArr: Array,
})

const emit = defineEmits(['close']);

const tabs = ref(['All']);
const products = ref([]);
const categories = ref(props.categoryArr.map((cat) => {
    return {
        ...cat,
        downsizedText: cat.text.toLowerCase()
    }
}));

const query = ref('');

const form = useForm({
    items: [],
});

onMounted(async() => {
    try {
        const productsResponse = await axios.get(route('orders.getAllProducts'));
        products.value = productsResponse.data;
        
        if (categories.value) {
            categories.value.forEach(category => {
                tabs.value.push(category.text);
            });
        }

        // Initialize form.items based on products
        form.items = products.value.map(product => ({
            product_id: product.id,
            add_item_qty: 0, // default quantity
        }));

    } catch (error) {
        console.error(error);
    } finally {

    }
});

const updateProductQuantity = (productId, quantity) => {
    const productIndex = form.items.findIndex(item => item.product_id === productId);
    if (productIndex !== -1) {
        form.items[productIndex].add_item_qty = quantity;
    }
};

const filteredProducts = computed(() => {
    if (!products.value) return [];

    const searchValue = query.value.toLowerCase();

    if (!query.value) return products.value;

    return products.value.filter(product => product.product_name.toLowerCase().includes(searchValue));
});

const filteredProductsByCategory = (category) => {
    if (!products.value) return [];
    
    const searchValue = query.value.toLowerCase();

    const filteredArr = products.value.filter(product => product.category_name === category);
    
    if (!query.value) return filteredArr;

    return filteredArr.filter(product => product.product_name.toLowerCase().includes(searchValue));
};
</script>

<template>
    <form novalidate @submit.prevent="formSubmit">
        <div class="flex flex-col gap-6 items-start rounded-[5px]">
            <div class="px-6 py-3 w-full">

                <TabView :tabs="tabs">
                    <template #all>
                        <div class="flex flex-col justify-center items start pt-4 gap-3">
                            <SearchBar 
                                :placeholder="'Search'"
                                :inputName="'searchbar'" 
                                :showFilter="false"
                                v-model="query"
                            />
                            <div class="flex flex-col justify-center divide-y-[0.5px] divide-grey-200">
                                <template v-if="filteredProducts.length > 0">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 items-center self-stretch py-3" v-for="product in filteredProducts">
                                        <div class="col-span-full sm:col-span-7 flex items-center gap-3">
                                            <div class="size-[60px] bg-primary-100 rounded-[1.5px] border-[0.3px] border-grey-100"></div>
                                            <div class="flex flex-col justify-center items-start self-stretch gap-2">
                                                <p class="text-grey-900 text-ellipsis overflow-hidden text-base font-medium self-stretch">{{ product.product_name }}</p>
                                                <div class="flex items-center gap-2">
                                                    <p class="text-primary-950 text-base font-medium">RM {{ product.price }}</p>
                                                    <span class="text-grey-200">&#x2022;</span>
                                                    <p class="text-green-700 text-base font-normal">{{ product.stock_left }} left</p>
                                                </div>
                                            </div>
                                        </div>
        
                                        <NumberCounter
                                            :inputName="`item_${product.id}_add_item_qty`"
                                            :errorMessage="(form.errors) ? form.errors[`item_${product.id}.add_item_qty`] : ''"
                                            :maxValue="product.stock_qty"
                                            v-model="form.items.find(item => item.product_id === product.id).add_item_qty"
                                            @update="updateProductQuantity(product.id, $event)"
                                            class="col-span-full sm:col-span-5"
                                        />
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="flex flex-col items-center justify-center gap-5">
                                        <UndetectableIllus />
                                        <span class="text-primary-900 text-sm font-medium">No product in this category yet...</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template 
                        v-for="category in categories" 
                        :key="category.text" 
                        v-slot:[category.downsizedText]
                    >
                        <div class="flex flex-col justify-center items start pt-4 gap-3">
                            <SearchBar 
                                :placeholder="'Search'"
                                :inputName="'searchbar'" 
                                :showFilter="false"
                                v-model="query"
                            />
                            <div class="flex flex-col justify-center divide-y-[0.5px] divide-grey-200">
                                <template v-if="filteredProductsByCategory(category.text).length > 0">
                                    <div 
                                        class="grid grid-cols-1 sm:grid-cols-12 items-center self-stretch py-3" 
                                        v-for="product in filteredProductsByCategory(category.text)"
                                        :key="product.id" 
                                    >
                                        <div class="col-span-full sm:col-span-7 flex items-center gap-3">
                                            <div class="size-[60px] bg-primary-100 rounded-[1.5px] border-[0.3px] border-grey-100"></div>
                                            <div class="flex flex-col justify-center items-start self-stretch gap-2">
                                                <p class="text-grey-900 text-ellipsis overflow-hidden text-base font-medium self-stretch">{{ product.product_name }}</p>
                                                <div class="flex items-center gap-2">
                                                    <p class="text-primary-950 text-base font-medium">RM {{ product.price }}</p>
                                                    <span class="text-grey-200">&#x2022;</span>
                                                    <p class="text-green-700 text-base font-normal">{{ product.stock_left }} left</p>
                                                </div>
                                            </div>
                                        </div>

                                        <NumberCounter
                                            :inputName="`item_${product.id}_add_item_qty`"
                                            :errorMessage="(form.errors) ? form.errors[`item_${product.id}.add_item_qty`] : ''"
                                            :maxValue="product.stock_qty"
                                            v-model="form.items.find(item => item.product_id === product.id).add_item_qty"
                                            @update="updateProductQuantity(product.id, $event)"
                                            class="col-span-full sm:col-span-5"
                                        />
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="flex flex-col items-center justify-center gap-5">
                                        <UndetectableIllus />
                                        <span class="text-primary-900 text-sm font-medium">No product in this category yet...</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </TabView>
            </div>

            <div class="fixed bottom-0 w-full flex flex-col px-6 pt-6 pb-12 justify-center gap-6 self-stretch bg-white">
                <p class="self-stretch text-grey-900 text-right text-md font-medium">Total: RM{{ '223' }}</p>
                <Button
                    type="button"
                    size="lg"
                >
                    Add to Order
                </Button>
            </div>
        </div>
    </form>
</template>
    