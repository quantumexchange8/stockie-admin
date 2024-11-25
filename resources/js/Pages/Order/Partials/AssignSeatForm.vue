<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import Toggle from '@/Components/Toggle.vue';
import DateInput from "@/Components/Date.vue";
import Dropdown from '@/Components/Dropdown.vue'
import TextInput from '@/Components/TextInput.vue';
import { TimesIcon } from '@/Components/Icons/solid';
import dayjs from 'dayjs';
import { useCustomToast, useInputValidator } from '@/Composables/index.js';
import MultiSelect from '@/Components/MultiSelect.vue';

const props = defineProps({
    waiters: {
        type: Array,
        default: () => [],
    },
    table: Object,
    tablesArr: Array,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.data.id)

const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const emit = defineEmits(['close']);

const selectedTableName = ref(props.table.table_no);

const form = useForm({
    table_id: props.table.id,
    table_no: props.table.table_no,
    pax: '',
    user_id: userId.value,
    assigned_waiter: '',
    order_id: '',
    merge_table: false,
    tables: [props.table.id],
});

const formSubmit = () => { 
    form.post(route('orders.tables.store'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: `You've successfully check in customer to '${selectedTableName.value}'.`,
                });
                form.reset();
            }, 200);
            emit('close');
        },
    })
};

const isFormValid = computed(() => {
    return ['tables','pax', 'assigned_waiter'].every(field => form[field]) && !form.processing;
});

watch(() => form.merge_table, (newValue) => form.assigned_waiter = newValue ? form.assigned_waiter : '');

const updateSelectedTables = (event) => {
    selectedTableName.value = 
        props.tablesArr.map(table => event.find((value) => table.value === value) ? table.text : null)
                    .filter((table) => table !== null)
                    .join(', ');

    // Reset selected table
    if (!selectedTableName.value) {
        selectedTableName.value = props.table.table_no;
        form.tables = [props.table.id];
    } 
};

</script>

<template>
    <form novalidate @submit.prevent="formSubmit">
        <div class="flex flex-col gap-9 w-[370px]">
            <div class="flex items-center justify-between">
                <span class="text-primary-950 text-center text-md font-medium">Assign Seat</span>
                <TimesIcon
                    class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                    @click="$emit('close')"
                />
            </div>
            
            <div class="flex flex-col justify-between gap-6">
                <div class="flex flex-nowrap justify-between gap-6">
                    <p class="text-xl font-bold text-primary-900">{{ selectedTableName }}</p>
                    <div class="flex items-center justify-end gap-3">
                        <p class="text-base text-grey-900 font-normal">Merge table</p>
                        <Toggle
                            :inputName="'merge_table'"
                            :checked="form.merge_table"
                            v-model:checked="form.merge_table"
                            class="col-span-full xl:col-span-2"
                        />
                    </div>
                </div>
                <div class="flex flex-col gap-6 items-center self-stretch">
                    <MultiSelect 
                        v-if="form.merge_table"
                        inputName="table_no"
                        labelText="Table Merged"
                        placeholder="Select"
                        class="col-span-full sm:col-span-6"
                        :inputArray="tablesArr"
                        :errorMessage="form.errors?.tables || ''"
                        :dataValue="form.tables"
                        v-model="form.tables"
                        @onChange="updateSelectedTables"
                    />
                    <TextInput
                        :inputName="'pax'"
                        :labelText="'No. of pax'"
                        :placeholder="'No. of pax'"
                        :errorMessage="form.errors?.pax || ''"
                        v-model="form.pax"
                        @keypress="isValidNumberKey($event, false)"
                    />
                    <Dropdown
                        imageOption
                        :inputName="'assigned_waiter'"
                        :labelText="'Assign Waiter'"
                        :inputArray="waiters"
                        :errorMessage="form.errors?.assigned_waiter || ''"
                        v-model="form.assigned_waiter"
                    />
                </div>
            </div>

            <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                <Button
                    :type="'button'"
                    :variant="'tertiary'"
                    :size="'lg'"
                    @click="$emit('close')"
                >
                    Cancel
                </Button>
                <Button
                    :size="'lg'"
                    :disabled="!isFormValid"
                >
                    Done
                </Button>
            </div>
        </div>
    </form>
</template>
