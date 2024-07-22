<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Button from './Button.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from '@/Components/Dropdown.vue'
import { ViewIcon, ReplenishIcon, EditIcon, DeleteIcon } from './Icons/solid';
import TextInput from '@/Components/TextInput.vue';
import Tag from '@/Components/Tag.vue';
import Paginator from 'primevue/paginator';

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
});

const dt = ref();
const currentPage = ref(1);
const loading = ref(false);
const selectedProduct = ref();
const expandedRowGroups = ref();

const defaultActions = {
    view: () => '#',
    replenish: () => '#',
    edit: () => '#',
    delete: () => '#'
};

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

const handleLinkClick = (event) => {
    event.stopPropagation();  // Prevent the row selection event
    event.preventDefault();   // Prevent the default link action
    window.location.href = event.currentTarget.href;  // Manually handle the link navigation
};

const redirectAction = (url) => {
    window.location.href = Object.keys(props.actions) > 0 ? url : '#';
}

const onRowSelect = (event) => {
    window.location.href = Object.keys(props.actions) > 0 ? props.actions.view(event.data.id) : '#';
};

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


onMounted(() => {
    loading.value = true;
    if (props.rows) {
        loading.value = false;
    }
});
</script>

<template>
    <div class="card p-fluid gap-6">
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
            tableStyle="min-width: 50rem"
            stripedRows
            @rowSelect="onRowSelect"
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
            }"
        >
            <template #header>
                <slot name="header">
                </slot>
            </template>

            <template #empty>
                <slot name="empty">
                    <div>
                        <svg width="38000" height="300" viewBox="0 0 380 300" fill="none" xmlns="http://www.w3.org/2000/svg" class="">
                            <g filter="url(#filter0_f_756_35712)">
                                <ellipse cx="198" cy="196.543" rx="86" ry="85" fill="#FFF1F2"/>
                            </g>
                            <path d="M189.5 253.458C232.302 253.458 267 218.984 267 176.458C267 133.932 232.302 99.458 189.5 99.458C146.698 99.458 112 133.932 112 176.458C112 218.984 146.698 253.458 189.5 253.458Z" fill="url(#paint0_linear_756_35712)"/>
                            <path d="M245.901 254.857L159.902 243.937C157.623 243.648 156.011 241.566 156.3 239.288L170.296 129.068C170.585 126.789 172.667 125.177 174.945 125.466L260.945 136.386C263.223 136.676 264.836 138.757 264.546 141.036L250.551 251.256C250.261 253.534 248.18 255.147 245.901 254.857Z" fill="#FFC7C9"/>
                            <path opacity="0.2" d="M236.631 143.743H161.04C159.93 143.743 158.964 144.614 158.883 145.689L150.476 257.397C150.395 258.472 151.229 259.343 152.339 259.343H243.326C244.436 259.343 245.402 258.472 245.483 257.397L252.769 160.592L236.631 143.743Z" fill="#7E171B"/>
                            <path d="M244.792 157.841V257.846C244.792 258.306 244.637 258.731 244.376 259.069V259.071C244.008 259.549 243.431 259.857 242.781 259.857H151.794C150.684 259.857 149.784 258.957 149.784 257.846V142.445C149.784 141.335 150.684 140.435 151.794 140.435H227.385L244.792 157.841Z" fill="#FFF9F9"/>
                            <path d="M229.755 157.841H244.79L227.384 140.435V155.469C227.384 156.779 228.446 157.841 229.755 157.841Z" fill="#FFF1F2"/>
                            <g opacity="0.2">
                                <path d="M199.949 179.101C200.594 177.054 201.904 175.187 203.881 173.5C205.857 171.813 208.324 170.678 211.279 170.093C214.234 169.509 217.394 169.745 220.755 170.805C223.879 171.789 226.456 173.235 228.484 175.142C230.513 177.048 231.866 179.197 232.546 181.591C233.224 183.984 233.197 186.344 232.464 188.671C231.887 190.503 231.009 191.992 229.832 193.136C228.653 194.281 227.395 195.193 226.056 195.873C224.716 196.551 222.348 197.659 218.951 199.194C218.008 199.632 217.235 200.034 216.634 200.401C216.033 200.768 215.558 201.127 215.211 201.48C214.863 201.832 214.563 202.2 214.312 202.583C214.061 202.966 213.651 203.648 213.081 204.628C211.965 206.741 210.331 207.457 208.176 206.778C207.055 206.425 206.227 205.762 205.695 204.788C205.159 203.815 205.12 202.606 205.575 201.162C206.146 199.352 206.919 197.873 207.898 196.724C208.875 195.575 209.985 194.646 211.228 193.936C212.469 193.226 214.095 192.43 216.103 191.546C217.861 190.774 219.145 190.177 219.953 189.757C220.76 189.336 221.493 188.815 222.151 188.193C222.809 187.571 223.277 186.819 223.555 185.935C224.098 184.212 223.916 182.555 223.007 180.966C222.098 179.377 220.631 178.263 218.606 177.625C216.236 176.878 214.302 176.926 212.804 177.769C211.307 178.611 209.804 180.075 208.295 182.157C206.795 184.362 205.021 185.141 202.975 184.496C201.767 184.116 200.883 183.369 200.322 182.256C199.761 181.144 199.636 180.093 199.949 179.101ZM204.559 219.495C203.246 219.081 202.232 218.294 201.519 217.134C200.807 215.974 200.692 214.629 201.174 213.099C201.602 211.742 202.436 210.749 203.676 210.121C204.916 209.493 206.225 209.397 207.604 209.832C208.963 210.26 209.958 211.082 210.593 212.301C211.227 213.519 211.331 214.807 210.903 216.165C210.428 217.673 209.57 218.706 208.329 219.263C207.087 219.818 205.831 219.896 204.559 219.495Z" fill="#7E171B"/>
                            </g>
                            <path d="M192.584 181.839C192.854 179.711 193.811 177.64 195.456 175.629C197.101 173.617 199.326 172.061 202.13 170.959C204.934 169.858 208.085 169.529 211.581 169.972C214.83 170.385 217.624 171.349 219.959 172.864C222.294 174.379 224.009 176.253 225.104 178.487C226.198 180.722 226.591 183.049 226.284 185.469C226.041 187.375 225.443 188.996 224.488 190.331C223.532 191.667 222.456 192.789 221.26 193.696C220.062 194.603 217.929 196.114 214.859 198.229C214.009 198.828 213.32 199.361 212.795 199.829C212.268 200.298 211.865 200.736 211.586 201.145C211.306 201.553 211.076 201.968 210.898 202.39C210.719 202.811 210.436 203.555 210.051 204.622C209.328 206.899 207.847 207.894 205.607 207.61C204.441 207.462 203.508 206.957 202.81 206.093C202.111 205.231 201.857 204.048 202.048 202.547C202.287 200.664 202.784 199.071 203.543 197.766C204.3 196.462 205.228 195.35 206.324 194.43C207.419 193.511 208.877 192.438 210.695 191.211C212.288 190.138 213.446 189.322 214.166 188.765C214.885 188.207 215.514 187.564 216.05 186.835C216.587 186.105 216.913 185.283 217.03 184.363C217.258 182.57 216.783 180.972 215.606 179.57C214.429 178.168 212.787 177.333 210.681 177.066C208.215 176.753 206.321 177.144 204.997 178.241C203.673 179.336 202.455 181.044 201.341 183.361C200.257 185.798 198.651 186.88 196.522 186.61C195.266 186.451 194.263 185.873 193.513 184.878C192.763 183.883 192.453 182.871 192.584 181.839ZM204.311 220.768C202.944 220.595 201.807 220.001 200.899 218.986C199.991 217.971 199.639 216.669 199.841 215.077C200.02 213.665 200.665 212.54 201.773 211.701C202.881 210.863 204.153 210.535 205.587 210.717C207 210.896 208.126 211.529 208.968 212.615C209.808 213.701 210.14 214.95 209.96 216.362C209.761 217.931 209.1 219.1 207.978 219.868C206.856 220.636 205.633 220.936 204.311 220.768Z" fill="#7E171B"/>
                            <path opacity="0.2" d="M219.6 234.726C219.51 239.337 218.145 243.895 215.789 247.963C213.644 251.673 210.672 254.976 207.094 257.543C206.851 257.718 206.605 257.888 206.356 258.056H175.992C175.785 257.89 175.584 257.719 175.384 257.543C172.697 255.186 170.601 252.145 169.343 248.56L163.928 250.173L163.997 250.558C164.165 251.482 163.521 252.463 162.561 252.749L160.646 253.319H160.644L159.781 253.577V241.634L160.511 241.416C160.873 241.308 161.23 241.315 161.542 241.415C162.056 241.58 162.449 241.996 162.553 242.571L162.622 242.957L167.965 241.366L168.037 241.345C167.955 238.134 168.499 234.932 169.576 231.885C172.631 223.242 179.967 215.849 189.398 213.041C203.428 208.865 216.78 216.415 219.22 229.908C219.51 231.508 219.633 233.121 219.6 234.726Z" fill="#7E171B"/>
                            <path d="M168.476 246.625L166.242 239.41L157.8 242.023L160.033 249.238L168.476 246.625Z" fill="#7E171B"/>
                            <path d="M124.551 260.651L122.079 252.665C121.793 251.741 122.31 250.76 123.234 250.474L156.435 240.195C157.359 239.909 158.34 240.426 158.626 241.35L161.098 249.337C161.384 250.261 160.867 251.242 159.943 251.528L126.742 261.806C125.818 262.092 124.838 261.575 124.551 260.651Z" fill="#9F151A"/>
                            <path d="M207.314 254.334C217.301 244.347 217.301 228.155 207.314 218.167C197.327 208.18 181.134 208.18 171.147 218.167C161.16 228.155 161.16 244.347 171.147 254.334C181.134 264.322 197.327 264.322 207.314 254.334Z" fill="#9F151A"/>
                            <path d="M203.037 250.057C210.662 242.432 210.662 230.07 203.037 222.445C195.412 214.82 183.05 214.82 175.425 222.445C167.8 230.07 167.8 242.432 175.425 250.057C183.05 257.682 195.412 257.682 203.037 250.057Z" fill="white"/>
                            <path d="M183.613 248.545C183.356 248.545 183.095 248.484 182.851 248.355C182.051 247.934 181.744 246.943 182.166 246.143L193.402 224.83C193.824 224.03 194.814 223.724 195.614 224.145C196.414 224.567 196.721 225.558 196.299 226.358L185.063 247.671C184.77 248.227 184.201 248.545 183.613 248.545Z" fill="#9F151A"/>
                            <path d="M199.888 243.506C199.631 243.506 199.369 243.445 199.126 243.317L177.813 232.081C177.013 231.659 176.706 230.669 177.128 229.868C177.55 229.068 178.541 228.762 179.341 229.183L200.653 240.419C201.453 240.841 201.76 241.832 201.338 242.632C201.045 243.188 200.476 243.506 199.888 243.506Z" fill="#9F151A"/>
                            <defs>
                                <filter id="filter0_f_756_35712" x="0.599998" y="0.142967" width="394.8" height="392.8" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                    <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                    <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
                                    <feGaussianBlur stdDeviation="55.7" result="effect1_foregroundBlur_756_35712"/>
                                </filter>
                                <linearGradient id="paint0_linear_756_35712" x1="147.338" y1="167.203" x2="189.06" y2="253.67" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#FFF1F2"/>
                                    <stop offset="1" stop-color="white" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
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
                :header="col.header"
                :sortable="col.sortable"
                v-for="col of columns" :key="col.field"
                :style="{ width: col.width + '%' }"
            >
                <template #body="slotProps">
                    <slot :name="col.field" :="slotProps.data">
                        {{ slotProps.data[col.field] }}
                    </slot>
                    
                    <div class="flex justify-end items-start gap-2" v-if="col.field === 'action' && !mergedRowType.rowGroups">
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
                        <Link
                            :as="'button'"
                            :href="mergedActions.delete(slotProps.data.id)"
                            class="block transition duration-150 ease-in-out"
                            @click="handleLinkClick"
                            v-if="col.delete"
                        >
                            <DeleteIcon
                                class="w-6 h-6 text-primary-600 hover:text-primary-700 cursor-pointer pointer-events-none"
                                @click="console.log('click')"
                            />
                        </Link>
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
                    Showing: <span class="text-grey-900">{{ currentPage }} of {{ props.totalPages }}</span>
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
                        @change="goToPage($event)"
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
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4" v-if="props.variant === 'grid'">
            <div v-for="(item, index) in paginatedRows" :key="index" class="col-span-full md:col-span-3">
                <div class="flex flex-col items-start gap-3">
                    <slot name="item-body">
                        <div 
                            :class="[
                                'self-stretch w-full rounded-[5px] border border-grey-100',
                                {
                                    'bg-primary-25': index % 2 === 0,
                                    'bg-white': index % 2 !== 0
                                }
                            ]"
                        >
                            <div class="w-full h-[170px]"></div>
                            <div class="flex p-[2px] items-start flex-shrink-0 gap-0.5">
                                <Button
                                    :type="'button'"
                                    :size="'md'"
                                    @click="redirectAction(mergedActions.view(item.id))"
                                    class="!bg-primary-100 hover:!bg-primary-200 rounded-tl-none rounded-tr-none rounded-br-none rounded-bl-[5px]"
                                >
                                    <EditIcon
                                        class="w-5 h-5 text-primary-900 hover:text-primary-800 cursor-pointer"
                                    />
                                </Button>
                                <Button
                                    :type="'button'"
                                    :size="'md'"
                                    @click="redirectAction(mergedActions.delete(item.id))"
                                    class="!bg-primary-600 hover:!bg-primary-700 rounded-tl-none rounded-tr-none rounded-bl-none rounded-br-[5px]"
                                >
                                    <DeleteIcon
                                        class="w-5 h-5 text-primary-100 hover:text-primary-50 cursor-pointer pointer-events-none"
                                    />
                                </Button>
                            </div>
                        </div>
                        <span class="text-md font-semibold text-primary-950">RM {{ item.price }}</span>
                        <Tag
                            :variant="item.keep ? 'green' : 'yellow'"
                            :value="item.keep === 'Active' ? 'Keep is allowed' : 'Keep is not allowed'"
                        />
                        <div class="flex items-center self-stretch gap-2">
                            <span class="text-base font-medium text-grey-900">{{ item.point }} pts</span>
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                width="5" 
                                height="4" 
                                viewBox="0 0 5 4" 
                                fill="none"
                            >
                                <circle 
                                    cx="2.5" 
                                    cy="2" 
                                    r="2" 
                                    fill="#353D45"
                                />
                            </svg>
                            <span class="text-base font-medium text-primary-600">{{ item.stock_qty }} left</span>
                        </div>
                        <div class="flex gap-2 items-center">
                            <!-- Awaiting addition of 'set' column in product table to determine if its set or not -->
                            <!-- <Tag
                                :value="'Set'"
                            /> -->
                            <span class="text-sm font-medium text-grey-900 overflow-hidden text-ellipsis">{{ item.product_name }}</span>
                        </div>
                    </slot>
                </div>
            </div>
        </div>
        <Paginator 
            :rows="props.rowsPerPage" 
            :totalRecords="props.rows.length"
            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
            @page="onPageChange"
            v-if="props.variant === 'grid'"
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
                    Showing: <span class="text-grey-900">{{ currentPage }} of {{ props.totalPages }}</span>
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
                        @change="goToPage($event)"
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
