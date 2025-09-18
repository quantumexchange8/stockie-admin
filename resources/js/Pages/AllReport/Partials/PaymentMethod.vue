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

const paymentMethods = computed(() => [
    { text: wTrans('public.cash'), value: 'Cash'},
    { text: wTrans('public.card'), value: 'Credit/Debit Card'},
    { text: wTrans('public.e_wallet'), value: 'E-Wallets'},
]);

const getTotalSalesCount = (type) => {
    return props.rows.filter((p) => {
            return p.payment_methods?.filter((pd) => {
                let conditions;

                switch (type) {
                    case 'Cash':
                        conditions = pd.payment_method === type;
                        
                        break;
                    case 'Credit/Debit Card':
                        conditions = pd.payment_method === 'Card';
                        
                        break;
                    case 'E-Wallets':
                        conditions = pd.payment_method === 'E-Wallet';
                        
                        break;
                }

                return conditions;
            }).length > 0;
        }).length;
};

const getTotalSalesAmount = (type) => {
    return props.rows
        .filter((p) => 
            p.payment_methods?.filter((pd) => {
                let conditions;

                switch (type) {
                    case 'Cash':
                        conditions = pd.payment_method === type;
                        
                        break;
                    case 'Credit/Debit Card':
                        conditions = pd.payment_method === 'Card';
                        
                        break;
                    case 'E-Wallets':
                        conditions = pd.payment_method === 'E-Wallet';
                        
                        break;
                }

                return conditions;
            }).length > 0
        )
        .reduce((total, p) => 
            total + Number(p.payment_methods?.find((pd) => {
                let conditions;

                switch (type) {
                    case 'Cash':
                        conditions = pd.payment_method === type;
                        
                        break;
                    case 'Credit/Debit Card':
                        conditions = pd.payment_method === 'Card';
                        
                        break;
                    case 'E-Wallets':
                        conditions = pd.payment_method === 'E-Wallet';
                        
                        break;
                }

                return conditions;
            }).amount)
        , 0.00)
        .toFixed(2);
};

const getTotalRefundCount = (type) => {
    return props.rows
        .filter((p) => 
            p.payment_refunds?.filter((pf) => {
                let conditions;

                switch (type) {
                    case 'Cash':
                        conditions = pf.refund_method === type;
                        
                        break;
                    case 'Credit/Debit Card':
                        conditions = pf.refund_method.includes(type) || pf.refund_method.includes('Credit') || 
                                pf.refund_method.includes('Debit' || pf.refund_method.includes('Card')) ||
                                pf.refund_method.includes('credit') || pf.refund_method.includes('debit') || pf.refund_method.includes('card');
                        
                        break;
                    case 'E-Wallets':
                        conditions = pf.refund_method.includes(type) || pf.refund_method.includes('E-wallet') || 
                                pf.refund_method.includes('e-wallet' || pf.refund_method.includes('ewallet')) ||
                                pf.refund_method.includes('Ewallet') || pf.refund_method.includes('EWallet');
                        
                        break;
                }

                return conditions;
            }).length > 0
        )
        .reduce((total, p) => total + p.payment_refunds?.length, 0);
};

const getTotalRefundAomunt = (type) => {
    return props.rows
        .filter((p) => 
            p.payment_refunds?.filter((pf) => {
                let conditions;

                switch (type) {
                    case 'Cash':
                        conditions = pf.refund_method === type;
                        
                        break;
                    case 'Credit/Debit Card':
                        conditions = pf.refund_method.includes(type) || pf.refund_method.includes('Credit') || 
                                pf.refund_method.includes('Debit' || pf.refund_method.includes('Card')) ||
                                pf.refund_method.includes('credit') || pf.refund_method.includes('debit') || pf.refund_method.includes('card');
                        
                        break;
                    case 'E-Wallets':
                        conditions = pf.refund_method.includes(type) || pf.refund_method.includes('E-wallet') || 
                                pf.refund_method.includes('e-wallet' || pf.refund_method.includes('ewallet')) ||
                                pf.refund_method.includes('Ewallet') || pf.refund_method.includes('EWallet');
                        
                        break;
                }

                return conditions;
            }).length > 0
        )
        .reduce((total, p) => 
            total + p.payment_refunds?.reduce((subTotal, pf) => subTotal + pf.total_refund_amount, 0)
        , 0);
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
    const title = wTrans('public.payment_method').value;
    const startDate = dayjs(props.dateFilter[0]).format('DD/MM/YYYY');
    const endDate = props.dateFilter[1] != null ? dayjs(props.dateFilter[1]).format('DD/MM/YYYY') : dayjs(props.dateFilter[0]).endOf('day').format('DD/MM/YYYY');
    const dateRange = `${wTrans('public.date_range').value}: ${startDate} - ${endDate}`;

    // Use consistent keys with empty values, and put title/date range in the first field
    const formattedRows = [
        { Method: title, 'No.of Sales': '', 'Sales (RM)': '', 'No.of Refund': '', 'Refund (RM)': '', 'Balance (RM)': '' },
        { Method: dateRange, 'No.of Sales': '', 'Sales (RM)': '', 'No.of Refund': '', 'Refund (RM)': '', 'Balance (RM)': '' },
        { Method: wTrans('public.method').value, 'No.of Sales': wTrans('public.report.no_of_sales').value, 'Sales (RM)': `${wTrans('public.sales').value} (RM)`, 'No.of Refund': wTrans('public.report.no_of_refund').value, 'Refund (RM)': `${wTransChoice('public.refund', 1).value} (RM)`, 'Balance (RM)': `${wTrans('public.balance').value} (RM)` },
        ...paymentMethods.value.map(method => ({
            'Method': method.text.value,
            'No.of Sales': getTotalSalesCount(method.value),
            'Sales (RM)': formatAmount(getTotalSalesAmount(method.value)),
            'No.of Refund': getTotalRefundCount(method.value),
            'Refund (RM)': formatAmount(getTotalRefundAomunt(method.value)),
            'Balance (RM)': formatAmount(Number(getTotalSalesAmount(method.value)) - Number(getTotalRefundAomunt(method.value))),
        })),
    ];


    exportToCSV(formattedRows, wTrans('public.report.payment_method_report').value);
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
            <template v-for="method in paymentMethods">
                <tr class="border-b border-grey-100">
                    <td class="w-[20%]">
                        <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden px-3 py-4">{{ method.text }}</span>
                    </td>
                    <td class="w-[16%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ getTotalSalesCount(method.value) }}</span>
                        </div>
                    </td>
                    <td class="w-[16%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getTotalSalesAmount(method.value)) }}</span>
                        </div>
                    </td>
                    <td class="w-[16%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ getTotalRefundCount(method.value) }}</span>
                        </div>
                    </td>
                    <td class="w-[16%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(getTotalRefundAomunt(method.value)) }}</span>
                        </div>
                    </td>
                    <td class="w-[16%]">
                        <div class="flex justify-start items-center gap-3 px-3">
                            <span class="text-grey-900 text-2xs font-medium text-ellipsis overflow-hidden py-4">{{ formatAmount(Number(getTotalSalesAmount(method.value)) - Number(getTotalRefundAomunt(method.value))) }}</span>
                        </div>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</template>