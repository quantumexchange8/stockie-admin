<script setup>
import { onMounted, ref } from "vue";
import dayjs from 'dayjs';
import { useForm } from "@inertiajs/vue3";
import Toggle from "@/Components/Toggle.vue";
import Button from "@/Components/Button.vue";
import DateInput from "@/Components/Date.vue";
import Dropdown from "@/Components/Dropdown.vue";
import TextInput from "@/Components/TextInput.vue";
import { Calendar, UploadLogoIcon } from "@/Components/Icons/solid";
import Accordion from "@/Components/Accordion.vue";
import { periodOption, rewardOption, emptyReward } from "@/Composables/constants";
import { useCustomToast, useInputValidator } from "@/Composables";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    inventoryItems: {
        type: Array,
        default: [],
    },
    logos: {
        type: Array,
        default: [],
    }
});

const emit = defineEmits(["close"]);
const rewardList = ref([]);
const logoPreview = ref([]);
const fileInput = ref(null);
const selectedLogo = ref(null);

const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();
const toggleMinPurchase = (index) => {
    const reward = rewardList.value[index];
    reward.min_purchase = reward.min_purchase === "active" ? "inactive" : "active";
    reward.min_purchase_amount = '';
};

const addReward = () => {
    rewardList.value.push(emptyReward());
};

const toggleReward = () => {
    const isActive = form.reward === "active";
    form.reward = isActive ? "inactive" : "active";
    
    if (isActive) {
        rewardList.value = [];
        return;
    }
    
    addReward();
};

const form = useForm({
    name: "",
    min_amount: "",
    reward: "inactive",
    rewards: [],
    icon: "",
});

const submit = () => {
    form.rewards = rewardList.value;
    form.rewards.forEach(item => {
        item.item_qty = item.free_item ? '1' : '';
        item.free_item = item.free_item.toString();
    });

    form.post(route("loyalty-programme.tiers.store"), {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            closeModal();
            setTimeout(() => {
                showMessage({ 
                    severity: 'success',
                    summary: 'Tier successfully added.',
                });
                form.reset();
            }, 200);
        },
    });
};

const closeModal = () => {
    form.reset();
    form.errors = {};
    emit("close");
};

const resetReward = (value, index) => {
    rewardList.value[index] = emptyReward();
    rewardList.value[index].reward_type = value;
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

const handleLogoUpload = () => {
    fileInput.value.click()
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]

  if (file && file.type.startsWith('image/')) { 
      const previewUrl = URL.createObjectURL(file);
      logoPreview.value.push(previewUrl);
      selectedLogo.value = previewUrl;
      form.icon = file;
  }
}

const selectLogo = (logo) => {
    selectedLogo.value = logo;
}

onMounted(() => {
    logoPreview.value = props.logos.map((logo) => URL.createObjectURL(logo));
});
</script>

<template>
    <form @submit.prevent="submit">
        <div class="max-h-[773px] overflow-y-scroll scrollbar-webkit scrollbar-thin p-2">
            <div class="w-full flex flex-col gap-6">
                <div class="flex flex-col gap-4">
                    <TextInput
                        :placeholder="'eg: Gold / VIP etc'"
                        :labelText="'Tier Name'"
                        inputId="name"
                        type="'text'"
                        v-model="form.name"
                        :errorMessage="form.errors.name"
                    />

                    <div class="w-full flex gap-6">
                        <div class="flex flex-col gap-1">
                            <p class="text-xs">Select an icon</p>
                            <div class="w-[308px] flex flex-wrap gap-4 items-end">
                                <!-- Existing icons from database -->
                                    <!-- <img :src="logoPreview.value" alt="" /> -->
                                <template v-for="logo in logoPreview" :key="logo" v-if="logoPreview.length > 0">
                                    <div class="w-[44px] h-[44px] border-grey-100 border-dashed border-[1px] rounded-[5px] bg-grey-25 items-center justify-center"
                                        :class="logo === selectedLogo ? 'border-primary-900 !border-solid border-[2px]' : ''"
                                        @click="selectLogo(logo)"
                                    >
                                        <img :src="logo" alt="logo" class="w-full h-full object-cover"/>
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
                                <InputError :message="form.errors.image" v-if="form.errors.image" />
                            </div>
                        </div>

                        <TextInput
                            :labelText="'Amount spend to achieve this tier'"
                            inputId="min_amount"
                            type="'text'"
                            v-model="form.min_amount"
                            :iconPosition="'left'"
                            :errorMessage="form.errors.min_amount"
                            @keypress="isValidNumberKey($event, true)"
                        >
                            <template #prefix> RM </template>
                        </TextInput>
                    </div>
                </div>
                <div class="justify-end flex gap-3">
                    <p class="font-normal">
                        This tier is entitled to entry reward(s)
                    </p>
                    <Toggle
                        class="xl:col-span-2"
                        :checked="form.reward === 'active'"
                        :inputName="'reward'"
                        inputId="reward"
                        v-model="form.reward"
                        @change="toggleReward"
                    />
                </div>

                <!-- Rewards section -->
                <div class="flex flex-col gap-6" v-if="form.reward === 'active'">
                    <Accordion
                        v-for="(reward, index) in rewardList"
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
                                    :labelText="rewardList[index].reward_type !== '' ? `Reward Type ${index + 1}` : 'Select Reward Type'"
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
                                    <div class="flex flex-col gap-3">
                                        <!--Discount Amount & Discount Percent -->
                                        <template v-if="reward.reward_type === 'Discount (Amount)' || reward.reward_type === 'Discount (Percentage)'">
                                            <div class="flex gap-3">
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
                                                    @keypress="isValidNumberKey($event, true)"
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
                                            <div class="flex gap-4 items-center">
                                                <Dropdown
                                                    :labelText="'Select an item'"
                                                    :inputArray="inventoryItems"
                                                    grouped
                                                    :inputName="'free_item_' + index"
                                                    :errorMessage="form.errors ? form.errors['items.' + index + '.free_item']  : ''"
                                                    v-model="reward.free_item"
                                                    placeholder="Select"
                                                    class="w-full"
                                                >
                                                    <template #optionGroup="group">
                                                        <div class="flex flex-nowrap items-center gap-3">
                                                            <!-- <div class="bg-grey-50 border border-grey-200 h-6 w-6"></div> -->
                                                            <img 
                                                                :src="group.group_image ? group.group_image : 'https://www.its.ac.id/tmesin/wp-content/uploads/sites/22/2022/07/no-image.png'" 
                                                                alt=""
                                                                class="bg-grey-50 border border-grey-200 h-6 w-6"
                                                            >
                                                            <span class="text-grey-400 text-base font-bold">{{ group.group_name }}</span>
                                                        </div>
                                                    </template>
                                                </Dropdown>
                                            </div>
                                        </template>

                                        <!-- All except Bonus Point -->
                                        <template v-if="reward.reward_type !== 'Bonus Point'">
                                            <div class="flex flex-wrap sm:flex-nowrap gap-3 items-start">
                                                <Dropdown
                                                    labelText="Valid Period"
                                                    :placeholder="'Select'"
                                                    :inputArray="periodOption"
                                                    :dataValue="reward.valid_period"
                                                    inputId="valid_period"
                                                    :inputName="'valid_period_' + index"
                                                    :errorMessage="form.errors ? form.errors['items.' + index + '.valid_period_from']  : ''"
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
                                                    :range="true"
                                                    class="sm:pt-[22px]"
                                                    @onChange="updateValidPeriod(reward, $event)"
                                                    v-model="reward.date_range"
                                                />
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Accordion>

                    <!-- Button to add more rewards -->
                    <Button
                        variant="secondary"
                        :size="'lg'"
                        @click="addReward"
                        :type="'button'"
                    >
                        Another Reward
                    </Button>
                </div>

                <div class="flex gap-4 pt-3">
                    <Button
                        type="button"
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
                    >
                        Add
                    </Button>
                </div>
            </div>
        </div>
    </form>
</template>
