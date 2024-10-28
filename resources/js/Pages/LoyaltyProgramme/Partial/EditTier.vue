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
import axios from "axios";
import TextInput from "@/Components/TextInput.vue";
import Accordion from "@/Components/Accordion.vue";
import { Calendar } from "@/Components/Icons/solid";
import Carlsbeg from "../../../../assets/images/Loyalty/Carlsbeg.svg";
import Tiger from "../../../../assets/images/Loyalty/Tiger.svg";
// import DiscountAmount from "@/Pages/LoyaltyProgramme/Partial/DiscountAmount.vue";
import { periodOption, rewardOption, emptyReward } from "@/Composables/constants";
import Toast from '@/Components/Toast.vue'
import { useInputValidator } from "@/Composables";

const props = defineProps({
    tier: {
        type: Object,
        default: () => {},
    },
    inventoryItems: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close"]);
const { isValidNumberKey } = useInputValidator();
// const ranking = ref({});
// const rankingRewards = ref([]);

// onMounted(() => {
// });

//function to display the option for valid period
const calculateValidPeriod = (fromDate, toDate) => {
    return dayjs(toDate).diff(dayjs(fromDate), 'month');
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

const fetchData = () => {
    // axios
    //     .get(`/loyalty-programme/getTierData?id=${props.id}`)
    //     .then((response) => {
    //         ranking.value = response.data.ranking;
    //         //rankingRewards.value = response.data.rankingRewards;
    //         rankingRewards.value = response.data.rankingRewards.map(
    //             (reward) => {
    //                 const validPeriod = calculateValidPeriod(
    //                     reward.valid_period_from,
    //                     reward.valid_period_to
    //                 );
    //                 return {
    //                     ...reward,
    //                     valid_period: validPeriod,
    //                     date_range:
    //                         validPeriod === 0
    //                             ? `${formatDate(
    //                                   reward.valid_period_from
    //                               )} - ${formatDate(reward.valid_period_to)}`
    //                             : null,
    //                 };
    //             }
    //         );
    //     })
    //     .catch((error) => {
    //         console.error("Error fetching data:", error);
    //     });
};

const form = useForm({
    id:props.tier.id,
    name: props.tier.name,
    min_amount: props.tier.min_amount.toString(),
    reward: props.tier.reward,
    rewards: props.tier.ranking_rewards.map((reward) => {
        const validPeriod = calculateValidPeriod(reward.valid_period_from, reward.valid_period_to);

        reward.item_qty = reward.item_qty ? parseInt(reward.item_qty) : 1;
        reward.min_purchase_amount = reward.min_purchase_amount ? reward.min_purchase_amount.toString() : '';

        return {
            ...reward,
            valid_period: validPeriod,
            date_range:
                validPeriod === 0
                    ? `${dayjs(reward.valid_period_from).format('DD/MM/YYYY')} - ${dayjs(reward.valid_period_to).format('DD/MM/YYYY')}`
                    : null,
        };
    }),
    icon: props.tier.icon,
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
    // axios
    //     .post(`/loyalty-programme/updateTierData?id=${props.id}`, {
    //         ranking: ranking.value,
    //         rankingRewards: rankingRewards.value,
    //     })
    //     .then((response) => {
    //         console.log("Form submitted successfully");
    //     })
    //     .catch((error) => {
    //         console.error("Error submitting form:", error);
    //     });
    
    form.rewards.forEach(item => {
        item.item_qty = item.item_qty ? item.item_qty.toString() : '';
        item.free_item = item.free_item ? item.free_item.toString() : '';
    });
    
    form.put(`/loyalty-programme/tiers/update/${props.tier.id}`, {
        preserveScroll: true,
        preserveState: 'errors',
        onSuccess: () => {
            closeModal()
        },
    })
};

</script>

<template>
    <form @submit.prevent="submit">
        <div class="max-h-[773px] overflow-y-scroll scrollbar-webkit scrollbar-thin p-2">
            <div class="w-full flex flex-col gap-6">
                <div class="flex flex-col gap-4">
                    <TextInput
                        :placeholder="'eg: Gold / VIP etc'"
                        :labelText="'Tier Name'"
                        :errorMessage="form.errors?.name  ?? ''"
                        inputName="name"
                        type="'text'"
                        v-model="form.name"
                    />
                </div>
                <div class="w-full flex flex-wrap sm:flex-nowrap gap-6">
                    <div class="flex flex-col gap-1">
                        <p class="text-xs">Select an icon</p>

                        <div class="w-[308px] flex flex-wrap gap-4 items-center">
                            <!-- Icons  -->
                            <div class="flex flex-col gap-4 items-center">
                                <div class="flex w-[44px] h-[44px] border-grey-100 border-dashed border-[1px] rounded-[5px] bg-grey-25 items-center justify-center">
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
                        :errorMessage="form.errors?.min_amount  ?? ''"
                        :iconPosition="'left'"
                        type="'text'"
                        v-model="form.min_amount"
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
                                            <div class="flex gap-3 flex-wrap sm:flex-nowrap items-end justify-center">
                                                <Dropdown
                                                    :labelText="'Select an item'"
                                                    :inputArray="inventoryItems"
                                                    grouped
                                                    :inputName="'free_item_' + index"
                                                    :errorMessage="form.errors ? form.errors['items.' + index + '.free_item']  : ''"
                                                    :dataValue="reward.inventory_item?.id ?? ''"
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
                    >
                        Save Changes
                    </Button>
                </div>
            </div>
        </div>
    </form>
</template>
