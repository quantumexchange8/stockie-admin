<script setup>
import SearchBar from "@/Components/SearchBar.vue";
import DateInput from '@/Components/Date.vue';
import Button from '@/Components/Button.vue';
import { DotVerticleIcon, PlusIcon, ToastInfoIcon } from '@/Components/Icons/solid';
import { computed, onMounted, ref, watch, watchEffect } from 'vue';
import { FilterMatchMode } from "primevue/api";
import Table from '@/Components/Table.vue';
import Tag from '@/Components/Tag.vue';
import Modal from '@/Components/Modal.vue';
import { transactionFormat } from '@/Composables';
import TabView from "@/Components/TabView.vue";
import OverlayPanel from '@/Components/OverlayPanel.vue';
import RefundDetail from "./RefundDetail.vue";
import RefundProduct from "./RefundProduct.vue";
import RefundMethod from "./RefundMethod.vue";
import { DeleteIllus } from '@/Components/Icons/illus';
import NumberCounter from "@/Components/NumberCounter.vue";
import RadioButton from "@/Components/RadioButton.vue";
import TextInput from "@/Components/TextInput.vue";
import Checkbox from "@/Components/Checkbox.vue";

const salesColumn = ref([
    {field: 'created_at', header: 'Date & Time', width: '20', sortable: true},
    {field: 'refund_no', header: 'Refund No. ', width: '21.5', sortable: true},
    {field: 'total_refund_amount', header: 'Total', width: '16', sortable: true},
    {field: 'customer.full_name', header: 'Customer', width: '20', sortable: true},
    {field: 'status', header: 'Status', width: '16', sortable: true},
    {field: 'action', header: '', width: '5', sortable: false},
]);

const props = defineProps({
    selectedTab: Number,
})

const filters = ref({ 'global': { value: null, matchMode: FilterMatchMode.CONTAINS } });
const date_filter = ref(''); 
const saleTransaction = ref([]);
const { formatAmount, formatDateTime } = transactionFormat();
const detailIsOpen = ref(false);
const selectedVal = ref(null);
const tabs = ref(["Refund Detail", "Refund Product", "Refund Method"]);
const op = ref(null);
const voideIsOpen = ref(false);
const refundIsOpen = ref(false);

const fetchRefundTransaction = async (filters = {}) => {

try {
    const response = await axios.get('/transactions/getRefundTransaction', {
        params: { dateFilter: filters }
    });

    saleTransaction.value = response.data;

} catch (error) {
    console.error(error);
}
};

onMounted(() => fetchRefundTransaction());

watch(date_filter, (newValue) => fetchRefundTransaction(newValue));

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: "",
};

const saleRowsPerPage = ref(6);
const saleTotalPages = computed(() => {
    return Math.ceil(saleTransaction.length / saleRowsPerPage.value);
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
            
            response = await axios.post('/transactions/voidrefund-transaction', {
                params: {
                    id: selectedVal.value.id
                }
            })

        } catch (error) {
            console.error(error);
        }

    }
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
            <!-- <div class="flex items-center lg:justify-between xl:justify-center gap-5 lg:w-full xl:max-w-[500px]">
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
                    >
                        <template #icon>
                            <PlusIcon />
                        </template>
                        Consolidate
                    </Button>
                </div>
            </div> -->
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
                <template #created_at="row">
                    <span class="text-grey-900 text-sm font-medium">{{ formatDateTime(row.created_at) }}</span>
                </template>
                <template #total_refund_amount="row">
                    <span class="text-grey-900 text-sm font-medium">RM {{ formatAmount(row.total_refund_amount) }}</span>
                </template>
                <template #customer.full_name="row">
                    <span class="text-grey-900 text-sm font-medium">{{ row.customer ? row.customer.full_name : 'Guest' }}</span>
                </template>
                <template #status="row">
                    <Tag
                        :variant="row.status === 'Completed' 
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
                            :variant="selectedVal.status === 'Completed' ? 'green' : selectedVal.status === 'Voided' ? 'red' : 'grey' "  
                            :value="selectedVal.status === 'Completed' ? 'Completed' : 'Voided'" 
                        />
                    </div>
                    <div @click="actionOption($event, row)"><DotVerticleIcon /></div>
                </div>
                <div class="grid grid-cols-2 gap-5">
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Date & Time</div>
                        <div class="text-gray-900 text-base font-bold">{{ formatDateTime(selectedVal.created_at) }}</div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Transaction No.</div>
                        <div class="text-gray-900 text-base font-bold">{{ selectedVal.refund_no }}</div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Total</div>
                        <div class="text-gray-900 text-base font-bold">RM {{ formatAmount(selectedVal.total_refund_amount) }}</div>
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
                    <template #refund-detail>
                        <RefundDetail :selectedVal="selectedVal" />
                    </template>
                    <template #refund-product>
                        <RefundProduct :selectedVal="selectedVal" />
                    </template>
                    <template #refund-method>
                        <RefundMethod :selectedVal="selectedVal" />
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

    </Modal>

</template>