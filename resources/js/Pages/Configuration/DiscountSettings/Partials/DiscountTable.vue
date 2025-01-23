<script setup>
import { EmptyIllus } from '@/Components/Icons/illus';
import { DeleteIcon, EditIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import { transactionFormat } from '@/Composables';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { computed, ref, watch } from 'vue';
import EditDiscount from './EditDiscount.vue';
import TextInput from '@/Components/TextInput.vue';
import Paginator from 'primevue/paginator';

const props = defineProps({
    details: {
        type: Array,
    }
});
const emit = defineEmits(['discountDetails']);
const { formatAmount } = transactionFormat();
const currentPage = ref(1);
const isEditDiscountOpen = ref(false);
const isDeleteDiscountOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const selectedDiscount = ref(null);
const totalPages = computed(() => Math.ceil((props.details?.length || 0) / 4));
// const details = ref(props.details);

const computedRowsPerPage = computed(() => {
    const start = (currentPage.value - 1) * 4;
    const end = start + 4;
    return props.details.slice(start, end);
});

const discountDetails = () => {
    emit('discountDetails');
}

const formatRate = (rate, type) => {
    if(type === 'fixed'){
        return 'RM' + formatAmount(rate);
    } else {
        return rate + '%';
    }
}

const openEditDiscount = (event, discount) => {
    event.preventDefault();
    event.stopPropagation();
    isDirty.value = false;
    isEditDiscountOpen.value = true;
    selectedDiscount.value = discount;
};

const openDeleteDiscount = (event, discountId) => {
    event.preventDefault();
    event.stopPropagation();
    isDeleteDiscountOpen.value = true;
    selectedDiscount.value = discountId;
};

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isEditDiscountOpen.value = false;
            }
            break;
        };
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isEditDiscountOpen.value = false;
            isDeleteDiscountOpen.value = false;
            break;
        }
    }
}

const hideDeleteModal = () => {
    isDeleteDiscountOpen.value = false;
}

const onPageChange = (event) => {
    currentPage.value = event.page + 1;
};

const goToPage = (event) => {
    const page = parseInt(event.target.value);
    if (page > 0 && page <= totalPages.value) {
        currentPage.value = page;
    }
}

watch(() => props.details, () => {
    currentPage.value = 1;
}, { immediate: true });


</script>

<template>
    <table class="w-full border-spacing-3 border-collapse">
        <thead class="w-full bg-primary-50">
            <tr>
                <th class="w-[31%] py-2 px-3 transition ease-in-out hover:bg-primary-200">
                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold pl-[60px]">
                        Product
                    </span>
                </th>
                <th class="w-[13%] py-2 px-3 transition ease-in-out hover:bg-primary-200">
                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                        Before
                    </span>
                </th>
                <th class="w-[9%] py-2 px-3 transition ease-in-out hover:bg-primary-200">
                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                        Discount
                    </span>
                </th>
                <th class="w-[13%] py-2 px-3 transition ease-in-out hover:bg-primary-200">
                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                        After
                    </span>
                </th>
                <th class="w-[13%] py-2 px-3 transition ease-in-out hover:bg-primary-200">
                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                        Start on
                    </span>
                </th>
                <th class="w-[21%] py-2 px-3 transition ease-in-out pr-[80px] hover:bg-primary-200">
                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                        End on
                    </span>
                </th>
            </tr>
        </thead>
        <tbody class="w-full before:content-['@'] before:table-row before:leading-3 before:indent[-99999px] before:invisible">
            <tr 
                v-if="details.length > 0" 
                v-for="(group, groupIndex) in computedRowsPerPage" :key="groupIndex" 
                class="rounded-[5px]"
                :class="groupIndex % 2 === 0 ? 'bg-white' : 'bg-primary-25'"
            >
                <td colspan="8" class="p-0">
                    <Disclosure 
                        as="div" 
                        :defaultOpen="false"
                        v-slot="{ open }" 
                        class="flex flex-col justify-center min-h-12"
                    >
                        <DisclosureButton class="flex items-center justify-between gap-2.5 rounded-sm p-3 hover:bg-primary-50">
                            <table class="w-full border-spacing-1 border-separate">
                                <tbody class="w-full">
                                    <tr>
                                        <td class="w-[5%]">
                                            <svg 
                                                width="20" 
                                                height="20" 
                                                viewBox="0 0 20 20" 
                                                fill="none" 
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="inline-block text-grey-900 transition ease-in-out"
                                                :class="[open ? '' : '-rotate-90']"
                                            >
                                                <path d="M15.8337 7.08337L10.0003 12.9167L4.16699 7.08337" stroke="currentColor" stroke-width="1.5" stroke-linecap="square"/>
                                            </svg>
                                        </td>
                                        <td class="w-[23%]">
                                            <div class="flex items-center gap-3">
                                                <span class="text-grey-900 text-sm font-medium text-ellipsis overflow-hidden">{{ group.discount }}</span>
                                            </div>
                                        </td>
                                        <td class="w-[56%]"></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="flex items-center justify-between">
                                <div class="flex flex-nowrap gap-2">
                                    <EditIcon 
                                        class="w-5 h-5 text-primary-900 hover:text-primary-800 cursor-pointer"
                                        @click="openEditDiscount($event, group)"
                                    />
                                    <DeleteIcon 
                                        class="w-5 h-5 text-primary-600 hover:text-primary-700 cursor-pointer"
                                        @click="openDeleteDiscount($event, group.id)"
                                    />
                                </div>
                            </div>
                        </DisclosureButton>
                        <transition
                            enter-active-class="transition duration-100 ease-out"
                            enter-from-class="transform scale-95 opacity-0"
                            enter-to-class="transform scale-100 opacity-100"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="transform scale-100 opacity-100"
                            leave-to-class="transform scale-95 opacity-0"
                        >
                            <DisclosurePanel class="bg-white pt-2 pb-3" >
                                <div 
                                    class="w-full flex items-center gap-x-3 rounded-[5px] text-sm text-grey-900 font-medium odd:bg-white even:bg-primary-25 odd:text-grey-900 even:text-grey-900 hover:bg-primary-50" 
                                    v-for="(data, index) in group.details" :key="index"
                                >
                                    <div class="w-[6%] py-2 px-3"></div>
                                    <div class="w-[25.5%] py-2 px-3 truncate flex gap-2.5 items-center">
                                        <!-- <div class="size-8 rounded-full bg-primary-300"></div> -->
                                        <img 
                                            :src="data.image ? data.image 
                                                            : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                            alt=""
                                            class="size-8 rounded-full"
                                        />
                                        {{ data.product }}
                                    </div>
                                    <div class="w-[13%] py-2 px-3">RM {{ formatAmount(data.before) }}</div>
                                    <div class="w-[9.5%] py-2 px-3">{{ formatRate(data.discount, data.type) }}</div>
                                    <div class="w-[13%] py-2 px-3">RM {{ formatAmount(data.after) }}</div>
                                    <div class="w-[13%] py-2 px-3">{{ data.start_on }}</div>
                                    <div class="w-[13%] py-2 px-3">{{ data.end_on }}</div>
                                    <div class="w-[7%] py-2 px-3"></div>
                                </div>
                            </DisclosurePanel>
                        </transition>
                    </Disclosure>
                </td>
            </tr>
            <tr v-else>
                <td colspan="8">
                    <div class="flex flex-col items-center justify-center gap-5">
                        <EmptyIllus />
                        <span class="text-primary-900 text-sm font-medium">We couldn't find any result...</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- <Paginator
        v-if="props.details.length > 0"
        :rows="4" 
        :totalRecords="props.details.length"
        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
        @page="onPageChange"
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
                        'hidden': totalPages < 5,
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
                        'hidden': totalPages === 1
                    },
                    'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                    'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                    'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                ]
            },
            nextpagebutton: {
                class: [
                    {
                        'hidden': totalPages === 1
                    },
                    'flex w-[38px] h-[38px] py-2 px-[10px] justify-center items-center text-grey-900',
                    'hover:rounded-full hover:bg-primary-50 hover:text-primary-900',
                    'focus:rounded-full focus:bg-primary-900 focus:text-primary-50',
                ]
            },
            lastpagebutton: {
                class: [
                    {
                        'hidden': totalPages < 5
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
                Showing: <span class="text-grey-900">{{ totalPages.value === 0 ? 0 : currentPage }} of {{ totalPages.value === 0 ? 0 : totalPages }}</span>
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
                    v-if="totalPages === 1"
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
    </Paginator> -->

    <Modal
        :title="'Edit Discount'"
        :maxWidth="'md'"
        :closeable="true"
        :show="isEditDiscountOpen"
        @close="closeModal('close')"
    >
        <EditDiscount 
            :details="selectedDiscount"
            @close="closeModal"
            @isDirty="isDirty = $event"
            @discountDetails="discountDetails"
        />

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        >
        </Modal>

    </Modal>

    <Modal 
        :show="isDeleteDiscountOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/configurations/deleteDiscount/${selectedDiscount}`"
        :confirmationTitle="'Delete this discount?'"
        :confirmationMessage="'Deleting this discount will also remove discount from related products. Are you sure?'"
        :toastMessage="'Discount has been deleted.'"
        @close="hideDeleteModal"
    />

</template>

