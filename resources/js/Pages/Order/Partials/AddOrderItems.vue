<script setup>
import axios from 'axios';
import { useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import Button from '@/Components/Button.vue';
import TabView from '@/Components/TabView.vue';
import Modal from '@/Components/Modal.vue';
import SearchBar from '@/Components/SearchBar.vue';
import NumberCounter from '@/Components/NumberCounter.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import { useCustomToast } from '@/Composables/index.js';
import { ArrowLeftIcon } from '@/Components/Icons/solid';
import Tag from '@/Components/Tag.vue';

const props = defineProps({
    errors: Object,
    // categoryArr: Array,
    selectedTable: {
        type: Object,
        default: () => {}
    },
    order: {
        type: Object,
        default: () => {}
    },
    matchingOrderDetails: {
        type: Object,
        default: () => {}
    }
})

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)

const { showMessage } = useCustomToast();

const emit = defineEmits(['close', 'fetchZones', 'fetchOrderDetails','fetchPendingServe', 'update:hasItemInCart']);

const query = ref('');
const tabs = ref([
    { key: 'All', title: 'All', disabled: false },
]);
const products = ref([]);
const newOrderId = ref({});
const categories = ref([]);
const isStockDetailModalOpen = ref(false);
const selectedProduct = ref('');

const form = useForm({
    user_id: userId.value,
    order_id: props.order.id,
    action_type: '',
    matching_order_details: props.matchingOrderDetails,
    items: [],
});

const orderTableNames = computed(() => {
    return props.order.order_table
            ?.map((orderTable) => orderTable.table.table_no)
            .sort((a, b) => a.localeCompare(b))
            .join(', ') ?? '';
});

const submit = async () => { 
    form.processing = true;
    try {
        // Post using axios and get the new order id if new order is created
        const addItemResponse = await axios.post(route('orders.items.store'), form);
        newOrderId.value = addItemResponse.data;

        const summary = `Product has been added to ${orderTableNames.value} order` + (form.action_type === 'now' ? ' and served.' : '.');

        setTimeout(() => {
            showMessage({ 
                severity: 'success',
                summary: summary,
            });
        }, 200);

        emit('fetchZones');
        emit('fetchOrderDetails');
        emit('fetchPendingServe');
        emit('close');
        form.reset();
    } catch (error) {
        console.error(error);
    } finally {
        form.processing = false;
    }
    // form.post(route('orders.items.store'), {
    //     preserveScroll: true,
    //     preserveState: true,
    //     onSuccess: () => {
    //         const summary = `Product has been added to ${props.selectedTable.table_no} order` + (form.action_type === 'now' ? ' and served.' : '.');
    //         setTimeout(() => {
    //             showMessage({ 
    //                 severity: 'success',
    //                 summary: summary,
    //             });
    //         }, 200);
    //         form.reset();
    //         // emit('fetchZones');
    //         emit('close');
    //     },
    // })
};

onMounted(async() => {
    try {
        const categoryResponse = await axios.get(route('orders.getAllCategories'));
        categories.value = categoryResponse.data.map((cat) => {
            return {
                ...cat,
                downsizedText: cat.text.toLowerCase()
            }
        })
        
        categories.value.forEach(category => tabs.value.push({ key: category.text, title: category.text, disabled: false }));

        const productsResponse = await axios.get(route('orders.getAllProducts'));
        products.value = productsResponse.data;        
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
                product_items: product.product_items,
                discount_item: product.discount_item,
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
            return total + ((item.discount_item ? item.discount_item.price_after : item.price) * item.item_qty);
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

const isFormValid = computed(() => (form.items.length > 0 && !form.processing));

const getCurrentProductKeptAmount = (product) => {
    return product.product_items.reduce((total, item) => {
        return total + item.inventory_item.current_kept_amt;
    }, 0);
}
const getAvailableForSaleQty = (product) => {
    let temp = [];
    product.product_items.forEach((item) => {
        const qty = item.qty;
        const stockLeft = item.inventory_item.stock_qty;
        const keptAmt = item.inventory_item.current_kept_amt;

        let availableStockToSell = Math.floor((stockLeft + keptAmt) / qty);
        temp.push(availableStockToSell);
    });
    
    return Math.min(...temp);
}

const getTotalQtyAvailable = (product) => {
    return product.product_items.reduce((total, item) => {
        return total + (item.qty * product.stock_left);
    }, 0);
}

const openStockDetailItemModal = (product) => {
    selectedProduct.value = product;
    isStockDetailModalOpen.value = true;
}

const closeStockDetailItemModal = () => {
    isStockDetailModalOpen.value = false;
    setTimeout(()=> selectedProduct.value = '', 200);
}

watch(() => form.items.map(i => i.item_qty), (newValue) => {
    const totalQtyPlaced = newValue.reduce((total, qty) => total + qty, 0);
    emit('update:hasItemInCart', totalQtyPlaced > 0);
});

</script>

<template>
    <form novalidate @submit.prevent="submit">
        <div class="flex flex-col gap-6 items-start rounded-[5px]">
            <div class="w-full flex items-center px-6 pt-6 pb-3 justify-between">
                <ArrowLeftIcon
                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer" 
                    @click="$emit('close')" 
                />
                <div class="flex items-center justify-center w-full">
                    <span class="text-primary-950 text-center text-md font-medium">Order for {{ orderTableNames  }}</span>
                </div>
            </div>

            <div class="flex flex-col justify-center items-start gap-3 px-6 py-3 w-full">
                <SearchBar 
                    :placeholder="'Search'"
                    :inputName="'searchbar'" 
                    :showFilter="false"
                    v-model="query"
                />

                <div class="w-full max-h-[calc(100dvh-20.5rem)] pr-1 overflow-y-auto scrollbar-thin scrollbar-webkit">
                    <TabView :tabs="tabs">
                        <template #all>
                            <div class="flex flex-col justify-center divide-y-[0.5px] divide-grey-200">
                                <template v-if="filteredProducts.length > 0">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 items-center self-stretch py-3" v-for="product in filteredProducts">
                                        <div class="col-span-full sm:col-span-8 flex items-start gap-3">
                                            <img 
                                                :src="product.image ? product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt="ProductImage"
                                                :width="'60'"
                                                :height="'60'"
                                                :class="[
                                                    'w-[60px] h-[60px] min-w-[60px] min-h-[60px] rounded-[1.5px] border-[0.3px] border-grey-100 object-contain',
                                                    { 'opacity-30': getCurrentProductKeptAmount(product) + product.stock_left == 0 }
                                                ]"
                                            >
                                            <div class="flex flex-col justify-center items-start self-stretch gap-0.5">
                                                <div class="flex flex-nowrap gap-2 items-start">
                                                    <Tag value="Set" v-if="product.bucket === 'set'"/>
                                                    <p 
                                                        :class="[
                                                            'text-ellipsis overflow-hidden text-base font-medium',
                                                            getCurrentProductKeptAmount(product) + product.stock_left > 0 ? 'text-grey-900' : 'text-grey-500'
                                                        ]"
                                                    >
                                                        {{ product.product_name }}
                                                    </p>
                                                </div>
                                                <div class="flex flex-col gap-1 items-start">
                                                    <template v-if="product.stock_left > 0">
                                                        <div v-if="product.discount_id && product.discount_item" class="flex items-center gap-x-1.5">
                                                            <span class="line-clamp-1 text-ellipsis text-primary-950 text-base font-medium ">RM {{ product.discount_item.price_after }}</span>
                                                            <span class="line-clamp-1 text-grey-900 text-ellipsis text-xs font-medium line-through">RM {{ product.discount_item.price_before }}</span>
                                                        </div>
                                                        <span class="text-primary-950 text-base font-medium" v-else>RM {{ parseFloat(product.price).toFixed(2) }}</span>
                                                        <!-- <span class="text-grey-200">&#x2022;</span> -->
                                                    </template>
                                                    <p class="text-green-700 text-sm font-normal cursor-pointer" @click="openStockDetailItemModal(product)">
                                                        {{ getCurrentProductKeptAmount(product) + product.stock_left > 0
                                                                ? getCurrentProductKeptAmount(product) > 0
                                                                    ? `${getTotalQtyAvailable(product)} (+${getCurrentProductKeptAmount(product)}) left`
                                                                    : `${getTotalQtyAvailable(product)} left`
                                                                : product.status }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
        
                                        <NumberCounter
                                            :inputName="`item_${product.id}_item_qty`"
                                            :errorMessage="(form.errors) ? form.errors[`item_${product.id}.item_qty`] : ''"
                                            :maxValue="getCurrentProductKeptAmount(product) <= 0 
                                                    ? product.stock_left 
                                                    : product.bucket === 'set' 
                                                        ? getAvailableForSaleQty(product) 
                                                        : getCurrentProductKeptAmount(product) + product.stock_left"
                                            :disabled="getCurrentProductKeptAmount(product) > 0 
                                                    ? (getCurrentProductKeptAmount(product) + product.stock_left) === 0 
                                                    : product.stock_left === 0"
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
                                        <div class="col-span-full sm:col-span-8 flex items-start gap-3">
                                            <img 
                                                :src="product.image ? product.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt="ProductImage"
                                                :width="'60'"
                                                :height="'60'"
                                                :class="[
                                                    'w-[60px] h-[60px] min-w-[60px] min-h-[60px] rounded-[1.5px] border-[0.3px] border-grey-100 object-contain',
                                                    { 'opacity-30': getCurrentProductKeptAmount(product) + product.stock_left == 0 }
                                                ]"
                                            >
                                            <div class="flex flex-col justify-center items-start self-stretch gap-0.5">
                                                <div class="flex flex-nowrap gap-2 items-start">
                                                    <Tag value="Set" v-if="product.bucket === 'set'"/>
                                                    <p 
                                                        :class="[
                                                            'text-ellipsis overflow-hidden text-base font-medium',
                                                            getCurrentProductKeptAmount(product) + product.stock_left > 0 ? 'text-grey-900' : 'text-grey-500'
                                                        ]"
                                                    >
                                                        {{ product.product_name }}
                                                    </p>
                                                </div>
                                                <div class="flex flex-col gap-1 items-start">
                                                    <template v-if="product.stock_left > 0">
                                                        <div v-if="product.discount_id && product.discount_item" class="flex items-center gap-x-1.5">
                                                            <span class="line-clamp-1 text-ellipsis text-primary-950 text-base font-medium ">RM {{ product.discount_item.price_after }}</span>
                                                            <span class="line-clamp-1 text-grey-900 text-ellipsis text-xs font-medium line-through">RM {{ product.discount_item.price_before }}</span>
                                                        </div>
                                                        <span class="text-primary-950 text-base font-medium" v-else>RM {{ parseFloat(product.price).toFixed(2) }}</span>
                                                        <!-- <span class="text-grey-200">&#x2022;</span> -->
                                                    </template>
                                                    <p class="text-green-700 text-sm font-normal cursor-pointer" @click="openStockDetailItemModal(product)">
                                                        {{ getCurrentProductKeptAmount(product) + product.stock_left > 0
                                                                ? getCurrentProductKeptAmount(product) > 0
                                                                    ? `${product.stock_left} (+${getCurrentProductKeptAmount(product)}) left`
                                                                    : `${product.stock_left} left`
                                                                : product.status }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <NumberCounter
                                            :inputName="`item_${product.id}_item_qty`"
                                            :errorMessage="(form.errors) ? form.errors[`item_${product.id}.item_qty`] : ''"
                                            :maxValue="getCurrentProductKeptAmount(product) <= 0 
                                                    ? product.stock_left 
                                                    : product.bucket === 'set' 
                                                        ? getAvailableForSaleQty(product) 
                                                        : getCurrentProductKeptAmount(product) + product.stock_left"
                                            :disabled="getCurrentProductKeptAmount(product) > 0 
                                                    ? (getCurrentProductKeptAmount(product) + product.stock_left) === 0 
                                                    : product.stock_left === 0"
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
                <div class="flex gap-3">
                    <Button
                        size="lg"
                        variant="secondary"
                        :disabled="!isFormValid"
                        @click="form.action_type = 'later'"
                    >
                        Serve Later
                    </Button>
                    <Button
                        size="lg"
                        :disabled="!isFormValid"
                        @click="form.action_type = 'now'"
                    >
                        Serve Now
                    </Button>
                </div>
            </div>
        </div>
    </form>

    <Modal
        :title="'Stock detail'"
        :maxWidth="'xs'"
        :closeable="true"
        :show="isStockDetailModalOpen"
        @close="closeStockDetailItemModal"
    >
        <template v-if="selectedProduct">
        <div class="flex flex-col gap-y-4">
            <div class="flex flex-col items-start gap-6 rounded-[5px] bg-white">
                <div class="flex py-3 justify-between self-stretch items-start bg-blue-50 w-full">
                    <p class="text-blue-600 font-normal text-base">Available for sale</p>
                    <p class="text-blue-600 font-semibold text-base">{{ selectedProduct.bucket === 'set' ? getAvailableForSaleQty(selectedProduct) : getCurrentProductKeptAmount(selectedProduct) + selectedProduct.stock_left }} {{ selectedProduct.bucket === 'set' ? 'set' : '' }}</p>
                </div>
            </div>

            <div class="w-full divide-y divide-grey-200 flex flex-col">
                <div class="flex flex-col items-start gap-4 rounded-[5px] bg-white py-2" v-for="item in selectedProduct.product_items" :key="item.id">
                    <p class="text-grey-900 font-semibold text-medium">{{ item.inventory_item.item_name }}</p>
                    <div class="flex flex-col gap-y-2 w-full">
                        <div class="flex justify-between self-stretch items-center">
                            <p class="text-grey-900 font-normal text-base">Available stock</p>
                            <p class="text-grey-900 font-semibold text-base">{{ item.qty * selectedProduct.stock_left }} left</p>
                        </div>
                        <div class="flex justify-between self-stretch items-center">
                            <p class="text-grey-900 font-normal text-base">Kept item</p>
                            <p class="text-grey-900 font-semibold text-base">{{ item.inventory_item.current_kept_amt }} left</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </template>
    </Modal>
</template>
    