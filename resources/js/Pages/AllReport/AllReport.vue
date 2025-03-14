<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from "@inertiajs/vue3";
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { ref } from 'vue';
import Button from '@/Components/Button.vue';
import { UploadIcon } from '@/Components/Icons/solid';
import Dropdown from '@/Components/Dropdown.vue';

const home = ref({
    label: 'Report',
});

const salesType = [
    { text: 'Sales Summary', value: 'sales_summary'},
    { text: 'Payment Method', value: 'payment_method'},
    { text: 'Product Sales', value: 'product_sales'},
    { text: 'Category Sales', value: 'category_sales'},
    { text: 'Employee Earnings', value: 'employee_earning'},
    { text: 'Member Purchase', value: 'member_purchase'},
]

const sales_types = ref(null);

</script>

<template>

    <Head title="Report" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <div class="flex flex-col max-w-full justify-end items-start gap-5 self-stretch">
            <div class="flex flex-col p-6 gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class=" w-full md:max-w-[218px] xl:max-w-[268px]">
                            <Dropdown
                                :inputName="'sale_type'"
                                :labelText="''"
                                :inputArray="salesType"
                                v-model="sales_types"
                                :dataValue="sales_types"
                                class="w-full"
                            />
                        </div>
                        <div>
                            
                        </div>
                    </div>
                    <div>
                        <Button
                            :type="'button'"
                            :variant="'tertiary'"
                            :size="'lg'"
                            :iconPosition="'left'"
                            class="!w-fit"
                        >
                            <template #icon >
                                <UploadIcon class="size-6" />
                            </template>
                            Export
                        </Button>
                    </div>
                </div>
                <div></div>
            </div>
        </div>

    </AuthenticatedLayout>
    
</template>