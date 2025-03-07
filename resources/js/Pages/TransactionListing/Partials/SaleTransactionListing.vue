<script setup>
import Button from '@/Components/Button.vue';
import { DotVerticleIcon, PlusIcon } from '@/Components/Icons/solid';
import SearchBar from "@/Components/SearchBar.vue";
import Checkbox from '@/Components/Checkbox.vue';
import Slider from "@/Components/Slider.vue";
import DateInput from '@/Components/Date.vue';
import Table from '@/Components/Table.vue';
import { computed, onMounted, ref, watch, watchEffect } from 'vue';
import { FilterMatchMode } from "primevue/api";
import Tag from '@/Components/Tag.vue';
import Modal from '@/Components/Modal.vue';

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
    {field: 'receipt_no', header: 'Transaction No. ', width: '21.5', sortable: true},
    {field: 'grand_total', header: 'Total', width: '16', sortable: true},
    {field: 'customer.full_name', header: 'Customer', width: '20', sortable: true},
    {field: 'status', header: 'Status', width: '16', sortable: true},
    {field: 'action', header: '', width: '5', sortable: false},
]);

const saleTransaction = ref([]);
const date_filter = ref(''); 
const filters = ref({ 'global': { value: null, matchMode: FilterMatchMode.CONTAINS } });
const detailIsOpen = ref(false);
const selectedVal = ref(false);

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

watch(date_filter, (newValue) => fetchTransaction(newValue));

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
        :show="detailIsOpen"
        @close="closeAction"
        :title="'Sales transaction detail'"
        :maxWidth="'sm'"
    >
        <!-- {{ selectedVal }} -->
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div>
                        <Tag 
                            :variant="selectedVal.status === 'Successful' ? 'green' : row.status === 'Voided' ? 'red' : 'grey' "  
                            :value="selectedVal.status === 'Successfull' ? 'Completed' : 'Voided'" 
                        />
                    </div>
                    <div><DotVerticleIcon /></div>
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
                        <div class="text-gray-900 text-base font-bold">RM {{ selectedVal.grand_total }}</div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Customer</div>
                        <div class="text-gray-900 text-base font-bold">{{ selectedVal.customer ?  selectedVal.customer.full_name : 'Guest'}}</div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="text-gray-900 text-base">Points Given</div>
                        <div class="text-gray-900 text-base font-bold">{{ selectedVal.customer ? selectedVal.point_earned : 0}}</div>
                    </div>
                </div>
            </div>
            <div>
                
            </div>
        </div>
    </Modal>

</template>