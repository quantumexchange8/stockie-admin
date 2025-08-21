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
import { CancelIllus, DeleteIllus } from '@/Components/Icons/illus';
import NumberCounter from '@/Components/NumberCounter.vue';
import { useForm } from '@inertiajs/vue3';
import RadioButton from '@/Components/RadioButton.vue';
import TextInput from '@/Components/TextInput.vue';
import TransactionList from './TransactionList.vue';
import Myinvois from './Myinvois.vue';
import { useCustomToast, useInputValidator } from '@/Composables/index.js';
import dayjs from 'dayjs';

const salesColumn = ref([
    {field: 'c_datetime', header: 'Date & Time', width: '30', sortable: true},
    {field: 'c_invoice_no', header: 'Transaction No. ', width: '30', sortable: true},
    {field: 'c_total_amount', header: 'Total', width: '18', sortable: true},
    {field: 'docs_type', header: 'Type', width: '20', sortable: true},
    {field: 'status', header: 'Status', width: '15', sortable: true},
    {field: 'action', header: '', width: '5', sortable: false},
]);

const saleTransaction = ref([]);
const initialSaleTransaction = ref([]);
const date_filter = ref(''); 
const detailIsOpen = ref(false);
const voideIsOpen = ref(false);
const refundIsOpen = ref(false);
const selectedVal = ref(null);
const { formatAmount, formatDate } = transactionFormat();
const tabs = ref([
    { key: 'Transaction List', title: 'Transaction List', disabled: false },
    { key: 'MyInvois', title: 'MyInvois', disabled: false },
]);
const op = ref(null);
const cancelSubmitFormIsOpen = ref(false);
const reason = ref('');
const reasonError = ref('');
const searchQuery = ref('');

const { showMessage } = useCustomToast();

const props = defineProps({
    selectedTab: Number,
})

const fetchTransaction = async (filters = {}) => {

try {
    const response = await axios.get('/e-invoice/getAllSaleInvoice', {
        params: { dateFilter: filters }
    });

    initialSaleTransaction.value = response.data;
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

const form = useForm({
    refund_method: '',
    refund_others: '',
    refund_item: [],
    refund_tax: false,
});

const ConfirmCancel = async () => {
    cancelSubmitFormIsOpen.value = true;
    op.value.hide();
}

const hideConfirmCancel = () => {
    cancelSubmitFormIsOpen.value = false;
}



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

const submit = async () => {
    reasonError.value = ''; // Reset error message

    if (!reason.value.trim()) {
        reasonError.value = 'Reason is required.';
        return;
    }

    try {
        
        const response = await axios.post('/e-invoice/cancel-submission', {
            invoice_id: selectedVal.value.id,
            reason: reason.value,
        });

        if (response.data.status === 'success') {
            showMessage({
                severity: 'success',
                summary: 'Consolidated Invoice Cancelled'
            });
            fetchTransaction();
        } else {
            showMessage({
                severity: 'error',
                summary: 'Invoice cannot be cancelled after 72 hours'
            });
        } 

    } catch (error) {
        console.error('error ', error);
        showMessage({
            severity: 'error',
            summary: 'Something went wrong, please try again later.'
        });
    }
}

const getStatusVariant = (status) => {
    if (!status) return 'red';

    const loweredStatus = status.toLowerCase();
    
    return loweredStatus === 'submitted' || loweredStatus === 'valid'
        ? 'green' 
        : loweredStatus === 'cancelled' 
            ? 'grey'
            : 'red';
};

const capFirstLetter = (status) => {
    return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Invalid';
};

watch(() => searchQuery.value, (newValue) => {
    if (newValue === '') {
        // If no search query, reset rows to props.rows
        saleTransaction.value = initialSaleTransaction.value;
        return;
    }

    const query = newValue.toLowerCase();

    saleTransaction.value = initialSaleTransaction.value.filter(row => {
        const cDateTime = dayjs(row.c_datetime).format('DD/MM/YYYY, HH:mm').toLowerCase();
        const invoiceNo = row.c_invoice_no.toLowerCase();
        const totalTotal = row.c_total_amount.toString().toLowerCase();
        const docType = row.docs_type.toLowerCase();
        const status = row.status?.toLowerCase() ?? '';

        return  cDateTime.includes(query) ||
                invoiceNo.includes(query) ||
                totalTotal.includes(query) ||
                docType.includes(query) ||
                status.includes(query);
    });
}, { immediate: true });

const PrintInvoice = (id) => {
    window.open(`/e-invoice/downloadInvoice/${id}/download`, "_blank");
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
                        v-model="searchQuery"
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
            >
                <template #grand_total="row">
                    <span class="text-grey-900 text-sm font-medium">RM {{ row.grand_total }}</span>
                </template>
                <template #docs_type="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.docs_type === 'sales_transaction' ? 'Consolidated' : 'Single Submission' }}</span>
                </template>
                <template #status="row">
                    <Tag
                        :variant="getStatusVariant(row.status)"
                        :value="capFirstLetter(row.status)"
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
        <div class="flex flex-col gap-6 overflow-y-auto scrollbar-thin scrollbar-webkit max-h-[calc(100dvh-8rem)]">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div>
                        <Tag 
                            :variant="getStatusVariant(selectedVal.status)"  
                            :value="capFirstLetter(selectedVal.status)" 
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

        <OverlayPanel ref="op" @close="closeOverlay" class="[&>div]:p-0">
            <div class="flex flex-col items-center border-2 border-primary-50 rounded-md">
                <!-- <Button
                    type="button"
                    variant="tertiary"
                    class="w-fit border-0 hover:bg-primary-50 !justify-start"
                    @click="ConfirmCancel"
                >
                    <span class="text-grey-700 font-normal">Cancel Submission</span>
                </Button> -->
                <Button
                    type="button"
                    variant="tertiary"
                    class="w-fit border-0 hover:bg-primary-50 !justify-start"
                    @click="PrintInvoice(selectedVal.id)"
                    :disabled="selectedVal.status !== 'Valid' ? true : false"
                >
                    <span class="text-grey-700 font-normal">Print Receipt</span>
                </Button>
            </div>
        </OverlayPanel>

        <Modal
            :maxWidth="'2xs'" 
            :closeable="true"
            :show="cancelSubmitFormIsOpen"
            @close="hideConfirmCancel"
            :withHeader="false"
        >
            
            <div class="flex flex-col gap-9 pt-36">
                <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] fixed top-0 w-full left-0">
                    <CancelIllus class="mt-2.5"/>
                </div>
                <div class="flex flex-col gap-1" >
                    <div class="text-primary-900 text-2xl font-medium text-center">
                        Cancel Submission
                    </div>
                    <div class="text-gray-900 text-base font-medium text-center leading-tight" >
                        Are you sure you want to cancel this Consolidated Invoice submission?
                    </div>
                    <div>
                        <TextInput 
                            :inputName="'name'"
                            :labelText="'Reason'"
                            :placeholder="'e.g. Wrong details'"
                            :errorMessage="reasonError"
                            v-model="reason"
                        />
                    </div>
                </div>
                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="hideConfirmCancel"
                    >
                        Keep
                    </Button>
                    <Button
                        variant="red"
                        size="lg"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        </Modal>
    </Modal>

</template>