<script setup>
import { ref, computed, onMounted } from "vue";
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
import { Calendar, UploadLogoIcon } from "@/Components/Icons/solid";
import { periodOption, rewardOption, emptyReward } from "@/Composables/constants";
import Toast from '@/Components/Toast.vue'
import { useCustomToast, useInputValidator } from "@/Composables";

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

const logoPreview = ref(props.logos);
const fileInput = ref(null);
const selectedLogo = ref(props.tier.icon);

const emit = defineEmits(["close"]);
const { isValidNumberKey } = useInputValidator();
const { showMessage } = useCustomToast();

//function to display the option for valid period
const calculateValidPeriod = (fromDate, toDate) => {
    return dayjs(toDate).diff(dayjs(fromDate), 'month');    
};

const form = useForm({
    id:props.tier.id,
    name: props.tier.name,
    min_amount: props.tier.min_amount.toString(),
    reward: props.tier.reward,
    rewards: props.inventoryItems.map((reward) => {
        const validPeriod = calculateValidPeriod(reward.valid_period_from, reward.valid_period_to); // calculate difference in month
        reward.item_qty = reward.item_qty ? parseInt(reward.item_qty) : 1;
        reward.min_purchase_amount = reward.min_purchase_amount ? reward.min_purchase_amount.toString() : '';
        // reward.discount = reward.reward_type === 'Discount (Percentage)' ? reward.transformed_rate.toString() : reward.discount;

        return {
            ...reward,
            valid_period: validPeriod,
            date_range: [new Date(reward.valid_period_from), new Date(reward.valid_period_to)],
        };
    }),
    icon: props.tier.icon ?? '',
});

const addReward = () => {
    form.rewards.push(emptyReward());
};

const closeModal = () => {
    form.reset();
    form.errors = {};
    emit("close");
};

const toggleReward = () => {
    const isActive = form.reward === "active";
    form.reward = isActive ? "inactive" : "active";
    
    if (isActive) {
        form.rewards = [];
        return;
    }
    
    // addReward();
};

const toggleMinPurchase = (index) => {
    const reward = form.rewards[index];
    reward.min_purchase =
        reward.min_purchase === "active" ? "inactive" : "active";
};

const resetReward = (value, index) => {
    form.rewards[index] = emptyReward();
    form.rewards[index].reward_type = value;
}

const initializeMinItemQty = (value, index) => {
    form.rewards[index].item_qty = value !== '' ? 1 : '';
}

const updateValidPeriod = (reward, option) => {
    reward.valid_period_from = '';
    reward.valid_period_to = '';
    
    if (reward.valid_period === 0 && typeof option === 'object') {
        reward.valid_period_from = dayjs(option[0]).format('YYYY-MM-DD HH:mm:ss');
        reward.valid_period_to = dayjs(option[1]).format('YYYY-MM-DD HH:mm:ss');
    }

    if (reward.valid_period !== 0) {
        reward.valid_period_from = dayjs().format('YYYY-MM-DD HH:mm:ss');
        reward.valid_period_to = dayjs().add(option, 'month').format('YYYY-MM-DD HH:mm:ss');
    }
}

const submit = () => {
    
    form.rewards.forEach(item => {
        item.item_qty = item.item_qty ? item.item_qty.toString() : '';
        item.free_item = item.free_item ? item.free_item.toString() : '';
    });
    
    form.put(`/loyalty-programme/tiers/update/${props.tier.id}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            closeModal();
            setTimeout(() => {
                showMessage({
                    severity: 'success',
                    summary: 'Tier has been edited successfully.'
                });
            }, 200);
        },
    })
};

const handleLogoUpload = () => {
    fileInput.value.click()
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
//   console.log(file);
  if (file && file.type.startsWith('image/')) { 
        const previewUrl = file;
        logoPreview.value.push(previewUrl);
        selectedLogo.value = previewUrl;
  }
}

const selectLogo = (logo) => {
    selectedLogo.value = logo;
}

</script>

<template>
    <form @submit.prevent="submit">
        <div class="max-h-[773px] overflow-y-scroll scrollbar-webkit scrollbar-thin p-2">
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
                                <!-- <img :src="logoPreview.value" alt="" /> -->
                            <template v-for="logo in logoPreview" v-if="logoPreview">
                                <div class="w-[44px] h-[44px] border-grey-100 border-dashed border-[1px] rounded-[5px] bg-grey-25 items-center justify-center"
                                    :class="logo === selectedLogo ? 'border-primary-900 !border-solid border-[2px]' : ''"
                                    @click="selectLogo(logo)"
                                >
                                </div>
                            </template>

                            <!-- Icons  -->
                            <div class="flex flex-col gap-4 items-center">
                                <div class="flex w-[44px] h-[44px] border-grey-100 border-dashed border-[1px] rounded-[5px] bg-grey-25 items-center justify-center">
                                    <UploadLogoIcon 
                                        @click="handleLogoUpload"
                                        class="cursor-pointer"
                                    />

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
                    <Accordion
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
                            <div class="flex flex-col gap-4">
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
                                            <div class="flex gap-3 flex-wrap sm:flex-nowrap items-end justify-center">
                                                <Dropdown
                                                    :labelText="'Select an item'"
                                                    :inputArray="props.items"
                                                    grouped
                                                    :inputName="'free_item_' + index"
                                                    :errorMessage="form.errors ? form.errors['items.' + index + '.free_item']  : ''"
                                                    :dataValue="reward.free_item ?? ''"
                                                    v-model="reward.free_item"
                                                    placeholder="Select"
                                                    class="w-full"
                                                    @onChange="initializeMinItemQty($event, index)"
                                                />
                                                <div 
                                                    v-if="reward.free_item !== ''"
                                                    class="w-fit flex max-h-[44px]" 
                                                >
                                                    <NumberCounter
                                                        :labelText="''"
                                                        :inputName="'item_qty_' + index"
                                                        :errorMessage="form.errors ? form.errors['items.' + index + '.item_qty']  : ''"
                                                        :dataValue="reward.item_qty || 1"
                                                        :minValue="1"
                                                        v-model="reward.item_qty"
                                                    />
                                                </div>
                                            </div>
                                        </template>

                                        <!-- All except Bonus Point -->
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
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Accordion>
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
                        :size="'lg'"
                        @click="closeModal"
                    >
                        Cancel
                    </Button>
                    <Button 
                        variant="primary" 
                        :size="'lg'" 
                        :type="'submit'"
                        :disabled="form.processing"
                    >
                        Save Changes
                    </Button>
                </div>
            </div>
        </div>
    </form>
</template>
