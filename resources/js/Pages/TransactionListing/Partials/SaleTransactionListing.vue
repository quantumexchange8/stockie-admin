<script setup>
import Button from '@/Components/Button.vue';
import { DotVerticleIcon, PlusIcon, ToastInfoIcon } from '@/Components/Icons/solid';
import SearchBar from "@/Components/SearchBar.vue";
import Checkbox from '@/Components/Checkbox.vue';
import Slider from "@/Components/Slider.vue";
import DateInput from '@/Components/Date.vue';
import Table from '@/Components/Table.vue';
import { computed, onMounted, ref, watch, watchEffect } from 'vue';
import { FilterMatchMode } from "primevue/api";
import Tag from '@/Components/Tag.vue';
import Modal from '@/Components/Modal.vue';
import { transactionFormat } from '@/Composables';
import TabView from "@/Components/TabView.vue";
import SalesDetail from './SalesDetail.vue';
import ProductSold from './ProductSold.vue';
import PaymentMethod from './PaymentMethod.vue';
import OverlayPanel from '@/Components/OverlayPanel.vue';
import axios from 'axios';
import { DeleteIllus } from '@/Components/Icons/illus';
import NumberCounter from '@/Components/NumberCounter.vue';
import { useForm } from '@inertiajs/vue3';
import RadioButton from '@/Components/RadioButton.vue';
import TextInput from '@/Components/TextInput.vue';
import dayjs from 'dayjs'

const clearFilters = (close) => {
    checkedFilters.value = resetFilters();
    searchQuery.value = '';
    rows.value = props.rows;
    emit('applyCategoryFilter', selectedCategory.value);
    emit('applyCheckedFilters', checkedFilters.value);
    close();
};

const salesColumn = ref([
    {field: 'receipt_end_date', header: 'Date & Time', width: '20', sortable: true},
    {field: 'receipt_no', header: 'Transaction No. ', width: '22', sortable: true},
    {field: 'grand_total', header: 'Total', width: '18', sortable: true},
    {field: 'customer.full_name', header: 'Customer', width: '20', sortable: true},
    {field: 'status', header: 'Status', width: '15', sortable: true},
    {field: 'action', header: '', width: '5', sortable: false},
]);

const saleTransaction = ref([]);
const date_filter = ref(''); 
const lastMonthDate = ref('');
const filters = ref({ 'global': { value: null, matchMode: FilterMatchMode.CONTAINS } });
const detailIsOpen = ref(false);
const voideIsOpen = ref(false);
const refundIsOpen = ref(false);
const consolidateIsOpen = ref(false);
const selectedVal = ref(null);
const { formatAmount } = transactionFormat();
const tabs = ref(["Sales Detail", "Product Sold", "Payment Method"]);
const op = ref(null);

const props = defineProps({
    selectedTab: Number,
})

const fetchTransaction = async (filters = {}) => {

    try {
        const response = await axios.get('/transactions/getSalesTransaction', {
            params: { dateFilter: filters }
        });

        saleTransaction.value = response.data;

    } catch (error) {
        console.error(error);
    }
};

onMounted(() => fetchTransaction());
onMounted(() => {
    const today = dayjs()
    const startOfLastMonth = today.subtract(1, 'month').startOf('month')
    const endOfLastMonth = today.subtract(1, 'month').endOf('month')

    lastMonthDate.value = `${startOfLastMonth.format('DD/MM/YYYY')} - ${endOfLastMonth.format('DD/MM/YYYY')}`
})

watch(date_filter, (newValue) => fetchTransaction(newValue));

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const saleRowsPerPage = ref(6);

const saleTotalPages = computed(() => {
    return Math.ceil(saleTransaction.value.length / saleRowsPerPage.value);
})

const action = (event, item) => {
    detailIsOpen.value = true;
    selectedVal.value = item;
}

const closeAction = () => {
    detailIsOpen.value = false;
    
}

const actionOption = (event) => {
    op.value.show(event)
}

const closeOverlay = () => {
    if (op.value)  op.value.hide();
};

const ConfirmVoid = () => {
    voideIsOpen.value = true;
    op.value.hide();
}

const closeConfirmmVoid = () => {
    voideIsOpen.value = false;
    op.value.hide();
}

const voidAction = async () => {
    if (selectedVal) {

        try {
            
            response = await axios.post('/transactions/void-transaction', {
                params: {
                    id: selectedVal.value.id
                }
            })

        } catch (error) {
            console.error(error);
        }

    }
}

const refundModal = () => {
    refundIsOpen.value = true;
    op.value.hide();
}

const closeRefundModal = () => {
    refundIsOpen.value = false;
}

const refundMethod = [
    { text: 'Cash', value: 'Cash'},
    { text: 'Others', value: 'Others'},
]

const openConsolidate = () => {
    consolidateIsOpen.value = true;
}
const closeConsolidate = () => {
    consolidateIsOpen.value = false;
}

const form = useForm({
    refund_method: '',
    refund_others: '',
    refund_item: [],
    refund_tax: false,
});

const updateRefundQty = (itemId, qty, productId) => {
    const orderItem = selectedVal.value.order?.filter_order_items.find(o => o.id === itemId);
    if (!orderItem || orderItem.item_qty === 0) return; // Prevent division by zero

    const unitPrice = parseFloat(orderItem.amount) / orderItem.item_qty; // Calculate per-unit price
    const refundAmount = qty * unitPrice; // Refund amount based on qty

    const existingItem = form.refund_item.find(item => item.id === itemId);
    if (existingItem) {
        existingItem.refund_quantities = qty;
        existingItem.refund_amount = refundAmount; // Update refund amount
        existingItem.product_id = productId; // Update refund amount
    } else {
        form.refund_item.push({ id: itemId, refund_quantities: qty, refund_amount: refundAmount, product_id: productId});
    }
};


const subTotalRefundAmount = computed(() => {
    if (!selectedVal.value || !selectedVal.value.order || !selectedVal.value.order.filter_order_items) {
        return "0.00"; // Return default value if data is missing
    }

    return form.refund_item.reduce((total, item) => {
        const orderItem = selectedVal.value.order.filter_order_items.find(o => o.id === item.id);
        if (!orderItem || orderItem.item_qty === 0) return total; // Prevent division by zero

        const unitPrice = parseFloat(orderItem.amount) / orderItem.item_qty; // Get per unit price
        return total + item.refund_quantities * unitPrice;
    }, 0).toFixed(2) || 0;
});

const totalSstRefund = computed(() => { 
    if (!selectedVal.value || !selectedVal.value.order || !selectedVal.value.order.filter_order_items) {
        return "0.00"; // Return default value if data is missing
    }

    if (form.refund_tax === true) {
        const subtotal = parseFloat(selectedVal.value.total_amount) || 0; // Ensure subtotal exists
        const sstAmount = parseFloat(selectedVal.value.sst_amount) || 0; // Ensure SST exists
        
        if (subtotal === 0) return "0.00"; // Prevent division by zero

        return ((subTotalRefundAmount.value / subtotal) * sstAmount).toFixed(2) || 0;
    }
    
});

const totalServiceTaxRefund = computed(() => { 
    if (!selectedVal.value || !selectedVal.value.order || !selectedVal.value.order.filter_order_items) {
        return "0.00"; // Return default value if data is missing
    }

    if (form.refund_tax === true) {
        const subtotal = parseFloat(selectedVal.value.total_amount) || 0; // Ensure subtotal exists
        const serviceTaxAmount = parseFloat(selectedVal.value.service_tax_amount) || 0; // Ensure SST exists

        if (subtotal === 0) return "0.00"; // Prevent division by zero

        return ((subTotalRefundAmount.value / subtotal) * serviceTaxAmount).toFixed(2) || 0;
    }
});

const totalRoundingRefund = computed(() => {
    if (!selectedVal.value || !selectedVal.value.order) return 0;

    if (form.refund_tax === true) {
        const subtotal = parseFloat(selectedVal.value.total_amount) || 0;
        const roundingAmount = parseFloat(selectedVal.value.rounding) || 0;

        if (subtotal === 0) return 0; // Prevent division by zero

        return ((subTotalRefundAmount.value / subtotal) * roundingAmount).toFixed(2) || 0;
    }
    
});

const totalRefundAmount = computed(() => {

    const subtotal = parseFloat(subTotalRefundAmount.value) || 0;
    const sst = parseFloat(totalSstRefund.value) || 0;
    const serviceTax = parseFloat(totalServiceTaxRefund.value) || 0;
    const rounding = parseFloat(totalRoundingRefund.value) || 0;

    const total = subtotal + sst + serviceTax + rounding;

    return total.toFixed(2);
})

const confirmRefund = async () => {

    try {

        response = await axios.post('/transactions/refund-transaction', {
            params: {
                id: selectedVal.value.id,
                customer_id: selectedVal.value.customer ? selectedVal.value.customer.id : 'Guest',
                form: form,
                refund_subtotal: subTotalRefundAmount.value,
                refund_sst: totalSstRefund.value,
                refund_service_tax: totalServiceTaxRefund.value,
                refund_rounding: totalRoundingRefund.value,
                refund_total: totalRefundAmount.value,
            }
        })

        if (response.status === 200) {
            closeConfirmmVoid();
            closeAction();
            fetchTransaction();
        }
        
    } catch (error) {
        console.error(error)
    }

}

const cancelRefund = () => {
    refundIsOpen.value = false;
}


</script>

<template>

    <div class="flex flex-col gap-5">
        <div class="flex lg:flex-col xl:flex-row items-center gap-5">
            <div class="flex items-center gap-3 w-full">
                <div class="w-full">
                    <SearchBar
                        placeholder="Search"
                        :showFilter="false"
                        v-model="filters['global'].value"
                        class="sm:max-w-[309px]"
                    />
                </div>
            </div>
            <div class="flex items-center lg:justify-between xl:justify-center gap-5 lg:w-full xl:max-w-[500px]">
                <div class="">
                    <DateInput
                        :inputName="'date_filter'"
                        :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                        :range="true"
                        class="w-2/3 sm:w-auto sm:!max-w-[309px]"
                        v-model="date_filter"
                    />
                </div>
                <div class="">
                    <Button
                        type="button"
                        variant="primary"
                        :size="'lg'"
                        iconPosition="left"
                        @click="openConsolidate"
                    >
                        <template #icon>
                            <PlusIcon />
                        </template>
                        Consolidate
                    </Button>
                </div>
            </div>
        </div>
        <div class="">
            <Table
                :columns="salesColumn"
                :rows="saleTransaction"
                :variant="'list'"
                :rowType="rowType"
                :totalPages="saleTotalPages"
                :rowsPerPage="saleRowsPerPage"
                :searchFilter="true"
                :filters="filters"
            >
                <template #grand_total="row">
                    <span class="text-grey-900 text-sm font-medium">RM {{ row.grand_total }}</span>
                </template>
                <template #customer.full_name="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.customer ? row.customer.full_name : 'Guest' }}</span>
                </template>
                <template #status="row">
                    <Tag
                        :variant="row.status === 'Successful' 
                                ? 'green' 
                                : row.status === 'Voided' 
                                    ? 'red'
                                    : 'grey'"
                        :value="row.status"
                    />
                </template>
                <template #action="row">
                    <div @click="action($event, row)" class="cursor-pointer">
                        <DotVerticleIcon />
                    </div>
                </template>
            </Table>
        </div>
    </div>

    <Modal
        :show="consolidateIsOpen"
        @close="closeConsolidate"
        :title="'Submit consolidated invoice'"
        :maxWidth="'lg'"
    >
        <div class="flex flex-col gap-8">
            <!-- Date -->
            <div class="w-full flex gap-6">
                <div class="flex flex-col gap-4 w-full">
                    <div class="flex flex-col gap-1">
                        <div class="text-grey-950 text-base font-bold">Date Period</div>
                        <div class="text-grey-950 text-sm">Transaction which has not yet been validated from last month.</div>
                    </div>
                    <div>
                        <DateInput
                            :inputName="'date_filter'"
                            :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                            :range="true"
                            class="w-2/3 sm:w-auto sm:!max-w-[309px]"
                            v-model="lastMonthDate"
                            disabled
                        />
                    </div>
                </div>
                <div class="flex flex-col gap-4 w-full">
                    <div class="flex flex-col gap-1">
                        <div class="text-grey-950 text-base font-bold">Document Type</div>
                        <div class="text-grey-950 text-sm">Document type you’ll consolidate.</div>
                    </div>
                    <div>
                        
                    </div>
                </div>
            </div>
            <!-- Submittable transaction -->
            <div></div>
        </div>
    </Modal>

    <Modal
        :show="detailIsOpen"
        @close="closeAction"
        :title="'Sales transaction detail'"
        :maxWidth="'sm'"
    >
        <!-- {{ selectedVal }} -->
        <div class="flex flex-col gap-6 ">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div>
                        <Tag 
                            :variant="selectedVal.status === 'Successful' ? 'green' : selectedVal.status === 'Voided' ? 'red' : 'grey' "  
                            :value="selectedVal.status === 'Successful' ? 'Completed' : 'Voided'" 
                        />
                    </div>
                    <div @click="actionOption($event, row)"><DotVerticleIcon /></div>
                </div>
                <div class="grid grid-cols-2 gap-5">
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Date & Time</div>
                        <div class="text-gray-900 text-base font-bold">{{ selectedVal.receipt_end_date }}</div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Transaction No.</div>
                        <div class="text-gray-900 text-base font-bold">{{ selectedVal.receipt_no }}</div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Total</div>
                        <div class="text-gray-900 text-base font-bold">RM {{ formatAmount(selectedVal.grand_total) }}</div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Customer</div>
                        <div class="text-gray-900 text-base font-bold flex items-center gap-2">
                            <div class="max-w-5 max-h-5" v-if="selectedVal.customer">
                                <img :src="selectedVal.customer.profile_photo ? selectedVal.customer.profile_photo : '' " alt="">
                            </div>
                            <div>{{ selectedVal.customer ?  selectedVal.customer.full_name : 'Guest'}}</div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Points Given</div>
                        <div class="text-gray-900 text-base font-bold">{{ selectedVal.customer ? selectedVal.point_earned : 0}} pts</div>
                    </div>
                </div>
            </div>
            <div>
                <TabView :tabs="tabs" :selectedTab="props.selectedTab ? props.selectedTab : 0">
                    <template #sales-detail>
                        <SalesDetail :selectedVal="selectedVal" />
                    </template>
                    <template #product-sold>
                        <ProductSold :selectedVal="selectedVal" />
                    </template>
                    <template #payment-method>
                        <PaymentMethod :selectedVal="selectedVal" />
                    </template>
                </TabView>
            </div>
        </div>

        <OverlayPanel ref="op" @close="closeOverlay" class="[&>div]:p-0">
            <div class="flex flex-col items-center border-2 border-primary-50 rounded-md">
                <Button
                    type="button"
                    variant="tertiary"
                    class="w-fit border-0 hover:bg-primary-50 !justify-start"
                    @click="ConfirmVoid"
                >
                    <span class="text-grey-700 font-normal">Void</span>
                </Button>
                <Button
                    type="button"
                    variant="tertiary"
                    class="w-fit border-0 hover:bg-primary-50 !justify-start"
                    @click="refundModal"
                >
                    <span class="text-grey-700 font-normal">Refund</span>
                </Button>
                <Button
                    type="button"
                    variant="tertiary"
                    class="w-fit border-0 hover:bg-primary-50 !justify-start"
                >
                    <span class="text-grey-700 font-normal">Print Receipt</span>
                </Button>
            </div>
        </OverlayPanel>

        <!-- Void -->
        <Modal
            :maxWidth="'2xs'"
            :closeable="true"
            :show="voideIsOpen"
            @close="closeConfirmmVoid"
            :withHeader="false"
        >
            <div class="flex flex-col gap-9">
                <div class="bg-primary-50 flex flex-col items-center gap-[10px] rounded-t-[5px] m-[-24px] pt-6 px-3">
                    <div class="w-full flex justify-center shrink-0">
                        <DeleteIllus />
                    </div>
                </div>
                <div class="flex flex-col gap-5 pt-6">
                    <div class="flex flex-col gap-1 text-center self-stretch">
                        <span class="text-primary-900 text-lg font-medium self-stretch">Void this transaction?</span>
                        <span class="text-grey-900 text-base font-medium self-stretch">Voiding this transaction will remove it from active records and reverse any associated points or balances. This action cannot be undone.</span>
                    </div>
                </div>
                <div class="flex justify-center items-start self-stretch gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeConfirmmVoid"
                    >
                        Keep
                    </Button>
                    <Button
                        variant="primary"
                        size="lg"
                        @click="voidAction"
                    >
                        Void
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Refund -->
        <Modal
            :maxWidth="'sm'"
            :closeable="true"
            :show="refundIsOpen"
            @close="closeRefundModal"
            :title="'Select refund item'"
        >
            <div class="flex flex-col gap-6 max-h-[80vh] overflow-y-scroll">
                <div class="p-4 flex items-center gap-10">
                    <div class="flex gap-3">
                        <div>
                            <ToastInfoIcon />
                        </div>
                        <div class="flex flex-col gap-1">
                            <div class="text-blue-500 text-base font-medium">Refunds are limited to cost items only.</div>
                            <div class="text-sm text-gray-700">Kept item, redeemed product, and entry reward item will not show in this list.</div>
                        </div>
                    </div>
                    <div>
                        <Button
                            
                        >
                            OK
                        </Button>
                    </div>
                </div>
                <div class="border border-gray-100 bg-white p-5 shadow-container flex flex-col gap-5 rounded-[5px] min-h-40 max-h-80 overflow-y-scroll">
                    <div class="text-gray-950 text-md font-semibold">Select refund item</div>
                    <div class="flex flex-col gap-4">
                        <div v-for="item in selectedVal.order.filter_order_items" :key="item.id" class="flex items-center gap-6"  >
                            <div class="text-gray-900 text-base font-normal">{{ item.item_qty }}x</div>
                            <div class="flex flex-col gap-1 w-full">
                                <div class="text-gray-900 text-base font-semibold max-w-[284px] truncate">{{ item.product.product_name }}</div>
                                <div class="flex items-center">
                                    <div v-if="item.discount_amount > 0" class="flex items-center gap-2">
                                        <span class="text-gray-900 text-base">RM{{ item.product_discount.price_after }} </span> 
                                        <span class="line-through text-gray-500 text-base ">RM{{ item.product_discount.price_before }} </span>
                                    </div>
                                    <span v-else>RM{{ item.amount }}</span>
                                </div>
                            </div>
                            <div>
                                <template v-if="item.item_qty - item.refund_qty > 0">
                                    <NumberCounter
                                        :labelText="''"
                                        :inputName="'qty_' + item.id"
                                        :minValue="0"
                                        :maxValue="item.item_qty - item.refund_qty"
                                        v-model="item.refund_quantities"
                                        @update:modelValue="(qty) => updateRefundQty(item.id, qty, item.product.id)"
                                        class="!w-fit whitespace-nowrap max-w-[139px]"
                                    />
                                </template>
                                <span v-else class="text-red-500 text-sm font-medium">Fully Refunded</span>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="border border-gray-100 bg-white p-5 shadow-container flex flex-col gap-5 rounded-[5px]">
                    <div class="text-gray-950 text-md font-semibold">Refund Method</div>
                    <div>
                        <RadioButton
                            :optionArr="refundMethod"
                            :checked="form.refund_method"
                            v-model:checked="form.refund_method"
                        />
                    </div>
                    <div v-if="form.refund_method === 'Others'">
                        <TextInput
                            label-text=""
                            :inputType="'text'"
                            :placeholder="'Enter others details'"
                            v-model="form.refund_others"
                            autofocus
                            autocomplete="refund_others"
                        />
                    </div>
                </div>
                <div class="border border-gray-100 bg-white p-5 shadow-container flex flex-col gap-5 rounded-[5px]">
                    <div class="text-gray-950 text-md font-semibold">Refund Reason</div>
                    <div>
                        <TextInput
                            label-text=""
                            :inputType="'text'"
                            :placeholder="'Enter refund reason'"
                            v-model="form.refund_reason"
                            autofocus
                            autocomplete="refund_reason"
                        />
                    </div>
                </div>
                <div class=" py-4 px-3 flex flex-col gap-5 bg-[#FCFCFC]">
                    <div class="flex items-center justify-between">
                        <div>Total Refund</div>
                        <div>RM {{ totalRefundAmount }}</div>
                    </div>
                    <div v-if="form.refund_tax === true" class="flex flex-col gap-1">
                        <div class="flex items-center w-full">
                            <div class="text-gray-900 text-base w-full">Sub-total</div>
                            <div class="text-gray-900 font-bold text-base text-right w-full">{{ subTotalRefundAmount }}</div>
                        </div>
                        <div class="flex items-center w-full">
                            <div class="text-gray-900 text-base w-full">SST(6%)</div>
                            <div class="text-gray-900 font-bold text-base text-right w-full">{{ totalSstRefund }}</div>
                        </div>
                        <div class="flex items-center w-full">
                            <div class="text-gray-900 text-base w-full">Service Tax</div>
                            <div class="text-gray-900 font-bold text-base text-right w-full">{{ totalServiceTaxRefund }}</div>
                        </div>
                        <div class="flex items-center w-full">
                            <div class="text-gray-900 text-base w-full">Rouding</div>
                            <div class="text-gray-900 font-bold text-base text-right w-full">{{ totalRoundingRefund }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox 
                            v-model:checked="form.refund_tax"
                            :value="'refund_with_tax'"
                        />
                        <span>Refund with taxes</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-full">
                        <Button size="lg" variant="tertiary" class="w-full" @click="cancelRefund">
                            Cancel
                        </Button>
                    </div>
                    <div class="w-full">
                        <Button size="lg" variant="primary" class="w-full" @click="confirmRefund">
                            Confirm
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    </Modal>

</template>