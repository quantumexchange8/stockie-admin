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

const getTotalPurchaseAmount = (customer) => {
    return customer.payments.reduce((total, transaction) => total + Number(transaction.grand_total), 0.00);
};

const getTotalPurchaseCount = (customer) => {
    return customer.payments.length;
};

const getTotalPointsEarned = (customer) => {
    return customer.payments.reduce((total, transaction) => total + Number(transaction.point_earned), 0);
};

const combinedTotalPurchaseAmount = computed(() => {
    return props.rows.reduce((total, customer) =>
        total + customer.payments.reduce((totalAmount, transaction) => totalAmount + Number(transaction.grand_total), 0.00)
    , 0.00);
});

const combinedTotalPurchaseCount = computed(() => {
    return props.rows.reduce((total, customer) => total + customer.payments.length, 0);
});

const combinedTotalPointsEarned = computed(() => {
    return props.rows.reduce((total, customer) =>
        total + customer.payments.reduce((totalPoints, transaction) => totalPoints + Number(transaction.point_earned), 0)
    , 0.00);
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
    const title = 'Member Purchase';
    const startDate = dayjs(props.dateFilter[0]).format('DD/MM/YYYY');
    const endDate = props.dateFilter.length > 1 ? dayjs(props.dateFilter[1]).format('DD/MM/YYYY') : dayjs(props.dateFilter[0]).format('DD/MM/YYYY');
    const dateRange = `Date Range: ${startDate} - ${endDate}`;

    // Use consistent keys with empty values, and put title/date range in the first field
    const formattedRows = [
        { Customer: title, 'Total Purchase (RM)': '', 'No.of Purchase': '', 'Avg. Amt. Spent (RM)': '', 'Points Earned': '' },
        { Customer: dateRange, 'Total Purchase (RM)': '', 'No.of Purchase': '', 'Avg. Amt. Spent (RM)': '', 'Points Earned': '' },
        { Customer: 'Customer', 'Total Purchase (RM)': 'Total Purchase (RM)', 'No.of Purchase': 'No.of Purchase', 'Avg. Amt. Spent (RM)': 'Avg. Amt. Spent (RM)', 'Points Earned': 'Points Earned' },
        ...props.rows.map(row => ({
            'Customer': row.full_name,
            'Total Purchase (RM)': formatAmount(getTotalPurchaseAmount(row)),
            'No.of Purchase': getTotalPurchaseCount(row),
            'Avg. Amt. Spent (RM)': formatAmount(getTotalPurchaseAmount(row) / getTotalPurchaseCount(row)),
            'Points Earned': `${formatAmount(getTotalPointsEarned(row), 0)} pts`,
        })),
        {
            'Customer': 'Total',
            'Total Purchase (RM)': formatAmount(combinedTotalPurchaseAmount.value),
            'No.of Purchase': combinedTotalPurchaseCount.value,
            'Avg. Amt. Spent (RM)': formatAmount(isNaN(combinedTotalPurchaseAmount.value / combinedTotalPurchaseCount.value) ? 0.00 : combinedTotalPurchaseAmount.value / combinedTotalPurchaseCount.value),
            'Points Earned': `${formatAmount(combinedTotalPointsEarned.value, 0)} pts`,
        }
    ];

    exportToCSV(formattedRows, 'Member Purchase Report');
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
                    <td class="w-[22%]">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis whitespace-nowrap overflow-hidden px-3 py-4">{{ row.full_name }}</span>
                    </td>
                    <td class="w-[21%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4"> {{ formatAmount(getTotalPurchaseAmount(row)) }}</span>
                        </div>
                    </td>
                    <td class="w-[17%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ getTotalPurchaseCount(row) }}</span>
                        </div>
                    </td>
                    <td class="w-[23%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getTotalPurchaseAmount(row) / getTotalPurchaseCount(row)) }}</span>
                        </div>
                    </td>
                    <td class="w-[17%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getTotalPointsEarned(row), 0) }} pts</span>
                        </div>
                    </td>
                </tr>
            </template>
            <tr class="!border-y-2 border-grey-200">
                <td class="w-[22%]">
                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden px-3 py-4">Total</span>
                </td>
                <td class="w-[21%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4"> {{ formatAmount(combinedTotalPurchaseAmount) }}</span>
                    </div>
                </td>
                <td class="w-[17%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ combinedTotalPurchaseCount }}</span>
                    </div>
                </td>
                <td class="w-[23%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ formatAmount(isNaN(combinedTotalPurchaseAmount / combinedTotalPurchaseCount) ? 0.00 : combinedTotalPurchaseAmount / combinedTotalPurchaseCount ) }}</span>
                    </div>
                </td>
                <td class="w-[17%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ formatAmount(combinedTotalPointsEarned, 0) }} pts</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</template>