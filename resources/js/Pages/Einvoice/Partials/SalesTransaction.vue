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
import OverlayPanel from '@/Components/OverlayPanel.vue';
import axios from 'axios';
import { DeleteIllus } from '@/Components/Icons/illus';
import NumberCounter from '@/Components/NumberCounter.vue';
import { useForm } from '@inertiajs/vue3';
import RadioButton from '@/Components/RadioButton.vue';
import TextInput from '@/Components/TextInput.vue';
import TransactionList from './TransactionList.vue';
import Myinvois from './Myinvois.vue';

const salesColumn = ref([
    {field: 'c_datetime', header: 'Date & Time', width: '30', sortable: true},
    {field: 'c_invoice_no', header: 'Transaction No. ', width: '30', sortable: true},
    {field: 'c_total_amount', header: 'Total', width: '18', sortable: true},
    {field: 'docs_type', header: 'Type', width: '20', sortable: true},
    {field: 'status', header: 'Status', width: '15', sortable: true},
    {field: 'action', header: '', width: '5', sortable: false},
]);

const saleTransaction = ref([]);
const date_filter = ref(''); 
const filters = ref({ 'global': { value: null, matchMode: FilterMatchMode.CONTAINS } });
const detailIsOpen = ref(false);
const voideIsOpen = ref(false);
const refundIsOpen = ref(false);
const selectedVal = ref(null);
const { formatAmount, formatDate } = transactionFormat();
const tabs = ref(["Transaction List", "MyInvois"]);
const op = ref(null);

const props = defineProps({
    selectedTab: Number,
})

const fetchTransaction = async (filters = {}) => {

try {
    const response = await axios.get('/e-invoice/getAllSaleInvoice');

    saleTransaction.value = response.data;

} catch (error) {
    console.error(error);
}
};

onMounted(() => fetchTransaction());

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
            <div class="flex items-center lg:justify-between xl:justify-end gap-5 lg:w-full xl:max-w-[500px]">
                <div class="">
                    <DateInput
                        :inputName="'date_filter'"
                        :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                        :range="true"
                        class="w-2/3 sm:w-auto sm:!max-w-[309px]"
                        v-model="date_filter"
                    />
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
                <template #docs_type="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.docs_type === 'sales_transaction' ? 'Consolidated' : 'Single Submission' }}</span>
                </template>
                <template #status="row">
                    <Tag
                        :variant="row.status === 'Submitted' 
                                    ? 'green' 
                                : row.status === 'Cancelled' 
                                    ? 'grey'
                                : row.status === 'Pending'
                                    ? 'red' 
                                : 'red'"
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
        :show="detailIsOpen"
        @close="closeAction"
        :title="'Submission detail'"
        :maxWidth="'sm'"
    >
        <div class="flex flex-col gap-6 ">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div>
                        <Tag 
                            :variant="selectedVal.status === 'Submitted' ? 'green' : selectedVal.status === 'Valid' ? 'green' : selectedVal.status === 'Valid' ? 'red' : 'grey' "  
                            :value="selectedVal.status" 
                        />
                    </div>
                    <div @click="actionOption($event, row)"><DotVerticleIcon /></div>
                </div>
                <div class="grid grid-cols-2 gap-5">
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Submmission Date & Time</div>
                        <div class="text-gray-900 text-base font-bold">{{ selectedVal.c_datetime }}</div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Transaction No.</div>
                        <div class="text-gray-900 text-base font-bold">{{ selectedVal.c_invoice_no }}</div>
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Total</div>
                        <div class="text-gray-900 text-base font-bold">RM {{ formatAmount(selectedVal.c_total_amount) }}</div>
                    </div>
                    <div v-if="selectedVal.invoice_child.length > 0" class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Customer</div>
                        <div class="text-gray-900 text-base font-bold flex items-center gap-2">
                            <div>{{ selectedVal.user ?  selectedVal.user : 'General Public'}}</div>
                        </div>
                    </div>
                    <div v-if="selectedVal.docs_type === 'sales_transaction'" class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Date Period</div>
                        <div class="text-gray-900 text-base font-bold">{{ formatDate(selectedVal.c_period_start) }} - {{ formatDate(selectedVal.c_period_end) }}</div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Document Type</div>
                        <div class="text-gray-900 text-base font-bold">{{ selectedVal.docs_type === 'sales_transaction' ? 'Consolidated' : 'Single Submission' }}</div>
                    </div>
                </div>
            </div>
            <div>
                <TabView :tabs="tabs" :selectedTab="props.selectedTab ? props.selectedTab : 0">
                    <template #transaction-list>
                        <TransactionList :selectedVal="selectedVal" />
                    </template>
                    <template #myinvois>
                        <Myinvois :selectedVal="selectedVal" />
                    </template>
                </TabView>
            </div>
        </div>
    </Modal>

</template>