<script setup>
import Button from "@/Components/Button.vue";
import { DeleteCustomerIllust, UndetectableIllus } from "@/Components/Icons/illus";
import { DeleteIcon, EditIcon, PlusIcon, UploadIcon } from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";
import SearchBar from "@/Components/SearchBar.vue";
import Table from "@/Components/Table.vue";
import Tag from "@/Components/Tag.vue";
import TextInput from "@/Components/TextInput.vue";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { Head, useForm } from "@inertiajs/vue3";
import { FilterMatchMode } from "primevue/api";
import { computed, onMounted, ref, watch } from "vue";
import Checkbox from "@/Components/Checkbox.vue";
import Slider from "@/Components/Slider.vue";
import RightDrawer from "@/Components/RightDrawer/RightDrawer.vue";
import CustomerDetail from "./CustomerDetail.vue";
import { useCustomToast, useFileExport } from "@/Composables";
import dayjs from "dayjs";
import CreateCustomer from "./CreateCustomer.vue";
import EditCustomer from "./EditCustomer.vue";
import Textarea from "@/Components/Textarea.vue";


const props = defineProps ({
    columns: Array,
    customers: Array,
    actions: {
        type: Object,
        default: () => {},
    },
    rowType: Object,
    totalPages: Number,
    rowsPerPage: Number,
    highestPoints: Number,
    rankingArr: Array
})

const emit = defineEmits(["applyCheckedFilters"]);
const { exportToCSV } = useFileExport();
const { showMessage } = useCustomToast();

const customer = ref(props.customers);
const isSidebarOpen = ref(false);
const isCreateCustomerOpen = ref(false);
const isEditCustomerOpen = ref(false);
const isDeleteCustomerOpen = ref(false);
const selectedCustomer = ref(null);
const highestPoints = ref(props.highestPoints);
const searchQuery = ref('');
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);
const customerCurrentOrdersCount = ref(0);
const showUploadButton = ref(false);

const form = useForm({
    id: customer.id,
    password: '',
    remark: '',
})

const showSideBar = (customer) => {
    isSidebarOpen.value = true;
    selectedCustomer.value = customer;
}

const hideSideBar = () => {
    isSidebarOpen.value = false;
}

const getCurrentOrdersCount = async (id) => {
    // isLoading.value = true;
    try {
        const response = await axios.get(`customer/getCurrentOrdersCount/${id}`);
        customerCurrentOrdersCount.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {
        // isLoading.value = false;
    }
};

const openModal = (action, data) => {
    isDirty.value = false;
    switch(action) {
        case 'create': 
            isCreateCustomerOpen.value = true;
            break;
        case 'edit': 
            selectedCustomer.value = data;
            isEditCustomerOpen.value = true;
            break;
        case 'delete': 
            getCurrentOrdersCount(data);
            form.id = data;
            setTimeout(() => {
                isDeleteCustomerOpen.value = true;
            }, 300);
            break;
    };
}

const closeModal = (status) => {
    switch(status) {
        case 'close':{
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isCreateCustomerOpen.value = false;
                isEditCustomerOpen.value = false;
                isDeleteCustomerOpen.value = false;
                selectedCustomer.value = null;
                form.reset();
                form.clearErrors();
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isCreateCustomerOpen.value = false;
            isEditCustomerOpen.value = false;
            isDeleteCustomerOpen.value = false;
            selectedCustomer.value = null;
            form.reset();
            form.clearErrors();

            break;
        }
    }

}

// const showDeleteModal = (id) => {
//     isDeleteCustomerOpen.value = true;
//     form.id = id;
// }

// const closeModal = () => {
//     isDeleteCustomerOpen.value = false;
//     setTimeout(() => form.reset(), 300);
// }

const submit = () => {
    form.delete(route(`customer.delete-customer`, form.id), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            closeModal();
            form.reset();
            form.clearErrors();
        }
    })
}

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

// const tierArr = ref(['No Tier', 'VIP', 'VVIP', 'VVVIP']);
const checkedFilters = ref({
    tier: [],
    points: [0, props.highestPoints],
    keepItems: [],
})

const resetFilters = () => {
    return {
        tier: [],
        points: [0, props.highestPoints],
        keepItems: [],
    }
};

const clearFilters = (close) => {
    checkedFilters.value = resetFilters();
    emit('applyCheckedFilters', checkedFilters.value);
    close();
};

const applyCheckedFilters = (close) => {
    emit('applyCheckedFilters', checkedFilters.value);
    close();
};

const toggleTier = (value) => {
    const index = checkedFilters.value.tier.indexOf(value);
    if (index > -1) {
        checkedFilters.value.tier.splice(index, 1);
    } else {
        checkedFilters.value.tier.push(value);
    }
};

const toggleKeepStatus = () => {
    checkedFilters.value.keepItems = 
        checkedFilters.value.keepItems.includes(true) ? [] : [true];
}

const csvExport = () => {
    const mappedCustomers = props.customers.map(customer => ({
        'Tier': parseInt(customer.ranking) !== 0 ? customer.rank.name : 'No Tier',
        'Customer': customer.full_name,
        'Points': customer.point,
        'Keep': customer.keep_items_count,
        'Joined Date': dayjs(customer.created_at).format('DD/MM/YYYY'),
    }));
    exportToCSV(mappedCustomers, 'Customer List')
}

const formatPoints = (points) => {
  const pointsStr = points.toString();
  return pointsStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const fileInput = ref(null);
const isLoading = ref(false);
const fileName = ref('');
const importForm = useForm({
    keep_item_list: []
});

// Define which columns you want to extract
const columnsToExtract = ref([
  'Date2',
  'NAME',
  'ROOM',
  'Item2',
  'QTY',
]);

const importKeepItems = async () => {
    isLoading.value = true;
    importForm.post(`/customer/importKeepItems`, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            setTimeout(() => {
                showMessage({
                    severity: 'success',
                    summary: 'Keep item list has been imported successfully.'
                });
            }, 200);
        },
        onError: (error) => {
            console.error(error);
        },
        onFinish: visit => {
            isLoading.value = false;
            fileInput.value = null;
            importForm.keep_item_list = [];
            fileName.value = '';
        },
    })
    // try {
    //     const response = await axios.post('/customer/importKeepItems', importForm);

    //     setTimeout(() => {
    //         showMessage({
    //             severity: 'success',
    //             summary: 'Keep item list has been imported successfully.'
    //         });
    //     }, 200);

    // } catch (error) {
    //     console.error(error);
    // }  finally {
    //     isLoading.value = false;
    //     fileInput.value = null;
    //     importForm.keep_item_list = [];
    //     fileName.value = '';
    // }
}

// Function to reformat dates
const reformatDate = (dateValue) => {
  if (!dateValue) return null;
  
  // If it's already a proper date string (from Excel)
  if (typeof dateValue === 'string' && dateValue.match(/^\d{4}-\d{2}-\d{2}/)) {
    const [year, month, day] = dateValue.split('-');
    return `${year}/${month}/${day}`;
  }
  
  // If it's an Excel date number (serial date)
  if (typeof dateValue === 'number') {
    const date = new Date((dateValue - (25567 + 1)) * 86400 * 1000);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}/${month}/${day}`;
  }
  
  // Handle day-month formats (like "21-6")
  if (typeof dateValue === 'string') {
    // Check for day-month format (e.g., "21-6", "22/10")
    const dayMonthMatch = dateValue.match(/^(\d{1,2})[-/](\d{1,2})(?:[-/](\d{2,4}))?$/);
    
    if (dayMonthMatch) {
      let day = dayMonthMatch[1];
      let month = dayMonthMatch[2];
      let year = dayMonthMatch[3];
      
      // Pad with zeros if needed
      day = day.padStart(2, '0');
      month = month.padStart(2, '0');
      
      // If year is missing, use 2025 as default
      year = year || '2025';
      
      // If year is 2 digits, assume 20XX
      if (year.length === 2) {
        year = `20${year}`;
      }
      
      return `${year}/${month}/${day}`;
    }
  }
  
  // Return original value if we can't parse it
  return dateValue;
};

// Load SheetJS from CDN
const loadSheetJS = () => {
  return new Promise((resolve) => {
    if (window.XLSX) return resolve(window.XLSX);
    
    const script = document.createElement('script');
    script.src = 'https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js';
    script.onload = () => resolve(window.XLSX);
    document.head.appendChild(script);
  });
};

const triggerFileInput = () => {
  fileInput.value.click();
};

const handleFileUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  fileName.value = file.name;
  importForm.keep_item_list = []; // Reset previous data
  
  try {
    const XLSX = await loadSheetJS();
    const reader = new FileReader();
    
    reader.onload = (e) => {
      const data = new Uint8Array(e.target.result);
      const workbook = XLSX.read(data, { type: 'array' });
      
      // Get first worksheet
      const firstSheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[firstSheetName];
      
      // Convert to array of arrays (raw data)
      const rawData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
      
      if (rawData.length === 0) {
        throw new Error('Empty Excel file');
      }
      
      // Get headers (first row)
      const headers = rawData[0].map(header => header.trim());
      
      // Find indices of columns we want to extract
      const columnIndices = columnsToExtract.value.map(col => {
        const index = headers.findIndex(header => header === col);
        if (index === -1) {
          console.warn(`Column "${col}" not found in Excel file`);
        }
        return index;
      }).filter(index => index !== -1);
      
      // Extract data for selected columns
      const result = rawData.slice(1)
            .map(row => {
                const extractedRow = {};
                columnIndices.forEach((colIndex, i) => {
                    if (colIndex !== -1 && colIndex < row.length) {
                        const colName = columnsToExtract.value[i];
                        let value = row[colIndex];
                        
                        // Special handling for Date2 column
                        if (colName === 'Date2') {
                            value = reformatDate(value);
                        }
                        
                        extractedRow[colName] = value;
                    }
                });
                return extractedRow;
            })
            .filter(row => Object.keys(row).length > 0)
            .filter(row => row.NAME && String(row.NAME).trim() !== '');
        
        importForm.keep_item_list = result;
        importKeepItems();
    };
    
    reader.onerror = () => {
      console.error('Error reading file');
    };
    
    reader.readAsArrayBuffer(file);
  } catch (error) {
    console.error('Error processing Excel file:', error);
  } finally {
    fileInput.value = null;
    importForm.keep_item_list = [];
    fileName.value = '';
  }
};

let hasTriggered = false;

onMounted(() => {
    const path = window.location.pathname;
    if (path === '/customer/import-keep-items') {
        showUploadButton.value = true;
    }
});

watch(() => props.customers, (newValue) => customer.value = newValue)

watch (() => searchQuery.value, (newValue) => {
    if(newValue === '') {
        customer.value = props.customers;
        return;
    }

    const query = newValue.toLowerCase();

    customer.value = props.customers.filter(customer => {
        const tier = customer.rank.name.toLowerCase();
        const name = customer.full_name.toLowerCase();
        const email = customer.email?.toLowerCase();
        const phoneNumber = customer.phone?.toString().toLowerCase();
        // const keptItem = customer.keep_items ? customer.keep_items.item_name.toLowerCase() : '';
        const keptItems = Array.isArray(customer.keep_items)
            ? customer.keep_items.some(item => item.item_name.toLowerCase().includes(query))
            : false;

        return  tier.includes(query) ||
                name.includes(query) ||
                email?.includes(query) ||
                phoneNumber?.includes(query) ||
                keptItems;
                // phoneNumber.includes(query);
    })
}, { immediate: true })

const deleteModalTitle = computed(() => {
    return customerCurrentOrdersCount.value > 0
        ? 'This customer still has open orders!'
        : 'Passcode Required';
});

const deleteModalDescription = computed(() => {
    return customerCurrentOrdersCount.value > 0
        ? 'You cannot delete this customer until all current orders are completed and paid. Would you like to proceed to view customer order?'
        : 'To delete this customer, you have to enter the passcode provided from the master admin.';
});

</script>

<template>
    <div class="flex flex-col gap-5">
        <div class="flex flex-col p-6 items-start self-stretch gap-6 border border-primary-100 rounded-[5px]">
            <div class="inline-flex items-center w-full justify-between gap-2.5">
                <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">Visited Customer</span>
                <input 
                    type="file" 
                    ref="fileInput" 
                    @change="handleFileUpload" 
                    accept=".xlsx,.xls,.csv"
                    class="hidden"
                />
                <Button
                    v-if="showUploadButton"
                    :type="'button'"
                    :variant="'tertiary'"
                    :size="'lg'"
                    class="!w-fit"
                    @click="triggerFileInput"
                    :disabled="isLoading"
                >
                    <template v-if="isLoading">Processing...</template>
                    <template v-else>Import Keep Items</template>
                </Button>
                <Menu as="div" class="relative inline-block text-left">
                    <div>
                        <MenuButton
                            class="inline-flex items-center w-full justify-center rounded-[5px] gap-2 bg-white border border-primary-800 px-4 py-3 max-h-11 text-sm font-medium text-primary-900 hover:text-primary-800">
                            Export
                            <UploadIcon class="size-4 cursor-pointer" />
                        </MenuButton>
                    </div>

                    <transition 
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0" 
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100" 
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <MenuItems
                            class="absolute z-10 right-0 mt-2 w-32 p-1 gap-0.5 origin-top-right divide-y divide-y-grey-100 rounded-md bg-white shadow-lg"
                            >
                            <MenuItem v-slot="{ active }">
                            <button type="button" :class="[
                                { 'bg-primary-100': active },
                                { 'bg-grey-50 pointer-events-none': customers.length === 0 },
                                'group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                            ]" :disabled="customers.length === 0" @click="csvExport">
                                CSV
                            </button>
                            </MenuItem>

                            <MenuItem v-slot="{ active }">
                            <button type="button" :class="[
                                // { 'bg-primary-100': active },
                                { 'bg-grey-50 pointer-events-none': customers.length === 0 },
                                'bg-grey-50 pointer-events-none group flex w-full items-center rounded-md px-4 py-2 text-sm text-gray-900',
                            ]" :disabled="customers.length === 0">
                                PDF
                            </button>
                            </MenuItem>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>

            <div class="w-full flex gap-5 flex-wrap sm:flex-nowrap items-center justify-between">
                <SearchBar 
                    placeholder="Search"
                    :showFilter="true"
                    v-model="searchQuery"
                >
                    <template #default="{ hideOverlay }">
                        <div class="flex flex-col self-stretch gap-4 items-start">
                            <span class="text-grey-900 text-base font-semibold">Tier</span>
                            <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                                <div 
                                    v-for="(tier, index) in rankingArr" 
                                    :key="index"
                                    class="flex py-2 px-3 gap-2 items-center border border-grey-100 rounded-[5px]"
                                >
                                    <Checkbox 
                                        :checked="checkedFilters.tier.includes(tier.id)"
                                        @click="toggleTier(tier.id)"
                                    />
                                    <span class="text-grey-700 text-sm font-medium">{{ tier.name }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col self-stretch gap-4 items-start">
                            <span class="text-grey-900 text-base font-semibold">Points</span>
                            <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                                <div class="flex items-center w-full">
                                    <Slider 
                                        :minValue="0"
                                        :maxValue="highestPoints"
                                        v-model="checkedFilters.points"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col self-stretch gap-4 items-start">
                            <div class="flex gap-3 self-stretch items-start justify-center flex-wrap">
                                <div class="inline-flex w-full gap-2 justify-between border border-grey-100 rounded-[5px]">
                                    <span>With keep item only</span>
                                    <Checkbox 
                                        :checked="checkedFilters.keepItems.includes(true)"
                                        @click="toggleKeepStatus()"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                            <Button
                                :type="'button'"
                                :variant="'tertiary'"
                                :size="'lg'"
                                @click="clearFilters(hideOverlay)"
                            >
                                Clear All
                            </Button>
                            <Button
                                :size="'lg'"
                                @click="applyCheckedFilters(hideOverlay)"
                            >
                                Apply
                            </Button>
                        </div>
                    </template>
                </SearchBar>
                
                <Button
                    :type="'button'"
                    :size="'lg'"
                    :iconPosition="'left'"
                    class="!w-fit"
                    @click="openModal('create')"
                >
                    <template #icon>
                        <PlusIcon class="w-6 h-6"/>
                    </template>
                    New Customer
                </Button>
            </div>

            <Table
                :columns="columns"
                :rows="customer"
                :variant="'list'"
                :actions="actions"
                :rowType="rowType"
                :totalPages="totalPages"
                :rowsPerPage="rowsPerPage"
                :searchFilter="true"
                :filters="filters"
                @onRowClick="showSideBar($event.data)"
            >
                <template #empty>
                    <UndetectableIllus class="w-44 h-44"/>
                    <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                </template>
                <template #editAction="customer">
                    <EditIcon
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click.stop="openModal('edit', customer)"
                    />
                </template>
                <template #deleteAction="customer">
                    <DeleteIcon
                        class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                        @click.stop="openModal('delete', customer.id)"
                    />
                </template>
                <template #ranking="customer">
                    <Tag
                        :variant="parseInt(customer.ranking) !== 0 ? 'default' : 'grey'"
                        :value="parseInt(customer.ranking) !== 0 ? customer.rank.name : 'No Tier'"
                    />
                </template>
                <template #full_name="customer">
                    <template class="flex flex-row gap-[10px] items-center">
                        <img :src="customer.image ? customer.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt=""
                                class="w-[32px] h-[32px] flex-shrink-0 rounded-full object-contain"
                        />
                        <span class="text-grey-900 text-sm font-medium line-clamp-1">{{ customer.full_name }}</span>
                    </template>
                </template>
                <template #point="customer">
                    <span class="text-grey-900 text-sm font-medium line-clamp-1 flex-[1_0_0]">{{ formatPoints(customer.point) }} pts</span>
                </template>
                <template #created_at="customer">
                    <span class="text-grey-900 text-sm font-medium line-clamp-1 flex-[1_0_0]">{{ dayjs(customer.created_at).format('DD/MM/YYYY') }}</span>
                </template>
            </Table>
        </div>
    </div>

    <Modal
        :maxWidth="'2xs'"
        :closeable="true"
        :show="isDeleteCustomerOpen"
        :confirmationTitle="`Passcode Required`"
        :confirmationMessage="`To delete this customer, you have to enter the passcode provided from the master admin.`"
        @close="closeModal('close')"
        :withHeader="false"
    >
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-9" >
                <div class="bg-primary-50 flex items-center justify-center rounded-t-[5px] m-[-24px]">
                    <DeleteCustomerIllust />
                </div>
                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-1 text-center">
                        <span class="text-primary-900 text-lg font-medium self-stretch">{{ deleteModalTitle }}</span>
                        <span class="text-grey-900 text-base font-medium self-stretch">{{ deleteModalDescription }}</span>
                    </div>
                    <TextInput
                        v-if="customerCurrentOrdersCount === 0"
                        required
                        :labelText="'Passcode'"
                        inputName="password"
                        inputType="password"
                        :errorMessage="form.errors && form.errors.password ? form.errors.password : ''"
                        v-model="form.password"
                    />
                    <Textarea 
                        v-if="customerCurrentOrdersCount === 0"
                        :inputName="'remark'"
                        :labelText="'Reason of customer deletetion'"
                        :errorMessage="form.errors && form.errors.remark ? form.errors.remark : ''"
                        :placeholder="'Enter the reason'"
                        :rows="3"
                        v-model="form.remark"
                    />
                </div>

                <div class="flex gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeModal('close')"
                    >
                        {{ customerCurrentOrdersCount > 0 ? 'Maybe later' : 'Cancel' }}
                    </Button>
                    <Button
                        v-if="customerCurrentOrdersCount === 0"
                        variant="red"
                        size="lg"
                        type="submit"
                    >
                        Delete
                    </Button>
                    <Button
                        v-else
                        :href="route('orders')"
                        size="lg"
                        type="button"
                    >
                        Go view
                    </Button>
                </div>
            </div>
        </form>
    </Modal>

    <Modal 
        :title="'Create New Customer'"
        :show="isCreateCustomerOpen" 
        :maxWidth="'xs'" 
        :closeable="true" 
        @close="closeModal('close')"
    >
        <CreateCustomer
            @update:customerListing="customer = $event"
            @close="closeModal" 
            @isDirty="isDirty=$event"
        />

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>

    <Modal 
        :title="'Edit Customer'"
        :show="isEditCustomerOpen" 
        :maxWidth="'xs'" 
        :closeable="true" 
        @close="closeModal('close')"
    >
        <!-- <template v-if="selectedCustomer"> -->
            <EditCustomer
                :customer="selectedCustomer"
                @update:customerListing="customer = $event"
                @close="closeModal"
                @isDirty="isDirty=$event"
            />
    
            <Modal
                :unsaved="true"
                :maxWidth="'2xs'"
                :withHeader="false"
                :show="isUnsavedChangesOpen"
                @close="closeModal('stay')"
                @leave="closeModal('leave')"
            />
        <!-- </template> -->
    </Modal>

    <!-- right drawer -->
     <RightDrawer
        :title="''"
        :header="'Customer Detail'"
        :show="isSidebarOpen"
        @close="hideSideBar"
     >
        <CustomerDetail 
            :customer="selectedCustomer"
            @update:customerKeepItems="selectedCustomer.keep_items = $event"
            @update:customerPoints="selectedCustomer.point = $event"
        />
     </RightDrawer>
</template>
