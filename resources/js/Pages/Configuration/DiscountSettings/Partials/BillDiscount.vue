<script setup>
import Button from '@/Components/Button.vue';
import { DeleteIcon, EditIcon, PlusIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import TabView from '@/Components/TabView.vue';
import { computed, onMounted, ref, watch } from 'vue';
import AddBillDiscount from './AddBillDiscount.vue';
import axios from 'axios';
import Toggle from '@/Components/Toggle.vue';
import dayjs from 'dayjs';
import BillDiscountDetail from './BillDiscountDetail.vue';
import EditBillDiscount from './EditBillDiscount.vue';
import { useForm } from '@inertiajs/vue3';
import { useCustomToast } from '@/Composables';

const bill_discounts = ref([]);
const tabs = ref(['Active', 'Inactive'])
const isAddBillOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const isEdited = ref(false);
const isLoading = ref(false);
const isEditBillOpen = ref(false);
const isBillDetailOpen = ref(false);
const isDeleteBillOpen = ref(false);
const selectedBill = ref('');
const { showMessage } = useCustomToast();


const form = useForm({
    id: '',
    status: ''
})

const getBillDiscounts = async() => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('configurations.getAllBillDiscounts'));
        bill_discounts.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const updateStatus = (discount) => {
    
    form.id = discount.id;
    form.status = discount.status === 'active' ? 'inactive' : 'active';
    const status = form.status;

    form.post(route('configurations.updateBillStatus', form.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            setTimeout(() => {
                showMessage({
                    severity: 'success',
                    summary: `${discount.name} is now ${status}.`
                });
            }, 200);
            getBillDiscounts();
            form.reset();
        },
    });
};


const openAddBill = () => {
    isAddBillOpen.value = true;
}

const closeAddBill = (status) => {
    switch(status) {
        case 'close': {
            if(isDirty.value) isUnsavedChangesOpen.value = true;
            else isAddBillOpen.value = false;

            if(isEdited.value) isUnsavedChangesOpen.value = true;
            else isEditBillOpen.value = false;
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isAddBillOpen.value = false;
            isEditBillOpen.value = false;
            isBillDetailOpen.value = false;
            isDirty.value = false;
            break;
        }
    }
}

const closeDeleteBill = () => {
    isDeleteBillOpen.value = false;
}

const openBillDetail = (bill) => {
    selectedBill.value = bill;
    isBillDetailOpen.value = true;
}

const openEditBill = (bill) => {
    selectedBill.value = bill;
    isEditBillOpen.value = true;
}

const deleteBill = (id) => {
    isDeleteBillOpen.value = true;
    selectedBill.value = id;
}

onMounted(() => {
    getBillDiscounts();
})
</script>

<template>
    <div class="flex flex-col p-6 items-start gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
        <div class="flex items-center gap-5 self-stretch">
            <span class="flex flex-col h-5 justify-center flex-[1_0_0] text-primary-900 text-md font-medium">Bill Discount</span>
            <Button
                :type="'button'"
                :size="'lg'"
                :iconPosition="'left'"
                class="!w-fit"
                @click="openAddBill"
            >
                <template #icon>
                    <PlusIcon />
                </template>
                Bill Discount
            </Button>
        </div>

        <TabView :tabs="tabs" :tabFooter="bill_discounts.status">
            <template #count="{ tabs, selected }">
                <span 
                    :class="[
                        'text-center text-sm font-medium group-hover:text-primary-800', 
                        selected ? 'text-primary-900' : 'text-grey-200'
                    ]" 
                    v-if="bill_discounts.length"
                >
                    ({{ bill_discounts.filter(discount => discount.status.toLowerCase() === tabs.toLowerCase()).length }})
                </span>
            </template>
            <template
                v-for="tab in tabs.map((tab) => tab.toLowerCase())"
                :key="tab"
                v-slot:[tab]
            >
                <div class="flex flex-col items-start gap-5 self-stretch">
                    <template v-for="discount in bill_discounts.filter(discount => discount.status === tab)">
                        <div class="flex flex-col p-5 items-start gap-4 self-stretch rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)] cursor-pointer" @click="openBillDetail(discount)">
                            <div class="flex flex-col items-start self-stretch">
                                <div class="flex justify-between items-start self-stretch">
                                    <div class="flex items-center gap-3">
                                        <span class="text-grey-950 text-xl font-bold">{{ discount.discount_type === 'percentage' ? discount.discount_rate + '%' : 'RM' + discount.discount_rate }} off</span>
                                    </div>
                                    <div class="flex justify-end items-start gap-4">
                                        <div class="flex items-center gap-3">
                                            <DeleteIcon class="text-primary-600 hover:text-primary-800 cursor-pointer size-6" @click.prevent.stop="deleteBill(discount.id)"/>
                                            <EditIcon class="text-primary-900 hover:text-primary-800 cursor-pointer size-6" @click.prevent.stop="openEditBill(discount)"/>
                                        </div>
                                        <Toggle 
                                            :checked="discount.status === 'active'"
                                            @update:checked="updateStatus(discount)"
                                            @click.stop
                                        />
                                    </div>
                                </div>
                                <span class="self-stretch text-grey-700 text-md font-normal">{{ discount.name }}</span>
                            </div>
                            <span class="self-stretch text-grey-950 text-xs font-normal">{{ dayjs(discount.discount_from).format('DD/MM/YYYY') }} - {{ dayjs(discount.discount_to).format('DD/MM/YYYY') }}</span>
                        </div>
                    </template>
                </div>
            </template>
            <!-- <template #active>
                <div class="flex flex-col items-start gap-5 self-stretch">
                    <template v-for="discount in bill_discounts.filter(discount => discount.status === 'active')">
                        <div class="flex flex-col p-5 items-start gap-4 self-stretch rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)] cursor-pointer" @click="openBillDetail(discount)">
                            <div class="flex flex-col items-start self-stretch">
                                <div class="flex justify-between items-start self-stretch">
                                    <div class="flex items-center gap-3">
                                        <span class="text-grey-950 text-xl font-bold">{{ discount.discount_type === 'percentage' ? discount.discount_rate + '%' : 'RM' + discount.discount_rate }} off</span>
                                    </div>
                                    <div class="flex justify-end items-start gap-4">
                                        <div class="flex items-center gap-3">
                                            <DeleteIcon class="text-primary-600 hover:text-primary-800 cursor-pointer size-6" @click.prevent.stop="deleteBill(discount.id)"/>
                                            <EditIcon class="text-primary-900 hover:text-primary-800 cursor-pointer size-6" @click.prevent.stop="openEditBill(discount)"/>
                                        </div>
                                        <Toggle 
                                            :checked="discount.status === 'active'"
                                            @update:checked="updateStatus(discount, $event)"
                                            @click.stop
                                        />
                                    </div>
                                </div>
                                <span class="self-stretch text-grey-700 text-md font-normal">{{ discount.name }}</span>
                            </div>
                            <span class="self-stretch text-grey-950 text-xs font-normal">{{ dayjs(discount.discount_from).format('DD/MM/YYYY') }} - {{ dayjs(discount.discount_to).format('DD/MM/YYYY') }}</span>
                        </div>
                    </template>
                </div>
            </template>
            <template #inactive>
                <div class="flex flex-col items-start gap-5 self-stretch">
                    <template v-for="discount in bill_discounts.filter(discount => discount.status === 'inactive')">
                        <div class="flex flex-col p-5 items-start gap-4 self-stretch rounded-[5px] border border-solid border-grey-100 bg-white shadow-[0_1px_12px_0_rgba(0,0,0,0.06)] cursor-pointer" @click="openBillDetail(discount)">
                            <div class="flex flex-col items-start self-stretch">
                                <div class="flex justify-between items-start self-stretch">
                                    <div class="flex items-center gap-3">
                                        <span class="text-grey-950 text-xl font-bold">{{ discount.discount_type === 'percentage' ? discount.discount_rate + '%' : 'RM' + discount.discount_rate }} off</span>
                                    </div>
                                    <div class="flex justify-end items-start gap-4">
                                        <div class="flex items-center gap-3">
                                            <DeleteIcon class="text-primary-600 hover:text-primary-800 cursor-pointer size-6" @click.prevent.stop="deleteBill(discount.id)"/>
                                            <EditIcon class="text-primary-900 hover:text-primary-800 cursor-pointer size-6" @click.prevent.stop="openEditBill(discount)"/>
                                        </div>
                                        <Toggle 
                                            :checked="discount.status === 'active'"
                                            @update:checked="updateStatus(discount, $event)"
                                            @click.stop
                                        />
                                    </div>
                                </div>
                                <span class="self-stretch text-grey-700 text-md font-normal">{{ discount.name }}</span>
                            </div>
                            <span class="self-stretch text-grey-950 text-xs font-normal">{{ dayjs(discount.discount_from).format('DD/MM/YYYY') }} - {{ dayjs(discount.discount_to).format('DD/MM/YYYY') }}</span>
                        </div>
                    </template>
                </div>
            </template> -->
        </TabView>
    </div>
    
    <Modal
        :title="'Add Bill Discount'"
        :maxWidth="'lg'"
        :closeable="true"
        :show="isAddBillOpen"
        @close="closeAddBill('close')"
    >
        <AddBillDiscount 
            @isDirty="isDirty=$event"
            @close="closeAddBill"
            @getBillDiscounts="getBillDiscounts"
        />

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeAddBill('stay')"
            @leave="closeAddBill('leave')"
        />
    </Modal>

    <Modal
        :title="'Edit Bill Discount'"
        :maxWidth="'lg'"
        :closeable="true"
        :show="isEditBillOpen"
        @close="closeAddBill('close')"
    >
        <EditBillDiscount 
            @isEdited="isEdited=$event"
            @close="closeAddBill"
            @getBillDiscounts="getBillDiscounts"
            :discount="selectedBill" 
        />

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeAddBill('stay')"
            @leave="closeAddBill('leave')"
        />
    </Modal>

    <Modal
        :title="'Bill Discount Detail'"
        :maxWidth="'lg'"
        :closeable="true"
        :show="isBillDetailOpen"
        @close="closeAddBill('leave')"
    >
        <BillDiscountDetail 
            :discount="selectedBill" 
        />
    </Modal>

    <Modal 
        :show="isDeleteBillOpen" 
        :maxWidth="'2xs'" 
        :closeable="true" 
        :deleteConfirmation="true"
        :deleteUrl="`/configurations/deleteBillDiscount/${selectedBill}`"
        :confirmationTitle="'Delete this bill discount?'"
        :confirmationMessage="'This action is irreversible. Are you sure?'"
        :toastMessage="'Bill discount has been deleted.'"
        @close="closeDeleteBill"
    />
</template>

