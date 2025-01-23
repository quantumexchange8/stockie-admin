<script setup>
import { ref, computed, onMounted, watch } from "vue";
import dayjs from "dayjs";
import { defineProps } from "vue";
import Toggle from "@/Components/Toggle.vue";
import { useForm } from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import DateInput from "@/Components/Date.vue";
import NumberCounter from "@/Components/NumberCounter.vue";
import Dropdown from "@/Components/Dropdown.vue";
import TextInput from "@/Components/TextInput.vue";
import Accordion from "@/Components/Accordion.vue";
import { Calendar, DeleteIcon, UploadLogoIcon } from "@/Components/Icons/solid";
import { periodOption, rewardOption, emptyReward } from "@/Composables/constants";
import Toast from '@/Components/Toast.vue'
import { useCustomToast, useInputValidator } from "@/Composables";
import InputError from "@/Components/InputError.vue";
import Modal from "@/Components/Modal.vue";
import { DeleteIllus } from "@/Components/Icons/illus";

//------------------------
// DNR = DO NOT REMOVE code that has until confirmed is not needed by requirements
//------------------------

const props = defineProps({
    tier: {
        type: Object,
        default: () => {},
    },
    inventoryItems: {
        type: Array,
        default: () => [],
    },
    items: {
        type: Array,
        default: () => [],
    },
    logos: {
        type: Array,
        default: () => [],
    }
});

const logoPreview = ref([]);
const initialLogos = ref([...props.logos]);
const fileInput = ref(null);
const selectedLogo = ref(props.tier.icon);
const isUnsavedChangesOpen = ref(false);
const selectedReward = ref(null);
const isDeleteRewardModalOpen = ref(false);

const emit = defineEmits(["close", "isDirty"]);
const { isValidNumberKey } = useInputValidator();
const { showMessage } = useCustomToast();

// *DNR*
// Calculates difference in month
// const calculateValidPeriod = (fromDate, toDate) => dayjs(toDate).diff(dayjs(fromDate), 'month');

const form = useForm({
    id: props.tier.id,
    name: props.tier.name,
    min_amount: props.tier.min_amount.toString(),
    reward: props.tier.reward,
    rewards: props.inventoryItems.map((reward) => {
        // const validPeriod = calculateValidPeriod(reward.valid_period_from, reward.valid_period_to); // *DNR*
        reward.item_qty = reward.item_qty ? parseInt(reward.item_qty) : 1;
        reward.min_purchase_amount = reward.min_purchase_amount ? reward.min_purchase_amount.toString() : '';
        reward.free_item = reward.free_item;

        return {
            ...reward,
            // valid_period: validPeriod, // *DNR*
            // date_range: [new Date(reward.valid_period_from), new Date(reward.valid_period_to)], // *DNR*
            valid_period: null,
            date_range: null,
        };
    }),
    icon: props.tier.icon ?? '',
});

const addReward = () => form.rewards.push(emptyReward());

const removeReward = (id) => {
    let reward = form.rewards.find((item) => item.id === id);
    reward.status = 'Inactive';
    closeDeleteRewardModal();
}

const openDeleteRewardModal = (rewardId) => {
    selectedReward.value = rewardId;
    isDeleteRewardModalOpen.value = true;
}

const closeDeleteRewardModal = () => {
    isDeleteRewardModalOpen.value = false;
    setTimeout(() => selectedReward.value = null, 200);
}

const closeModal = (status) => {
    emit("close", status);
    setTimeout(() => {
        form.reset();
        logoPreview.value = [...initialLogos.value];
        selectedLogo.value = null;
        if (fileInput.value) fileInput.value.value = "";
    }, 200);
};

const toggleReward = () => {
    const isActive = form.reward === "active";
    form.reward = isActive ? "inactive" : "active";
    
    if (isActive) {
        form.rewards = [];
        return;
    }

    addReward();
};

const toggleMinPurchase = (index) => {
    const reward = form.rewards[index];
    reward.min_purchase =
        reward.min_purchase === "active" ? "inactive" : "active";
};

const resetReward = (value, index) => {
    form.rewards[index] = emptyReward();
    form.rewards[index].reward_type = value;
};

const initializeMinItemQty = (value, index) => {
    form.rewards[index].item_qty = value !== '' ? 1 : '';
};

// *DNR*
// const updateValidPeriod = (reward, option) => {
//     reward.valid_period_from = reward.valid_period === 0 && typeof option === 'object'
//             ? dayjs(option[0]).format('YYYY-MM-DD HH:mm:ss')
//             : reward.valid_period !== 0
//                     ? dayjs().format('YYYY-MM-DD HH:mm:ss')
//                     : '';

//     reward.valid_period_to = reward.valid_period === 0 && typeof option === 'object'
//             ? dayjs(option[1]).format('YYYY-MM-DD HH:mm:ss')
//             : reward.valid_period !== 0
//                     ? dayjs().add(option, 'month').format('YYYY-MM-DD HH:mm:ss')
//                     : '';
// }

const submit = () => {
    form.rewards.forEach(item => {
        item.item_qty = item.item_qty ? item.item_qty.toString() : '';
    });
    
    form.post(`/loyalty-programme/tiers/update/${props.tier.id}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            closeModal('leave');
            setTimeout(() => {
                showMessage({
                    severity: 'success',
                    summary: 'Tier has been edited successfully.'
                });
            }, 200);
        },
        // onError: () => {
        //     form.rewards.forEach(item => {
        //         item.item_qty = item.item_qty ? parseInt(item.item_qty) : '';
        //         item.free_item = item.free_item ? parseInt(item.free_item) : '';
        //     });
        // },
    })
};

const handleLogoUpload = () => fileInput.value.click();

const handleFileSelect = (event) => {
  const file = event.target.files[0]

  if (file && file.type.startsWith('image/')) { 
        logoPreview.value.push(file);
        selectLogo(file);
  }
};

const selectLogo = (logo) => (selectedLogo.value = checkFileInstance(logo) ? logo : selectedLogo.value);

watch(selectedLogo, (newValue) => form.icon = newValue, { immediate: true });

watch(() => props.logos, (newLogos) => {
    logoPreview.value = [...newLogos];
    initialLogos.value = [...newLogos];
});

onMounted(() => logoPreview.value = [...props.logos]);

const getObjectURL = (image) => URL.createObjectURL(image);

const checkFileInstance = (file) => file instanceof File;

const checkSelectedIcon = (logo) => {
    if (selectedLogo.value) {
        return selectedLogo.model_id === logo.model_id
            && selectedLogo.model_type === logo.model_type
            && selectedLogo.original_url === logo.original_url;
    }
    
    return logo === selectedLogo.value;
};

// const getProductStockLeft = (freeItem) => {
//     return ;
// }

const isFormValid = computed(() => ['name', 'min_amount'].every(field => form[field]) && !form.processing);

watch(form, (newValue) => emit('isDirty', newValue.isDirty));

</script>

<template>
    <form @submit.prevent="submit">
        <div class="max-h-[calc(100dvh-12rem)] overflow-y-auto scrollbar-webkit scrollbar-thin p-2">
            <div class="w-full flex flex-col gap-6">
                <div class="flex flex-col gap-4">
                    <TextInput
                        :placeholder="'eg: Gold / VIP etc'"
                        :labelText="'Tier Name'"
                        :errorMessage="form.errors?.name ?? ''"
                        inputName="name"
                        type="'text'"
                        v-model="form.name"
                    />
                </div>
                <div class="w-full flex flex-wrap sm:flex-nowrap gap-6">
                    <div class="flex flex-col gap-1">
                        <p class="text-xs">Select an icon</p>
                        <div class="w-[308px] flex flex-wrap gap-4 items-end">
                            <!-- Existing icons from database -->
                            <template v-for="logo in logoPreview" :key="logo" v-if="logoPreview.length > 0">
                                <div :class="[
                                        'size-[44px] border-grey-100 border-dashed border-[1px] rounded-[5px] bg-grey-25 items-center justify-center',
                                        { 'border-primary-900 !border-solid border-[2px]': checkSelectedIcon(logo) },
                                        { 'cursor-not-allowed opacity-30': !checkFileInstance(logo) && !checkSelectedIcon(logo)},
                                        { 'cursor-pointer transition ease-in-out delay-50 hover:-translate-y-1 hover:scale-110 duration-200': checkFileInstance(logo) },
                                    ]"
                                    @click="selectLogo(logo)"
                                >
                                    <img 
                                        :src="logo.original_url ?? getObjectURL(logo)" 
                                        alt="logo" 
                                        class="size-full object-contain"
                                    />
                                </div>
                            </template>

                            <!-- Icons  -->
                            <div class="flex flex-col gap-4 items-center">
                                <div class="flex w-[44px] h-[44px] border-grey-100 border-dashed border-[1px] rounded-[5px] bg-grey-25 items-center justify-center cursor-pointer" @click="handleLogoUpload">
                                    <UploadLogoIcon class="flex-shrink-0" />
                                    <input 
                                        type="file" 
                                        ref="fileInput" 
                                        @change="handleFileSelect" 
                                        accept="image/*" 
                                        class="hidden" 
                                    />
                                </div>
                            </div>
                        </div>
                        <InputError :message="form.errors.icon" v-if="form.errors.icon" />
                    </div>

                    <TextInput
                        class="w-[366px] text-center-input"
                        :labelText="'Amount spend to achieve this tier'"
                        inputId="min_amount"
                        :errorMessage="form.errors?.min_amount  ?? ''"
                        :iconPosition="'left'"
                        type="'Number'"
                        v-model="form.min_amount"
                        @keypress="isValidNumberKey($event, false)"
                    >
                        <template #prefix> RM </template>
                    </TextInput>
                </div>
                <div class="justify-end flex gap-3">
                    <span class="text-grey-900 text-base font-normal">
                        This tier is entitled to entry reward(s)
                    </span>
                    <Toggle
                        class="xl:col-span-2"
                        :checked="form.reward === 'active'"
                        :inputName="'reward'"
                        inputId="reward"
                        v-model="form.reward"
                        @change="toggleReward(form.reward)"
                    />
                </div>
                
                <Toast 
                    inline
                    severity="info"
                    summary="Edit entry reward for this tier"
                    detail="The changes will only be applied to new entry member only."
                    actionLabel="OK"
                    :closable="false"
                />

                <div class="flex flex-col gap-6" v-if="form.reward === 'active'">
                    <!-- <Accordion
                        v-for="(reward, index) in form.rewards"
                        :key="index"
                        accordionClasses="gap-4"
                    >
                        <template #head>
                            <span class="text-sm text-grey-900 font-medium">
                                Reward {{ index + 1 }}
                            </span>
                        </template>

                        <template #body>
                        </template>
                    </Accordion> -->
                    <div class="flex flex-col gap-4" v-for="(reward, index) in form.rewards.filter((item) => item.status === 'Active')">
                        <div class="flex justify-between items-center self-stretch">
                            <span class="text-md font-bold text-grey-900">Reward {{ index + 1 }}</span>
                            <!-- *DNR* -->
                            <DeleteIcon
                                v-if="reward.isFullyRedeemed"
                                class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                                @click="openDeleteRewardModal(reward.id)"
                            />
                        </div>
                        <!-- Reward type selection -->
                        <Dropdown
                            :labelText="reward.reward_type !== '' ? `Reward Type ${index + 1}` : 'Select Reward Type'"
                            :placeholder="'Select'"
                            :inputArray="rewardOption"
                            :inputName="'reward_type_' + index"
                            :hint-text="'The reward can only be redeemed once.'"
                            :dataValue="reward.reward_type"
                            v-model="reward.reward_type"
                            :errorMessage="form.errors ? form.errors['items.' + index + '.reward_type']  : ''"
                            @onChange="resetReward($event, index)"
                        />

                        <div v-if="reward.reward_type !== null && reward.reward_type !== undefined">
                            <div 
                                class="flex flex-col"
                                :class="{
                                    'gap-10': form.errors['items.' + index + '.item_qty'],
                                    'gap-3': !form.errors['items.' + index + '.item_qty'],
                                }"
                            >
                                <!--Discount Amount & Discount Percent -->
                                <template v-if="reward.reward_type === 'Discount (Amount)' || reward.reward_type === 'Discount (Percentage)'">
                                    <div class="flex flex-wrap sm:flex-nowrap gap-3">
                                        <TextInput
                                            :iconPosition="reward.reward_type === 'Discount (Amount)' ? 'left' : 'right'"
                                            :inputName="'discount_' + index"
                                            :errorMessage="form.errors ? form.errors['items.' + index + '.discount']  : ''"
                                            :labelText="reward.reward_type === 'Discount (Amount)' ? 'Discount Amount' : 'Discount Percentage'"
                                            inputId="discount"
                                            v-model="reward.discount"
                                            @keypress="isValidNumberKey($event, true)"
                                        >
                                            <template #prefix>{{ reward.reward_type === 'Discount (Amount)' ? 'RM' : '%' }}</template>
                                        </TextInput>
                                        <TextInput
                                            v-if="reward.min_purchase === 'active'"
                                            :inputName="'min_purchase_amount_' + index"
                                            :iconPosition="'left'"
                                            :errorMessage="form.errors ? form.errors['items.' + index + '.min_purchase_amount']  : ''"
                                            labelText="Minimum purchase amount"
                                            inputId="min_purchase_amount"
                                            v-model="reward.min_purchase_amount"
                                            @keypress="isValidNumberKey($event, false)"
                                        >
                                            <template #prefix>RM</template>
                                        </TextInput>
                                    </div>
                                    <div class="justify-end flex gap-3">
                                        <p class="font-normal">With minimum purchase</p>
                                        <Toggle
                                            class="xl:col-span-2"
                                            :checked="reward.min_purchase === 'active'"
                                            :inputName="'min_purchase'"
                                            :errorsMessage="form.errors ? form.errors['items.' + index + '.min_purchase']  : ''"
                                            inputId="min_purchase"
                                            v-model="reward.min_purchase"
                                            @change="() => toggleMinPurchase(index)"
                                        />
                                    </div>
                                </template>
                                
                                <!--Bonus Point -->
                                <template v-if="reward.reward_type === 'Bonus Point'">
                                    <TextInput
                                        :labelText="'Bonus point get'"
                                        :placeholder="'10'"
                                        :iconPosition="'right'"
                                        v-model="reward.bonus_point"
                                        :inputName="'bonus_point_' + index"
                                        :errorMessage="form.errors ? form.errors['items.' + index + '.bonus_point']  : ''"
                                        @keypress="isValidNumberKey($event, false)"
                                    >
                                        <template #prefix>pts</template>
                                    </TextInput>
                                </template>

                                <!--Free Item-->
                                <template v-if="reward.reward_type === 'Free Item'">
                                    <div class="flex gap-3 flex-wrap sm:flex-nowrap items-start justify-center">
                                        <Dropdown
                                            :labelText="'Select a product'"
                                            :inputArray="items"
                                            imageOption
                                            :inputName="'free_item_' + index"
                                            :errorMessage="form.errors ? form.errors['items.' + index + '.free_item']  : ''"
                                            :dataValue="reward.free_item"
                                            v-model="reward.free_item"
                                            placeholder="Select"
                                            class="w-full"
                                            @onChange="initializeMinItemQty($event, index)"
                                        />
                                        <div v-if="reward.free_item" class="w-fit flex max-h-[44px] pt-[22px]">
                                            <NumberCounter
                                                :labelText="''"
                                                :inputName="'item_qty_' + index"
                                                :errorMessage="form.errors ? form.errors['items.' + index + '.item_qty']  : ''"
                                                :dataValue="parseInt(reward.item_qty) || 1"
                                                :minValue="1"
                                                :maxValue="props.items.find((item) => item.value === reward.free_item)?.stock_left"
                                                v-model="reward.item_qty"
                                            />
                                        </div>
                                    </div>
                                </template>

                                <!-- All except Bonus Point -->
                                <!-- *DNR*
                                <template v-if="reward.reward_type !== 'Bonus Point'">
                                    <div class="flex gap-3 flex-wrap sm:flex-nowrap items-end">
                                        <Dropdown
                                            labelText="Valid Period"
                                            :placeholder="'Select'"
                                            :inputArray="periodOption"
                                            :dataValue="reward.valid_period"
                                            inputId="valid_period"
                                            :inputName="'valid_period_' + index"
                                            v-model="reward.valid_period"
                                            @onChange="updateValidPeriod(reward, $event)"
                                            :iconOptions="{
                                                'Customise range...': Calendar,
                                            }"
                                        />

                                        <DateInput
                                            v-if="reward.valid_period === 0"
                                            :labelText="''"
                                            :inputName="'date_range_' + index"
                                            :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                            @onChange="updateValidPeriod(reward, $event)"
                                            :range="true"
                                            v-model="reward.date_range"
                                        />
                                    </div>
                                </template> -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Button to add more rewards -->
                <Button
                    variant="secondary"
                    :size="'lg'"
                    :type="'button'"
                    @click="addReward"
                    v-if="form.reward === 'active'"
                >
                    Another Reward
                </Button>

                <div class="flex gap-4 pt-3">
                    <Button
                        variant="tertiary"
                        :type="'button'"
                        :size="'lg'"
                        @click="closeModal('close')"
                    >
                        Cancel
                    </Button>
                    <Button 
                        variant="primary" 
                        :size="'lg'" 
                        :type="'submit'"
                        :disabled="!isFormValid"
                    >
                        Save Changes
                    </Button>
                </div>
            </div>
        </div>
        <Modal
            :unsaved="true"
            :maxWidth="'2xs'"
            :withHeader="false"
            :closeable="true"
            :show="isUnsavedChangesOpen"
            @close="closeModal('stay')"
            @leave="closeModal('leave')"
        >
        </Modal>
    </form>

    <Modal
        :maxWidth="'2xs'"
        :closeable="true"
        :withHeader="false"
        :show="isDeleteRewardModalOpen"
        class="[&>div>div>div]:!p-0"
        @close="closeDeleteRewardModal"
    >
        <template v-if="selectedReward">
            <div class="flex flex-col gap-9">
                <div class="bg-primary-50 pt-6 flex items-center justify-center rounded-t-[5px]">
                    <DeleteIllus/>
                </div>
                <div class="flex flex-col justify-center items-center self-stretch gap-1 px-6">
                    <p class="text-center text-primary-900 text-lg font-medium self-stretch">Remove this reward?</p>
                    <p class="text-center text-grey-900 text-base font-medium self-stretch">By removing this reward, new members entering this tier will no longer benefit from it. Are you sure you want to remove?</p>
                </div>
                <div class="flex px-6 pb-6 justify-center items-end gap-4 self-stretch">
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        @click="closeDeleteRewardModal"
                    >
                        Keep
                    </Button>
                    <Button 
                        :size="'lg'"
                        :variant="'red'"
                        :disabled="form.processing"
                        @click="removeReward(selectedReward)"
                    >
                        Remove
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>
