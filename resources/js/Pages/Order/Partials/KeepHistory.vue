<script setup>
import Button from '@/Components/Button.vue';
import DateInput from '@/Components/Date.vue';
import { UndetectableIllus } from '@/Components/Icons/illus';
import { CommentIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import TabView from '@/Components/TabView.vue';
import Tag from '@/Components/Tag.vue';
import { useCustomToast } from '@/Composables';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import { wTrans } from 'laravel-vue-i18n';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    customer: {
        type: Object,
        default: () => {}
    },
});

const tabs = ref([
    { key: 'All', title: wTrans('public.all'), disabled: false },
    { key: 'Keep', title: wTrans('public.keep'), disabled: false },
    { key: 'Served/Returned', title: wTrans('public.served_returned'), disabled: false },
    { key: 'Expired', title: wTrans('public.expired'), disabled: false },
    { key: 'Edited', title: wTrans('public.edited'), disabled: false },
    { key: 'Deleted', title: wTrans('public.deleted'), disabled: false },
]);
const keepHistory = ref([]);
const isReactivateOpen = ref(false);
const selectedItem = ref('');
const isUnsavedChangesOpen = ref(false);
const initialExpiry = ref('');
const historyDetailIsOpen = ref(false);
const selectedRecord = ref(null);
const form = useForm({
    id: '',
    expiry_date: '',
})
const { showMessage } = useCustomToast();

const formatHistoryStatus = (status) => {
    return status.toLowerCase().replace(/[/_]+/g, "-").replace(/^-+|-+$/g, " "); // Replace spaces, '/', and '_' with '-' | Remove leading or trailing '-'
};

const openReactivateModal = (item) => {
    selectedItem.value = item;
    form.expiry_date = selectedItem.value.keep_item.expired_to ? dayjs(selectedItem.value.keep_item.expired_to).format('DD/MM/YYYY') : '';
    initialExpiry.value = form.expiry_date;
    isReactivateOpen.value = true;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(form.expiry_date !== initialExpiry.value) isUnsavedChangesOpen.value = true;
            else isReactivateOpen.value = false; selectedItem.value = '';
            break;
        };
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        };
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isReactivateOpen.value = false;
            form.reset();
            selectedItem.value = '';
            break;
        }
    }
}

const reactivateExpiredItems = () => {
    form.id = selectedItem.value.id;
    try {
        form.post(route('reactivateExpiredItems'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                closeModal('leave');
                showMessage({ 
                    severity: 'success',
                    summary: 'Kept item has been reactivated successfully',
                    detail: "You’ve extended the expiration date for selected kept item.",
                });
                form.reset();
                getKeepHistory();
            },
        })
    } catch (error) {
        console.error(error);
    }
}

const tabsSlug = ref(
  tabs.value
    .map((tab) => formatHistoryStatus(tab.key))
    .filter((slug) => slug !== 'all')
);

const getKeepHistory = async () => {
    try {
        const response = await axios.get(route('orders.customer.keep.getCustomerKeepHistories', props.customer.id));
        keepHistory.value = response.data;
    }
    catch(errors){
        console.error(errors)
    } finally {

    }
};

const filteredHistoryByStatus = (status) => {
    if(!keepHistory.value) return [];
    const filteredArr = keepHistory.value.filter(history => {
        if (status === 'served-returned') {
            return formatHistoryStatus(history.status) === 'served' || formatHistoryStatus(history.status) === 'returned';
        } else {
            return formatHistoryStatus(history.status) === status;
        }
    });
    return filteredArr;
};

const getStatusVariant = (status) => {
    switch (status) {
        case 'Keep': return 'default';
        case 'Returned':
        case 'Served': return 'green'
        case 'Edited': return 'blue'
        case 'Deleted': return 'red'
        default: return 'grey'
    }
}; 

const getStatusTranslatedText = (status) => {
    let text = '';
    switch (status) {
        case 'Keep': return wTrans('public.keep').value;
        case 'Returned': return wTrans('public.returned').value;
        case 'Served': return wTrans('public.served').value;
        case 'Edited': return wTrans('public.edited').value;
        case 'Expired': return wTrans('public.expired').value;
        case 'Deleted': return wTrans('public.deleted').value;
    }    return text;
}; 

const openForm = (record) => {
    selectedRecord.value = record;
    historyDetailIsOpen.value = true;
}

const closeForm = () => {
    historyDetailIsOpen.value = false;
    setTimeout(() => {
        selectedRecord.value = null;
    }, 300);
}

onMounted(() => {
    getKeepHistory();
});
</script>

<template>
    <div class="w-full p-6 max-h-[calc(100dvh-4rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
        <TabView :tabs="tabs">
            <template #all>         
                <div class="w-full flex flex-col items-start py-6 self-stretch rounded-[5px]">
                    <template v-if="keepHistory.length > 0">
                        <div class="w-full flex items-center gap-3 self-stretch" v-for="item in keepHistory" :key="item.id">
                            <div class="w-full flex flex-col items-center gap-2 flex_[1_0_0] pb-6">
                                <div class="flex w-full px-[10px] py-1 items-center gap-[10px] self-stretch rounded-sm bg-primary-25">
                                    <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                                </div>
                                <div class="w-full flex justify-between items-center self-stretch" @click="openForm(item)">
                                    <div class="flex w-full self-stretch items-start gap-3">
                                        <div class="rounded-[1.5px] size-[60px] bg-primary-25">
                                            <img 
                                                :src="item.keep_item.image ? item.keep_item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt="KeepItemImage"
                                                class="size-full object-contain"
                                            >
                                        </div>
                                        <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                                            <div class="flex items-center gap-1 self-stretch">
                                                <span class="text-grey-400 text-2xs font-normal">{{ item.keep_item?.expired_to ? `${$t('public.expire_on')} ${dayjs(item.keep_item.expired_to).format('DD/MM/YYYY')}` : '' }}</span>
                                                <span class="w-1 h-1 bg-grey-900 rounded-full"></span>
                                                <span class="text-primary-900 text-2xs font-normal">{{ $t('public.keep_handled_by') }}</span>
                                                <img 
                                                    :src="item.waiter.image ? item.waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                    alt="KeepItemImage"
                                                    class="size-4 rounded-full object-contain"
                                                >
                                                <span class="text-primary-900 text-2xs font-normal">
                                                    {{ item.waiter.full_name }} {{ $t('public.at') }} 
                                                    <span class="font-bold">{{ item.status === 'Served' ? item.redeemed_to_table : item.kept_from_table }}</span>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 self-stretch">
                                                <Tag 
                                                    :variant="getStatusVariant(item.status)"
                                                    :value="getStatusTranslatedText(item.status)"
                                                    class="flex px-2.5 py-1.5 justify-center items-center gap-2.5"
                                                />
                                                <div class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">{{ item.keep_item.item_name }}</div>
                                            </div>
                                            <div class="flex flex-nowrap gap-x-1 items-start" v-if="item.keep_item.remark && item.status !== 'Deleted' && item.status !== 'Edited'">
                                                <CommentIcon class="flex-shrink-0 mt-1" />
                                                <span class="text-grey-900 text-xs font-normal">{{ item.keep_item.remark }}</span>
                                            </div>
                                            <div class="flex flex-nowrap gap-x-1 items-start" v-if="item.status === 'Deleted' || item.status === 'Edited'">
                                                <CommentIcon class="flex-shrink-0 mt-1" />
                                                <span class="text-grey-900 text-xs font-normal">{{ item.remark }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col justify-center items-end gap-3">
                                        <span class="text-primary-900 text-base font-medium whitespace-nowrap">{{ item.qty > item.cm ? `x ${item.qty}` : `${item.cm} cm`  }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="flex w-full flex-col items-center justify-center gap-5">
                            <UndetectableIllus />
                            <span class="text-primary-900 text-sm font-medium">{{ $t('public.empty.no_data') }}</span>
                        </div>
                    </template>
                </div>
            </template>
            <template
                v-for="tab in tabsSlug"
                :key="tab"
                v-slot:[tab]
            >
                <div class="w-full flex flex-col items-start py-6 self-stretch rounded-[5px]">
                    <template v-if="filteredHistoryByStatus(tab).length > 0">
                        <div class="w-full flex items-center gap-3 self-stretch" v-for="item in filteredHistoryByStatus(tab)" :key="item.id">
                            <div class="w-full flex flex-col items-center gap-2 flex_[1_0_0] pb-6">
                                <div class="flex w-full px-[10px] py-1 items-center gap-[10px] self-stretch rounded-sm bg-primary-25">
                                    <span class="text-primary-900 text-sm font-medium">{{ dayjs(item.created_at).format('DD/MM/YYYY, hh:mm A') }}</span>
                                </div>
                                <div class="w-full flex justify-between items-center self-stretch" @click="openForm(item)">
                                    <div class="flex w-full self-stretch items-start gap-3">
                                        <div class="rounded-[1.5px] size-[60px] bg-primary-25">
                                            <img 
                                                :src="item.keep_item.image ? item.keep_item.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                alt=""
                                                class="size-full object-contain"
                                            >
                                        </div>
                                        <div class="flex flex-col items-start flex-[1_0_0] self-stretch">
                                            <div class="flex items-center gap-1 self-stretch">
                                                <!-- <span class="text-grey-400 text-2xs font-normal">{{ item.keep_item.expired_to ? `Expire on ${dayjs(item.keep_item.expired_to).format('DD/MM/YYYY')}` : '' }}</span> -->
                                                <span class="text-grey-400 text-2xs font-normal">{{ item.keep_item.expired_to ? item.status === 'Edited' || item.status === 'Expired' 
                                                                                                                                    ? `${$t('public.expire_on')} ${dayjs(item.keep_date).format('DD/MM/YYYY')}`
                                                                                                                                    : `${$t('public.expire_on')} ${dayjs(item.keep_item.expired_to).format('DD/MM/YYYY')}` 
                                                                                                                                : '' }}</span>
                                                <span class="w-1 h-1 bg-grey-900 rounded-full"></span>
                                                <span class="text-primary-900 text-2xs font-normal">{{ $t('public.keep_handled_by') }}</span>
                                                <img 
                                                    :src="item.waiter.image ? item.waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                    alt="KeepItemImage"
                                                    class="size-4 rounded-full"
                                                >
                                                <span class="text-primary-900 text-2xs font-normal">
                                                    {{ item.waiter.full_name }} {{ $t('public.at') }}  
                                                    <span class="font-bold">{{ item.status === 'Served' ? item.redeemed_to_table : item.kept_from_table }}</span>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 self-stretch">
                                                <Tag 
                                                    :variant="getStatusVariant(item.status)"
                                                    :value="getStatusTranslatedText(item.status)"
                                                    class="flex px-2.5 py-1.5 justify-center items-center gap-2.5"
                                                />
                                                <div class="line-clamp-1 overflow-hidden text-grey-900 text-ellipsis text-sm font-medium">{{ item.keep_item.item_name }}</div>
                                            </div>
                                            <div class="flex flex-nowrap gap-x-1 items-start" v-if="item.keep_item.remark && item.status !== 'Deleted' && item.status !== 'Edited'">
                                                <CommentIcon class="flex-shrink-0 mt-1" />
                                                <span class="text-grey-900 text-xs font-normal">{{ item.keep_item.remark }}</span>
                                            </div>
                                            <div class="flex flex-nowrap gap-x-1 items-start" v-if="item.status === 'Deleted' || item.status === 'Edited' && item.remark">
                                                <CommentIcon class="flex-shrink-0 mt-1" />
                                                <span class="text-grey-900 text-xs font-normal">{{ item.remark }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col justify-center items-end gap-3">
                                        <span class="text-primary-900 text-base font-medium whitespace-nowrap">{{ item.qty > item.cm ? `x ${item.qty}` : `${item.cm} cm`  }}</span>
                                    </div>
                                </div>
                                <!-- <Button
                                    :variant="'tertiary'"
                                    :type="'button'"
                                    :size="'md'"
                                    :disabled="item.keep_date < item.keep_item.expired_to"
                                    @click="openReactivateModal(item)"
                                    v-if="item.status === 'Expired'"
                                >
                                    Reactivate
                                </Button> -->
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="flex w-full flex-col items-center justify-center gap-5">
                            <UndetectableIllus />
                            <span class="text-primary-900 text-sm font-medium">{{ $t('public.empty.no_data') }}</span>
                        </div>
                    </template>
                </div>
            </template>
        </TabView>
    </div>


    <!-- View keep history details -->
    <Modal
        :title="$t('public.detail')"
        :show="historyDetailIsOpen" 
        :maxWidth="'2xs'" 
        @close="closeForm"
    >
        <template v-if="selectedRecord">
            <div class="flex flex-col gap-y-6 items-start max-h-[calc(100dvh-12rem)] overflow-y-auto scrollbar-thin scrollbar-webkit">
                <div class="flex flex-col items-start gap-y-4 self-stretch">
                    <div class="flex flex-col gap-y-5 items-start self-stretch">
                        <div class="flex flex-col gap-y-1 items-start">
                            <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.current_status') }}</p>
                            <Tag :value="getStatusTranslatedText(selectedRecord.status)" :variant="getStatusVariant(selectedRecord.status)" />
                        </div>
                        
                        <div class="flex flex-col gap-y-1 items-start">
                            <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.kept_item') }}</p>
                            <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedRecord.keep_item.item_name }}</p>
                        </div>
                    
                        <div class="flex flex-col gap-y-1 items-start">
                            <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.quantity_cm') }}</p>
                            <p class="text-grey-900 text-base font-bold self-stretch">
                                <template v-if="selectedRecord.qty > selectedRecord.cm">{{ `x${selectedRecord.qty}` }}</template>
                                <template v-else>{{ `${selectedRecord.cm} cm` }}</template>
                            </p>
                        </div>
                    
                        <div class="flex flex-col gap-y-1 items-start">
                            <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.expire_on') }}</p>
                            <p class="text-grey-900 text-base font-bold self-stretch">{{ dayjs(selectedRecord.keep_item.expired_to).format('DD/MM/YYYY') }}</p>
                        </div>
                    
                        <div class="flex flex-col gap-y-1 items-start">
                            <p class="text-grey-900 text-base font-normal self-stretch">{{ $t('public.remark') }}</p>
                            <p class="text-grey-900 text-base font-bold self-stretch">{{ selectedRecord.remark ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-y-4 p-2 items-start self-stretch bg-grey-50">
                        <div class="flex flex-col items-start gap-y-1 self-stretch">
                            <p class="text-black text-sm font-semibold">
                                {{ `${selectedRecord.kept_from_table}, ${$t('public.at')} ${dayjs(selectedRecord.keep_date).format('DD/MM/YYYY, HH:mm')}` }}
                            </p>
                            <div class="flex items-center gap-1 self-stretch">
                                <p class="text-grey-700 text-xs font-normal">{{ $t('public.kept_by') }}: {{ selectedRecord.keep_item.waiter.full_name }}</p>
                                <img 
                                    :src="selectedRecord.keep_item.waiter.image ? selectedRecord.keep_item.waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt="WaiterProfileImage"
                                    class="size-4 rounded-full object-contain"
                                >
                            </div>
                        </div>

                        <div v-if="selectedRecord.status === 'Served'" class="flex flex-col items-start gap-y-1 self-stretch">
                            <p class="text-black text-sm font-semibold">
                                {{ `${selectedRecord.redeemed_to_table}, ${$t('public.at')} ${dayjs(selectedRecord.created_at).format('DD/MM/YYYY, HH:mm')}` }}
                            </p>
                            <div class="flex items-center gap-1 self-stretch">
                                <p class="text-grey-700 text-xs font-normal">{{ $t('public.served_by') }}: {{ selectedRecord.waiter.full_name }}</p>
                                <img 
                                    :src="selectedRecord.waiter.image ? selectedRecord.waiter.image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                    alt="WaiterProfileImage"
                                    class="size-4 rounded-full object-contain"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Modal>

    <!-- Extend expiration date -->
    <Modal
        :title="'Extend to'"
        :maxWidth="'xs'"
        :show="isReactivateOpen"
        @close="closeModal('close')"
    >
        <form @submit.prevent="reactivateExpiredItems">
            <div class="flex flex-col items-start gap-6">
                <DateInput 
                    :placeholder="'DD/MM/YYYY'"
                    :minDate="new Date(form.expiry_date)"
                    v-model="form.expiry_date"
                />
                <div class="flex pt-3 justify-center items-end gap-4 self-stretch">
                    <Button
                        :variant="'tertiary'"
                        :type="'button'"
                        :size="'lg'"
                        @click="closeModal('close')"
                    >
                        Cancel
                    </Button>
                    <Button
                        :variant="'primary'"
                        :type="'submit'"
                        :size="'lg'"
                        :disabled="form.expiry_date === ''"
                    >
                        Confirm
                    </Button>
                </div>
            </div>
        </form>

        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        />
    </Modal>
</template>
