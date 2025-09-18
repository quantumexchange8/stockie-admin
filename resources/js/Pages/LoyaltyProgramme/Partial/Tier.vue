<script setup>
import { computed, ref } from "vue";
import { EmptyTierIllus } from "@/Components/Icons/illus.jsx";
import Modal from "@/Components/Modal.vue";
import Table from "@/Components/Table.vue";
import Button from "@/Components/Button.vue";
import SearchBar from "@/Components/SearchBar.vue";
import AddTier from "./AddTier.vue";
import { FilterMatchMode } from 'primevue/api';
import { PlusIcon, EditIcon, DeleteIcon } from '@/Components/Icons/solid';
import EditTier from "./EditTier.vue";
import { wTrans } from "laravel-vue-i18n";

const props = defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    products: {
        type: Array,
    },
    rowType: {
        type: Object,
        required: true,
    },
    actions: {
        type: Object,
        default: () => {},
    },
    rowsPerPage: {
        type: Number,
        required: true,
    },
    logos: {
        type: Array,
        default: () => {},
    }
});
const isModalOpen = ref(false);
const editTierFormIsOpen = ref(false);
const deleteTierFormIsOpen = ref(false);
const selectedTier = ref(null);
const isDirty = ref(false);
const isUnsavedChangesOpen = ref(false);
const searchQuery = ref('');

const openModal = () => {
    isDirty.value = false;
    isModalOpen.value = true;
};

// const closeModal = () => {
//     isModalOpen.value = false;
// };

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                isModalOpen.value = false;
                editTierFormIsOpen.value = false;
            }
            break;
        }
        case 'stay': {
            isUnsavedChangesOpen.value = false; 
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            isModalOpen.value = false;
            editTierFormIsOpen.value = false;
        }
    }
}

const showEditTierForm = (tier) => {
    isDirty.value = false;
    selectedTier.value = tier;
    editTierFormIsOpen.value = true;
}

const hideEditTierForm = () => {
    editTierFormIsOpen.value = false;
    setTimeout(() => {
        selectedTier.value = null;
    }, 300);
}

const showDeleteTierForm = (id) => {
    selectedTier.value = id;
    deleteTierFormIsOpen.value = true;
}

const hideDeleteTierForm = () => {
    deleteTierFormIsOpen.value = false;
    setTimeout(() => {
        selectedTier.value = null;
    }, 300);
}

const formatAmount = (num) => {
    var str = num.toString().split('.');

    if (str[0].length >= 4) {
        str[0] = str[0].replace(/(\d)(?=(\d{3})+$)/g, '$1,');
    }

    if (str[1] && str[1].length >= 5) {
        str[1] = str[1].replace(/(\d{3})/g, '$1 ');
    }

    return str.join('.');
}

const getTranslatedType = (merged_type) => {
    // split raw merged types
    const typeArr = merged_type.split(', ');

    // translate without mutating original
    const translatedTypes = typeArr.map((type) => {
        switch (type) {
            case 'Discount (Amount)': return wTrans('public.amount_discount').value;
            case 'Discount (Percentage)': return wTrans('public.percent_discount').value;
            case 'Free Item': return wTrans('public.free_item').value;
            case 'Bonus Point': return wTrans('public.bonus_point').value;
            default: return type;
        }
    });

    return translatedTypes.join(', ');
}

const filteredRows = computed(() => {
    if (!searchQuery.value) return props.rows;

    const query = searchQuery.value.toLowerCase();
    
    return props.rows.filter(row =>
        row.name.toLowerCase().includes(query) ||
        row.min_amount.toString().toLowerCase().includes(query) ||
        row.merged_reward_type.toLowerCase().includes(query) ||
        row.member.toString().toLowerCase().includes(query)
    );
});

const totalPages = computed(() => {
    return Math.ceil(filteredRows.value.length / props.rowsPerPage);
})

</script>

<template>
    <div class="flex flex-col p-6 gap-5 rounded-[5px] border border-red-100 overflow-y-auto">
        <span class="text-md font-medium text-primary-900 whitespace-nowrap w-full">{{ $t('public.loyalty.current_tier_list') }}</span>
        <div class="flex flex-col gap-5">
            <div class="flex gap-5 flex-wrap sm:flex-nowrap">
                <SearchBar 
                    :placeholder="$t('public.search')"
                    :showFilter="false"
                    v-model="searchQuery"
                />

                <Button
                    :type="'button'"
                    :size="'lg'"
                    :iconPosition="'left'"
                    class="md:w-[144px] flex items-center gap-2"
                    @click="openModal"
                >
                    <template #icon>
                        <PlusIcon />
                    </template>
                    {{ $t('public.action.new_tier') }}
                </Button>
            </div>
            <!--CurrentTierTable-->
            <Table
                :variant="'list'"
                :rows="filteredRows"
                :totalPages="totalPages"
                :columns="columns"
                :rowsPerPage="rowsPerPage"
                :actions="actions"
                :rowType="rowType"
                minWidth="min-w-[1070px]"
            >
                <template #empty>
                    <div class="flex flex-col gap-5 items-center">
                        <EmptyTierIllus/>
                        <span class="text-primary-900 text-sm font-medium">{{ $t('public.empty.no_tier') }}</span>
                    </div>
                </template>
                <template #editAction="row">
                    <EditIcon
                        class="w-6 h-6 text-primary-900 hover:text-primary-800 cursor-pointer"
                        @click.stop.prevent="showEditTierForm(row)"
                    />
                </template>
                <!-- <template #deleteAction="row">
                    <DeleteIcon
                        class="w-6 h-6 block transition duration-150 ease-in-out text-primary-600 hover:text-primary-700 cursor-pointer"
                            @click.stop.prevent="showDeleteTierForm(row.id)"
                    />
                </template> -->
                <template #icon="row">
                    <img 
                        :src="row.icon ? row.icon : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                        alt="TierIconImage"
                        class="size-6 rounded-full"
                    />
                </template>
                <template #min_amount="row">
                    <span class="text-primary-900 text-sm font-medium">RM {{ formatAmount(row.min_amount) }}</span>
                </template>
                <template #merged_reward_type="row">
                    <span class="text-primary-900 text-sm font-medium overflow-hidden text-ellipsis">{{ row.merged_reward_type ? getTranslatedType(row.merged_reward_type) : '-' }}</span>
                </template>
                <template #member="row">
                    <span class="">{{ row.member ?? 0 }}</span>
                </template>
            </Table>
            <Modal
                :show="isModalOpen"
                :title="$t('public.loyalty.add_new_tier')"
                :maxWidth="'md'"
                @close="closeModal('close')"
            >
                <AddTier 
                    :products="products"
                    :logos="logos" 
                    @isDirty="isDirty=$event"
                    @close="closeModal" 
                />
                <Modal
                    :unsaved="true"
                    :maxWidth="'2xs'"
                    :withHeader="false"
                    :show="isUnsavedChangesOpen"
                    @close="closeModal('stay')"
                    @leave="closeModal('leave')"
                >
                </Modal>
            </Modal>
            <Modal 
                :title="$t('public.loyalty.edit_tier')"
                :show="editTierFormIsOpen" 
                :maxWidth="'md'" 
                @close="closeModal('close')"
            >
                <template v-if="selectedTier">
                    <EditTier
                        :tier="selectedTier"
                        :logos="logos"
                        :inventoryItems="selectedTier.ranking_rewards" 
                        :items="products"
                        @isDirty="isDirty=$event"
                        @close="closeModal"
                    />
                    <Modal
                        :unsaved="true"
                        :maxWidth="'2xs'"
                        :withHeader="false"
                        :show="isUnsavedChangesOpen"
                        @close="closeModal('stay')"
                        @leave="closeModal('leave')"
                    >
                    </Modal>
                </template>
            </Modal>
            <Modal 
                :show="deleteTierFormIsOpen" 
                :maxWidth="'2xs'" 
                :closeable="true" 
                :deleteConfirmation="true"
                :deleteUrl="actions.delete(selectedTier)"
                :confirmationTitle="$t('public.loyalty.delete_tier')"
                :confirmationMessage="$t('public.loyalty.delete_tier_message')"
                @close="hideDeleteTierForm"
                v-if="selectedTier"
            />
        </div>
    </div>

</template>
