<script setup>
import { transactionFormat } from '@/Composables';
import dayjs from 'dayjs';

const props = defineProps({
    columns: Array,
    rows: Array,
    dateFilter: Array,
})

const { formatAmount } = transactionFormat();

const getGroupTotalStockQty = (group) => {
    return group.inventory_items.reduce((total, item) => total + item.stock_qty, 0);
};

const getGroupTotalKeepQty = (group) => {
    return group.inventory_items
        .filter((item) => item.keep === 'Active')
        .reduce((total, item) => total + item.total_keep_qty, 0);
};

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
    const title = 'Current Stock Report';
    const currentDate = dayjs().format('DD/MM/YYYY HH:mm:ss');

    // Use consistent keys with empty values, and put title/date range in the first field
    const formattedRows = [
        { Item: title, 'Stock Qty': '', 'Unit': '', 'Kept Qty': ''},
        { Item: currentDate, 'Stock Qty': '', 'Unit': '', 'Kept Qty': ''},
        { Item: 'Item', 'Stock Qty': 'Stock Qty', 'Unit': 'Unit', 'Kept Qty': 'Kept Qty' },
    ];
    props.rows.forEach((row) => {
        formattedRows.push({});
        formattedRows.push({ Item: row.name, 'Stock Qty': '', 'Unit': '', 'Kept Qty': ''});
        
        row.inventory_items.forEach((item) => {
            formattedRows.push({
                'Item': item.item_name,
                'Stock Qty': formatAmount(item.stock_qty, 0),
                'Unit': item.item_category.name,
                'Kept Qty': item.keep === 'Active' ? formatAmount(item.total_keep_qty, 0) : 'NA',
            });
        });

        formattedRows.push({ Item: 'Total', 'Stock Qty': formatAmount(getGroupTotalStockQty(row), 0), 'Unit': 'NA', 'Kept Qty': formatAmount(getGroupTotalKeepQty(row), 0)});
    });
    exportToCSV(formattedRows, 'Current Stock Report');
}

defineExpose({
  csvExport
});

</script>

<template>
    <div class="flex flex-col gap-y-5 items-start self-stretch">
        <div v-for="(row, index) in props.rows" :key="index" class="flex flex-col gap-y-4 items-start self-stretch">
            <p class="text-grey-950 font-bold text-sm self-stretch">{{ row.name }}</p>
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
                    <template v-for="item in row.inventory_items">
                        <tr class="border-b border-grey-100">
                            <td class="w-[43%]">
                                <span class="text-grey-900 text-2xs font-semibold text-ellipsis whitespace-nowrap overflow-hidden px-3 py-4">{{ item.item_name }}</span>
                            </td>
                            <td class="w-[19%]">
                                <div class="flex justify-start items-center gap-3 px-3">
                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(item.stock_qty, 0) }}</span>
                                </div>
                            </td>
                            <td class="w-[19%]">
                                <div class="flex justify-start items-center gap-3 px-3">
                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ item.item_category.name }}</span>
                                </div>
                            </td>
                            <td class="w-[19%]">
                                <div class="flex justify-start items-center gap-3 px-3">
                                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ item.keep === 'Active' ? formatAmount(item.total_keep_qty, 0) : 'NA' }}</span>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr class="!border-y-2 border-grey-190">
                        <td class="w-[43%]">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden px-3 py-4">Total</span>
                        </td>
                        <td class="w-[19%]">
                            <div class="flex justify-start items-center gap-3 px-3">
                                <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4"> {{ formatAmount(getGroupTotalStockQty(row), 0) }}</span>
                            </div>
                        </td>
                        <td class="w-[19%]">
                            <div class="flex justify-start items-center gap-3 px-3">
                                <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">NA</span>
                            </div>
                        </td>
                        <td class="w-[19%]">
                            <div class="flex justify-start items-center gap-3 px-3">
                                <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ formatAmount(getGroupTotalKeepQty(row), 0) }}</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>