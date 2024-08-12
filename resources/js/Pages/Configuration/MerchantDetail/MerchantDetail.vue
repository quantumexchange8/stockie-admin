<script setup>
import { EditIcon, PlusIcon } from "@/Components/Icons/solid";
import Label from "@/Components/Label.vue";
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import Button from '@/Components/Button.vue';
import Action from "./Partials/Action.vue";

const props = defineProps({
    merchant: Object,
})

const form = useForm({
    merchant_id: props.merchant.id ?? '',
    merchant_name: props.merchant.merchant_name ?? '',
    merchant_contact: props.merchant.merchant_contact ?? '',
    merchant_address: props.merchant.merchant_address ?? '',
    name: '',
    percentage: '',
});

const merchantDetailsModal = ref(false);
const taxModal = ref(false);

const editDetails = () => {
    merchantDetailsModal.value = true;
}

const closeEditDetails = () => {
    merchantDetailsModal.value = false;
}

const addTax = () => {
    taxModal.value = true;
}

const closeTax = () => {
    taxModal.value = false;
}

const formSubmit = () => { 

    form.post(route('configurations.updateMerchant'), {
        preserveScroll: true,
        onSuccess: () => closeEditDetails(),
    })
};

const taxSubmit = () => {
    form.post(route('configurations.addTax'), {
        preserveScroll: true,
        onSuccess: () => closeTax(),
    })
}


const taxes = ref({data: []});
const isLoading = ref(false);

const getResults = async () => {
    isLoading.value = true
    try {
        let url = `/configurations/getTax`;

        const response = await axios.get(url);
        taxes.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

getResults()

</script>

<template>
    <Head title="Configuration" />
    <div class="flex flex-col gap-5">
        <div class="flex flex-col p-6 items-start self-stretch gap-6 rounded-[5px] border border-primary-100">
            <div class="flex items-center justify-between w-full">
                <div class="text-primary-900 text-md font-medium leading-tight">
                    Merchant detail
                </div>
                <div class="cursor-pointer" @click="editDetails">
                    <EditIcon class="text-primary-900" />
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="w-[240px] h-[240px] bg-gray-200">
                
                </div>
                <div class="flex flex-col ">
                    <div class="space-y-1">
                        <Label value="Merchant Name" class="text-gray-600 text-sm font-medium"/>
                        {{ props.merchant ? props.merchant.merchant_name : '-' }}
                    </div>
                    <div class="space-y-1">
                        <Label value="Merchant Contact No." class="text-gray-600 text-sm font-medium"/>
                        {{ props.merchant ? props.merchant.merchant_name : '-' }}
                    </div>
                    <div class="space-y-1">
                        <Label value="Merchant Name" class="text-gray-600 text-sm font-medium"/>
                        {{ props.merchant ? props.merchant.merchant_name : '-' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 flex flex-col gap-6 border border-primary-100 rounded-[5px]">
            <div class="text-primary-900 text-md font-medium">
                Tax Setting
            </div>
            <div class="flex flex-col gap-5">
                <table>
                    <thead class="text-xs font-medium text-gray-700 uppercase bg-primary-50 rounded-[5px]">
                        <tr>
                            <th Tax scope="col" class="p-3 text-left w-2/3">Tax Type</th>
                            <th Percentage scope="col" class="p-3 text-left w-1/3">Percentage</th>
                            <th Percentage scope="col" class="p-3 text-left w-1/3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="tax in taxes">
                            <td class="p-3 text-left">
                                {{ tax.name }}
                            </td>
                            <td class="p-3 flex justify-end">
                                {{ tax.percentage }} %
                            </td>
                            <td class="p-3 text-left">
                                <Action />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div>
                    <Button variant="tertiary" size="lg" @click="addTax">
                        <PlusIcon />
                        Tax Type
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <Modal
        :title="'Edit Merchant Details'"
        :show="merchantDetailsModal" 
        :maxWidth="'lg'" 
        :closeable="true" 
        @close="closeEditDetails"
    >
        <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
            <div class="flex gap-6">
                <div class="w-1/3 h-[372px] bg-gray-200">
                    
                </div>
                <div class="flex flex-col gap-4 w-2/3">
                    <div>
                        <TextInput
                            :inputName="'merchant_name'"
                            :labelText="'Merchant Name'"
                            :placeholder="'eg: Heineken B1F1 Promotion'"
                            :errorMessage="form.errors.merchant_name"
                            v-model="form.merchant_name"
                        />
                    </div>
                    <div>
                        <TextInput
                            :inputName="'merchant_contact'"
                            :labelText="'Merchant Contact No.'"
                            :placeholder="'eg: Heineken B1F1 Promotion'"
                            :errorMessage="form.errors.merchant_contact"
                            v-model="form.merchant_contact"
                        />
                    </div>
                    <div>
                        <TextInput
                            :inputName="'merchant_address'"
                            :labelText="'Merchant Address'"
                            :placeholder="'eg: Heineken B1F1 Promotion'"
                            :errorMessage="form.errors.merchant_address"
                            v-model="form.merchant_address"
                        />
                    </div>
                </div>
            </div>
            <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
                <Button
                    :type="'button'"
                    :variant="'tertiary'"
                    :size="'lg'"
                    @click="closeEditDetails"
                >
                    Cancel
                </Button>
                <Button
                    :size="'lg'"
                >
                    Save Changes
                </Button>
            </div>
        </form>
    </Modal>

    <Modal
        :title="'Add Tax'"
        :show="taxModal" 
        :maxWidth="'xs'" 
        :closeable="true" 
        @close="closeTax"
    >
        <form class="flex flex-col gap-6" novalidate @submit.prevent="taxSubmit">
            <div class="flex flex-col gap-6">
                <div>
                    <TextInput
                        :inputName="'name'"
                        :labelText="'Tax Type'"
                        :errorMessage="form.errors.name"
                        v-model="form.name"
                    />
                </div>
                <div>
                    <TextInput
                        inputType="number"
                        :inputName="'percentage'"
                        :labelText="'Percentage'"
                        :errorMessage="form.errors.percentage"
                        v-model="form.percentage"
                    />
                </div>
            </div>
            <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
                <Button
                    :type="'button'"
                    :variant="'tertiary'"
                    :size="'lg'"
                    @click="closeTax"
                >
                    Cancel
                </Button>
                <Button
                    :size="'lg'"
                >
                    Save Changes
                </Button>
            </div>
        </form>
    </Modal>
</template>
