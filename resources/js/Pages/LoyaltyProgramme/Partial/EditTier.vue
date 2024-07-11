<script setup>
import { ref, computed, onMounted } from "vue";
import { defineProps } from "vue";
import Toggle from "@/Components/Toggle.vue";
import { useForm } from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import DateInput from "@/Components/Date.vue";
import NumberCounter from "@/Components/NumberCounter.vue";
import Dropdown from "@/Components/Dropdown.vue";
import axios from "axios";
import TextInput from "@/Components/TextInput.vue";
import { Calendar } from "@/Components/Icons/solid";
import Carlsbeg from "../../../../assets/images/Loyalty/Carlsbeg.svg";
import Tiger from "../../../../assets/images/Loyalty/Tiger.svg";
import DiscountAmount from "@/Pages/LoyaltyProgramme/Partial/DiscountAmount.vue";

const props = defineProps({
    id: String,
    initialData: Object,
});

const emit = defineEmits(["close"]);
const ranking = ref({});
const rankingRewards = ref([]);
onMounted(() => {
    fetchData(); // Fetch data when component is mounted
});
const periodOption = ref([
    { text: "1 month starting from entry date", value: 1 },
    { text: "3 months starting from entry date", value: 3 },
    { text: "6 months starting from entry date", value: 6 },
    { text: "12 months starting from entry date", value: 12 },
    { text: "Customise range...", value: 0 },
]);

//function to display the option for valid period
const calculateValidPeriod = (fromDate, toDate) => {
    const from = new Date(fromDate);
    const to = new Date(toDate);
    const diffInMonths =
        (to.getFullYear() - from.getFullYear()) * 12 +
        to.getMonth() -
        from.getMonth();

    switch (diffInMonths) {
        case 1:
            return 1;
        case 3:
            return 3;
        case 6:
            return 6;
        case 12:
            return 12;
        default:
            return 0; // Customise range...
    }
};

const calculateDateRange = (props) => {
    if (props.valid_period !== 0) {
        const from = new Date(props.valid_period_from);
        from.setMonth(from.getMonth() + props.valid_period);
        props.valid_period_to = from.toISOString().split("T")[0];
    }
};

//display date in format dd/mm/yyyy
const formatDate = (date) => {
    const d = new Date(date);
    const day = `0${d.getDate()}`.slice(-2);
    const month = `0${d.getMonth() + 1}`.slice(-2); // Months are zero-indexed
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
};

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

const fetchData = () => {
    axios
        .get(`/loyalty-programme/getTierData?id=${props.id}`)
        .then((response) => {
            ranking.value = response.data.ranking;
            //rankingRewards.value = response.data.rankingRewards;
            rankingRewards.value = response.data.rankingRewards.map(
                (reward) => {
                    const validPeriod = calculateValidPeriod(
                        reward.valid_period_from,
                        reward.valid_period_to
                    );
                    return {
                        ...reward,
                        valid_period: validPeriod,
                        date_range:
                            validPeriod === 0
                                ? `${formatDate(
                                      reward.valid_period_from
                                  )} - ${formatDate(reward.valid_period_to)}`
                                : null,
                    };
                }
            );
        })
        .catch((error) => {
            console.error("Error fetching data:", error);
        });
};

const submit = () => {
    axios
        .post(`/loyalty-programme/updateTierData?id=${props.id}`, {
            ranking: ranking.value,
            rankingRewards: rankingRewards.value,
        })
        .then((response) => {
            console.log("Form submitted successfully");
        })
        .catch((error) => {
            console.error("Error submitting form:", error);
        });
};

const addReward = () => {
    rankingRewards.value.push({
        reward_type: "",
        min_purchase: "inactive",
        min_purchase_amount: "",
        discount: "",
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
    const reward = rankingRewards.value[index];
    reward.min_purchase =
        reward.min_purchase === "active" ? "inactive" : "active";
};
</script>

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
                        v-model="ranking.name"
                    ></TextInput>
                </div>

                <div class="w-full flex gap-6">
                    <div class="flex flex-col gap-1">
                        <p class="text-xs">Select an icon</p>

                        <div
                            class="w-[308px] flex flex-wrap gap-4 items-center"
                        >
                            <!-- Icons  -->
                            <div class="flex flex-col gap-4 items-center">
                                <div
                                    class="flex w-[44px] h-[44px] border-grey-100 border-dashed border-[1px] rounded-[5px] bg-grey-25 items-center justify-center"
                                >
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
                        class="w-[366px] text-center-input"
                        :labelText="'Amount spend to achieve this tier'"
                        inputId="min_amount"
                        type="'text'"
                        v-model="ranking.min_amount"
                        :iconPosition="'left'"
                    >
                        <template #prefix> RM </template>
                    </TextInput>
                </div>
                <div class="justify-end flex gap-3">
                    <p class="font-normal">
                        This tier is entitled to entry reward(s)
                    </p>
                    <Toggle
                        class="xl:col-span-2"
                        :checked="ranking.reward === 'active'"
                        :inputName="'reward'"
                        inputId="reward"
                        v-model="ranking.reward"
                        @change="toggleReward(ranking.reward)"
                    />
                </div>

                <div
                    class="flex flex-col gap-6"
                    v-for="(props, index) in rankingRewards"
                    :key="index"
                >
                    <div class="flex flex-col gap-4">
                        <div class="b-t-[1px] border-grey-200 bg-grey-50">
                            <p class="text-sm font-grey-900 py-1 pl-[10px]">
                                Reward {{ index + 1 }}
                            </p>
                        </div>
                        <Dropdown
                            :placeholder="'Select'"
                            :inputArray="rewardOption"
                            inputId="type"
                            :inputName="'type'"
                            :hint-text="'The reward can only be redeemed once.'"
                            :dataValue="props.reward_type"
                            v-model="props.reward_type"
                        />

                        <!-- Discount Amount -->
                        <template
                            v-if="props.reward_type === 'Discount (Amount)'"
                        >
                            <DiscountAmount :periodOption="periodOption" />
                            <!-- Discount Amount fields -->
                        </template>
                        <!-- Discount Percentage -->
                        <template
                            v-if="props.reward_type === 'Discount (Percentage)'"
                        >
                            <div class="flex flex-col gap-3">
                                <div class="flex gap-3">
                                    <TextInput
                                        :labelText="'Discount Percentage'"
                                        :iconPosition="'right'"
                                        v-model="props.discount"
                                        inputId="amount"
                                    >
                                        <template #prefix>%</template>
                                    </TextInput>
                                    <TextInput
                                        :labelText="'Minimum purchase amount'"
                                        :iconPosition="'left'"
                                        v-if="props.min_purchase === 'active'"
                                        inputId="min_purchase_amount"
                                        :inputName="'min_purchase_amount'"
                                        v-model="props.min_purchase_amount"
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
                                            props.min_purchase === 'active'
                                        "
                                        :inputName="'min_purchase'"
                                        inputId="min_purchase"
                                        v-model="props.min_purchase"
                                        @change="() => toggleMinPurchase(index)"
                                    />
                                </div>
                                <div class="flex gap-3 items-end">
                                    <Dropdown
                                        :labelText="'Valid Period'"
                                        :placeholder="'Select'"
                                        :inputArray="periodOption"
                                        :dataValue="props.valid_period"
                                        inputId="valid_period"
                                        :inputName="'valid_period'"
                                        v-model="props.valid_period"
                                        @change="calculateDateRange(props)"
                                        :iconOptions="{
                                            'Customise range...': Calendar,
                                        }"
                                    ></Dropdown>

                                    <DateInput
                                        v-if="props.valid_period === 0"
                                        :labelText="''"
                                        :inputName="'date_range'"
                                        :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                        :range="true"
                                        v-model="props.date_range"
                                    ></DateInput>
                                </div>
                            </div>
                        </template>

                        <!--Bonus Point -->
                        <template v-if="props.reward_type === 'Bonus Point'">
                            <TextInput
                                :labelText="'Bonus point get'"
                                :placeholder="'10'"
                                :iconPosition="'right'"
                                v-model="props.bonus_point"
                                inputId="bonus_point"
                                :inputName="'bonus_point'"
                            >
                                <template #prefix>pts</template>
                            </TextInput>
                        </template>

                        <!--Free Item-->
                        <template v-if="props.reward_type === 'Free Item'">
                            <div class="flex flex-col gap-3">
                                <div class="flex gap-4 items-end">
                                    <Dropdown
                                        :labelText="'Select an item'"
                                        :inputArray="groupedItem"
                                        grouped
                                        :inputName="'free_item'"
                                        :dataValue="props.free_item"
                                        v-model="props.free_item"
                                        placeholder="Select"
                                        class="w-full"
                                    />

                                    <div class="w-fit flex max-h-[44px]">
                                        <NumberCounter
                                            :labelText="''"
                                            :inputName="'item_qty'"
                                            v-model="props.item_qty"
                                        />
                                    </div>
                                </div>
                                <div class="flex gap-3 items-end">
                                    <Dropdown
                                        :labelText="'Valid Period'"
                                        :placeholder="'Select'"
                                        :inputArray="periodOption"
                                        :dataValue="props.valid_period"
                                        inputId="valid_period"
                                        :inputName="'valid_period'"
                                        v-model="props.valid_period"
                                        @change="calculateDateRange(props)"
                                        :iconOptions="{
                                            'Customise range...': Calendar,
                                        }"
                                    ></Dropdown>

                                    <DateInput
                                        v-if="props.valid_period === 0"
                                        :labelText="''"
                                        :inputName="'date_range'"
                                        :placeholder="'DD/MM/YYYY - DD/MM/YYYY'"
                                        :range="true"
                                        v-model="props.date_range"
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

                <div class="flex gap-4 pt-3">
                    <Button
                        variant="tertiary"
                        :size="'lg'"
                        @click="form.reset()"
                    >
                        Cancel
                    </Button>
                    <Button variant="primary" :size="'lg'" :type="'submit'"
                        >Save Changes</Button
                    >
                </div>
            </div>
        </div>
    </form>
</template>
