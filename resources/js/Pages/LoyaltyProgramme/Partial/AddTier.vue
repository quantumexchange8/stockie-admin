<script setup>
import TextInput from "@/Components/TextInput.vue";
import Toggle from "@/Components/Toggle.vue";
import Button from "@/Components/Button.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DateInput from "@/Components/Date.vue";
import NumberCounter from "@/Components/NumberCounter.vue";
import { Calendar } from "@/Components/Icons/solid";
import { useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Carlsbeg from "../../../../assets/images/Loyalty/Carlsbeg.svg";
import Tiger from "../../../../assets/images/Loyalty/Tiger.svg";
const emit = defineEmits(["close"]);

const rewardOption = ref([
    {
        text: "Discount (Amount)",
        value: "Discount (Amount)",
    },
    {
        text: "Discount (Percentage)",
        value: "Discount (Percentage)",
    },
    {
        text: "Bonus Point",
        value: "Bonus Point",
    },
    {
        text: "Free Item",
        value: "Free Item",
    },
]);

const periodOption = ref([
    { text: "1 month starting from entry date", value: 1 },
    { text: "3 months starting from entry date", value: 3 },
    { text: "6 months starting from entry date", value: 6 },
    { text: "12 months starting from entry date", value: 12 },
    { text: "Customise range...", value: 0 },
]);

const groupedItem = ref([
    {
        group_name: "Carlsberg",
        image: Carlsbeg,
        items: [
            {
                text: "Carlsberg bottle 500ml",
                value: "Carlsberg bottle 500ml",
            },
            { text: "Carlsberg can 300ml", value: "Carlsberg can 300ml" },
            { text: "Carlsberg pint 500ml", value: "Carlsberg pint 500ml" },
            { text: "Carlsberg tower", value: "Carlsberg bucket" },
        ],
    },
    {
        group_name: "Tiger",
        image: Tiger,
        items: [
            { text: "Tiger bottle 500ml", value: "Tiger bottle 500ml" },
            { text: "Tiger can 300ml", value: "Tiger can 300ml" },
            { text: "Tiger pint 500ml", value: "Tiger pint 500ml" },
        ],
    },
]);

const form = useForm({
    name: "",
    min_amount: "",
    reward: "inactive",
    rewards: [],
});

const rewardList = ref([
    {
        type: "",
        min_purchase: "inactive",
        min_purchase_amount: "",
        amount: "",
        valid_period: "",
        date_range: "",
        valid_period_from: "",
        valid_period_to: "",
        bonus_point: "",
        free_item: "",
        item_qty: 0,
        error: "",
    },
]);
const validateAmount = (reward) => {
    const value = reward.amount;
    const value2 = reward.min_purchase_amount;
    const value3 = reward.bonus_point;
    const isNumeric = /^\d+(\.\d+)?$/.test(value);
    const isNumeric2 = /^\d+(\.\d+)?$/.test(value2);
    const isNumeric3 = /^\d+(\.\d+)?$/.test(value3);
    if (!isNumeric) {
        reward.error = "Invalid Value";
    } else {
        reward.error = "";
    }

    if (!isNumeric2) {
        reward.minPurchaseAmountError = "Invalid Value"; // Adjust error property name as needed
    } else {
        reward.minPurchaseAmountError = "";
    }

    if (!isNumeric3) {
        reward.bonuspoint = "Invalid Value"; // Adjust error property name as needed
    } else {
        reward.bonuspoint = "";
    }
};

const addReward = () => {
    rewardList.value.push({
        type: "",
        min_purchase: "inactive",
        min_purchase_amount: "",
        amount: "",
        valid_period: "",
        date_range: "",
        valid_period_from: "",
        valid_period_to: "",
        bonus_point: "",
        free_item: "",
        item_qty: 0,
    });
};

const toggleMinPurchase = (index) => {
    rewardList.value[index].min_purchase =
        rewardList.value[index].min_purchase === "active"
            ? "inactive"
            : "active";
};

const toggleReward = () => {
    if (form.reward === "active") {
        rewardList.value.forEach((reward) => {
            reward.type = "";
            reward.min_purchase = "inactive";
            reward.min_purchase_amount = "";
            reward.amount = "";
            reward.valid_period = "";
            reward.date_range = "";
            reward.valid_period_from = "";
            reward.valid_period_to = "";
            reward.bonus_point = "";
            reward.free_item = "";
            reward.item_qty = 0;
        });
    }
    form.reward = form.reward === "active" ? "inactive" : "active";
};

const submit = () => {
    rewardList.value.forEach((reward) => {
        if (reward.valid_period === 0 && reward.date_range !== "") {
            const startDate = new Date(reward.date_range[0]);
            const endDate = new Date(reward.date_range[1]);

            startDate.setHours(0, 0, 0, 0);
            endDate.setHours(0, 0, 0, 0);

            reward.valid_period_from =
                (startDate.getFullYear() < 10
                    ? "0" + startDate.getFullYear()
                    : startDate.getFullYear()) +
                "/" +
                (startDate.getMonth() + 1 < 10
                    ? "0" + (startDate.getMonth() + 1)
                    : startDate.getMonth() + 1) +
                "/" +
                (startDate.getDate() < 10
                    ? "0" + startDate.getDate()
                    : startDate.getDate());
            reward.valid_period_to =
                (endDate.getFullYear() < 10
                    ? "0" + endDate.getFullYear()
                    : endDate.getFullYear()) +
                "/" +
                (endDate.getMonth() + 1 < 10
                    ? "0" + (endDate.getMonth() + 1)
                    : endDate.getMonth() + 1) +
                "/" +
                (endDate.getDate() < 10
                    ? "0" + endDate.getDate()
                    : endDate.getDate());
        } else if (reward.valid_period) {
            const startDate = new Date();
            startDate.setHours(0, 0, 0, 0);

            const endDate = new Date(startDate);
            endDate.setMonth(endDate.getMonth() + reward.valid_period);
            endDate.setHours(23, 59, 59, 999); // Include the entire end day

            reward.valid_period_from = `${startDate.getFullYear()}-${(
                startDate.getMonth() + 1
            )
                .toString()
                .padStart(2, "0")}-${startDate
                .getDate()
                .toString()
                .padStart(2, "0")}`;
            reward.valid_period_to = `${endDate.getFullYear()}-${(
                endDate.getMonth() + 1
            )
                .toString()
                .padStart(2, "0")}-${endDate
                .getDate()
                .toString()
                .padStart(2, "0")}`;
        }
    });

    form.rewards = rewardList.value;

    form.post(route("loyalty.create-tier"), {
        onSuccess: () => {
            console.log("Form submitted successfully!");
            closeModal();
        },
        onError: (error) => {
            console.error(error);
        },
    });
};

const closeModal = () => {
    form.reset();
    form.errors = {};
    emit("close");
};
const getDropdownLabel = (type, index) => {
    if (type !== null) {
        return `Reward Type ${index + 1}`;
    } else {
        return "Select Reward Type";
    }
};

const isAddButtonDisabled = computed(() => {
    for (const reward of rewardList.value) {
        if (form.reward === "active" && reward.type === "") {
            return true;
        } else if (
            reward.type === "Discount (Amount)" ||
            reward.type === "Discount (Percentage)"
        ) {
            if (
                !reward.amount ||
                (reward.min_purchase === "active" &&
                    !reward.min_purchase_amount) ||
                reward.valid_period === "" ||
                (reward.valid_period === 0 && !reward.date_range)
            ) {
                return true;
            }
        } else if (reward.type === "Bonus Point" && !reward.bonus_point) {
            return true;
        } else if (reward.type === "Free Item") {
            if (
                reward.free_item === "" ||
                reward.valid_period === "" ||
                (reward.valid_period === 0 && !reward.date_range)
            ) {
                return true;
            }
        }
    }
    return false; // Enable button if all required fields are filled
});
</script>

<style>
.text-center-input input[type="text"] {
    text-align: center;
}
</style>

<template>
    <form @submit.prevent="submit">
        <div
            class="max-h-[773px] overflow-y-scroll scrollbar-webkit scrollbar-thin p-2"
        >
            <div class="w-full flex flex-col gap-6">
                <div class="flex flex-col gap-4">
                    <TextInput
                        :placeholder="'eg: Gold / VIP etc'"
                        :labelText="'Tier Name'"
                        inputId="name"
                        type="'text'"
                        v-model="form.name"
                        :errorMessage="form.errors.name"
                    ></TextInput>

                    <div class="w-full flex gap-6">
                        <div class="flex flex-col gap-1">
                            <p class="text-xs">Select an icon</p>
                            <div class="w-[308px] flex gap-4">
                                <div
                                    class="w-[44px] h-[44px] border-[#D6DCE1] border-[1px] rounded-[5px] border-dashed bg-[#F8F9FA]"
                                ></div>
                                <div
                                    class="w-[44px] h-[44px] border-[#D6DCE1] border-[1px] rounded-[5px] border-dashed bg-[#F8F9FA]"
                                ></div>
                                <div
                                    class="w-[44px] h-[44px] border-[#D6DCE1] border-[1px] rounded-[5px] border-dashed bg-[#F8F9FA]"
                                ></div>
                                <div
                                    class="w-[44px] h-[44px] border-[#D6DCE1] border-[1px] rounded-[5px] border-dashed bg-[#F8F9FA]"
                                ></div>
                                <div
                                    class="w-[44px] h-[44px] border-[#D6DCE1] border-[1px] rounded-[5px] border-dashed bg-[#F8F9FA]"
                                ></div>
                            </div>
                        </div>

                        <TextInput
                            class="w-[366px] text-center-input"
                            :labelText="'Amount spend to achieve this tier'"
                            inputId="min_amount"
                            type="'text'"
                            v-model="form.min_amount"
                            :iconPosition="'left'"
                            :errorMessage="form.errors.min_amount"
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
                <div
                    class="flex flex-col gap-6"
                    v-if="form.reward === 'active'"
                >
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
                            :labelText="getDropdownLabel(reward.type, index)"
                            :placeholder="'Select'"
                            :inputArray="rewardOption"
                            inputId="type"
                            :inputName="'type'"
                            :hint-text="'The reward can only be redeemed once.'"
                            :dataValue="reward.type"
                            v-model="reward.type"
                            :errorMessage="form.errors.type"
                        />

                        <div
                            v-if="
                                reward.type !== null &&
                                reward.type !== undefined
                            "
                        >
                            <!-- Discount Amount -->
                            <template
                                v-if="reward.type === 'Discount (Amount)'"
                            >
                                <!-- Discount Amount fields -->
                                <div class="flex flex-col gap-3">
                                    <div class="flex gap-3">
                                        <TextInput
                                            :labelText="'Discount Amount'"
                                            :iconPosition="'left'"
                                            v-model="reward.amount"
                                            inputId="amount"
                                            :errorMessage="reward.error"
                                            @input="validateAmount(reward)"
                                        >
                                            <template #prefix>RM</template>
                                        </TextInput>
                                        <TextInput
                                            :labelText="'Minimum purchase amount'"
                                            :iconPosition="'left'"
                                            v-if="
                                                reward.min_purchase === 'active'
                                            "
                                            inputId="min_purchase_amount"
                                            :inputName="'min_purchase_amount'"
                                            v-model="reward.min_purchase_amount"
                                            :errorMessage="
                                                reward.minPurchaseAmountError
                                            "
                                            @input="validateAmount(reward)"
                                        >
                                            <template #prefix>RM</template>
                                        </TextInput>
                                    </div>
                                    <div class="justify-end flex gap-3">
                                        <p class="font-normal">
                                            With minimum purchase
                                        </p>
                                        <Toggle
                                            class="xl:col-span-2"
                                            :checked="
                                                form.min_purchase === 'active'
                                            "
                                            :inputName="'min_purchase'"
                                            inputId="min_purchase"
                                            v-model="reward.min_purchase"
                                            @change="
                                                () => toggleMinPurchase(index)
                                            "
                                        />
                                    </div>
                                    <div class="flex gap-3 items-end">
                                        <Dropdown
                                            :labelText="'Valid Period'"
                                            :placeholder="'Select'"
                                            :inputArray="periodOption"
                                            :dataValue="reward.valid_period"
                                            inputId="valid_period"
                                            :inputName="'valid_period'"
                                            v-model="reward.valid_period"
                                            :iconOptions="{
                                                'Customise range...': Calendar,
                                            }"
                                        ></Dropdown>

                                        <DateInput
                                            v-if="reward.valid_period === 0"
                                            :labelText="''"
                                            :inputName="'date_range'"
                                            :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                            :range="true"
                                            v-model="reward.date_range"
                                        ></DateInput>
                                    </div>
                                </div>
                            </template>

                            <!--Discount Percent -->
                            <template
                                v-if="reward.type === 'Discount (Percentage)'"
                            >
                                <div class="flex flex-col gap-3">
                                    <div class="flex gap-3">
                                        <TextInput
                                            labelText="Discount Percentage"
                                            :iconPosition="'right'"
                                            v-model="reward.amount"
                                            inputId="amount"
                                            :inputName="'amount'"
                                            :errorMessage="reward.error"
                                            @input="validateAmount(reward)"
                                        >
                                            <template #prefix>%</template>
                                        </TextInput>
                                        <TextInput
                                            labelText="Minimum purchase amount"
                                            inputId="min_purchase_amount"
                                            :inputName="'min_purchase_amount'"
                                            v-model="reward.min_purchase_amount"
                                            :iconPosition="'left'"
                                            v-if="
                                                reward.min_purchase === 'active'
                                            "
                                            :errorMessage="
                                                reward.minPurchaseAmountError
                                            "
                                            @input="validateAmount(reward)"
                                        >
                                            <template #prefix>RM</template>
                                        </TextInput>
                                    </div>
                                    <div class="justify-end flex gap-3">
                                        <p class="font-normal">
                                            With minimum purchase
                                        </p>
                                        <Toggle
                                            class="xl:col-span-2"
                                            :checked="
                                                form.min_purchase === 'active'
                                            "
                                            :inputName="'min_purchase'"
                                            inputId="min_purchase"
                                            v-model="reward.min_purchase"
                                            @change="
                                                () => toggleMinPurchase(index)
                                            "
                                        />
                                    </div>
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
                                        ></Dropdown>

                                        <DateInput
                                            v-if="reward.valid_period === 0"
                                            :labelText="''"
                                            :inputName="'date_range'"
                                            :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                            :range="true"
                                            v-model="reward.date_range"
                                        ></DateInput>
                                    </div>
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
                                    @input="validateAmount(reward)"
                                >
                                    <template #prefix>pts</template>
                                </TextInput>
                            </template>

                            <!--Free Item-->
                            <template v-if="reward.type === 'Free Item'">
                                <div class="flex flex-col gap-3">
                                    <div class="flex gap-4 items-end">
                                        <Dropdown
                                            :labelText="'Select an item'"
                                            :inputArray="groupedItem"
                                            grouped
                                            :inputName="'free_item'"
                                            v-model="reward.free_item"
                                            placeholder="Select"
                                            class="w-full"
                                        />
                                        <div
                                            class="w-fit flex max-h-[44px]"
                                            v-if="reward.free_item !== ''"
                                        >
                                            <NumberCounter
                                                :labelText="''"
                                                :inputName="'item_qty'"
                                                v-model="reward.item_qty"
                                            />
                                        </div>
                                    </div>

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
                                        ></Dropdown>

                                        <DateInput
                                            v-if="reward.valid_period === 0"
                                            :labelText="''"
                                            :inputName="'date_range'"
                                            :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                            :range="true"
                                            v-model="reward.date_range"
                                        ></DateInput>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Button to add more rewards -->
                    <Button
                        variant="secondary"
                        :size="'lg'"
                        @click="addReward"
                        :type="'button'"
                        >Another Reward</Button
                    >
                </div>

                <div class="flex gap-4 pt-3">
                    <Button
                        variant="tertiary"
                        :size="'lg'"
                        @click="form.reset()"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        :size="'lg'"
                        :type="'submit'"
                        :disabled="isAddButtonDisabled"
                        >Add</Button
                    >
                </div>
            </div>
        </div>
    </form>
</template>
