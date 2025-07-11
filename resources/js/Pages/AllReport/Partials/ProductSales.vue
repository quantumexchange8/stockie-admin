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

const getProductTotalQtySold = (product) => {
    return product.order_items.reduce((total, item) => total + item.item_qty, 0);
};

const getProductTotalGrossAmount = (product) => {
    return product.order_items.reduce((total, item) => {
        const amount = Number(item.amount);
        const payment = item.order.payment;

        if (!payment || payment.total_amount <= 0 || amount <= 0) {
            return total + amount;
        }

        const taxTotal = Number(payment.sst_amount || 0) + Number(payment.service_tax_amount || 0);
        const taxRatio = amount / payment.total_amount;

        const itemTax = taxTotal * taxRatio;

        return total + amount + itemTax;
    }, 0.00);
};

const getProductTotalDiscAmount = (product) => {
    return product.order_items.reduce((total, item) => total + item.discount_amount, 0.00);
};

const getProductTotalTaxAmount = (product) => {
    return product.order_items.reduce((total, item) => {
        const amount = Number(item.amount);
        const payment = item.order.payment;

        if (!payment || payment.total_amount <= 0 || amount <= 0) {
            return total;
        }

        const taxTotal = Number(payment.sst_amount || 0) + Number(payment.service_tax_amount || 0);
        const taxRatio = amount / payment.total_amount;

        const itemTax = taxTotal * taxRatio;

        return total + itemTax;
    }, 0.00);
};

const getProductTotalRefundAmount = (product) => {
    return product.refund_details.reduce((total, item) => total + Number(item.refund_amount), 0.00);
};

const getProductNetTotal = (product) => {
    const refund = product.refund_details?.reduce((sum, item) => sum + Number(item.refund_amount || 0), 0);
    return product.order_items.reduce((total, item) => total + Number(item.amount), 0.00) - refund;
};

const totalQtySold = computed(() => {
    return props.rows.reduce((total, product) => total + getProductTotalQtySold(product), 0.00);
});

const totalGrossAmount = computed(() => {
    return formatAmount(props.rows.reduce((total, product) => total + getProductTotalGrossAmount(product), 0.00));
});

const totalDiscAmount = computed(() => {
    return formatAmount(props.rows.reduce((total, product) => total + getProductTotalDiscAmount(product), 0.00));
});

const totalTaxAmount = computed(() => {
    return formatAmount(props.rows.reduce((total, product) => total + getProductTotalTaxAmount(product), 0.00));
});

const totalRefundAmount = computed(() => {
    return formatAmount(props.rows.reduce((total, product) => total + getProductTotalRefundAmount(product), 0.00));
});

const combinedNetTotal = computed(() => {
    return formatAmount(props.rows.reduce((total, product) => total + getProductNetTotal(product), 0.00));
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
    const title = 'Product Sales';
    const startDate = dayjs(props.dateFilter[0]).format('DD/MM/YYYY');
    const endDate = props.dateFilter[1] != null ? dayjs(props.dateFilter[1]).format('DD/MM/YYYY') : dayjs(props.dateFilter[0]).endOf('day').format('DD/MM/YYYY');
    const dateRange = `Date Range: ${startDate} - ${endDate}`;

    // Use consistent keys with empty values, and put title/date range in the first field
    const formattedRows = [
        { Product: title, 'Sold': '', 'Gross (RM)': '', 'Disc. (RM)': '', 'Tax (RM)': '', 'Refund (RM)': '', 'Net (RM)': '' },
        { Product: dateRange, 'Sold': '', 'Gross (RM)': '', 'Disc. (RM)': '', 'Tax (RM)': '', 'Refund (RM)': '', 'Net (RM)': '' },
        { Product: 'Product', 'Sold': 'Sold', 'Gross (RM)': 'Gross (RM)', 'Disc. (RM)': 'Disc. (RM)', 'Tax (RM)': 'Tax (RM)', 'Refund (RM)': 'Refund (RM)', 'Net (RM)': 'Net (RM)' },
        ...props.rows.map(row => ({
            'Product': row.product_name,
            'Sold': getProductTotalQtySold(row),
            'Gross (RM)': formatAmount(getProductTotalGrossAmount(row)),
            'Disc. (RM)': formatAmount(getProductTotalDiscAmount(row)),
            'Tax (RM)': formatAmount(getProductTotalTaxAmount(row)),
            'Refund (RM)': formatAmount(getProductTotalRefundAmount(row)),
            'Net (RM)': formatAmount(getProductNetTotal(row)),
        })),
        {
            'Product': 'Total',
            'Sold': totalQtySold.value,
            'Gross (RM)': totalGrossAmount.value,
            'Disc. (RM)': totalDiscAmount.value,
            'Tax (RM)': totalTaxAmount.value,
            'Refund (RM)': totalRefundAmount.value,
            'Net (RM)': combinedNetTotal.value,
        }
    ];

    exportToCSV(formattedRows, 'Product Sales Report');
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
                    <td class="w-[22%] px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ row.product_name }}</span>
                    </td>
                    <td class="w-[7%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4"> {{ getProductTotalQtySold(row) }}</span>
                        </div>
                    </td>
                    <td class="w-[14%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getProductTotalGrossAmount(row)) }}</span>
                        </div>
                    </td>
                    <td class="w-[14%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getProductTotalDiscAmount(row)) }}</span>
                        </div>
                    </td>
                    <td class="w-[14%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getProductTotalTaxAmount(row)) }}</span>
                        </div>
                    </td>
                    <td class="w-[15%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getProductTotalRefundAmount(row)) }}</span>
                        </div>
                    </td>
                    <td class="w-[14%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getProductNetTotal(row)) }}</span>
                        </div>
                    </td>
                </tr>
            </template>
        </tbody>
        <tfoot>
            <tr class="!border-y-2 border-grey-200">
                <td class="w-[22%]">
                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden px-3 py-4">Total</span>
                </td>
                <td class="w-[7%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4"> {{ totalQtySold }}</span>
                    </div>
                </td>
                <td class="w-[14%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ totalGrossAmount }}</span>
                    </div>
                </td>
                <td class="w-[14%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ totalDiscAmount }}</span>
                    </div>
                </td>
                <td class="w-[14%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ totalTaxAmount }}</span>
                    </div>
                </td>
                <td class="w-[15%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ totalRefundAmount }}</span>
                    </div>
                </td>
                <td class="w-[14%]">
                    <div class="flex justify-start items-center gap-3 px-3">
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ combinedNetTotal }}</span>
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
</template>