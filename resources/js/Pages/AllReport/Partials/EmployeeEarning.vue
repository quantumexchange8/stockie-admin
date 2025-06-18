<script setup>
import { computed, ref } from 'vue';
import { transactionFormat } from '@/Composables';
import dayjs from 'dayjs';

const props = defineProps({
    columns: Array,
    rows: Array,
    dateFilter: Array,
})

const { formatAmount } = transactionFormat();

const totalEmployeeSalesAmount = computed(() => {
    return formatAmount(props.rows.reduce((total, emp) => total + emp.sales, 0.00));
});

const totalEmployeeIncentiveAmount = computed(() => {
    return formatAmount(props.rows.reduce((total, emp) => total + emp.incentives, 0.00));
});

const totalEmployeeCommissionAmount = computed(() => {
    return formatAmount(props.rows.reduce((total, emp) => total + emp.commission, 0.00));
});

const arrayToCsv = (data) => {
    const headers = Object.keys(data[0]);
    const rows = data.map(row => headers.map(header => `"${row[header] ?? ''}"`).join(','));
    return [...rows].join('\n');
};

const downloadBlob = (content, fileName, contentType) => {
    // Create a blob
    var blob = new Blob(['\uFEFF' + content], { type: contentType });
    var url = URL.createObjectURL(blob);
    // Create a link to download it
    var pom = document.createElement('a');
    pom.href = url;
    pom.setAttribute('download', fileName);
    pom.click();
    URL.revokeObjectURL(url); // Revoke the object URL after download
};

const exportToCSV = (mappedData, fileNamePrefix) => { 
    if (mappedData.length > 0) {
        const currentDateTime = dayjs().format('YYYYMMDDhhmmss');
        const fileName = `${fileNamePrefix}_${currentDateTime}.csv`;
        const contentType = 'text/csv;charset=utf-8;';
        const myLogs = arrayToCsv(mappedData);
        downloadBlob(myLogs, fileName, contentType);
    } else {
        console.log('No data available');
    }
}

const csvExport = () => {
    const title = 'Employee Earning';
    const startDate = dayjs(props.dateFilter[0]).format('DD/MM/YYYY');
    const endDate = props.dateFilter.length > 1 ? dayjs(props.dateFilter[1]).format('DD/MM/YYYY') : dayjs(props.dateFilter[0]).format('DD/MM/YYYY');
    const dateRange = `Date Range: ${startDate} - ${endDate}`;

    // Use consistent keys with empty values, and put title/date range in the first field
    const formattedRows = [
        { Employee: title, 'Sales (RM)': '', 'Incentive (RM)': '', 'Commission (RM)': '' },
        { Employee: dateRange, 'Sales (RM)': '', 'Incentive (RM)': '', 'Commission (RM)': '' },
        { Employee: 'Employee', 'Sales (RM)': 'Sales (RM)', 'Incentive (RM)': 'Incentive (RM)', 'Commission (RM)': 'Commission (RM)' },
        ...props.rows.map(row => ({
            'Employee': row.full_name,
            'Sales (RM)': formatAmount(row.sales),
            'Incentive (RM)': formatAmount(row.incentives),
            'Commission (RM)': formatAmount(row.commission),
        })),
        {
            'Employee': 'Total',
            'Sales (RM)': totalEmployeeSalesAmount.value,
            'Incentive (RM)': totalEmployeeIncentiveAmount.value,
            'Commission (RM)': totalEmployeeCommissionAmount.value,
        }
    ];

    exportToCSV(formattedRows, 'Employee Earning Report');
}

defineExpose({
  csvExport
});

</script>

<template>
    <table class="w-full border-spacing-3 border-collapse">
        <thead class="bg-grey-100">
            <tr>
                <th v-for="column in props.columns" :class="`w-[${column.width}%] py-2 px-3`">
                    <span class="flex justify-between items-center text-2xs text-grey-950 font-semibold">
                        {{ column.title }}
                    </span> 
                </th>
            </tr>
        </thead>
        <tbody>
            <template v-for="row in props.rows">
                <tr class="border-b border-grey-100">
                    <td class="w-[43%]">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis whitespace-nowrap overflow-hidden px-3 py-4">{{ row.full_name }}</span>
                    </td>
                    <td class="w-[19%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4"> {{ formatAmount(row.sales) }}</span>
                        </div>
                    </td>
                    <td class="w-[19%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(row.incentives) }}</span>
                        </div>
                    </td>
                    <td class="w-[19%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(row.commission) }}</span>
                        </div>
                    </td>
                </tr>
            </template>
            <tr class="!border-y-2 border-grey-200">
                <td class="w-[43%]">
                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden px-3 py-4">Total</span>
                </td>
                <td class="w-[19%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4"> {{ totalEmployeeSalesAmount }}</span>
                    </div>
                </td>
                <td class="w-[19%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ totalEmployeeIncentiveAmount }}</span>
                    </div>
                </td>
                <td class="w-[19%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ totalEmployeeCommissionAmount }}</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</template>