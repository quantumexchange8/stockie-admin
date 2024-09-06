<script setup>
import axios from 'axios';
import { useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import Button from '@/Components/Button.vue';
import TabView from '@/Components/TabView.vue';
import SearchBar from '@/Components/SearchBar.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';

const props = defineProps({
    errors: Object,
    categoryArr: Array,
    selectedTable: String,
    order: {
        type: Object,
        default: () => {}
    }
})

const page = usePage();
const userId = computed(() => page.props.auth.user.id)

const emit = defineEmits(['close']);

const query = ref('');
const tabs = ref(['All']);
const products = ref([]);
const categories = ref(props.categoryArr.map((cat) => {
    return {
        ...cat,
        downsizedText: cat.text.toLowerCase()
    }
}));


const form = useForm({
    user_id: userId.value,
    order_id: props.order.id,
    table_no: props.selectedTable,
    items: [],
});

const formSubmit = () => { 
    form.post(route('orders.items.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    })
};


onMounted(async() => {
    try {
        const productsResponse = await axios.get(route('orders.getAllProducts'));
        products.value = productsResponse.data;
        
        categories.value.forEach(category => tabs.value.push(category.text));
    } catch (error) {
        console.error(error);
    } finally {

    }
});

const getItemQuantity = (productId) => {
    const item = form.items.find(item => item.product_id === productId);
    return item ? item.item_qty : 0;
};

const updateProductQuantity = (productId, quantity) => {
    const product = products.value.find(product => product.id === productId);
    if (product) {
        const existingItem = form.items.find(item => item.product_id === productId);
        if (existingItem) {
            if (quantity > 0) {
                existingItem.item_qty = quantity;
            } else {
                form.items = form.items.filter(item => item.product_id !== productId);
            }
        } else if (quantity > 0) {
            form.items.push({
                product_id: productId,
                price: product.price,
                point: product.point,
                item_qty: quantity,
                product_items: product.product_items
            });
        }
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

const calculatedTotalAmount = computed (() => {
    return form.items.reduce((total, item) => {
        if (item.item_qty > 0) {
            return total + (item.price * item.item_qty);
        }
        return total;
    }, 0).toFixed(2);
});

const quantityComputed = (productId) => {
    return computed({
        get: () => getItemQuantity(productId),
        set: (value) => updateProductQuantity(productId, value)
    });
};
</script>

<template>
    <form novalidate @submit.prevent="formSubmit">
        <div class="flex flex-col gap-6 items-start rounded-[5px]">
            <div class="flex flex-col justify-center items-start gap-3 px-6 py-3 w-full">
                <SearchBar 
                    :placeholder="'Search'"
                    :inputName="'searchbar'" 
                    :showFilter="false"
                    v-model="query"
                />

                <div class="w-full max-h-[calc(100dvh-19.5rem)] pr-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
                    <TabView :tabs="tabs">
                        <template #all>
                            <div class="flex flex-col justify-center divide-y-[0.5px] divide-grey-200">
                                <template v-if="filteredProducts.length > 0">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 items-center self-stretch py-3" v-for="product in filteredProducts">
                                        <div class="col-span-full sm:col-span-8 flex items-center gap-3">
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
                                            :inputName="`item_${product.id}_item_qty`"
                                            :errorMessage="(form.errors) ? form.errors[`item_${product.id}.item_qty`] : ''"
                                            :maxValue="product.stock_left"
                                            v-model="quantityComputed(product.id).value"
                                            @onChange="updateProductQuantity(product.id, $event)"
                                            class="col-span-full sm:col-span-4"
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
                        </template>

                        <template 
                            v-for="category in categories" 
                            :key="category.text" 
                            v-slot:[category.downsizedText]
                        >
                            <div class="flex flex-col justify-center divide-y-[0.5px] divide-grey-200">
                                <template v-if="filteredProductsByCategory(category.text).length > 0">
                                    <div 
                                        class="grid grid-cols-1 sm:grid-cols-12 items-center self-stretch py-3" 
                                        v-for="product in filteredProductsByCategory(category.text)"
                                        :key="product.id" 
                                    >
                                        <div class="col-span-full sm:col-span-8 flex items-center gap-3">
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
                                            :inputName="`item_${product.id}_item_qty`"
                                            :errorMessage="(form.errors) ? form.errors[`item_${product.id}.item_qty`] : ''"
                                            :maxValue="product.stock_left"
                                            v-model="quantityComputed(product.id).value"
                                            @onChange="updateProductQuantity(product.id, $event)"
                                            class="col-span-full sm:col-span-4"
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
                        </template>
                    </TabView>
                </div>
            </div>

            <div class="fixed bottom-0 w-full flex flex-col px-6 pt-6 pb-12 justify-center gap-6 self-stretch bg-white">
                <p class="self-stretch text-grey-900 text-right text-md font-medium">Total: RM{{ calculatedTotalAmount }}</p>
                <Button
                    size="lg"
                    :disabled="form.items.length === 0"
                >
                    Add to Order
                </Button>
            </div>
        </div>
    </form>
</template>
    