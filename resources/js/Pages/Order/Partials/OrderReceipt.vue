<script setup>
import dayjs from 'dayjs';
import { ref, computed, onMounted } from 'vue'
import Table from '@/Components/Table.vue';
import { WineBackgroundIllus1 } from '@/Components/Icons/illus';
import StockieLogo from "../../../../../public/favicon.ico";
import QRCodeImage from "../../../../assets/images/stockie-admin-qr.png";
import { usePhoneUtils } from '@/Composables/index.js';
import QRCodeVue3 from "qrcode-vue3";
import md5 from 'crypto-js/md5';
import html2pdf from 'html2pdf.js'
import html2canvas from 'html2canvas'

const props = defineProps({
    orderId: Number,
});

const { formatPhone } = usePhoneUtils();

const order = ref('');
const taxes = ref('');
const merchant = ref('');
const payout = ref();
const receiptContent = ref(null);
const receiptHeight = ref(314);
const receiptImage = ref(null);

const getOrderPaymentDetails = async () => {
    try {
        const response = await axios.get(`/order-management/getOrderPaymentDetails/${props.orderId}`);
        order.value = response.data.order;
        taxes.value = response.data.taxes;
        merchant.value = response.data.merchant;

        const orderItemsCount = order.value.order_items?.filter((item) => item.status === 'Served' && item.item_qty > 0).length;
        
        if (orderItemsCount > 1) {
            receiptHeight.value += (8 * (orderItemsCount - 1));
        }
        
        if (order.value.payment?.point_history) {
            receiptHeight.value += 17;
        }
        
        if ((!!order.value.payment.discount_id && !!order.value.payment.voucher) || order.value.payment.applied_discounts?.length > 0) {
            receiptHeight.value += 35;
        }

    } catch (error) {
        console.error(error);
    } finally {

    }
}

const getPayoutDetails = async () => {
    try {
        const response = await axios.get('/getPayoutDetails');

        payout.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {

    }
}

onMounted(() => {
    getOrderPaymentDetails();
    getPayoutDetails();
});

const invoiceOrderItemsColumns = ref([
    {field: 'item_qty', header: 'Qty', width: '10', sortable: false},
    {field: 'product_name', header: 'Item', width: '55', sortable: false},
    {field: 'amount', header: 'Amt (RM)', width: '35', sortable: false},
]);

const discountSummaryColumns = ref([
    {field: 'discount_summary', header: 'Discount Summary', width: '60', sortable: false},
    {field: 'discount_amount', header: 'Amt (RM)', width: '40', sortable: false},
]);

const rowType = {
    rowGroups: false,
    expandable: false,
    groupRowsBy: '',
};

const hasVoucherApplied = computed(() => {
    if (!order.value) return false;
    return (!!order.value.payment.discount_id && !!order.value.payment.voucher) || order.value.payment.applied_discounts?.length > 0;
})

const appliedDiscounts = computed(() => {
    let discountSummary = [];

    if (!!order.value.payment.discount_id && !!order.value.payment.voucher) {
        const voucherRate = order.value.payment.voucher.reward_type === 'Discount (Percentage)' ? `${order.value.payment.voucher.discount}%` : `RM ${order.value.payment.voucher.discount}`;
        discountSummary.push({
            discount_summary: `${voucherRate} Discount (Entry Reward for ${order.value.customer.rank.name})`,
            discount_amount: Number(order.value.payment.discount_amount ?? 0).toFixed(2)
        });
    }

    order.value.payment?.applied_discounts?.forEach((d) => {
        let discountedAmount = d.discount_type === 'amount'
            ? d.discount_rate
            : order.value.payment.total_amount * (d.discount_rate / 100);

        discountSummary.push({
            discount_summary: d.name,
            discount_amount: Number(discountedAmount ?? 0).toFixed(2)
        })
    });

    return discountSummary;
});

// const sstAmount = computed(() => {
//     return (parseFloat(order.value.amount ?? 0) * (6 / 100));
// })

// const serviceChargeAmount = computed(() => {
//     return (parseFloat(order.value.amount ?? 0) * (10 / 100)) ?? 0.00;
// })

// const totalEarnedPoints = computed(() => {
//     return order.value.order_items.filter((item) => item.status === 'Served').reduce((total, item) => total + item.point_earned, 0) ?? 0.00;
// })

const orderTableNames = computed(() => order.value.order_table?.map((orderTable) => orderTable.table.table_no).join(', ') ?? '-');

const generateECode = (receipt_no, merchant, secret) => {
    return md5(`${receipt_no}${merchant}${secret}`).toString();
}

function copyStyles(sourceDoc, targetDoc) {
    for (const styleSheet of sourceDoc.styleSheets) {
        try {
            if (styleSheet.cssRules) {
                const newStyleEl = targetDoc.createElement('style');
                for (const rule of styleSheet.cssRules) {
                    newStyleEl.appendChild(targetDoc.createTextNode(rule.cssText));
                }
                targetDoc.head.appendChild(newStyleEl);
            }
        } catch (e) {
            // Skips stylesheets we can't access (e.g., from a CDN)
            // console.warn('Could not access stylesheet:', styleSheet.href);
        }
    }
}

const printReceipt = async () => {
    try {
        await Promise.all([
            getOrderPaymentDetails(),
            getPayoutDetails()
        ]);

    } catch (err) {
        console.error("Failed to fetch receipt data:", err);
        return; // Abort printing
    }

    // Using html2pdf
    // const element = receiptContent.value;
    // if (!element) return;

    // const images = element.querySelectorAll("img");
    // const imagePromises = Array.from(images).map(img => {
    //     return new Promise(resolve => {
    //     if (img.complete) {
    //         resolve();
    //     } else {
    //         img.onload = resolve;
    //         img.onerror = resolve;
    //     }
    //     });
    // });
    
    // Promise.all(imagePromises).then(() => {
    //     html2pdf().from(element).set({
    //         margin: 0,
    //         filename: `receipt-${props.orderId}.pdf`,
    //         image: { type: 'jpeg', quality: 0.98 },
    //         html2canvas: { scale: 3, useCORS: true },
    //         jsPDF: {
    //             unit: 'mm',
    //             format: [74, receiptHeight.value], // 80mm wide x A4 height
    //             orientation: 'portrait'
    //         }
    //     }).save()
    // });

    const printContents = receiptContent.value;

    if (!printContents) return;

    // Using window.print()
    // // Clone the content to avoid altering the original DOM
    // const clonedContent = printContents.cloneNode(true);

    // // Create a hidden iframe
    // const iframe = document.createElement('iframe');
    // iframe.style.position = 'fixed';
    // iframe.style.right = '0';
    // iframe.style.bottom = '0';
    // iframe.style.width = '0';
    // iframe.style.height = '0';
    // iframe.style.border = '0';
    // document.body.appendChild(iframe);

    // const iframeDoc = iframe.contentDocument || iframe.contentWindow?.document;
    // if (!iframeDoc) return;

    // // Build a minimal HTML document
    // iframeDoc.open();
    // iframeDoc.write('<!DOCTYPE html><html><head><title>Receipt</title>');
    // // Optional: Inject custom styles if needed
    // iframeDoc.write(`
    //     <style>
    //         @page {
    //             size: 74mm auto;
    //             margin: 0;
    //         }
    //         body {
    //             margin: 0;
    //             padding: 0;
    //             font-family: sans-serif;
    //         }
    //         .receipt-container {
    //             width: 74mm;
    //             font-size: 12px;
    //         }
    //     </style>
    // `);
    // iframeDoc.write('</head><body></body></html>');
    // iframeDoc.close();

    // // Wait for the iframe to be fully loaded
    // iframe.onload = () => {
    //     iframeDoc.body.appendChild(clonedContent);
    //     copyStyles(document, iframeDoc);

    //     setTimeout(() => {
    //         iframe.contentWindow?.focus();
    //         iframe.contentWindow?.print();
    //         document.body.removeChild(iframe);
    //     }, 250);
    // };

    const canvas = await html2canvas(receiptContent.value, {
        scale: 2, // for better quality;
        backgroundColor: '#ffffff',
    });

    canvas.toBlob((blob) => {
        if (!blob) return;

        const formData = new FormData();
        formData.append('receipt_image', blob);

        // ✅ Create object URL for preview
        receiptImage.value = URL.createObjectURL(blob);

        // Optionally send to backend:
        // await axios.post('/api/receipt-upload', formData)
    }, 'image/png');
}

defineExpose({
    printReceipt
});

</script>

<style>
@media print {
    @page {
        size: 74mm auto;
        margin: 0;
    }
    body {
        margin: 0;
        padding: 0;
    }
    .receipt-container {
        width: 80mm;
        font-size: 12px;
    }
    button {
        display: none;
    }
}
</style>

<template>
    <div v-if="receiptImage">
        <h3>Preview:</h3>
        <img :src="receiptImage" alt="Receipt Preview" class="w-full max-w-md" />
  </div>
    <div ref="receiptContent" class="w-full relative flex flex-col gap-6">
        <div class="relative flex flex-col gap-[100px]">
            <WineBackgroundIllus1 class="absolute inset-x-0 -top-10 !w-full z-0"/>

            <div class="relative z-10 flex flex-col pl-5 pr-6 pt-6 gap-y-4">
                <div class="flex flex-col gap-y-10 justify-center">
                    <div class="flex w-full items-start px-1">
                        <div class="flex flex-col w-10/12 justify-center items-start pr-1">
                            <p class="text-white text-sm font-medium">{{ merchant?.merchant_name }}</p>
                            <p class="text-white text-2xs font-normal">{{ merchant?.merchant_address_line1 }}, {{ merchant?.merchant_address_line2 }}</p>
                            <p class="text-white text-2xs font-normal">{{ merchant?.postal_code }} {{ merchant?.area }}, {{ merchant?.state }}</p>

                            <p class="text-white text-2xs font-normal">{{ formatPhone(merchant?.merchant_contact) }}</p>
                        </div>
                        <img 
                            src="../../../../../public/merchant/logo.jpg" 
                            alt="MerchantLogo" 
                            width="32" 
                            height="32" 
                            class="flex-shrink-0 w-2/12 rounded-full"
                        />
                        <!-- <img 
                            :src="merchant?.image ? merchant?.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                            alt="MerchantLogo" 
                            width="32" 
                            height="32" 
                            class="flex-shrink-0 w-2/12 rounded-full"
                            crossorigin="anonymous"
                        /> -->
                    </div>
    
                    <div class="flex flex-col rounded-md items-center px-4 pb-2">
                        <p class="text-primary-800 text-sm font-medium">TOTAL SPENT</p>
                        <p class="text-primary-800 text-base font-medium whitespace-nowrap !leading-none">RM <span class="text-[36px]">{{ order.payment?.grand_total ?? '0.00' }}</span></p>
                    </div>
                </div>
    
                <div class="flex flex-col gap-y-1.5 items-start self-stretch">
                    <p class="text-primary-950 text-sm font-medium self-stretch">{{ dayjs(order.payment?.receipt_end_date).format('DD/MM/YYYY, HH:mm') ?? '00/00/0000, 00:00' }}</p>
                    <div class="flex gap-x-3 py-4 items-start justify-evenly self-stretch">
                        <div class="w-5/12 flex flex-col justify-between items-start border-r border-primary-950">
                            <p class="text-primary-950 text-2xs font-light">Receipt No.</p>
                            <p class="text-primary-950 text-2xs font-medium break-all">{{ order.payment?.receipt_no ?? '-' }}</p>
                        </div>
                        <div class="w-2/12 flex flex-col justify-between items-start border-r border-primary-950">
                            <p class="text-primary-950 text-2xs font-light">Pax</p>
                            <p class="text-primary-950 text-2xs font-medium">{{ order.payment?.pax ?? '-' }}</p>
                        </div>
                        <div class="w-5/12 flex flex-col justify-between items-start">
                            <p class="text-primary-950 text-2xs font-light">Table No.</p>
                            <p class="text-primary-950 text-2xs font-medium">{{ orderTableNames }}</p>
                        </div>
                    </div>
                </div>
    
                <Table 
                    :variant="'list'"
                    :rows="order.order_items?.filter((item) => item.status === 'Served' && item.item_qty > 0)"
                    :columns="invoiceOrderItemsColumns"
                    :rowType="rowType"
                    :paginator="false"
                    class="[&>div>div>table]:gap-1 [&>div>div>table>thead>tr>th>div>span]:text-2xs [&>div>div>table>thead>tr>th]:items-center [&>div>div>table>thead>tr>th]:!p-0 [&>div>div>table>tbody>tr>td]:!py-0
                        [&>div>div>table>tbody>tr>td]:!px-0 [&>div>div>table>thead>tr>th]:!border-b [&>div>div>table>thead>tr>th]:!border-grey-950 [&>div>div>table>thead>tr>th:last-child>div]:!justify-end"
                >
                    <template #empty>
                        <span class="text-primary-900 text-xs font-medium">There is no item for this order.</span>
                    </template>
                    <template #item_qty="row">
                        <div class="flex items-start h-full text-grey-950 text-xs font-medium">{{ row.item_qty }}</div>
                    </template>
                    <template #product_name="row">
                        <div class="w-full flex flex-col items-start self-stretch gap-y-3">
                            <p class="text-grey-950 text-xs font-medium">
                                {{ row.type !== 'Normal' ? `(${row.type})` : '' }}
                                {{ row.product.bucket === 'set' ? '(Set) ' : '' }}
                                {{ row.product.product_name }}
                            </p>
                            <p class="text-grey-950 text-xs font-medium" v-if="row.discount_id">{{ `${row.product_discount.discount.name} Discount` }}</p>
                        </div>
                    </template>
                    <template #amount="row">
                        <div class="w-full flex flex-col items-end self-stretch gap-y-3" v-if="row.discount_id">
                            <p class="text-grey-950 text-xs font-medium text-right">{{ parseFloat(row.type === 'Normal' ? row.amount_before_discount : 0).toFixed(2) }}</p>
                            <p class="text-grey-950 text-xs font-medium">- {{ parseFloat(row.discount_amount).toFixed(2) }}</p>
                        </div>
                        <p class="text-grey-950 text-xs font-medium text-right w-full" v-else>{{ parseFloat(row.amount).toFixed(2) }}</p>
                    </template>
                </Table>
    
                <div class="flex flex-col items-center self-stretch">
                    <div class="flex items-start justify-between self-stretch">
                        <p class="text-primary-950 text-xs font-light">Subtotal</p>
                        <p class="text-primary-950 text-xs font-normal">{{ parseFloat(order.payment?.total_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch" v-if="order.payment?.bill_discounts && order.payment?.bill_discount_total > 0">
                        <p class="text-primary-950 text-xs font-light">Bill Discount</p>
                        <p class="text-primary-950 text-xs font-normal">- {{ parseFloat(order.payment?.bill_discount_total ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch" v-if="order.payment?.voucher">
                        <p class="text-primary-950 text-xs font-light">Voucher Discount {{ order.payment?.voucher.reward_type === 'Discount (Percentage)' ? `(${order.payment?.voucher.discount}%)` : `` }}</p>
                        <p class="text-primary-950 text-xs font-normal">- {{ parseFloat(order.payment?.discount_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch" v-if="order.payment?.service_tax_amount > 0">
                        <p class="text-primary-950 text-xs font-light">Service Tax ({{ Math.round((order.payment?.service_tax_amount / order.payment?.total_amount) * 100) }}%)</p>
                        <p class="text-primary-950 text-xs font-normal">{{ parseFloat(order.payment?.service_tax_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch" v-if="order.payment?.sst_amount > 0">
                        <p class="text-primary-950 text-xs font-light">SST ({{ Math.round((order.payment?.sst_amount / order.payment?.total_amount) * 100) }}%)</p>
                        <p class="text-primary-950 text-xs font-normal">{{ parseFloat(order.payment?.sst_amount ?? 0).toFixed(2) }}</p>
                    </div>
                    <div class="flex items-start justify-between self-stretch">
                        <p class="text-primary-950 text-xs font-light">Rounding</p>
                        <p class="text-primary-950 text-xs font-normal">{{ Math.sign(order.payment?.rounding) === -1 ? '-' : '' }} {{ parseFloat(Math.abs(order.payment?.rounding ?? 0)).toFixed(2) }}</p>
                    </div>
                </div>
    
                <template v-if="order.payment?.point_history">
                    <div class="border-y-2 border-dashed border-primary-950 h-2"></div>
                    <div class="flex flex-col items-center self-stretch">
                        <div class="flex items-start justify-between self-stretch">
                            <p class="text-primary-950 text-xs font-light">Points Earned</p>
                            <p class="text-primary-950 text-xs font-normal">{{ order.payment?.point_history.amount }}</p>
                        </div>
                        <div class="flex items-start justify-between self-stretch">
                            <p class="text-primary-950 text-xs font-light">Points Balance</p>
                            <p class="text-primary-950 text-xs font-normal">{{ order.payment?.point_history.new_balance }}</p>
                        </div>
                    </div>
                </template>
    
                <template v-if="hasVoucherApplied">
                    <div class="border-y-2 border-dashed border-primary-950 h-2"></div>
                    <Table 
                        :variant="'list'"
                        :rows="appliedDiscounts"
                        :columns="discountSummaryColumns"
                        :rowType="rowType"
                        :paginator="false"
                        class="[&>div>div>table]:gap-1 [&>div>div>table>thead>tr>th>div>span]:text-2xs [&>div>div>table>thead>tr>th]:items-center [&>div>div>table>thead>tr>th]:!p-0 [&>div>div>table>tbody>tr>td]:!py-0
                        [&>div>div>table>tbody>tr>td]:!px-0 [&>div>div>table>thead>tr>th]:!border-b [&>div>div>table>thead>tr>th]:!border-grey-950 [&>div>div>table>thead>tr>th:last-child>div]:!justify-end"
                    >
                        <template #discount_summary="row">
                            <p class="text-grey-950 text-2xs font-medium">
                                {{ row.discount_summary }}
                            </p>
                        </template>
                        <template #discount_amount="row">
                            <p class="text-grey-950 justify-end text-xs font-medium text-right w-full">- RM {{ row.discount_amount }}</p>
                        </template>
                    </Table>
                </template>
    
                <div class="border-y-2 border-dashed border-primary-950 h-2"></div>
                <div v-if="payout" class="flex items-center justify-center">
                    <!-- <img :src="QRCodeImage" alt="QRCodeImage" width="200" height="200"/> -->
                    <!-- generate qr -->
                    <QRCodeVue3 
                        :width="200"
                        :height="200"
                        :value="`${payout?.url}invoice?invoice_no=${order.payment?.receipt_no}&merchant_id=${payout?.merchant_id}&amount=${order.payment?.grand_total}&eCode=${generateECode(
                            order.payment?.receipt_no,
                            payout?.merchant_id,
                            payout?.api_key,
                        )}`"
                        :dotsOptions="{
                            type: 'square',
                            color: '#7e171b',
                        }"
                        :cornersSquareOptions="{ type: 'square', color: '#7e171b' }"
                        :cornersDotOptions="{ type: undefined, color: '#7e171b' }"
                    />
                </div>
    
                <div class="flex flex-col items-center">
                    <p class="text-primary-950 text-sm font-medium">Scan QR below to request your</p>
                    <p class="text-primary-950 text-sm font-semibold">e-Invoice</p>
                </div>
    
            </div>
        </div>

        <div class="flex flex-col gap-2 pb-4 pt-0 justify-center items-center bg-[#7E171B]">
            <p class="text-primary-950 text-base font-medium">Thank you for your visit!</p>
            <div class="flex items-center text-primary-950 text-2xs font-normal gap-1">
                <p>Order invoice generated by</p>
                <img 
                    :src="StockieLogo" 
                    alt="STOXPOSLogo" 
                    width="16" 
                    height="16" 
                    class="ml-1 mr-1 flex-shrink-0"
                />
                <span class="font-extrabold">STOXPOS</span>
            </div>
        </div>
    </div>
</template>
