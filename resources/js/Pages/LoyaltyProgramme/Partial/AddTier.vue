<script setup>
import dayjs from 'dayjs';
import TextInput from "@/Components/TextInput.vue";
import Toggle from "@/Components/Toggle.vue";
import Button from "@/Components/Button.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DateInput from "@/Components/Date.vue";
import NumberCounter from "@/Components/NumberCounter.vue";
import { Calendar } from "@/Components/Icons/solid";
import { useForm } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import Carlsbeg from "../../../../assets/images/Loyalty/Carlsbeg.svg";
import Tiger from "../../../../assets/images/Loyalty/Tiger.svg";
import { periodOption, rewardOption, emptyReward } from "@/Composables/constants";

const props = defineProps({
    inventoryItems: {
        type: Array,
        default: [],
    },
});

const emit = defineEmits(["close"]);

const rewardList = ref([]);

// const validateAmount = (reward) => {
//     const isNumeric = (field) => /^\d+(\.\d+)?$/.test(field);

//     reward.error = isNumeric(reward.amount) ? "" : reward.amount === "" ? "Please enter the minimum amount" : "This field must be a numeric.";
//     reward.minPurchaseAmountError = isNumeric(reward.min_purchase_amount) ? "" : reward.min_purchase_amount === "" ? "Please enter the minimum amount" : "Please enter the minimum amount";
//     reward.bonuspoint = isNumeric(reward.bonus_point) ? "" : reward.bonus_point === "" ? "Please enter amount" : "Please enter amount";
// };

const toggleMinPurchase = (index) => {
    const reward = rewardList.value[index];
    reward.min_purchase = reward.min_purchase === "active" ? "inactive" : "active";
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
    // rewardList.value = rewardList.value.slice(0, 1);
    // rewardList.value.forEach((reward) => {
    //     Object.assign(reward, emptyReward());
    // });
};

const form = useForm({
    name: "",
    min_amount: "",
    reward: "inactive",
    rewards: [],
    icon: "",
});

//function to submit the form
const submit = () => {
    rewardList.value.forEach((reward) => {
        if (reward.valid_period === 0 && reward.date_range !== "") {
            reward.valid_period_from = dayjs(reward.date_range[0]).format('YYYY/MM/DD');
            reward.valid_period_to = dayjs(reward.date_range[1]).format('YYYY/MM/DD');

        } else if (reward.valid_period) {
            reward.valid_period_from = dayjs().format('YYYY-MM-DD');
            reward.valid_period_to = dayjs().add(reward.valid_period, 'month').endOf('month').format('YYYY-MM-DD');
        }
    });

    form.rewards = rewardList.value;

    form.post(route("loyalty.create-tier"), {
        onSuccess: () => {
            form.reset();
            closeModal();
        },
    });
};

const closeModal = () => {
    form.reset();
    form.errors = {};
    emit("close");
};

//Function to disabled button unless all field is fill
const isAddButtonDisabled = computed(() => {
    if (rewardList.value.length === 0) { return false };

    return rewardList.value.some((reward) => {
        if (form.reward !== "active" || !reward.type) return true;

        switch (reward.type) {
            case "Discount (Amount)":
            case "Discount (Percentage)":
                return !reward.amount ||
                    (reward.min_purchase === "active" && !reward.min_purchase_amount) ||
                    reward.valid_period === "" ||
                    (reward.valid_period === 0 && !reward.date_range);
            case "Bonus Point":
                return !reward.bonus_point;
            case "Free Item":
                return !reward.free_item ||
                    reward.valid_period === "" ||
                    (reward.valid_period === 0 && !reward.date_range);
            default:
                return true;
        }
    });
});

// Validate input to only allow numeric value to be entered
const isNumber = (e, withDot = true) => {
    const { key, target: { value } } = e;
    
    if (/^\d$/.test(key)) return;

    if (withDot && key === '.' && /\d/.test(value) && !value.includes('.')) return;
    
    e.preventDefault();
};

const resetReward = (value, index) => {
    rewardList.value[index] = emptyReward();
    rewardList.value[index].type = value;
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
                        inputId="name"
                        type="'text'"
                        v-model="form.name"
                        :errorMessage="form.errors.name"
                    />

                    <div class="w-full flex gap-6">
                        <div class="flex flex-col gap-1">
                            <p class="text-xs">Select an icon</p>
                            <div class="w-[308px] flex flex-wrap gap-4 items-center">
                                <!-- Existing icons from database -->

                                <!-- Icons  -->
                                <div class="flex flex-col gap-4 items-center">
                                    <div  class="flex w-[44px] h-[44px] border-grey-100 border-dashed border-[1px] rounded-[5px] bg-grey-25 items-center justify-center">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                        >
                                            <path
                                                d="M4 16.2422C2.79401 15.435 2 14.0602 2 12.5C2 10.1564 3.79151 8.23129 6.07974 8.01937C6.54781 5.17213 9.02024 3 12 3C14.9798 3 17.4522 5.17213 17.9203 8.01937C20.2085 8.23129 22 10.1564 22 12.5C22 14.0602 21.206 15.435 20 16.2422M8 16L12 12M12 12L16 16M12 12V21"
                                                stroke="#B2BEC7"
                                                stroke-width="1.4"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <TextInput
                            :labelText="'Amount spend to achieve this tier'"
                            inputId="min_amount"
                            type="'text'"
                            v-model="form.min_amount"
                            :iconPosition="'left'"
                            :errorMessage="form.errors.min_amount"
                            @keypress="isNumber($event)"
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
                    <div
                        v-for="(reward, index) in rewardList"
                        :key="index"
                        class="flex flex-col gap-4"
                    >
                        <div class="b-t-[1px] border-grey-200 bg-grey-50">
                            <p class="text-sm font-grey-900 py-1 pl-[10px]">
                                Reward {{ index + 1 }}
                            </p>
                        </div>

                        <!-- Reward type selection -->
                        <Dropdown
                            :labelText="rewardList[index].type !== '' ? `Reward Type ${index + 1}` : 'Select Reward Type'"
                            :placeholder="'Select'"
                            :inputArray="rewardOption"
                            :inputName="'type'"
                            :hint-text="'The reward can only be redeemed once.'"
                            :dataValue="reward.type"
                            v-model="reward.type"
                            :errorMessage="form.errors.type"
                            @onChange="resetReward($event, index)"
                        />

                        <div v-if="reward.type !== null && reward.type !== undefined">
                            <div class="flex flex-col gap-3">
                                <!--Discount Amount & Discount Percent -->
                                <template v-if="reward.type === 'Discount (Amount)' || reward.type === 'Discount (Percentage)'">
                                    <div class="flex gap-3">
                                        <TextInput
                                            :iconPosition="reward.type === 'Discount (Amount)' ? 'left' : 'right'"
                                            :inputName="'amount'"
                                            :errorMessage="reward.error"
                                            :labelText="reward.type === 'Discount (Amount)' ? 'Discount Amount' : 'Discount Percentage'"
                                            inputId="amount"
                                            v-model="reward.amount"
                                            @keypress="isNumber($event)"
                                        >
                                            <template #prefix>{{ reward.type === 'Discount (Amount)' ? 'RM' : '%' }}</template>
                                        </TextInput>
                                        <TextInput
                                            v-if="reward.min_purchase === 'active'"
                                            :inputName="'min_purchase_amount'"
                                            :iconPosition="'left'"
                                            :errorMessage="reward.minPurchaseAmountError"
                                            labelText="Minimum purchase amount"
                                            inputId="min_purchase_amount"
                                            v-model="reward.min_purchase_amount"
                                            @keypress="isNumber($event)"
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
                                            inputId="min_purchase"
                                            v-model="reward.min_purchase"
                                            @change="() => toggleMinPurchase(index)"
                                        />
                                    </div>
                                </template>
                                
                                <!--Bonus Point -->
                                <template v-if="reward.type === 'Bonus Point'">
                                    <TextInput
                                        :labelText="'Bonus point get'"
                                        :placeholder="'10'"
                                        :iconPosition="'right'"
                                        v-model="reward.bonus_point"
                                        inputId="bonus_point"
                                        :inputName="'bonus_point'"
                                        :errorMessage="reward.bonuspoint"
                                        @keypress="isNumber($event)"
                                    >
                                        <template #prefix>pts</template>
                                    </TextInput>
                                </template>

                                <!--Free Item-->
                                <template v-if="reward.type === 'Free Item'">
                                    <div class="flex gap-4 items-end">
                                        <Dropdown
                                            :labelText="'Select an item'"
                                            :inputArray="inventoryItems"
                                            grouped
                                            :inputName="'free_item'"
                                            v-model="reward.free_item"
                                            placeholder="Select"
                                            class="w-full"
                                        />
                                        <div class="w-fit flex max-h-[44px]" v-if="reward.free_item !== ''">
                                            <NumberCounter
                                                :labelText="''"
                                                :inputName="'item_qty'"
                                                v-model="reward.item_qty"
                                            />
                                        </div>
                                    </div>
                                </template>

                                <!-- All except Bonus Point -->
                                <template v-if="reward.type !== 'Bonus Point'">
                                    <div class="flex gap-3 items-end">
                                    <Dropdown
                                        labelText="Valid Period"
                                        :placeholder="'Select'"
                                        :inputArray="periodOption"
                                        :dataValue="reward.valid_period"
                                        inputId="valid_period"
                                        :inputName="'valid_period'"
                                        v-model="reward.valid_period"
                                        :iconOptions="{
                                            'Customise range...': Calendar,
                                        }"
                                    />

                                    <DateInput
                                        v-if="reward.valid_period === 0"
                                        :labelText="''"
                                        :inputName="'date_range'"
                                        :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                        :range="true"
                                        v-model="reward.date_range"
                                    />
                                    </div>
                                </template>
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
                        @click="console.log(rewardList)"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        :size="'lg'"
                        :type="'submit'"
                        :disabled="isAddButtonDisabled"
                    >
                        Add
                    </Button>
                </div>
            </div>
        </div>
    </form>
</template>
