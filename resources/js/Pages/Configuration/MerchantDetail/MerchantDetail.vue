<script setup>
import Button from '@/Components/Button.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import { NoImageIcon, PercentageIcon } from '@/Components/Icons/solid';
import Table from '@/Components/Table.vue';
import TextInput from '@/Components/TextInput.vue';
import { useCustomToast, useInputValidator, usePhoneUtils } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    merchant: {
        type: Object,
        default: () => {},
    },
})

const emit = defineEmits(['refetchMerchant']);

const { isValidNumberKey } = useInputValidator();
const { showMessage } = useCustomToast();
const { formatPhone } = usePhoneUtils();

const taxes = ref([]);
const isLoading = ref(false);
const fileInput = ref(null);
const initialTaxes = ref([]);
const codes = ref([]);
const msic_codes = ref([]);
const merchant_detail = ref(props.merchant);
const phonePrefixes = ref([
    { text: '+60', value: '+60'},
]);

const getResults = async () => {
    isLoading.value = true
    try {
        let url = `/configurations/getTax`;

        const response = await axios.get(url);
        taxes.value = response.data.map(tax => ({
            id: tax.id,
            name: tax.name,
            percentage: parseInt(tax.value).toString(),
        }));
        initialTaxes.value = JSON.parse(JSON.stringify(taxes.value));
        taxForm.taxes = taxes.value;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

const getClassificationCodes = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/configurations/getClassificationCodes`);
        codes.value = response.data.map(code => ({
            text: code.code,
            value: code.id,
            description: code.description
        }));
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false;
    }
}

const getMSICCodes = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/configurations/getMSICCodes`);
        msic_codes.value = response.data.map(code => ({
            text: code['Code'],
            value: code['id'],
            description: code['Description']
        }));
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false;
    }
}

const merchantForm = useForm({
    id: merchant_detail.value.id ?? '',
    merchant_image: merchant_detail.value.merchant_image ?? '',
    name: merchant_detail.value.merchant_name ?? '',
    tin_no: merchant_detail.value.tin_no ?? '',
    prefix: phonePrefixes.value[0].value,
    registration_no: merchant_detail.value.registration_no ?? '',
    msic_code: merchant_detail.value.msic_code ?? '',
    phone_no: merchant_detail.value.merchant_contact ? formatPhone(merchant_detail.value.merchant_contact, false, true) : '',
    email_address: merchant_detail.value.email_address ?? '',
    sst_registration_no: merchant_detail.value.sst_registration_no ?? '',
    description: merchant_detail.value.description ?? '',
    classification_code: merchant_detail.value.classification_code ?? '',
})

const addressForm = useForm({
    id: merchant_detail.value.id ?? '',
    address_line1: merchant_detail.value.merchant_address_line1 ?? '',
    address_line2: merchant_detail.value.merchant_address_line2 ?? '',
    postal_code: merchant_detail.value.postal_code ?? '',
    area: merchant_detail.value.area ?? '',
    state: merchant_detail.value.state ?? '',
})

const taxForm = useForm({
    taxes: taxes.value ?? [],
})

const taxesColumn = ([
    { field: 'name', header: 'Tax type', width: '80', sortable: false},
    { field: 'value', header: 'Percentage', width: '20', sortable: false}
])

const handleImageUpload = () => fileInput.value.click();

const getObjectURL = (image) => URL.createObjectURL(image);

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    
    if (file && file.type.startsWith('image/')) { 
        merchantForm.merchant_image = getObjectURL(file);
    }
}

const editThisTax = (tax) => {
    const index = taxForm.taxes.indexOf(tax);
    if(index === -1){
        taxForm.taxes.push(tax);
    }
}

const editDetails = async () => {
    isLoading.value = true;
    try {
        const response = await axios.post(route('configurations.updateMerchant'), merchantForm);
        merchant_detail.value = response.data;
        showMessage({
            severity: 'success',
            summary: 'Merchant details has been edited successfully.'
        });
        emit('refetchMerchant');
        merchantForm.clearErrors();

    } catch(error) {
        if (error.response && error.response.data.errors) {
            merchantForm.errors = error.response.data.errors; 
        }
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const editAddress = async () => {
    isLoading.value = true;
    try {
        const response = await axios.put(route('configurations.editAddress'), addressForm);
        merchant_detail.value = response.data;
        showMessage({
            severity: 'success',
            summary: 'Merchant address has been edited successfully.'
        });
        emit('refetchMerchant');
        addressForm.clearErrors();
        // reassignForm();
    } catch(error) {
        if (error.response && error.response.data.errors) {
            addressForm.errors = error.response.data.errors; 
        }
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const editTaxes = async () => {
    // taxForm.errors = {};
    if (taxForm.taxes.length > 0) {
        isLoading.value = true;
        taxForm.put(route('configurations.editTax'), {
            onSuccess: () => {
                showMessage({
                    severity: 'success',
                    summary: `Taxes edited successfully.`
                });
                getResults();
                taxForm.reset();
            },
            onError: () => {
                // Errors are handled automatically by Inertia
                console.error(taxForm.errors);
            },
            preserveScroll: true,
            preserveState: true,
        });
        isLoading.value = false;
    }
    // taxForm.reset();
};



watch(() => props.merchant, (newValue) => {
    merchant_detail.value = newValue;
});

onMounted(() => {
    getResults();
})
</script>

<template>
    <div class="flex flex-col items-start gap-5">
        <!-- Merchant Detail -->
        <form novalidate @submit="editDetails" class="w-full">
            <div class="flex flex-col p-6 items-start gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
                <div class="flex justify-between items-center self-stretch">
                    <span class="flex flex-col justify-center flex-[1_0_0] text-primary-900 text-md font-medium h-[25px]">Merchant Detail</span>
                    <Button
                        :type="'button'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="merchantForm.processing || isLoading"
                        class="!w-fit"
                        @click="editDetails"
                    >
                        Save changes
                    </Button>
                </div>
                <div class="flex flex-col items-start gap-6 self-stretch">
                    <!-- Change merchant logo -->
                    <div class="flex items-center gap-6">
                        <div class="flex justify-center items-center rounded-[5px] bg-white">
                            <div class="size-[120px] shrink-0 rounded-[5px] bg-white relative group" v-if="merchantForm.merchant_image">
                                <img
                                    :src="merchantForm.merchant_image"
                                    alt="MerchantLogo"
                                    class="object-contain rounded-[5px]"
                                >
                                <div class="size-full shrink-0 items-center justify-center rounded-[5px] opacity-[.68] bg-grey-25 hidden absolute group-hover:flex inset-0">
                                </div>
                                <Button
                                    :variant="'primary'"
                                    :type="'button'"
                                    :size="'md'"
                                    class="!w-fit !h-fit inset-0 absolute hidden justify-self-center self-center group-hover:flex"
                                    @click="merchantForm.merchant_image = ''"
                                >
                                    Remove
                                </Button>
                            </div>
                            <div class="flex size-[120px] justify-center items-center shrink-0 rounded-[5px] bg-grey-100" v-else>
                                <NoImageIcon />
                            </div>
                        </div>
                        <Button
                            :variant="'secondary'"
                            :size="'md'"
                            :type="'button'"
                            @click="handleImageUpload"
                        >
                            Upload Photo
                        </Button>
                        <input 
                            type="file" 
                            ref="fileInput" 
                            @change="handleFileSelect" 
                            accept="image/*" 
                            class="hidden" 
                        />
                    </div>

                    <!-- Merchant form -->
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div class="flex items-start gap-4 self-stretch">
                            <TextInput 
                                :inputName="'merchant_name'"
                                :labelText="'Merchant Name'"
                                :errorMessage="merchantForm.errors.name ? merchantForm.errors.name[0] : ''"
                                :required="true"
                                v-model="merchantForm.name"
                            />

                            <TextInput 
                                :inputName="'tin_no'"
                                :labelText="'TIN No.'"
                                :errorMessage="merchantForm.errors.tin_no ? merchantForm.errors.tin_no[0] : ''"
                                :required="true"
                                v-model="merchantForm.tin_no"
                            />
                        </div>

                        <div class="flex items-start gap-4 self-stretch">
                            <!-- registration no is fixed 12 digits -->
                            <TextInput 
                                :inputName="'registration_no'"
                                :labelText="'Registration No.'"
                                :errorMessage="merchantForm.errors.registration_no ? merchantForm.errors.registration_no[0] : ''"
                                :required="true"
                                :maxlength="12"
                                v-model="merchantForm.registration_no"
                                @keypress="isValidNumberKey($event, false)"
                            />

                            <!-- MSIC code is fixed 5 digits -->
                            <Dropdown 
                                :inputName="'msic_code'"
                                :labelText="'MSIC Code'"
                                :errorMessage="merchantForm.errors.msic_code ? merchantForm.errors.msic_code[0] : ''"
                                :required="true"
                                :filter="true"
                                :withDescription="true"
                                :loading="isLoading"
                                :inputArray="msic_codes"
                                :dataValue="props.merchant.msic_code ?? ''"
                                @click="msic_codes.length > 0 ? '' : getMSICCodes()"
                                v-model="merchantForm.msic_code"
                            >
                                <template #value="slotProps">
                                    <!-- <span>{{ codes.length > 0 ? (codes.find(code => code.id === slotProps.value)) : '-' }}</span> -->
                                    <span class="w-[200px]">{{ msic_codes.length > 0 && !isLoading && props.merchant.msic_code !== merchantForm.msic_code ? `${msic_codes[slotProps.value-2].text} - ${msic_codes[slotProps.value-2].description}` : props.merchant.msic }}</span>
                                </template>
                                <template #description="slotProps">
                                    <span class="text-grey-500 text-sm font-base text-wrap max-w-full">{{ msic_codes[slotProps.index].description }}</span>
                                </template>
                            </Dropdown> 
                        </div>

                        <div class="flex items-start gap-4 self-stretch">
                            <div class="flex flex-col items-start gap-1 w-full">
                                <div class="flex items-start gap-1">
                                    <span class="text-grey-900 text-xs font-medium">Phone No.</span>
                                    <span class="text-primary-600 text-xs font-medium">*</span>
                                </div>
                                <div class="flex items-start gap-2 self-stretch">
                                    <Dropdown 
                                        :inputArray="phonePrefixes"
                                        :dataValue="phonePrefixes[0].value"
                                        v-model="merchantForm.prefix"
                                        class="!w-fit"
                                    />

                                    <TextInput 
                                        :inputName="'phone_no'"
                                        :errorMessage="merchantForm.errors.phone_no ? merchantForm.errors.phone_no[0] : ''"
                                        v-model="merchantForm.phone_no"
                                        @keypress="isValidNumberKey($event, false)"
                                    />
                                </div>
                            </div>

                            <TextInput 
                                :inputName="'email_address'"
                                :labelText="'Email Address'"
                                :errorMessage="merchantForm.errors.email_address ? merchantForm.errors.email_address[0] : ''"
                                :required="true"
                                v-model="merchantForm.email_address"
                            />
                        </div>

                        <div class="flex items-start gap-4 self-stretch">
                            <TextInput 
                                :inputName="'sst_registration_no'"
                                :labelText="'SST Registration No.'"
                                :errorMessage="merchantForm.errors.sst_registration_no ? merchantForm.errors.sst_registration_no[0] : ''"
                                :required="true"
                                v-model="merchantForm.sst_registration_no"
                            />

                            <TextInput 
                                :inputName="'business_activity_desc'"
                                :labelText="'Business Activity Description'"
                                :errorMessage="merchantForm.errors.description ? merchantForm.errors.description[0] : ''"
                                :required="true"
                                v-model="merchantForm.description"
                            />
                        </div>

                        <div class="flex items-start gap-4 self-stretch">
                            <Dropdown 
                                :inputName="'classification_code'"
                                :labelText="'Classification Code'"
                                :errorMessage="merchantForm.errors.classification_code ? merchantForm.errors.classification_code[0] : ''"
                                :required="true"
                                :withDescription="true"
                                :loading="isLoading"
                                :inputArray="codes"
                                :dataValue="props.merchant.code ?? ''"
                                @click="codes.length > 0 ? '' : getClassificationCodes()"
                                v-model="merchantForm.classification_code"
                                class="!w-1/2"
                            >
                                <template #value="slotProps">
                                    <!-- <span>{{ codes.length > 0 ? (codes.find(code => code.id === slotProps.value)) : '-' }}</span> -->
                                    <span>{{ codes.length > 0 && !isLoading && props.merchant.classification_code !== merchantForm.classification_code ? `${codes[slotProps.value-1].text} - ${codes[slotProps.value-1].description}` : props.merchant.code }}</span>
                                </template>
                                <template #description="slotProps">
                                    <span class="text-grey-500 text-sm font-base text-wrap max-w-full">{{ codes[slotProps.index].description }}</span>
                                </template>
                            </Dropdown> 
                        </div>
                    </div>
                </div>
            </div>
        </form>     

        <!-- Merchant Address -->
        <form novalidate @submit="editAddress" class="w-full">
            <div class="flex flex-col p-6 items-start gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
                <div class="flex justify-between items-center self-stretch">
                    <span class="flex flex-col justify-center flex-[1_0_0] text-primary-900 text-md font-medium h-[25px]">Merchant Address</span>
                    <Button
                        :type="'button'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="addressForm.processing || isLoading"
                        class="!w-fit"
                        @click="editAddress"
                    >
                        Save changes
                    </Button>
                </div>
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <div class="flex items-start gap-4 self-stretch">
                        <TextInput 
                            :inputName="'address_line1'"
                            :labelText="'Address Line 1'"
                            :required="true"
                            :errorMessage="addressForm.errors.address_line1 ? addressForm.errors.address_line1[0] : ''"
                            v-model="addressForm.address_line1"
                        />

                        <TextInput 
                            :inputName="'address_line2'"
                            :labelText="'Address Line 2'"
                            :required="true"
                            :errorMessage="addressForm.errors.address_line2 ? addressForm.errors.address_line2[0] : ''"
                            v-model="addressForm.address_line2"
                        />
                    </div>
                    <div class="flex items-start gap-4 self-stretch">
                        <div class="flex items-center gap-4 w-full">
                            <TextInput 
                                :inputName="'postal_code'"
                                :labelText="'Postal Code'"
                                :required="true"
                                :maxlength="5"
                                :errorMessage="addressForm.errors.postal_code ? addressForm.errors.postal_code[0] : ''"
                                v-model="addressForm.postal_code"
                                @keypress="isValidNumberKey($event, false)"
                            />

                            <TextInput 
                                :inputName="'area'"
                                :labelText="'Area'"
                                :required="true"
                                :errorMessage="addressForm.errors.area ? addressForm.errors.area[0] : ''"
                                v-model="addressForm.area"
                            />
                        </div>

                        <TextInput 
                            :inputName="'state'"
                            :labelText="'State'"
                            :required="true"
                            :errorMessage="addressForm.errors.state ? addressForm.errors.state[0] : ''"
                            v-model="addressForm.state"
                        />
                    </div>
                </div>
            </div>
        </form>

        <!-- Tax Settings -->
        <form novalidate @submit="editTaxes" class="w-full" >
            <div class="flex flex-col p-6 items-start gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
                <div class="flex justify-between items-center self-stretch">
                    <span class="flex flex-col justify-center flex-[1_0_0] text-primary-900 text-md font-medium h-[25px]">Tax Settings</span>
                    <Button
                        :type="'button'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="taxForm.processing || isLoading"
                        class="!w-fit"
                        @click="editTaxes"
                    >
                        Save changes
                    </Button>
                </div>

                <Table
                    :variant="'list'"
                    :rows="taxes"
                    :columns="taxesColumn"
                    :paginator="false"
                    v-if="taxForm.taxes.length > 0"
                >
                    <template #empty>
                        <UndetectableIllus class="size-44" />
                        <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                    </template>
                    <template #name="taxes">
                        <TextInput 
                            :inputName="'name_' + taxes.id"
                            :errorMessage="taxForm.errors ? taxForm.errors['name.'+taxes.id] : null"
                            v-model="taxForm.taxes.find((tax) => tax.id === taxes.id).name"
                        />
                        </template>
                    <template #value="taxes">
                        <TextInput 
                            :inputName="'percentage_' + taxes.id"
                            :errorMessage="taxForm.errors ? taxForm.errors['percentage.'+taxes.id] : null"
                            :iconPosition="'right'"
                            v-model="taxForm.taxes.find((tax) => tax.id === taxes.id).percentage"
                        >
                            <template #prefix>
                                <PercentageIcon />
                            </template>
                        </TextInput>
                    </template>
                </Table>
                <template v-else>
                    <div class="flex w-full flex-col items-center justify-center gap-5">
                        <UndetectableIllus />
                        <span class="text-primary-900 text-sm font-medium">No data can be shown yet...</span>
                    </div>
                </template>
            </div>
        </form>
    </div>
</template>