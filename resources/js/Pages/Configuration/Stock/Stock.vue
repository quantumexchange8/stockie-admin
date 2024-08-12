<script setup>
import { Head } from "@inertiajs/vue3";
import {ref} from "vue";
// import { Select } from 'primevue/select';

const stocks = ref({data: []});
const isLoading = ref(false);

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

// const selectedCity = ref();
// const cities = ref([
//     { name: 'New York', code: 'NY' },
//     { name: 'Rome', code: 'RM' },
//     { name: 'London', code: 'LDN' },
//     { name: 'Istanbul', code: 'IST' },
//     { name: 'Paris', code: 'PRS' }
// ]);

</script>


<template>
    <Head title="Configuration" />
    <div class="flex flex-col gap-5">
        <div
            class="flex flex-col h-[12px] py-6 gap-15 justify-center items-center rounded-[5px] border border-primary-100"
        ></div>
        <div
            class=""
        >
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
                                <!-- <Select v-model="selectedCity" editable :options="cities" optionLabel="name" placeholder="Select a City" class="w-full md:w-56" /> -->
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
