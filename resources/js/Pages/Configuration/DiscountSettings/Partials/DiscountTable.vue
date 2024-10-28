<script setup>
import { EmptyIllus } from '@/Components/Icons/illus';
import { DeleteIcon, EditIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import { transactionFormat } from '@/Composables';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { computed, ref } from 'vue';
import EditDiscount from './EditDiscount.vue';

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
const selectedDiscount = ref(null);

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
    isEditDiscountOpen.value = true;
    selectedDiscount.value = discount;
};

const openDeleteDiscount = (event, discountId) => {
    event.preventDefault();
    event.stopPropagation();
    isDeleteDiscountOpen.value = true;
    selectedDiscount.value = discountId;
};

const closeModal = () => {
    isEditDiscountOpen.value = false;
    isDeleteDiscountOpen.value = false;
}

</script>

<template>
    <table class="w-full border-spacing-3 border-collapse min-w-[755px]">
        <thead class="w-full bg-primary-50">
            <tr>
                <th class="w-[31%] py-2 px-3 transition ease-in-out hover:bg-primary-200">
                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold pl-[60px]">
                        Product
                    </span>
                </th>
                <th class="w-[11.5%] py-2 px-3 transition ease-in-out hover:bg-primary-200">
                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                        Before
                    </span>
                </th>
                <th class="w-[12%] py-2 px-3 transition ease-in-out hover:bg-primary-200">
                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                        Discount
                    </span>
                </th>
                <th class="w-[10.5%] py-2 px-3 transition ease-in-out hover:bg-primary-200">
                    <span class="flex justify-between items-center text-sm text-primary-900 font-semibold">
                        After
                    </span>
                </th>
                <th class="w-[14%] py-2 px-3 transition ease-in-out hover:bg-primary-200">
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
                v-for="(group, groupIndex, groupName) in computedRowsPerPage" :key="groupName" 
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
                                        <div class="size-8 rounded-full bg-primary-300"></div>
                                        {{ data.product }}
                                    </div>
                                    <div class="w-[11.5%] py-2 px-3">RM {{ formatAmount(data.before) }}</div>
                                    <div class="w-[11.5%] py-2 px-3">{{ formatRate(data.discount, data.type) }}</div>
                                    <div class="w-[10.5%] py-2 px-3">RM {{ formatAmount(data.after) }}</div>
                                    <div class="w-[14%] py-2 px-3">{{ data.start_on }}</div>
                                    <div class="w-[14%] py-2 px-3">{{ data.end_on }}</div>
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

    <Modal
        :title="'Edit Discount'"
        :maxWidth="'md'"
        :closeable="true"
        :show="isEditDiscountOpen"
        @close="closeModal"
    >
        <EditDiscount 
            :details="selectedDiscount"
            @close="closeModal"
            @discountDetails="discountDetails"
        />

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
        @close="closeModal"
    />
</template>

