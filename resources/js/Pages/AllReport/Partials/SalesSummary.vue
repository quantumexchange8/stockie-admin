<script setup>
import { transactionFormat } from '@/Composables';
import dayjs from 'dayjs';

const props = defineProps({
    columns: Array,
    rows: Array,
    dateFilter: Array,
})

const { formatAmount } = transactionFormat();

const getSalesTaxesAmount = (sale) => {
    return Number(sale.sst_amount) + Number(sale.service_tax_amount);
}

const getSalesRefundsAmount = (sale) => {
    return sale.order.order_items
        .filter((item) => item.refund_qty > 0)
        .reduce((total, item) => total + (item.refund_qty / item.item_qty) * item.amount, 0.00);
}

const getSalesDiscountAmount = (sale) => {
    return Number(sale.discount_amount) + Number(sale.bill_discount_total);
}

const getSalesNetAmount = (sale) => {
    return formatAmount(sale.grand_total - (getSalesTaxesAmount(sale) + getSalesRefundsAmount(sale) + getSalesDiscountAmount(sale)));
}

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
    const title = 'Sales Summary';
    const startDate = dayjs(props.dateFilter[0]).format('DD/MM/YYYY');
    const endDate = props.dateFilter.length > 1 ? dayjs(props.dateFilter[1]).format('DD/MM/YYYY') : dayjs(props.dateFilter[0]).format('DD/MM/YYYY');
    const dateRange = `Date Range: ${startDate} - ${endDate}`;

    // Use consistent keys with empty values, and put title/date range in the first field
    const formattedRows = [
        { Date: title, 'Gross (RM)': '', 'Tax (RM)': '', 'Refunds (RM)': '', 'Voids (RM)': '', 'Disc. (RM)': '', 'Net (RM)' : ''},
        { Date: dateRange, 'Gross (RM)': '', 'Tax (RM)': '', 'Refunds (RM)': '', 'Voids (RM)': '', 'Disc. (RM)': '', 'Net (RM)' : ''},
        { Date: 'Date', 'Gross (RM)': 'Gross (RM)', 'Tax (RM)': 'Tax (RM)', 'Refunds (RM)': 'Refunds (RM)', 'Voids (RM)': 'Voids (RM)', 'Disc. (RM)': 'Disc. (RM)', 'Net (RM)': 'Net (RM)' },
        ...props.rows.map(row => ({
            'Date': dayjs(row.receipt_start_date).format('DD/MM/YYYY'),
            'Gross (RM)': formatAmount(row.grand_total),
            'Tax (RM)': formatAmount(getSalesTaxesAmount(row)),
            'Refunds (RM)': formatAmount(getSalesRefundsAmount(row)),
            'Voids (RM)': 0.00,
            'Disc. (RM)': formatAmount(getSalesDiscountAmount(row)),
            'Net (RM)': getSalesNetAmount(row),
        })),
    ];


    exportToCSV(formattedRows, 'Sales Summary Report');
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
            <tr v-for="(row, index) in props.rows" :key="index" class="border-b border-grey-100">
                <td class="w-[14%]">
                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden px-3 py-4">{{ dayjs(row.receipt_start_date).format('DD/MM/YYYY') }}</span>
                </td>
                <td class="w-[14%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(row.grand_total) }}</span>
                    </div>
                </td>
                <td class="w-[14%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getSalesTaxesAmount(row)) }}</span>
                    </div>
                </td>
                <td class="w-[16%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getSalesRefundsAmount(row)) }}</span>
                    </div>
                </td>
                <td class="w-[14%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">0.00</span>
                    </div>
                </td>
                <td class="w-[14%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getSalesDiscountAmount(row)) }}</span>
                    </div>
                </td>
                <td class="w-[14%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ getSalesNetAmount(row) }}</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</template>