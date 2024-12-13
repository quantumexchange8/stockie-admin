<script setup>
import { computed, onMounted, ref, watch } from "vue";
import dayjs from 'dayjs';
import { useForm } from "@inertiajs/vue3";
import Toggle from "@/Components/Toggle.vue";
import Button from "@/Components/Button.vue";
import DateInput from "@/Components/Date.vue";
import Dropdown from "@/Components/Dropdown.vue";
import TextInput from "@/Components/TextInput.vue";
import { Calendar, DeleteIcon, UploadLogoIcon } from "@/Components/Icons/solid";
import Accordion from "@/Components/Accordion.vue";
import { periodOption, rewardOption, emptyReward } from "@/Composables/constants";
import { useCustomToast, useInputValidator } from "@/Composables";
import InputError from "@/Components/InputError.vue";
import Modal from "@/Components/Modal.vue";

//------------------------
// DNR = DO NOT REMOVE code that has until confirmed is not needed by requirements
//------------------------

const props = defineProps({
    products: {
        type: Array,
        default: [],
    },
    logos: {
        type: Array,
        default: [],
    }
});

const emit = defineEmits(["close", "isDirty"]);
const rewardList = ref([]);
const logoPreview = ref([]);
const initialLogos = ref([...props.logos]);
const fileInput = ref(null);
const selectedLogo = ref(null);
const isUnsavedChangesOpen = ref(false);

const { showMessage } = useCustomToast();
const { isValidNumberKey } = useInputValidator();

const toggleMinPurchase = (index) => {
    const reward = rewardList.value[index];
    reward.min_purchase = reward.min_purchase === "active" ? "inactive" : "active";
    reward.min_purchase_amount = '';
};

const addReward = () => rewardList.value.push(emptyReward());

const removeReward = (id) => {
    rewardList.value.splice(id, 1);
}

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
        item.free_item = item.free_item ? item.free_item.toString() : '';
    });

    form.post(route("loyalty-programme.tiers.store"), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            closeModal('leave');
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

const closeModal = (status) => {
    emit("close", status);
    setTimeout(() => {
        // form.reset();
        logoPreview.value = [...initialLogos.value];
        selectedLogo.value = null;
        if (fileInput.value) fileInput.value.value = "";
    }, 200);
};

const resetReward = (value, index) => {
    rewardList.value[index] = emptyReward();
    rewardList.value[index].reward_type = value;
}

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

const handleLogoUpload = () => fileInput.value.click();

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    
    if (file && file.type.startsWith('image/')) { 
        logoPreview.value.push(file);
        selectLogo(file);
    }
}

const selectLogo = (logo) => (selectedLogo.value = checkFileInstance(logo) ? logo : selectedLogo.value);

watch(selectedLogo, (newValue) => form.icon = newValue, { immediate: false });

// watch(() => props.logos, (newValue) => logoPreview.value = newValue);

watch(() => props.logos, (newLogos) => {
    logoPreview.value = [...newLogos];
    initialLogos.value = [...newLogos];
});

onMounted(() => logoPreview.value = [...props.logos]);

const getObjectURL = (image) => URL.createObjectURL(image);

const checkFileInstance = (file) => file instanceof File;

const isFormValid = computed(() => ['name', 'min_amount', 'icon'].every(field => form[field]) && !form.processing);

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
                                <template v-for="logo in logoPreview" :key="logo" v-if="logoPreview.length > 0">
                                    <div :class="[
                                            'size-[44px] border-grey-100 border-dashed border-[1px] rounded-[5px] bg-grey-25 items-center justify-center',
                                            { 'border-primary-900 !border-solid border-[2px]': logo === selectedLogo },
                                            { 'cursor-not-allowed opacity-30': !checkFileInstance(logo) },
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
                    <!-- <Accordion
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
                        </template>
                    </Accordion> -->
                    <div class="" v-for="(reward, index) in rewardList" :key="index">
                        <div class="flex flex-col gap-4 self-stretch">
                            <div class="flex justify-between items-center self-stretch">
                                <span class="text-md font-bold text-grey-900">Reward {{ index + 1 }}</span>
                                <DeleteIcon 
                                    class="w-6 h-6 text-primary-600 hover:text-primary-800 cursor-pointer"
                                    @click="removeReward(index)"
                                />
                            </div>
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
                                                :labelText="'Select a product'"
                                                imageOption
                                                :inputArray="products"
                                                :inputName="'free_item_' + index"
                                                :errorMessage="form.errors ? form.errors['items.' + index + '.free_item']  : ''"
                                                v-model="reward.free_item"
                                                placeholder="Select"
                                                class="w-full"
                                            />
                                        </div>
                                    </template>

                                    <!-- All except Bonus Point -->
                                    <!-- *DNR*                                          
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
                                    </template> -->
                                </div>
                            </div>
                        </div>
                    </div>

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
                        Add
                    </Button>
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
        </div>
    </form>
</template>
