<script setup>
import { ref, onMounted, computed, watch, reactive } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Button from './Button.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { ViewIcon, ReplenishIcon, EditIcon, DeleteIcon } from './Icons/solid';
import { UndetectableIllus } from "@/Components/Icons/illus.jsx";
import TextInput from '@/Components/TextInput.vue';
import Tag from '@/Components/Tag.vue';
import Paginator from 'primevue/paginator';
import Toggle from './Toggle.vue';
import { transactionFormat, useCustomToast } from '@/Composables';

const props = defineProps({
    errors: Object,
    variant: {
        type: String,
        default: 'grid'
    },
    rows: {
        type: Array,
        default: () => []
    },
    totalPages: {
        type: Number,
        default: 1
    },
    columns: {
        type: Array,
        default: () => []
    },
    rowsPerPage: {
        type: Number,
        default: 8
    },
    rowType: Object,
    actions: {
        type: Object,
        default: () => ({})
    },
    searchFilter: {
        type: Boolean,
        default: false
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    paginator: {
        type: Boolean,
        default: true
    },
    minWidth: {
        type: String,
        default: ''
    }
});
const dt = ref();
const currentPage = ref(1);
const loading = ref(false);
const selectedProduct = ref();
const expandedRowGroups = ref();
const forms = reactive({});

// const emit = defineEmits(["updateFilteredRowsCount"]);
const emit = defineEmits(["onRowClick"]);

const defaultActions = {
    view: () => '',
    replenish: () => '',
    edit: () => '',
    delete: () => ''
};

const { showMessage } = useCustomToast();
const { formatAmount } = transactionFormat();

const mergedActions = computed(() => {
    return Object.keys(props.actions).length === 0 
        ? defaultActions 
        : {
            ...defaultActions,
            ...props.actions
        };
});

const defaultRowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
}

const mergedRowType = computed(() => ({
    ...defaultRowType,
    ...props.rowType
}));

const onPageChange = (event) => {
    currentPage.value = event.page + 1;
};

const goToPage = (event) => {
    const page = parseInt(event.target.value);
    if (page > 0 && page <= props.totalPages) {
        currentPage.value = page;
    }
}

// const onFilter = (event) => {
//     emit('updateFilteredRowsCount', event.filteredValue);
// };

const handleLinkClick = (event) => {
    event.stopPropagation();  // Prevent the row selection event
    event.preventDefault();   // Prevent the default link action
    window.location.href = event.currentTarget.href;  // Manually handle the link navigation
};

const redirectAction = (url) => {
    if (typeof props.actions.view === 'function' && url) {
        if (url) {
            window.location.href = url;
        }
    }
}

const onRowSelect = (event) => {
    const url = mergedActions.value.view(event.data.id);

    if (typeof props.actions.view === 'function' && url) {
        if (url) window.location.href = url;
    }
};

const onRowClick = (event) => emit('onRowClick', event);

const groupedByColumnWidth = computed(() => {
    const matchingColumn = props.columns.find(column => column.field === mergedRowType.value.groupRowsBy);
    return matchingColumn ? matchingColumn.width : '0'; // Default width if not found
});

// Computed rows on pagination
const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * props.rowsPerPage;
    const end = start + props.rowsPerPage;
    return props.rows.slice(start, end);
});

// Computed property to conditionally include props
const computedProps = computed(() => {
    const props = {};
    if (mergedRowType.value.rowGroups) {
        props.rowGroupMode = "subheader";
        props.groupRowsBy = mergedRowType.value.groupRowsBy;
        props['v-model:expandedRowGroups'] = expandedRowGroups.value;

        if (mergedRowType.value.expandable) {
            props.expandableRowGroups = true;
        }
    }

    return props;
});

// Computed property to calculate total stock quantity for each group
const totalStockByGroup = computed(() => {
    const totals = {};
    props.rows.forEach(row => {
        const group = row.inventory.name;
        if (!totals[group]) {
            totals[group] = 0;
        }
        totals[group] += row.stock_qty;
    });
    return totals;
});


paginatedRows.value.forEach(item => {
    forms[item.id] = useForm({
        id: item.id ?? '',
        product_name: item.product_name ?? '',
        availability: item.availability === 'Available' ? true : false,
        availabilityWord: '',
    });
});

const toggleAvailability = (item) => {
    if (forms[item.id]) {
        forms[item.id].availability = !forms[item.id].availability;  
        forms[item.id].id = item.id;
        forms[item.id].product_name = item.product_name;
        forms[item.id].availabilityWord = forms[item.id].availability ? 'Available' : 'Unavailable';
        
        updateAvailability(item.id); 
    } else {
        console.error(`Item with id ${item.id} not found in forms.`);
    }
};


const updateAvailability = (id) => {
    const form = forms[id];
    if (form) {
        const availability = form.availability === true ? 'available' : 'unavailable';

        form.post(route('products.updateAvailability'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setTimeout(() => {
                    showMessage({
                        severity: 'success',
                        summary: `${form.product_name} is now ${availability}.`
                    });
                }, 200);
            },
            onError: () => {
                setTimeout(() => {
                    showMessage({
                        severity: 'danger',
                        summary: 'Error occurred. Please contact the administrator.'
                    });
                });
            }
        });
    } else {
        console.error(`Form for item ID ${id} does not exist.`);
    }
};

watch(() => props.rows, () => {
    currentPage.value = 1;
}, { immediate: true });

onMounted(() => {
    loading.value = true;
    if (props.rows) {
        loading.value = false;
    }
});


</script>

<template>
    <div class="w-full">
        <DataTable 
            ref="dt"
            :value="props.rows" 
            :paginator="props.rows.length > 0 && props.paginator ? true : false" 
            :rows="props.rowsPerPage" 
            :loading="loading" 
            :first="(currentPage - 1) * props.rowsPerPage"
            :metaKeySelection="false"
            v-model:selection="selectedProduct"
            v-model:expandedRowGroups="expandedRowGroups"
            v-bind="computedProps"
            selectionMode="single"
            dataKey="id"
            :filters="searchFilter ? filters : {}"
            stripedRows
            @rowSelect="onRowSelect"
            @rowClick="onRowClick"
            @page="onPageChange"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
            v-if="props.variant === 'list'"
            :pt="{
                bodyrow: ({ context, props }) => ({
                    class: [
                        // Color
                        'w-full flex',
                        'odd:bg-white even:bg-primary-25 odd:text-grey-900 even:text-grey-900 hover:bg-primary-50',
                        { 'font-bold bg-primary-0 z-20': props.frozenRow },
                    ]
                }),
                paginator: {
                    root: {
                        class: 'flex justify-center items-center flex-wrap bg-white text-grey-500 pt-5 pb-3'
                    },
                    start: {
                        class: 'mr-auto'
                    },
                    pages: {
                        class: 'flex justify-center items-center'
                    },
                    pagebutton: ({ context }) => {
                        return {
                            class: [
                                'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                                {
                                    'rounded-full bg-primary-900 text-primary-50': context.active,
                                    'hover:rounded-full hover:bg-primary-50 hover:text-primary-900': !context.active,
                                },
                            ]
                        };
                    },
                    end: {
                        class: 'ml-auto'
                    },
                    firstpagebutton: {
                        class: [
                            {
                                'hidden': props.totalPages < 5,
                            },
                            'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                            'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                            'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                            'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                            'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                        ]
                    },
                    previouspagebutton: {
                        class: [
                            {
                                'hidden': props.totalPages === 1
                            },
                            'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                            'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                            'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                        ]
                    },
                    nextpagebutton: {
                        class: [
                            {
                                'hidden': props.totalPages === 1
                            },
                            'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                            'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                            'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                        ]
                    },
                    lastpagebutton: {
                        class: [
                            {
                                'hidden': props.totalPages < 5
                            },
                            'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                            'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                            'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                        ]
                    },
                },
                wrapper: ({ props }) => ({
                    class: [
                        'overflow-x-auto',
                        { relative: props.scrollable, 'flex flex-col grow': props.scrollable && props.scrollHeight === 'flex' },
                        // Size
                        { 'h-full': props.scrollable && props.scrollHeight === 'flex' }
                    ]
                }),
                table: {
                    class: [
                        props.minWidth,
                        'w-full gap-3 flex flex-col items-start justify-center'
                    ]
                },
            }"
        >
            <!-- @filter="onFilter" -->
            <template #header v-if="$slots.header">
                <slot name="header">
                </slot>
            </template>

            <template #empty>
                <slot name="empty">
                    <UndetectableIllus/>
                    <span class="text-primary-900 text-sm font-medium pb-5">No data can be shown yet...</span>
                </slot>
            </template>

            <template #groupheader="slotProps" v-if="mergedRowType.rowGroups">
                <slot name="groupheader" :="slotProps.data">
                    <div class="flex justify-between items-start w-full">
                    </div> 
                </slot>
            </template>

            <Column 
                :field="''"
                :header="''"
                :style="{ width: groupedByColumnWidth + '%' }"
                v-if="mergedRowType.rowGroups && mergedRowType.expandable"
            ></Column>

            <Column 
                :field="col.field"
                :filterField="col.field"
                :header="col.header"
                :sortable="col.sortable"
                v-for="col of columns" :key="col.field"
                :style="{ width: col.width + '%' }"
            >
                <template #body="slotProps">
                    <slot :name="col.field" :="slotProps.data">
                        <p class="text-sm font-medium text-grey-900">{{ slotProps.data[col.field] }}</p>
                    </slot>
                    
                    <div class="flex justify-end items-center gap-2 size-full" v-if="col.field === 'action' && !mergedRowType.rowGroups">
                        <slot name="viewAction" :="slotProps.data">
                            <Link
                                :as="'button'"
                                :href="mergedActions.view(slotProps.data.id)"
                                class="block transition duration-150 ease-in-out"
                                @click="handleLinkClick"
                                v-if="col.view"
                            >
                                <ViewIcon
                                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                                />
                            </Link>
                        </slot>
                        <slot name="replenishAction" :="slotProps.data">
                            <Link
                                :as="'button'"
                                :href="mergedActions.replenish(slotProps.data.id)"
                                class="block transition duration-150 ease-in-out"
                                @click="handleLinkClick"
                                v-if="col.replenish"
                            >
                                <ReplenishIcon
                                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                                />
                            </Link>
                        </slot>
                        <slot name="editAction" :="slotProps.data">
                            <Link
                                :as="'button'"
                                :href="mergedActions.edit(slotProps.data.id)"
                                class="block transition duration-150 ease-in-out"
                                @click="handleLinkClick"
                                v-if="col.edit"
                            >
                                <EditIcon
                                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                                />
                            </Link>
                        </slot>
                        <slot name="deleteAction" :="slotProps.data">
                            <Link
                                :as="'button'"
                                :href="mergedActions.delete(slotProps.data.id)"
                                class="block transition duration-150 ease-in-out"
                                @click="handleLinkClick"
                                v-if="col.delete"
                            >
                                <DeleteIcon
                                    class="w-6 h-6 text-primary-600 hover:text-primary-700 cursor-pointer pointer-events-none"
                                />
                            </Link>
                        </slot>
                    </div>
                </template>
                <template #sorticon>
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        width="17" 
                        height="16" 
                        viewBox="0 0 17 16" 
                        fill="none"
                    >
                        <g clip-path="url(#clip0_393_6137)">
                            <path 
                                fill-rule="evenodd" 
                                clip-rule="evenodd" 
                                d="M11.5068 14.4768C12.2441 14.4768 12.8418 13.8792 12.8418 13.1419V7.92675C12.8418 7.8822 12.8957 7.8599 12.9272 7.89145L14.0162 8.9821C14.5418 
                                9.5084 15.3946 9.5087 15.9205 8.9828C16.4398 8.46355 16.447 7.624 15.9369 7.09585L11.1908 2.1821C10.8205 1.79875 10.1719 2.0609 10.1719 
                                2.59385V5.1321V13.1419C10.1719 13.8792 10.7696 14.4768 11.5068 14.4768Z" 
                                fill="#9F151A"
                            />
                            <path 
                                fill-rule="evenodd" 
                                clip-rule="evenodd" 
                                d="M6.04791 2C5.32236 2 4.73416 2.5882 4.73416 3.31375V8.54865C4.73416 8.59345 4.67986 8.61565 4.64851 8.5837L3.57841 7.49475C3.06121 6.9684 
                                2.22196 6.9681 1.70439 7.49405C1.19342 8.01325 1.18625 8.8528 1.68828 9.381L6.35891 14.2947C6.72326 14.6781 7.36161 14.416 7.36161 13.883L7.36166 
                                11.3448V3.31375C7.36166 2.5882 6.77346 2 6.04791 2Z" 
                                fill="#9F151A"
                            />
                        </g>
                        <defs>
                            <clipPath id="clip0_393_6137">
                                <rect 
                                    width="16" 
                                    height="16" 
                                    fill="white" 
                                    transform="translate(0.601562)"
                                />
                            </clipPath>
                        </defs>
                    </svg>
                </template>
            </Column>

            <template #groupfooter="slotProps" v-if="mergedRowType.rowGroups">
                <slot name="groupfooter" :="slotProps.data">
                    <div class="pr-[50px] text-sm font-semibold text-grey-950">
                        Total Stock: {{ totalStockByGroup[slotProps.data.inventory.name] }}
                    </div>
                </slot>
            </template> 

            <template #paginatorstart>
                <div class="text-xs font-medium text-grey-500">
                    Showing: <span class="text-grey-900">{{ props.totalPages === 0 ? 0 : currentPage }} of {{ props.totalPages }}</span>
                </div>
            </template>
            <template #paginatorend>
                <div class="flex justify-center items-center gap-2 text-xs font-medium text-grey-900 whitespace-nowrap">
                    Go to Page: 
                    <TextInput
                        :inputName="'go_to_page'"
                        :placeholder="'eg: 12'"
                        class="!w-20"
                        :disabled="true"
                        v-if="props.totalPages === 1"
                    />
                    <TextInput
                        :inputName="'go_to_page'"
                        :placeholder="'eg: 12'"
                        class="!w-20"
                        :disabled="false"
                        @input="goToPage($event)"
                        v-else
                    />
                </div>
            </template>
            <template #paginatorfirstpagelinkicon>
                <svg 
                    width="15" 
                    height="12" 
                    viewBox="0 0 15 12" 
                    fill="none" 
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path 
                        d="M14 11L9 6L14 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"/>
                    <path
                        d="M6 11L1 6L6 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                    />
                </svg>
            </template>
            <template #paginatorprevpagelinkicon>
                <svg 
                    xmlns="http://www.w3.org/2000/svg" 
                    width="7" 
                    height="12" 
                    viewBox="0 0 7 12" 
                    fill="none"
                >
                    <path 
                        d="M6 11L1 6L6 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                    />
                </svg>
            </template>
            <template #paginatornextpagelinkicon>
                <svg 
                    xmlns="http://www.w3.org/2000/svg" 
                    width="7" 
                    height="12" 
                    viewBox="0 0 7 12" 
                    fill="none"
                >
                    <path 
                        d="M1 11L6 6L1 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                    />
                </svg>
            </template>
            <template #paginatorlastpagelinkicon>
                <svg 
                    width="15" 
                    height="12" 
                    viewBox="0 0 15 12" 
                    fill="none" 
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path 
                        d="M1 11L6 6L1 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"/>
                    <path
                        d="M9 11L14 6L9 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                    />
                </svg>
            </template>
        </DataTable>
        <form @submit.prevent="updateAvailability">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4" v-if="props.variant === 'grid'">
                <div v-for="(item, index) in paginatedRows" :key="index" class="col-span-full md:col-span-3">
                    <div class="flex flex-col items-start gap-1 size-full">
                        <slot name="item-body">
                            <div 
                                :class="[
                                    'relative self-stretch w-full min-h-52 rounded-[5px] border border-grey-100',
                                    {
                                        'bg-primary-25': index % 2 === 0 && item.status !== 'Out of stock',
                                        'bg-white': index % 2 !== 0 && item.status !== 'Out of stock',
                                    }
                                ]"
                            >
                                <div 
                                    class="size-full cursor-pointer"
                                    :class="{'hover:bg-primary-50 h-[168px] ': item.status !== 'Out of stock' }"
                                    @click="redirectAction(mergedActions.view(item.id))"
                                >
                                    <div :class="['absolute size-full !z-10 bg-black', item.status === 'Out of stock' ? 'opacity-50' : 'opacity-0 hidden']"></div>
                                    <span class="absolute !z-20 top-[calc(50%-1rem)] left-[calc(50%-2.5rem)] bottom-0 text-white text-base font-medium" v-if="item.status === 'Out of stock'">Out of Stock</span>
                                    <img 
                                        :src="item.image ? item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                        alt="ProductImage" 
                                        class="min-h-[168px] w-full object-contain relative !z-0"
                                        :class="item.stock_left == 0 ? 'max-h-[200px]' : 'max-h-[168px]'"
                                    />
                                </div>
                                <div class="flex p-[2px] items-start flex-shrink-0 gap-0.5" v-if="item.status !== 'Out of stock'">
                                    <slot name="editAction" :="item">
                                        <Button
                                            :type="'button'"
                                            :size="'md'"
                                            @click="redirectAction(mergedActions.view(item.id))"
                                            class="!bg-primary-100 hover:!bg-primary-200 rounded-tl-none rounded-tr-none rounded-br-none rounded-bl-[5px]"
                                        >
                                            <EditIcon class="w-5 h-5 text-primary-900 hover:text-primary-800 cursor-pointer" />
                                        </Button>
                                    </slot>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-y-2 justify-between size-full min-h-[117px]">
                                <div class="flex flex-col items-start gap-y-1 size-full">
                                    <div class="w-full flex gap-2 items-center">
                                        <Tag value="Set" v-if="item.bucket === 'set'"/>
                                        <span class="w-full self-stretch text-primary-950 text-md font-semibold overflow-hidden text-ellipsis whitespace-nowrap">{{ item.product_name }}</span>
                                    </div>
                                    <div v-for="items in item.discountItems" v-if="item.discount_id !== null" class="flex flex-col items-start">
                                        <span class="text-grey-900 font-bold text-md ">RM {{ formatAmount(items.price_after) }}</span>
                                        <span class="text-grey-900 font-normal text-sm line-through">RM {{ formatAmount(items.price_before) }}</span>
                                    </div>
                                    <div v-else>
                                        <span class="line-clamp-1 text-grey-900 text-ellipsis font-bold text-md">
                                            RM {{ formatAmount(item.price) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center self-stretch gap-2">
                                    <Toggle 
                                        :checked="item.availability === 'Available'" 
                                        :inputName="'availability'"
                                        :inputId="'availability'"
                                        @change="toggleAvailability(item)"
                                    />
                                    <span class="text-base font-medium" 
                                    :class="item.status === 'In stock' ? 'text-green-700' 
                                            : item.status === 'Low in stock' ? 'text-yellow-700' 
                                            : 'text-primary-600'">
                                            <p v-if="item.status === 'Out of stock'">{{ item.status }}</p>
                                            <p v-else>{{ item.bucket === 'set' ? `${item.stock_left} set left` : `${item.stock_left} left` }}</p>
                                    </span>
                                </div>
                            </div>
                        </slot>
                    </div>
                </div>
                <div v-if="$slots.empty && paginatedRows.length === 0" class="col-span-full">
                    <slot name="empty">
                        <UndetectableIllus/>
                        <span class="text-primary-900 text-sm font-medium pb-5">No data can be shown yet...</span>
                    </slot>
                </div>
            </div>
        </form>
        <Paginator 
            :rows="props.rowsPerPage" 
            :first="(currentPage - 1) * props.rowsPerPage"
            :totalRecords="props.rows.length"
            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
            @page="onPageChange"
            v-if="props.variant === 'grid' && paginatedRows.length > 0"
            :pt="{
                root: {
                    class: 'flex justify-center items-center flex-wrap bg-white text-grey-500 py-3'
                },
                start: {
                    class: 'mr-auto'
                },
                pages: {
                    class: 'flex justify-center items-center'
                },
                pagebutton: ({ context }) => {
                    return {
                        class: [
                            'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                            {
                                'rounded-full bg-primary-900 text-primary-50': context.active,
                                'hover:rounded-full hover:bg-primary-50 hover:text-primary-900': !context.active,
                            },
                        ]
                    };
                },
                end: {
                    class: 'ml-auto'
                },
                firstpagebutton: {
                    class: [
                        {
                            'hidden': props.totalPages < 5,
                        },
                        'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                        'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                        'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',,
                        'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                        'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                    ]
                },
                previouspagebutton: {
                    class: [
                        {
                            'hidden': props.totalPages === 1
                        },
                        'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                        'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                        'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                    ]
                },
                nextpagebutton: {
                    class: [
                        {
                            'hidden': props.totalPages === 1
                        },
                        'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                        'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                        'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                    ]
                },
                lastpagebutton: {
                    class: [
                        {
                            'hidden': props.totalPages < 5
                        },
                        'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                        'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                        'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                    ]
                },
            }"
        >
            <template #start>
                <div class="text-xs font-medium text-grey-500">
                    Showing: <span class="text-grey-900">{{ props.totalPages === 0 ? 0 : currentPage }} of {{ props.totalPages }}</span>
                </div>
            </template>
            <template #end>
                <div class="flex justify-center items-center gap-2 text-xs font-medium text-grey-900 whitespace-nowrap">
                    Go to Page: 
                    <TextInput
                        :inputName="'go_to_page'"
                        :placeholder="'eg: 12'"
                        class="!w-20"
                        :disabled="true"
                        v-if="props.totalPages === 1"
                    />
                    <TextInput
                        :inputName="'go_to_page'"
                        :placeholder="'eg: 12'"
                        class="!w-20"
                        :disabled="false"
                        @input="goToPage($event)"
                        v-else
                    />
                </div>
            </template>
            <template #firstpagelinkicon>
                <svg 
                    width="15" 
                    height="12" 
                    viewBox="0 0 15 12" 
                    fill="none" 
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path 
                        d="M14 11L9 6L14 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"/>
                    <path
                        d="M6 11L1 6L6 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                    />
                </svg>
            </template>
            <template #prevpagelinkicon>
                <svg 
                    xmlns="http://www.w3.org/2000/svg" 
                    width="7" 
                    height="12" 
                    viewBox="0 0 7 12" 
                    fill="none"
                >
                    <path 
                        d="M6 11L1 6L6 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                    />
                </svg>
            </template>
            <template #nextpagelinkicon>
                <svg 
                    xmlns="http://www.w3.org/2000/svg" 
                    width="7" 
                    height="12" 
                    viewBox="0 0 7 12" 
                    fill="none"
                >
                    <path 
                        d="M1 11L6 6L1 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                    />
                </svg>
            </template>
            <template #lastpagelinkicon>
                <svg 
                    width="15" 
                    height="12" 
                    viewBox="0 0 15 12" 
                    fill="none" 
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path 
                        d="M1 11L6 6L1 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"/>
                    <path
                        d="M9 11L14 6L9 1" 
                        stroke="currentColor" 
                        stroke-width="2" 
                        stroke-linecap="round" 
                        stroke-linejoin="round"
                    />
                </svg>
            </template>
        </Paginator>
	</div>
</template>
