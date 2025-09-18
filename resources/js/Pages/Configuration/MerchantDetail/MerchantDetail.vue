<script setup>
import Button from '@/Components/Button.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import { CheckIcon, ClipboardIcon, NoImageIcon, PercentageIcon, SearchIdTypeIcon, SearchTaxPayerIcon } from '@/Components/Icons/solid';
import InputError from '@/Components/InputError.vue';
import Table from '@/Components/Table.vue';
import TextInput from '@/Components/TextInput.vue';
import { useCustomToast, usePhoneUtils } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import DateInput from '@/Components/Date.vue';
import dayjs from 'dayjs';
import Modal from '@/Components/Modal.vue';
import Label from '@/Components/Label.vue';
import Tooltip from '@/Components/Tooltip.vue';
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps({
    merchant: {
        type: Object,
        default: () => {},
    },
})

const idTypeArr = [
    { text: 'NRIC', value: 'NRIC'},
    { text: 'PASSPORT', value: 'PASSPORT'},
    { text: 'BRN', value: 'BRN'},
    { text: 'ARMY', value: 'ARMY'},
]

const emit = defineEmits(['refetchMerchant']);

const { showMessage } = useCustomToast();
const { transformPhone, formatPhoneInput } = usePhoneUtils();

const taxes = ref([]);
const isLoading = ref(false);
const fileInput = ref(null);
const initialTaxes = ref([]);
const codes = ref([]);
const msic_codes = ref([]);
const merchant_detail = ref(props.merchant);
const cutOffTime = ref(null);
const phonePrefixes = ref([{ text: '+60', value: '+60'}]);
const isSearchTinOpen = ref(false);
const searchType = ref(null);
const taxpayernameVal = ref(null);
const idType = ref(null);
const idVal = ref(null);
const tinVal = ref(null);
const tinNumIsCopied = ref(false);

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

const getCutOffTime = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/configurations/getCutOffTime`);
        cutOffTime.value = response.data;

        cutOffTimeForm.start_at = dayjs(cutOffTime.value.value).toDate();

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
    id: merchant_detail.value?.id ?? '',
    merchant_image: merchant_detail.value?.merchant_image ?? '',
    image: '',
    name: merchant_detail.value?.merchant_name ?? '',
    tin_no: merchant_detail.value?.tin_no ?? '',
    prefix: phonePrefixes.value[0]?.value,
    registration_no: merchant_detail.value?.registration_no ?? '',
    msic_code: merchant_detail.value?.msic_code ?? '',
    phone_temp: merchant_detail.value?.merchant_contact ? merchant_detail.value?.merchant_contact.slice(2) : '',
    phone_no: merchant_detail.value?.merchant_contact,
    email_address: merchant_detail.value?.email_address ?? '',
    sst_registration_no: merchant_detail.value?.sst_registration_no ?? '',
    description: merchant_detail.value?.description ?? '',
    classification_code: merchant_detail.value?.classification_code ?? '',
    irbm_client_id: merchant_detail.value?.irbm_client_id ?? '',
    irbm_client_key: merchant_detail.value?.irbm_client_key ?? '',
})

const addressForm = useForm({
    id: merchant_detail.value?.id ?? '',
    address_line1: merchant_detail.value?.merchant_address_line1 ?? '',
    address_line2: merchant_detail.value?.merchant_address_line2 ?? '',
    postal_code: merchant_detail.value?.postal_code ?? '',
    area: merchant_detail.value?.area ?? '',
    state: merchant_detail.value?.state ?? '',
})

const cutOffTimeForm = useForm({
    start_at: '',
})

const taxForm = useForm({
    taxes: taxes.value ?? [],
})

const taxesColumn = ([
    { field: 'name', header: wTrans('public.config.tax_type'), width: '70', sortable: false},
    { field: 'value', header: wTrans('public.config.percentage'), width: '30', sortable: false}
])

const handleImageUpload = () => fileInput.value.click();

const getObjectURL = (image) => URL.createObjectURL(image);

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    
    if (file && file.type.startsWith('image/')) { 
        merchantForm.image = file;
        merchantForm.merchant_image = file;
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
    merchantForm.phone_no = merchantForm.phone_temp ? transformPhone(merchantForm.phone_temp, true) : '';

    try {
        const response = await axios.post(route('configurations.updateMerchant'), merchantForm, { headers: { 'Content-Type': 'multipart/form-data' } });
        merchant_detail.value = response.data;
        showMessage({
            severity: 'success',
            summary: wTrans('public.toast.edit_merchant_details_success')
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
            summary: wTrans('public.toast.edit_merchant_address_success')
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

const editCutOffTime = async () => {
    isLoading.value = true;
    try {
        cutOffTimeForm.start_at = dayjs(cutOffTimeForm.start_at).format('H.mm');

        const response = await axios.put(route('configurations.editCutOffTime'), cutOffTimeForm);
        cutOffTime.value = response.data;

        cutOffTimeForm.start_at = dayjs(cutOffTime.value.value).toDate();
        showMessage({
            severity: 'success',
            summary: wTrans('public.toast.edit_cut_off_time_success')
        });
        cutOffTimeForm.clearErrors();

    } catch(error) {
        if (error.response && error.response.data.errors) {
            cutOffTimeForm.errors = error.response.data.errors; 
        }
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const openSearchTinModal = () => {
    isSearchTinOpen.value = true
}

const resetTinSearch = () => {
    setTimeout(() => {
        searchType.value = null;
        taxpayernameVal.value = null;
        idType.value = null;
        idVal.value = null;
        tinVal.value = null;
    }, 500);
};

const closeSearchTinModal = () => {
    isSearchTinOpen.value = false
    resetTinSearch();
}

const searchTin = async () => {
    isLoading.value = true;
    try {
        const response = await axios.post('/configurations/searchTIN', {
            searchType: searchType.value,
            taxpayerName: taxpayernameVal.value,  
            idType: idType.value,
            TINValue: idVal.value,
        })

        tinVal.value = response.data;

    } catch (error) {
        console.error('error: ', error);
    }
};

const editTaxes = async () => {
    // taxForm.errors = {};
    if (taxForm.taxes.length > 0) {
        isLoading.value = true;
        taxForm.put(route('configurations.editTax'), {
            onSuccess: () => {
                showMessage({
                    severity: 'success',
                    summary: wTrans('public.toast.edit_taxes_success')
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

const removeImage = () => {
    merchantForm.image = '';
    merchantForm.merchant_image = '';
};

const copyToClipboard = () => {
    // Write to clipboard
    navigator.clipboard.writeText(tinVal.value);

    tinNumIsCopied.value = true;

    showMessage({
        severity: 'success',
        summary: wTrans('public.toast.copy_tin_number_success')
    });
}

watch(() => props.merchant, (newValue) => {
    merchant_detail.value = newValue;
});

onMounted(() => {
    getResults();
    getCutOffTime();
})
</script>

<template>
    <div class="flex flex-col items-start gap-5">
        <!-- Merchant Detail -->
        <form novalidate @submit="editDetails" class="w-full">
            <div class="flex flex-col p-6 items-start gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
                <div class="flex justify-between items-center self-stretch">
                    <span class="flex flex-col justify-center flex-[1_0_0] text-primary-900 text-md font-medium h-[25px]">{{ $t('public.config.merchant_detail') }}</span>
                    <Button
                        :type="'button'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="merchantForm.processing || isLoading"
                        class="!w-fit"
                        @click="editDetails"
                    >
                        {{ $t('public.action.save_changes') }}
                    </Button>
                </div>
                <div class="flex flex-col items-start gap-6 self-stretch">
                    <!-- Change merchant logo -->
                    <div class="flex items-center gap-6">
                        <div class="flex flex-col justify-center items-start rounded-[5px] bg-white">
                            <div class="size-[120px] shrink-0 rounded-[5px] bg-white relative group">
                                <img 
                                    v-if="merchantForm.image" 
                                    :src="getObjectURL(merchantForm.image)"
                                    alt="MerchantLogo"
                                    class="object-contain rounded-[5px]"
                                >
                                <img
                                    v-else-if="merchantForm.merchant_image" 
                                    :src="merchantForm.merchant_image"
                                    alt="NewMerchantLogo"
                                    class="object-contain rounded-[5px]"
                                >
                                <div v-else class="flex size-[120px] justify-center items-center shrink-0 rounded-[5px] bg-grey-100">
                                    <NoImageIcon />
                                </div>
                                <div v-if="merchantForm.merchant_image || merchantForm.image" class="size-full shrink-0 items-center justify-center rounded-[5px] opacity-[.68] bg-grey-25 hidden absolute group-hover:flex inset-0">
                                </div>
                                <Button
                                    v-if="merchantForm.merchant_image || merchantForm.image"
                                    :variant="'primary'"
                                    :type="'button'"
                                    :size="'md'"
                                    class="!w-fit !h-fit inset-0 absolute hidden justify-self-center self-center group-hover:flex"
                                    @click="removeImage"
                                >
                                    {{ $t('public.action.remove') }}
                                </Button>
                            </div>
                            <InputError class="text-nowrap" :message="merchantForm.errors.merchant_image ? merchantForm.errors.merchant_image[0] : ''" />
                        </div>
                        <Button
                            :variant="'secondary'"
                            :size="'md'"
                            :type="'button'"
                            @click="handleImageUpload"
                        >
                            {{ $t('public.action.upload_photo') }}
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
                                :labelText="$t('public.config.merchant_name')"
                                :errorMessage="merchantForm.errors.name ? merchantForm.errors.name[0] : ''"
                                :required="true"
                                v-model="merchantForm.name"
                            />

                            <TextInput 
                                :inputName="'tin_no'"
                                :labelText="$t('public.config.tin_no')"
                                :errorMessage="merchantForm.errors.tin_no ? merchantForm.errors.tin_no[0] : ''"
                                :required="true"
                                v-model="merchantForm.tin_no"
                            />
                        </div>

                        <div class="flex items-start gap-4 self-stretch">
                            <!-- registration no is fixed 12 digits -->
                            <TextInput 
                                :inputName="'registration_no'"
                                :inputType="'number'"
                                :labelText="$t('public.config.registration_no')"
                                :errorMessage="merchantForm.errors.registration_no ? merchantForm.errors.registration_no[0] : ''"
                                :required="true"
                                :maxlength="12"
                                v-model="merchantForm.registration_no"
                            />

                            <!-- MSIC code is fixed 5 digits -->
                            <Dropdown 
                                :inputName="'msic_code'"
                                :labelText="$t('public.config.msic_code')"
                                :errorMessage="merchantForm.errors.msic_code ? merchantForm.errors.msic_code[0] : ''"
                                :required="true"
                                :filter="true"
                                :withDescription="true"
                                :loading="isLoading"
                                :inputArray="msic_codes"
                                :dataValue="props.merchant?.msic_code ?? ''"
                                @click="msic_codes.length > 0 ? '' : getMSICCodes()"
                                v-model="merchantForm.msic_code"
                            >
                                <template #value="slotProps">
                                    <!-- <span>{{ codes.length > 0 ? (codes.find(code => code.id === slotProps.value)) : '-' }}</span> -->
                                    <span class="w-[200px]">{{ msic_codes.length > 0 && !isLoading && props.merchant?.msic_code !== merchantForm.msic_code ? `${msic_codes[slotProps.value-2].text} - ${msic_codes[slotProps.value-2].description}` : props.merchant?.msic }}</span>
                                </template>
                                <template #description="slotProps">
                                    <span class="text-grey-500 text-sm font-base text-wrap max-w-full">{{ msic_codes[slotProps.index].description }}</span>
                                </template>
                            </Dropdown> 
                        </div>

                        <div class="flex items-start gap-4 self-stretch">
                            <div class="flex flex-col items-start gap-1 w-full">
                                <div class="flex items-start gap-1">
                                    <span class="text-grey-900 text-xs font-medium">{{ $t('public.field.phone_no') }}</span>
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
                                        :inputType="'number'"
                                        placeholder="12 345 1234"
                                        :errorMessage="merchantForm.errors.phone_no ? merchantForm.errors.phone_no[0] : ''"
                                        v-model="merchantForm.phone_temp"
                                        @input="formatPhoneInput($event, false)"
                                    />
                                </div>
                            </div>

                            <TextInput 
                                :inputName="'email_address'"
                                :labelText="$t('public.field.email')"
                                :errorMessage="merchantForm.errors.email_address ? merchantForm.errors.email_address[0] : ''"
                                :required="true"
                                v-model="merchantForm.email_address"
                            />
                        </div>

                        <div class="flex items-start gap-4 self-stretch">
                            <TextInput 
                                :inputName="'sst_registration_no'"
                                :labelText="$t('public.config.sst_registration_no')"
                                :errorMessage="merchantForm.errors.sst_registration_no ? merchantForm.errors.sst_registration_no[0] : ''"
                                :required="true"
                                v-model="merchantForm.sst_registration_no"
                            />

                            <TextInput 
                                :inputName="'business_activity_desc'"
                                :labelText="$t('public.config.business_activity_desc')"
                                :errorMessage="merchantForm.errors.description ? merchantForm.errors.description[0] : ''"
                                :required="true"
                                v-model="merchantForm.description"
                            />
                        </div>

                        <div class="flex items-start gap-4 self-stretch">
                            <TextInput 
                                :inputName="'irbm_client_id'"
                                :labelText="$t('public.config.irbm_client_id')"
                                :errorMessage="merchantForm.errors.irbm_client_id ? merchantForm.errors.irbm_client_id[0] : ''"
                                :required="true"
                                v-model="merchantForm.irbm_client_id"
                            />

                            <TextInput 
                                :inputName="'irbm_client_key'"
                                :labelText="$t('public.config.irbm_client_key')"
                                :errorMessage="merchantForm.errors.irbm_client_key ? merchantForm.errors.irbm_client_key[0] : ''"
                                :required="true"
                                v-model="merchantForm.irbm_client_key"
                                :inputType="'password'"
                            />
                        </div>

                        <div class="flex items-start gap-4 self-stretch">
                            <Dropdown 
                                :inputName="'classification_code'"
                                :labelText="$t('public.config.classification_code')"
                                :errorMessage="merchantForm.errors.classification_code ? merchantForm.errors.classification_code[0] : ''"
                                :required="true"
                                :withDescription="true"
                                :loading="isLoading"
                                :inputArray="codes"
                                :dataValue="props.merchant?.code ?? ''"
                                @click="codes.length > 0 ? '' : getClassificationCodes()"
                                v-model="merchantForm.classification_code"
                                class="!w-1/2"
                            >
                                <template #value="slotProps">
                                    <!-- <span>{{ codes.length > 0 ? (codes.find(code => code.id === slotProps.value)) : '-' }}</span> -->
                                    <span>{{ codes.length > 0 && !isLoading && props.merchant?.classification_code !== merchantForm.classification_code ? `${codes[slotProps.value-1].text} - ${codes[slotProps.value-1].description}` : props.merchant?.code }}</span>
                                </template>
                                <template #description="slotProps">
                                    <span class="text-grey-500 text-sm font-base text-wrap max-w-full">{{ codes[slotProps.index].description }}</span>
                                </template>
                            </Dropdown> 
                            
                            <div class="flex flex-col items-start">
                                <!-- <TextInput value="Search TIN" /> -->
                                <Label :class="'mb-1 text-xs !font-medium text-grey-900'">{{ $t('public.config.search_tin') }}</Label>
                                <Button @click="openSearchTinModal" type="button" >{{ $t('public.search') }}</Button>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </form>     

        <!-- Merchant Address -->
        <form novalidate @submit="editAddress" class="w-full">
            <div class="flex flex-col p-6 items-start gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
                <div class="flex justify-between items-center self-stretch">
                    <span class="flex flex-col justify-center flex-[1_0_0] text-primary-900 text-md font-medium h-[25px]">{{ $t('public.config.merchant_address') }}</span>
                    <Button
                        :type="'button'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="addressForm.processing || isLoading"
                        class="!w-fit"
                        @click="editAddress"
                    >
                        {{ $t('public.action.save_changes') }}
                    </Button>
                </div>
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <div class="flex items-start gap-4 self-stretch">
                        <TextInput 
                            :inputName="'address_line1'"
                            :labelText="$t('public.config.address_line_1')"
                            :required="true"
                            :errorMessage="addressForm.errors.address_line1 ? addressForm.errors.address_line1[0] : ''"
                            v-model="addressForm.address_line1"
                        />

                        <TextInput 
                            :inputName="'address_line2'"
                            :labelText="$t('public.config.address_line_2')"
                            :required="true"
                            :errorMessage="addressForm.errors.address_line2 ? addressForm.errors.address_line2[0] : ''"
                            v-model="addressForm.address_line2"
                        />
                    </div>
                    <div class="flex items-start gap-4 self-stretch">
                        <div class="flex items-center gap-4 w-full">
                            <TextInput 
                                :inputName="'postal_code'"
                                :inputType="'number'"
                                :labelText="$t('public.config.postal_code')"
                                :required="true"
                                :maxlength="5"
                                :errorMessage="addressForm.errors.postal_code ? addressForm.errors.postal_code[0] : ''"
                                v-model="addressForm.postal_code"
                            />

                            <TextInput 
                                :inputName="'area'"
                                :labelText="$t('public.config.area')"
                                :required="true"
                                :errorMessage="addressForm.errors.area ? addressForm.errors.area[0] : ''"
                                v-model="addressForm.area"
                            />
                        </div>

                        <TextInput 
                            :inputName="'state'"
                            :labelText="$t('public.config.state')"
                            :required="true"
                            :errorMessage="addressForm.errors.state ? addressForm.errors.state[0] : ''"
                            v-model="addressForm.state"
                        />
                    </div>
                </div>
            </div>
        </form>

        <!-- Cut Off Time (All Report) -->
        <form novalidate @submit="editCutOffTime" class="w-full">
            <div class="flex flex-col p-6 items-start gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
                <div class="flex justify-between items-center self-stretch">
                    <span class="flex flex-col justify-center flex-[1_0_0] text-primary-900 text-md font-medium h-[25px]">{{ $t('public.config.cutoff_time') }}</span>
                    <Button
                        :type="'button'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="cutOffTimeForm.processing || isLoading"
                        class="!w-fit"
                        @click="editCutOffTime"
                    >
                        {{ $t('public.action.save_changes') }}
                    </Button>
                </div>
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <div class="flex items-start gap-4 self-stretch">
                        <DateInput
                            timeOnly
                            required
                            :inputName="'start_at'"
                            :labelText="$t('public.field.start_at')"
                            :placeholder="'09:00'"
                            class="!w-1/2"
                            :errorMessage="cutOffTimeForm.errors.start_at ? cutOffTimeForm.errors.start_at[0] : ''"
                            v-model="cutOffTimeForm.start_at"
                        />
                    </div>
                </div>
            </div>
        </form>

        <!-- Tax Settings -->
        <form novalidate @submit="editTaxes" class="w-full" >
            <div class="flex flex-col p-6 items-start gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
                <div class="flex justify-between items-center self-stretch">
                    <span class="flex flex-col justify-center flex-[1_0_0] text-primary-900 text-md font-medium h-[25px]">{{ $t('public.config.tax_settings') }}</span>
                    <Button
                        :type="'button'"
                        :variant="'primary'"
                        :size="'lg'"
                        :disabled="taxForm.processing || isLoading"
                        class="!w-fit"
                        @click="editTaxes"
                    >
                        {{ $t('public.action.save_changes') }}
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
                        <span class="text-primary-900 text-sm font-medium">{{ $t('public.empty.no_data') }}</span>
                    </template>
                    <template #name="taxes">
                        <!-- <TextInput 
                            :inputName="'name_' + taxes.id"
                            :errorMessage="taxForm.errors ? taxForm.errors['name.'+taxes.id] : null"
                            v-model="taxForm.taxes.find((tax) => tax.id === taxes.id).name"
                        /> -->
                        <p class="text-sm font-medium text-grey-900">{{ taxes.name === 'Service Tax' ? $t('public.service_tax') : taxes.name }}</p>
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
                        <span class="text-primary-900 text-sm font-medium">{{ $t('public.empty.no_data') }}</span>
                    </div>
                </template>
            </div>
        </form>

        <Modal
            maxWidth="sm"
            closeable
            :title="$t('public.config.select_tin')"
            :show="isSearchTinOpen"
            @close="closeSearchTinModal"
        >
            <div v-if="!tinVal">
                <div v-if="!searchType" class="flex flex-col md:flex-row gap-5 items-center justify-center">
                    <div  class="w-full border border-gray-400 rounded-md shadow-sm flex flex-col gap-3 items-center p-3 hover:bg-gray-100 cursor-pointer" @click="searchType = 'taxpayerName'" >
                        <SearchTaxPayerIcon class="w-10 h-10" />
                        <div>{{ $t('public.config.search_taxpayer_name') }}</div>
                    </div>
                    <div class="w-full border border-gray-400 rounded-md shadow-sm flex flex-col gap-3 items-center p-3 hover:bg-gray-100 cursor-pointer" @click="searchType = 'idType'">
                        <SearchIdTypeIcon class="w-10 h-10" />
                        <div>{{ $t('public.config.search_by_id_type') }}</div>
                    </div>
                </div>
                <div class="flex flex-col gap-5" v-if="searchType === 'taxpayerName'">
                    <TextInput 
                        :inputName="'taxpayernameVal'"
                        :labelText="$t('public.config.taxpayer_name')"
                        :required="true"
                        v-model="taxpayernameVal"
                    />

                    <div class="flex items-center gap-3">
                        <Button size="md" variant="secondary" @click="resetTinSearch" >{{ $t('public.action.back') }}</Button>
                        <Button size="md" @click="searchTin">{{ $t('public.search') }}</Button>
                    </div>
                    
                </div>

                <div class="flex flex-col gap-5" v-if="searchType === 'idType'">
                    <div class="flex flex-col gap-3">
                        <Dropdown 
                            :inputName="'idType'"
                            :labelText="$t('public.config.id_type')"
                            :required="true"
                            :filter="false"
                            :inputArray="idTypeArr"
                            v-model="idType"
                        />
                        <TextInput 
                            :inputName="'idVal'"
                            :labelText="$t('public.config.id_value')"
                            :required="true"
                            :disabled="!idType"
                            v-model="idVal"
                        />
                    </div>
                    <div class="flex items-center gap-3">
                        <Button size="md" variant="secondary" @click="resetTinSearch" >{{ $t('public.action.back') }}</Button>
                        <Button size="md" @click="searchTin">{{ $t('public.search') }}<</Button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-5 justify-center items-center" v-else>
                <div class="flex justify-end gap-x-2 items-center" >
                    <p class="text-grey-900 text-base font-bold text-center truncate w-32">
                        {{ tinVal ?? '-' }}
                    </p>
                </div>
                <Tooltip
                    :message="$t('public.config.copy_tin_number')"
                    :position="'top'"
                    class="w-full"
                >
                    <Button 
                        size="md" 
                        variant="secondary" 
                        class="w-full"
                        @click="copyToClipboard" 
                    >
                        {{ tinNumIsCopied ? $t('public.config.copied') : $t('public.config.copy') }}
                        <span class="pl-1" v-if="tinNumIsCopied"><CheckIcon class="shrink-0 text-green-900" /></span>
                    </Button>
                </Tooltip>
            </div>
        </Modal>
    </div>
</template>