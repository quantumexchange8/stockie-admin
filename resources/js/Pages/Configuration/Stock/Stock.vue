<script setup>
import Table from "@/Components/Table.vue";
import TextInput from "@/Components/TextInput.vue";
import Toast from "@/Components/Toast.vue";
import { Head, useForm } from "@inertiajs/vue3";
import {ref} from "vue";

const stocks = ref();
const isLoading = ref(false);
const isTextInputVisible = ref(false);
const emit = defineEmits(["close"]);

const stockColumn = ref([
    { field: 'type', header: 'Unit type', width: '80', sortable: false},
    { field: 'low_stock_qty', header: 'Low stock quantity', width: '20', sortable: false}
])

const form = useForm({
    id: stocks.id,
    low_stock_qty: stocks.low_stock_qty,
})

const submit = (form) => {
    form.post(route('configurations.updateStock'), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            isTextInputVisible.value = false;
            form.reset();
            // emit('close');
        },
        onError: (errors) => {
            console.error('Form submission error: ', errors);
        }
    })
}

const getResults = async () => {
    isLoading.value = true
    try {
        let url = `/configurations/getStock`;

        const response = await axios.get(url);
        stocks.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}



getResults()

const editLowStock = (object) => {
    isTextInputVisible.value = true;
    form.id = object.id;
    form.low_stock_qty = object.low_stock_qty.toString();
}

const stopSetting = () => {
    isTextInputVisible.value = false;
}

</script>


<template>
    <Toast 
        inline
        severity="info"
        actionLabel="OK"
        summary="Set low stock quantity"
        detail="How many unit considered as low at stock? Set the low stock quantity for each unit so that we can notify when the item is low at stock. "
        :closable="false"
    />

    <form novalidate @submit.prevent="submit">
        <Table
            :columns="stockColumn"
            :rows="stocks"
            :variant="'list'"
            :paginator="false"
            class="pt-5"
        >
            <template #type="stocks">
                <span class="flex-[1_0_0] text-grey-900 text-sm font-medium">{{ stocks.type }}</span>
            </template>
            <template #low_stock_qty="stocks">
                <span class="flex-[1_0_0] text-grey-900 text-base font-medium" v-show="!isTextInputVisible || form.id !== stocks.id" @click="editLowStock(stocks)">{{ stocks.low_stock_qty }}</span>
                <TextInput
                    v-model="form.low_stock_qty"
                    v-show="isTextInputVisible && form.id == stocks.id"
                    :inputType="'Number'"
                    :inputName="'low_stock_qty'"
                    @blur="stopSetting"
                    @keydown.enter.prevent="submit(form)"
                >
                </TextInput>
            </template>
        </Table>
    </form>
    <!-- <div class="flex flex-col gap-5">
        <table class="w-full">
            <thead class="text-xs font-medium text-gray-700 uppercase bg-primary-50 rounded-[5px]">
                <tr>
                    <th scope="col" class="p-3 text-left">
                        Unit type
                    </th>
                    <th scope="col" class="p-3 text-right">
                        Low Stock quantity
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="stock in stocks">
                    <td class="p-3 text-left">
                        {{ stock.type }}
                    </td>
                    <td class="p-3 flex justify-end">
                        <div>
                            {{ stock.low_stock_qty }}
                        </div>
                        <div class="card flex justify-center">
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div> -->
</template>
