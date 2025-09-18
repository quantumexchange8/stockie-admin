<script setup>
import { computed, ref } from 'vue';
import { transactionFormat } from '@/Composables';
import dayjs from 'dayjs';
import { wTrans, wTransChoice } from 'laravel-vue-i18n';

const props = defineProps({
    columns: Array,
    rows: Array,
    dateFilter: Array,
})

const { formatAmount } = transactionFormat();

const getCategoryTotalQtySold = (category) => {
    return category.products.reduce((totalQty, product) => 
        totalQty + product.order_items.reduce((total, item) => total + item.item_qty, 0)
    , 0);
};

const getCategoryTotalGrossAmount = (category) => {
    return category.products.reduce((totalAmt, product) => {
        return totalAmt + product.order_items.reduce((total, item) => {
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
    }, 0.00);
};

const getCategoryTotalDiscAmount = (category) => {
    return category.products.reduce((totalAmt, product) => 
        totalAmt + product.order_items.reduce((total, item) => total + item.discount_amount, 0.00)
    , 0.00);
};

const getCategoryTotalTaxAmount = (category) => {
    return category.products.reduce((totalAmt, product) => {
        return totalAmt + product.order_items.reduce((total, item) => {
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
    }, 0.00);
};

const getCategoryTotalRefundAmount = (category) => {
    return category.products.reduce((totalAmt, product) => 
        totalAmt + product.refund_details.reduce((total, item) => total + Number(item.refund_amount), 0.00)
    , 0.00);
};

const getCategoryNetTotal = (category) => {
    return category.products.reduce((totalAmt, product) => {
        const refund = product.refund_details?.reduce((sum, item) => sum + Number(item.refund_amount || 0), 0);
        return totalAmt + product.order_items.reduce((total, item) => total + Number(item.amount), 0.00) - refund;
    }, 0.00);
};

const totalQtySold = computed(() => {
    return props.rows.reduce((total, category) => total + getCategoryTotalQtySold(category), 0.00);
});

const totalGrossAmount = computed(() => {
    return formatAmount(props.rows.reduce((total, category) => total + getCategoryTotalGrossAmount(category), 0.00));
});

const totalDiscAmount = computed(() => {
    return formatAmount(props.rows.reduce((total, category) => total + getCategoryTotalDiscAmount(category), 0.00));
});

const totalTaxAmount = computed(() => {
    return formatAmount(props.rows.reduce((total, category) => total + getCategoryTotalTaxAmount(category), 0.00));
});

const totalRefundAmount = computed(() => {
    return formatAmount(props.rows.reduce((total, category) => total + getCategoryTotalRefundAmount(category), 0.00));
});

const combinedNetTotal = computed(() => {
    return formatAmount(props.rows.reduce((total, category) => total + getCategoryNetTotal(category), 0.00));
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
    const title = wTrans('public.report.category_sales').value;
    const startDate = dayjs(props.dateFilter[0]).format('DD/MM/YYYY');
    const endDate = props.dateFilter[1] != null ? dayjs(props.dateFilter[1]).format('DD/MM/YYYY') : dayjs(props.dateFilter[0]).endOf('day').format('DD/MM/YYYY');
    const dateRange = `${wTrans('public.date_range').value}: ${startDate} - ${endDate}`;

    // Use consistent keys with empty values, and put title/date range in the first field
    const formattedRows = [
        { Category: title, 'Sold': '', 'Gross (RM)': '', 'Disc. (RM)': '', 'Tax (RM)': '', 'Refund (RM)': '', 'Net (RM)': '' },
        { Category: dateRange, 'Sold': '', 'Gross (RM)': '', 'Disc. (RM)': '', 'Tax (RM)': '', 'Refund (RM)': '', 'Net (RM)': '' },
        { Category: wTrans('public.category').value, 'Sold': wTrans('public.sold').value, 'Gross (RM)': `${wTrans('public.report.gross').value} (RM)`, 'Disc. (RM)': `${wTrans('public.report.disc').value} (RM)`, 'Tax (RM)': `${wTrans('public.report.tax').value} (RM)`, 'Refund (RM)': `${wTransChoice('public.refund', 1).value} (RM)`, 'Net (RM)': `${wTrans('public.report.net').value} (RM)` },
        ...props.rows.map(row => ({
            'Category': row.name,
            'Sold': getCategoryTotalQtySold(row),
            'Gross (RM)': formatAmount(getCategoryTotalGrossAmount(row)),
            'Disc. (RM)': formatAmount(getCategoryTotalDiscAmount(row)),
            'Tax (RM)': formatAmount(getCategoryTotalTaxAmount(row)),
            'Refund (RM)': formatAmount(getCategoryTotalRefundAmount(row)),
            'Net (RM)': formatAmount(getCategoryNetTotal(row)),
        })),
        {
            'Category': wTrans('public.total').value,
            'Sold': totalQtySold.value,
            'Gross (RM)': totalGrossAmount.value,
            'Disc. (RM)': totalDiscAmount.value,
            'Tax (RM)': totalTaxAmount.value,
            'Refund (RM)': totalRefundAmount.value,
            'Net (RM)': combinedNetTotal.value,
        }
    ];

    exportToCSV(formattedRows, wTrans('public.report.category_sales_report').value);
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
                        <span class="text-grey-900 text-2xs font-semibold text-ellipsis overflow-hidden py-4">{{ row.name }}</span>
                    </td>
                    <td class="w-[7%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4"> {{ getCategoryTotalQtySold(row) }}</span>
                        </div>
                    </td>
                    <td class="w-[14%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getCategoryTotalGrossAmount(row)) }}</span>
                        </div>
                    </td>
                    <td class="w-[14%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getCategoryTotalDiscAmount(row)) }}</span>
                        </div>
                    </td>
                    <td class="w-[14%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getCategoryTotalTaxAmount(row)) }}</span>
                        </div>
                    </td>
                    <td class="w-[15%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getCategoryTotalRefundAmount(row)) }}</span>
                        </div>
                    </td>
                    <td class="w-[14%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getCategoryNetTotal(row)) }}</span>
                        </div>
                    </td>
                </tr>
            </template>
        </tbody>
        <tfoot>
            <tr class="!border-y-2 border-grey-200">
                <td class="w-[22%]">
                    <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden px-3 py-4">{{ $t('public.total') }}</span>
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