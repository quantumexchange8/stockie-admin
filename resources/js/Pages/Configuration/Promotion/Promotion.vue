<script setup>
import { computed, ref, watch } from 'vue'
import { Head } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue'
import Button from '@/Components/Button.vue'
import SearchBar from '@/Components/SearchBar.vue'
import { PlusIcon, TimesIcon } from '@/Components/Icons/solid';
import CreatePromotionForm from './Partials/CreatePromotionForm.vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import Active from "@/Pages/Configuration/Promotion/Partials/Active.vue"
import Inactive from "@/Pages/Configuration/Promotion/Partials/Inactive.vue"
import Toast from '@/Components/Toast.vue';

const props = defineProps({
    ActivePromotions: Array,
    InactivePromotions: Array,
})

const searchQuery = ref('');
const filteredActivePromotions = ref(props.ActivePromotions);
const filteredInactivePromotions = ref(props.InactivePromotions);
const isUnsavedChangesOpen = ref(false);
const isDirty = ref(false);
const createFormIsOpen = ref(false);

// const activePromotionsCount = computed(() => {
//     return props.ActivePromotions.filter(promotion => promotion.status === 'Active').length
// })
// const InactivePromotionsCount = computed(() => {
//     return props.InactivePromotions.filter(promotion => promotion.status === 'Inactive').length
// })


const openModal = () => {
    createFormIsOpen.value = true;
    isDirty.value = false;
}

const closeModal = (status) => {
    switch(status){
        case 'close': {
            if(isDirty.value){
                isUnsavedChangesOpen.value = true;
            } else {
                createFormIsOpen.value = false;
            }
            break;
        };
        case 'stay': {
            isUnsavedChangesOpen.value = false;
            break;
        }
        case 'leave': {
            isUnsavedChangesOpen.value = false;
            createFormIsOpen.value = false;
            break;
        }

    }
}

const updatePromotions = (newPromotions) => {
    filteredActivePromotions.value = newPromotions.Active ?? [];
    filteredInactivePromotions.value = newPromotions.Inactive ?? [];
}

watch(() => searchQuery.value, (newValue) => {
    if(newValue === '') {
        filteredActivePromotions.value = props.ActivePromotions;
        filteredInactivePromotions.value = props.InactivePromotions;
        return;
    }

    const query = newValue.toLowerCase();

    filteredActivePromotions.value = props.ActivePromotions.filter(promotions => {
        const promoDescription = promotions.description.toLowerCase();
        const promoTitle = promotions.title.toLowerCase();
        const promoFrom = promotions.promotion_from.toString().toLowerCase();
        const promoTo = promotions.promotion_to.toString().toLowerCase();
        
        return promoDescription.includes(query) || 
               promoTitle.includes(query) || 
               promoFrom.includes(query) || 
               promoTo.includes(query);
    });

    filteredInactivePromotions.value = props.InactivePromotions.filter(promotions => {
        const promoDescription = promotions.description.toLowerCase();
        const promoTitle = promotions.title.toLowerCase();
        const promoFrom = promotions.promotion_from.toString().toLowerCase();
        const promoTo = promotions.promotion_to.toString().toLowerCase();

        return promoDescription.includes(query) || 
               promoTitle.includes(query) || 
               promoFrom.includes(query) || 
               promoTo.includes(query);
    });

}, { immediate: true });

watch(() => props.ActivePromotions, (newValue) => {
    filteredActivePromotions.value = newValue;
}, { immediate: true });

watch(() => props.InactivePromotions, (newValue) => {
    filteredInactivePromotions.value = newValue;
}, { immediate: true });
</script>

<template>
    <Toast />
    
    <div class="flex flex-col p-6 items-start self-stretch gap-6 border border-primary-100 rounded-[5px]">
        <div class="flex flex-col justify-center flex-[1_0_0] h-6">
            <p class="text-md font-medium text-primary-900">{{ $t('public.config.promotion_list') }}</p>
        </div>
        <div class="flex w-full gap-5 self-stretch flex-wrap md:flex-nowrap">
            <SearchBar
                :inputName="'searchbar'"
                :placeholder="$t('public.search')"
                v-model="searchQuery"
            />
            <Button
                :type="'button'"
                :size="'lg'"
                :iconPosition="'left'"
                class="md:!w-fit"
                @click="openModal"
            >
                <template #icon>
                    <PlusIcon
                        class="w-6 h-6"
                    />
                </template>
                {{ $t('public.action.new_promotion') }}
            </Button>
            <Modal 
                :title="$t('public.config.add_new_promotion')"
                :show="createFormIsOpen" 
                :maxWidth="'md'" 
                :closeable="true" 
                @close="closeModal('close')"
            >
                <CreatePromotionForm
                    @closeModal="closeModal"
                    @isDirty="isDirty = $event"
                    @update:promotions="updatePromotions($event)"
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
        </div>

        <div class=" w-full">
            <TabGroup>
                <TabList class="flex space-x-1 rounded-xl bg-transparent p-1">
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                            'w-[90px] p-3 text-sm font-medium leading-5',
                            'focus:outline-none focus:ring-0',
                            selected
                                ? 'bg-white text-primary-900 border-b-2 border-primary-900'
                                : 'text-blue-100 hover:text-primary-500',
                            ]"
                        >
                            {{ `${$t('public.active')} (${filteredActivePromotions?.length ?? '0'})` }}
                        </button>
                    </Tab>
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                            'w-[90px] py-3 text-sm font-medium leading-5',
                            'focus:outline-none focus:ring-0',
                            selected
                                ? 'bg-white text-primary-900 border-b-2 border-primary-900'
                                : 'text-blue-100 hover:text-primary-800',
                            ]"
                        >
                            {{ `${$t('public.inactive')} (${filteredInactivePromotions?.length ?? '0'})` }}
                        </button>
                    </Tab>
                </TabList>

                <TabPanels class="mt-2">
                    <TabPanel
                        class="py-3"
                    >
                        <Active :ActivePromotions="filteredActivePromotions"/>
                    </TabPanel>
                    <TabPanel
                        class="py-3"
                    >
                        <Inactive :InactivePromotions="filteredInactivePromotions"/>
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </div>

        <div></div>
    </div>

</template>
