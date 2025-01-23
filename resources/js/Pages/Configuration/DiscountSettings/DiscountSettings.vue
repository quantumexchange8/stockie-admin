<script setup>
import Button from '@/Components/Button.vue';
import { PlusIcon } from '@/Components/Icons/solid';
import Modal from '@/Components/Modal.vue';
import SearchBar from '@/Components/SearchBar.vue';
import Toast from '@/Components/Toast.vue';
import { onMounted, ref, watch } from 'vue';
import AddDiscount from './Partials/AddDiscount.vue';
import DiscountTable from './Partials/DiscountTable.vue';
import BillDiscount from './Partials/BillDiscount.vue';

const isAddDiscountOpen = ref(false);
const isUnsavedChangesOpen = ref(false);
const details = ref([]);
const detailsBackup = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');
const isDirty = ref(false);

const discountDetails = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/configurations/discountDetails');
        details.value = response.data;
        detailsBackup.value = [...details.value];
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

const openAddDiscount = () => {
    isDirty.value = false;
    isAddDiscountOpen.value = true;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            isUnsavedChangesOpen.value = isDirty.value ? true : false;
            isAddDiscountOpen.value = !isDirty.value ? false : true;
            break;
        };
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isAddDiscountOpen.value = false;
            break;
        }

    }
}

watch(() => details.value, (newValue) => {
    details.value = newValue;
}, { immediate: true });

watch(() => searchQuery.value, (newValue) => {
    if (newValue === '') {
        details.value = [...detailsBackup.value];
        return;
    }

    const query = newValue.toLowerCase();

    details.value = details.value.map(group => {
        const filteredDetails = group.details.filter(item => {
            const groupName = group.discount.toLowerCase();
            const productName = item.product.toLowerCase();
            const productStartOn = item.start_on.toString().toLowerCase();
            const productEndOn = item.end_on.toString().toLowerCase();
            const productType = item.type.toLowerCase();
            const productBefore = item.before.toString().toLowerCase();
            const productAfter = item.after.toString().toLowerCase();
            return  groupName.includes(query) ||
                    productName.includes(query) ||
                    productStartOn.includes(query) ||
                    productEndOn.includes(query) ||
                    productType.includes(query) ||
                    productBefore.includes(query) ||
                    productAfter.includes(query) ;
        });

        return filteredDetails.length > 0 ? {...group, details: filteredDetails } : null;
    })
    .filter(group => group !== null);
}, { immediate: true });

onMounted(() => {
    discountDetails();
});
</script>

<template>
    <div class="flex flex-col max-w-full justify-end items-start gap-5 self-stretch">
        <div class="flex flex-col p-6 gap-6 self-stretch rounded-[5px] border border-solid border-primary-100">
            <div class="flex flex-col justify-center">
                <span class="text-primary-900 text-md font-medium">Discount Settings</span>
            </div>
            
            <Toast 
                inline
                severity="info"
                actionLabel="OK"
                summary="Looking to boost your sales?"
                detail="Offer your product at a lower price! Set up your discount here to attract more customers and increase sales."
                :closable=false
            />

            <div class="flex items-start gap-5 self-stretch">
                <SearchBar 
                    placeholder="Search"
                    :showFilter="false"
                    v-model="searchQuery"
                />

                <Button
                    type="button"
                    size="lg"
                    iconPosition="left"
                    class="!w-fit"
                    @click="openAddDiscount"
                >
                    <template #icon>
                        <PlusIcon />
                    </template>
                    New Discount
                </Button>
            </div>

            <DiscountTable 
                :details="details"
                @discountDetails="discountDetails"
            />
        </div>
        
        <BillDiscount />
    </div>


    <Modal
        :show="isAddDiscountOpen"
        :maxWidth="'md'"
        :closeable="true"
        :title="'Add Discount'"
        @close="() => closeModal('close')"
    >
        <AddDiscount 
            @close="closeModal"
            @isDirty="isDirty = $event"
            @discountDetails="discountDetails"
        />
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

