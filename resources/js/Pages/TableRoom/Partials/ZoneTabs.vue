<script setup>
import Card from 'primevue/card';
import Button from '@/Components/Button.vue';
import { DeleteIcon, EditIcon } from '@/Components/Icons/solid';
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { useCustomToast } from '@/Composables';

const props = defineProps({
    zones: {
        type: Array,
        required: true
    },
    activeTab: {
        type: Number,
    }
});
const emit = defineEmits(['close']);
const zonesDetail = ref();
const editModal = ref(false);
const deleteModal = ref(false);
const tabs = ref([]);
const { showMessage } = useCustomToast();

watch(() => props.zones, (newValue) => {
    zonesDetail.value = newValue ? newValue : {};
}, { immediate: true }
)


const populateTabs = () => {
tabs.value = ['All'];
  for (const zone of props.zones) {
    if (zone.text) { 
      tabs.value.push(zone.text);
    }
  }
};

watch(() => props.zones, populateTabs, {immediate: true});

const form = useForm({
    id: '',
    type: '',
    table_no: '',
    seat: '',
    zone_id: '',
    errors: {}
})

const formatLabel = (type) => {
  if (!type) return '';
  return `${capitalize(type)} No.`;
};

const modalHeader = (type) => {
    if (!type) return '';
    return `Edit ${capitalize(type)}`;
}

const capitalize = (str) => {
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
};

const openEditModal = (table) => {
    form.id = table.id,
    form.type = table.type,
    form.table_no = table.table_no,
    form.seat = table.seat.toString(),
    form.zone_id = table.zone_id,
    editModal.value = true
};

const openDeleteModal = (type, id) => {
    form.type = type;
    form.id = id;
    deleteModal.value = true;
}

const closeModal = () => {
    editModal.value = false;
    deleteModal.value = false;
}


const isNumber = (e, withDot = true) => {
    const { key, target: { value } } = e;
    
    if (/^\d$/.test(key)) return;

    if (withDot && key === '.' && /\d/.test(value) && !value.includes('.')) return;
    
    e.preventDefault();
};

const formSubmit = () => {
    if(editModal){
        form.post(route('tableroom.edit-table'), {
            preserveScroll: true,
            preserveState: 'errors',
            onSuccess: () => {
                form.reset();
                emit('close');
                setTimeout(() => {
                    showMessage({ 
                        severity: 'success',
                        summary: 'Changes saved.',
                    });
                }, 200)
            },
            onError: (errors) => {
                console.error('Form submission error, ', errors);
            }
    })}
};
const requiredFields = ['table_no', 'seat', 'zone_id'];

const isFormValid = computed(() => {
    const valid = requiredFields.every(field => Boolean(form[field]));
    return valid;
});

</script>

<template>
    <div class="flex flex-col">
        <div v-for="zone in zones" :key="zone.value">
            <div class="grid xl:grid-cols-4 md:grid-cols-3 sm:grid-cols-2 gap-x-6">
                <div v-for="table in zone.tables" :key="table.id">
                    <div v-if="table.zone_id === activeTab">
                        <Card 
                            style="overflow: hidden;" 
                            class="border rounded-[5px] gap-6 mb-6">
                                <template #title>
                                    <div class="flex flex-col text-center items-center p-6 gap-2">
                                        <div class="text-xl text-primary-900 font-bold">{{ table.table_no }}</div>
                                        <div class="text-base text-grey-900 font-medium">{{ table.seat }} seats</div>
                                    </div>
                                </template>
                                <template #footer>
                                    <div class="flex flex-nowrap">
                                        <Button 
                                            :type="'button'" 
                                            :size="'md'"
                                            @click="openEditModal(table)"
                                            class="!bg-primary-100 hover:!bg-primary-200 rounded-tl-none rounded-tr-none rounded-br-none rounded-bl-[5px]">
                                            <EditIcon 
                                                class="w-5 h-5 text-primary-900 hover:text-primary-800 cursor-pointer" />
                                        </Button>
                                        <Button 
                                            :type="'button'" 
                                            :size="'md'"
                                            @click="openDeleteModal(table.type, table.id)"
                                            class="!bg-primary-600 hover:!bg-primary-700 rounded-tl-none rounded-tr-none rounded-bl-none rounded-br-[5px]">
                                            <DeleteIcon
                                                class="w-5 h-5 text-primary-100 hover:text-primary-50 cursor-pointer pointer-events-none" />
                                        </Button>
                                    </div>
                                </template>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Modal
        :title="modalHeader(form.type)"
        :maxWidth="'md'"
        :closeable="true"
        :show="editModal"
        @close="closeModal"
        v-if="editModal"
    >
    <form class="flex flex-col gap-6" novalidate @submit.prevent="formSubmit">
        <div class="gap-6 pl-1 pr-2 py-1 max-h-[700px] overflow-y-scroll scrollbar-thin scrollbar-webkit">
            <div class="col-span-full md:col-span-8 flex flex-col items-start gap-6 flex-[1_0_0] self-stretch">
                <TextInput
                    :inputName="'table_no'"
                    :labelText="formatLabel(form.type)"
                    :placeholder="'eg: 1'"
                    :errorMessage="form.errors?.table_no || ''"
                    v-model="form.table_no"
                    class="col-span-full md:col-span-8"
                />
                <div class="grid grid-cols-2 md:grid-cols-12 gap-3 self-stretch">
                <TextInput
                    :inputName="'seat'"
                    inputType="number"
                    :labelText="'No. of Seats Available'"
                    :placeholder="'number only (eg: 6)'"
                    :errorMessage="form.errors?.seat || ''"
                    @keypress="isNumber($event)"
                    v-model="form.seat"
                    class="col-span-full md:col-span-6"
                />
                <Dropdown
                    :inputName="'zone_id'"
                    :labelText="'Select Zone'"
                    :inputArray="zones"
                    :dataValue="form.zone_id"
                    :errorMessage="form.errors?.zone_id || ''"
                    v-model="form.zone_id"
                    class="col-span-full md:col-span-6"
                />
                </div>
            </div>
        </div>
        <div class="flex pt-4 justify-center items-end gap-4 self-stretch">
            <Button
                :type="'button'"
                :variant="'tertiary'"
                :size="'lg'"
                @click="closeModal"
            >
                Cancel
            </Button>
            <Button
                :size="'lg'"
                :disabled="!isFormValid"
                :type="'submit'"
            >
                Save Changes
            </Button>
        </div>
    </form>
    </Modal>

    <Modal 
        :maxWidth="'2xs'" 
        :closeable="true"
        :show="deleteModal"
        :deleteConfirmation="true"
        :deleteUrl="`/table-room/table-room/deleteTable/${form.id}`"
        :confirmationTitle="`Delete this ${form.type}?`"
        :confirmationMessage="`Are you sure you want to delete the selected ${form.type}? This action cannot be undone.`"
        @close="closeModal"
        v-if="deleteModal"
        :withHeader="false"
    >
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-9" >
                <div class="flex item-center gap-3">
                    <Button
                        variant="tertiary"
                        size="lg"
                        type="button"
                        @click="closeModal"
                    >
                        Keep
                    </Button>
                    <Button
                        variant="red"
                        size="lg"
                        type="submit"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
</template>
