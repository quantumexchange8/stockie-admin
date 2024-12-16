<script setup>
import Button from '@/Components/Button.vue';
import DateInput from '@/Components/Date.vue';
import { DeleteIcon, PercentageIcon, PlusIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { transactionFormat, useCustomToast, useInputValidator } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import SelectProduct from './SelectProduct.vue';
import dayjs from 'dayjs';
import { CancelIllus } from '@/Components/Icons/illus';

const isSelectProductOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const deletingProduct = ref(null);
const selectedType = ref('percentage');
const selectedProducts = ref([]);
const isLoading = ref(false);
const invalidDates = ref([]);

const emit = defineEmits(["close", "discountDetails", 'isDirty']);
const { isValidNumberKey } = useInputValidator();
const { formatAmount } = transactionFormat();
const { showMessage } = useCustomToast();

const form = useForm({
    discount_name: '',
    discount_type: 'percentage',
    discount_rate: '',
    discount_period: '',
    discount_product: '',
    discount_from: '',
    discount_to: '',
})

const setType = (type) => {
    selectedType.value = type;
    form.discount_type = type;
}

const openSelectProduct = () => {
    isSelectProductOpen.value = true;
}

const closeSelectProduct = (discountProduct) => {
    isSelectProductOpen.value = false;
    selectedProducts.value = discountProduct;
    form.discount_product = selectedProducts.value;
}

const cancelSelectProduct = () => {
    isSelectProductOpen.value = false;
}

const openDeleteModal = (productId) => {
    isDeleteModalOpen.value = true;
    deletingProduct.value = productId;
}

const hideDeleteModal = () => {
    isDeleteModalOpen.value = false;
}

const removeSelectProduct = (productId) => {
    isDeleteModalOpen.value = false;
    selectedProducts.value = selectedProducts.value.filter(product => product.id !== productId);
    dateFilter(selectedProducts.value);
}

const unsaved = (status) => {
    emit('close', status);
    
}

const submit = () => {
    // form.discount_product = selectedProducts.value;
    if(form.discount_period !== ''){
        let startDate, endDate;
        startDate = dayjs(form.discount_period[0]);
        if(form.discount_period[1] != null) {
            endDate = dayjs(form.discount_period[1]);
        } else {
            endDate = startDate;
        }

        form.discount_from = startDate.startOf('day').format('YYYY-MM-DD HH:mm:ss');
        form.discount_to = endDate.endOf('day').format('YYYY-MM-DD HH:mm:ss');

        if (startDate.isAfter(endDate)){
            let temp = form.discount_from;
            form.discount_from = form.discount_to;
            form.discount_to = temp;
        }
    }

    form.post(route('configurations.createDiscount'), {
        preserveScroll: true,
        onSuccess: () => {
            unsaved('leave');
            emit('discountDetails');
            setTimeout(() => {
                showMessage({
                    severity: 'success',
                    summary: 'Discount has been added successfully.'
                })
            }, 200)
        }
    })
}

function getAllDatesBetween(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const dates = [];

    while (start <= end) {
        dates.push(new Date(start)); 
        start.setDate(start.getDate() + 1); 
    }

    return dates;
}

const dateFilter = (selectedProducts) => {
    invalidDates.value = selectedProducts.map(product => product.discount_items.map(item => 
                                                        getAllDatesBetween(item.discount.discount_from, item.discount.discount_to)
                                                    )
                                            )
                                            .flat(2);

};

const isFormValid = computed(() => {
    return ['discount_name', 'discount_rate', 'discount_period'].every(field => form[field]) && form.discount_product.length > 0;
})

// watch(() => selectedProducts.value, (newValue) => {
//     selectedProducts.value = newValue;
// }, { immediate: true });

watch(() => invalidDates.value, (newValue) => {
    invalidDates.value = newValue;
}, { immediate: true });

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form novalidate @submit.prevent="submit">
        <div class="flex items-start gap-6 self-stretch pb-6">
            <div class="flex flex-col p-4 h-[385px] justify-center items-center gap-4 flex-[1_0_0] bg-grey-25 divide-y divide-grey-100 overflow-auto scrollbar-webkit">
                <div class="flex justify-end items-start gap-4" v-if="selectedProducts.length === 0">
                    <Button
                        type="button"
                        size="lg"
                        class="!w-fit"
                        @click="openSelectProduct"
                    >
                        Select Product
                    </Button>
                </div>
                <template v-else>
                    <div class="flex flex-col h-[300px] items-start flex-[1_0_0] self-stretch divide-y divide-grey-100 overflow-auto scrollbar-webkit">
                        <div class="flex py-3 items-center gap-3 self-stretch" v-for="products in selectedProducts">
                            <!-- <div class="size-11 bg-primary-200"></div> -->
                            <img 
                                :src="products.image ? products.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                alt=""
                                class="size-11 object-contain"
                            >
                            <div class="flex flex-col justify-center items-start flex-[1_0_0]">
                                <span class="self-stretch overflow-hidden text-grey-900 text-ellipsis whitespace-nowrap text-sm font-medium">{{ products.product_name }}</span>
                                <span class="line-clamp-1 text-grey-900 text-ellipsis text-base font-bold">RM {{ formatAmount(products.price) }}</span>
                            </div>
                            <DeleteIcon
                                class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                                @click="openDeleteModal(products.id)"
                            />
                        </div>
                    </div>
                    <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
                        <Button
                            :type="'button'"
                            :variant="'secondary'"
                            :size="'lg'"
                            :iconPosition="'left'"
                            @click="openSelectProduct"
                        >
                            <template #icon>
                                <PlusIcon />
                            </template>
                                Add More
                        </Button>
                    </div>
                </template>
            </div>

            <div class="flex flex-col items-start gap-6 flex-[1_0_0]">
                <TextInput 
                    :inputName="'discount_name'"
                    :labelText="'Discount Name'"
                    :placeholder="'e.g. Halloween Special Discount'"
                    :errorMessage="form.errors?.discount_name"
                    v-model="form.discount_name"
                />

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <span class="text-grey-900 text-xs font-medium">Discount Rate</span>
                    <div class="flex items-start gap-2 self-stretch">
                        <div class="flex items-center gap-2">
                            <div class="flex flex-col py-[9px] px-3.5 justify-center items-center gap-2.5 rounded-[5px]"
                                :class="{
                                    'bg-primary-50': selectedType === 'percentage',
                                    'border border-solid border-grey-200': selectedType !== 'percentage',
                                }"
                                @click="setType('percentage')"
                            >
                                <PercentageIcon class="size-5"/>
                            </div>

                            <div class="flex flex-col px-3 py-2 justify-center items-center gap-2.5 rounded-[5px]"
                                :class="{   
                                    'bg-primary-50': selectedType === 'fixed',
                                    'border border-solid border-grey-200': selectedType !== 'fixed',
                                }"
                                @click="setType('fixed')"
                            >
                                <span class="text-primary-900 text-base font-normal">
                                    RM
                                </span>
                            </div>

                            <TextInput 
                                :inputName="'discount_rate'"
                                :placeholder="'0'"
                                :iconPosition="'right'"
                                :errorMessage="form.errors?.discount_rate"
                                v-model="form.discount_rate"
                                class="w-full [&>div:nth-child(1)>input]:text-left [&>div:nth-child(1)>input]:pl-4 [&>div:nth-child(1)>input]:mb-0"
                                @keypress="isValidNumberKey($event, true)"
                                v-if="selectedType === 'percentage'"
                            >
                                <template #prefix>
                                    <span class="text-grey-700 text-base font-normal">%</span>
                                </template>
                            </TextInput>

                            <TextInput 
                                :inputName="'discount_rate'"
                                :placeholder="'0'"
                                :iconPosition="'left'"
                                v-model="form.discount_rate"
                                class="w-full [&>div:nth-child(1)>input]:text-right [&>div:nth-child(1)>input]:pr-4 [&>div:nth-child(1)>input]:mb-0"
                                @keypress="isValidNumberKey($event, true)"
                                v-if="selectedType === 'fixed'"
                            >
                                <template #prefix>
                                    <span class="text-grey-700 text-base font-normal">RM</span>
                                </template>
                            </TextInput>
                        </div>
                    </div>
                </div>

                <DateInput 
                    :inputName="'discount_period'"
                    :labelText="'Discount Period'"
                    :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                    :errorMessage="form.errors?.discount_period"
                    :range="true"
                    :disabledDates="invalidDates"
                    :disabled="isLoading"
                    :minDate="new Date()"
                    v-model="form.discount_period"
                />
            </div>
        </div>

        <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="unsaved('close')"
            >
                Cancel
            </Button>
            <Button
                :type="'submit'"
                :variant="'primary'"
                :size="'lg'"
                :disabled="form.processing || !isFormValid"
            >
                Done
            </Button>
        </div>
    </form>

    <Modal
        maxWidth="sm"
        closeable
        title="Select Product"
        :show="isSelectProductOpen"
        @close="cancelSelectProduct"
    >
        <SelectProduct 
            @closeSelectProduct="closeSelectProduct"
            @cancelSelectProduct="cancelSelectProduct"
            @date-filter="dateFilter"
            :action="'add'"
            :selectedProducts="selectedProducts"
            :dateFilter="form.discount_period ? form.discount_period : []"
        />
    </Modal>

    <Modal
        :maxWidth="'2xs'"
        :closeable="false"
        :withHeader="false"
        :show="isDeleteModalOpen"
        @close="hideDeleteModal"
    >
        <div class="flex flex-col items-center gap-9 rounded-[5px] border border-solid border-primary-200 bg-white m-[-24px]">
            <div class="w-full flex flex-col items-center gap-[10px] bg-primary-50">
                <div class="w-full flex pt-2 justify-center items-center">
                    <CancelIllus />
                </div>
            </div>
            <div class="flex flex-col px-6 items-center gap-1 self-stretch">
                <span class="self-stretch text-primary-900 text-center text-lg font-medium ">Remove Product</span>
                <span class="self-stretch text-grey-900 text-center text-base font-medium">Removing this product means it will no longer eligible for any discount, are you sure?</span>
            </div>

            <div class="flex px-6 pb-6 justify-center items-start gap-3 self-stretch">
                <Button
                    variant="tertiary"
                    size="lg"
                    type="button"
                    @click="hideDeleteModal"
                >
                    Cancel
                </Button>
                <Button
                    variant="red"
                    size="lg"
                    type="submit"
                    @click="removeSelectProduct(deletingProduct)"
                >
                    Remove
                </Button>
            </div>
        </div>
    </Modal>
    <Modal
        :unsaved="true"
        :maxWidth="'2xs'"
        :withHeader="false"
        :show="isUnsavedChangesOpen"
        @close="unsaved('stay')"
        @leave="unsaved('leave')"
    />
</template>

