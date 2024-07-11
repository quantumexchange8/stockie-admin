<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { defineProps } from "vue";
import MemberList from "./MemberList.vue";
import axios from "axios";
import { ref, onMounted } from "vue";
import Dialog from "primevue/dialog";
import Button from "@/Components/Button.vue";
import {
    ViewIcon,
    ReplenishIcon,
    EditIcon,
    DeleteIcon,
} from "@/Components/Icons/solid";
import Modal from "@/Components/Modal.vue";
import EditTier from "@/Pages/LoyaltyProgramme/Partial/EditTier.vue";
const emit = defineEmits(["close"]);

const visible = ref(false);
const isModalOpen = ref(false);
const openModal = () => {
    isModalOpen.value = true;
};
const closeModal = () => {
    isModalOpen.value = false;
};

const props = defineProps({
    id: String,
});

const customers = ref([]);
const ranking = ref([]);
const rankingRewards = ref([]);

onMounted(() => {
    fetchData(); // Fetch data when component is mounted
});

const fetchData = () => {
    axios
        .get(`/loyalty-programme/getMemberList?id=${props.id}`)
        .then((response) => {
            customers.value = response.data.customers;
            ranking.value = response.data.ranking;
            rankingRewards.value = response.data.rankingRewards;
        })
        .catch((error) => {
            console.error("Error fetching customers:", error);
        });
};

const calculateValidPeriod = (validFrom, validTo) => {
    const startDate = new Date(validFrom);
    const endDate = new Date(validTo);

    // Adjust for UTC+8 timezone
    startDate.setHours(startDate.getHours() + 8);
    endDate.setHours(endDate.getHours() + 8);

    const startDateString = startDate.toLocaleDateString("en-MY"); // Adjust locale as needed
    const endDateString = endDate.toLocaleDateString("en-MY"); // Adjust locale as needed

    const diffInMonths =
        (endDate.getFullYear() - startDate.getFullYear()) * 12 +
        (endDate.getMonth() - startDate.getMonth());

    if (diffInMonths === 1) {
        return "1 month starting from entry date";
    } else if (diffInMonths === 3) {
        return "3 months starting from entry date";
    } else if (diffInMonths === 6) {
        return "6 months starting from entry date";
    } else if (diffInMonths === 12) {
        return "12 months starting from entry date";
    } else {
        return `${startDateString} to ${endDateString}`;
    }
};
</script>

<template>
    <Head title="Tier Detail" />

    <AuthenticatedLayout>
        <template #header> Loyalty Programme > Tier Detail </template>

        <div class="w-full flex gap-5">
            <div
                class="w-full flex flex-col p-6 gap-6 rounded-[5px] border border-red-100"
            >
                <div
                    class="flex flex-col p-6 gap-6 rounded-[5px] border border-red-100"
                >
                    <span class="text-primary-900 text-md font-medium">
                        Member Spending
                    </span>
                </div>
                <div
                    class="flex flex-col p-6 gap-6 rounded-[5px] border border-red-100"
                >
                    <MemberList :id="id" />
                </div>
            </div>
            <div
                class="w-full flex flex-col p-6 gap-6 rounded-[5px] border border-red-100"
            >
                <div class="flex justify-between">
                    <span class="text-primary-900 font-medium text-md">
                        Tier Detail</span
                    >
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="text-primary-900 hover:text-primary-600"
                            @click="openModal"
                        >
                            <EditIcon
                                class="text-primary-900 hover:text-primary-600"
                            />
                            <Modal
                                :show="isModalOpen"
                                @close="closeModal"
                                :title="'Add New Tier'"
                                :maxWidth="'md'"
                            >
                                <EditTier :id="id" />
                            </Modal>
                        </button>

                        <button
                            type="button"
                            class="text-primary-900 hover:text-primary-600"
                        >
                            <DeleteIcon
                                class="text-primary-900 hover:text-primary-600"
                            />
                        </button>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="w-6 h-6 rounded-full bg-gray-500"></div>

                    {{ ranking.name }}
                </div>
                <div
                    class="rounded-[5px] border-primary-100 border-[1px] flex gap-4 p-3 items-center"
                >
                    <div class="bg-primary-50 p-[9px] rounded-[2px]">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 16 16"
                            fill="none"
                        >
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M9.28528 15L8.44243 12.0388C8.73142 11.9694 8.99929 11.8323 9.22095 11.6368C9.3198 11.5496 9.42904 11.5221 9.56094 11.5511C10.4736 11.7515 11.3971 11.2521 11.6732 10.4093L12.7324 14.1305L10.748 13.6488L9.28528 15V15ZM7.27073 2.2713C6.99622 2.5134 6.64198 2.60277 6.27564 2.52231C5.72851 2.40217 5.17557 2.70268 5.01254 3.20884C4.90339 3.5477 4.64403 3.79186 4.28408 3.89461C3.74644 4.04809 3.42719 4.56863 3.55481 5.0837C3.64025 5.42855 3.54535 5.76205 3.28818 6.02048C2.90394 6.4066 2.90394 7.00738 3.28818 7.39354C3.54535 7.65196 3.64025 7.98547 3.55481 8.33032C3.42719 8.84538 3.74644 9.36592 4.28408 9.5194C4.64403 9.62218 4.90339 9.86631 5.01254 10.2052C5.17557 10.7113 5.72851 11.0118 6.27564 10.8917C6.64195 10.8113 6.99622 10.9006 7.27073 11.1427C7.68089 11.5044 8.31907 11.5045 8.72926 11.1427C9.00377 10.9006 9.35804 10.8113 9.72435 10.8917C10.2715 11.0118 10.8244 10.7113 10.9875 10.2052C11.0966 9.86631 11.356 9.62218 11.7159 9.5194C12.2535 9.36592 12.5728 8.84538 12.4452 8.33032C12.3597 7.98547 12.4546 7.65196 12.7118 7.39354C13.096 7.00741 13.0961 6.40663 12.7118 6.02048C12.4546 5.76205 12.3597 5.42855 12.4452 5.0837C12.5728 4.56863 12.2535 4.04809 11.7159 3.89461C11.356 3.79186 11.0966 3.5477 10.9875 3.20884C10.8244 2.70268 10.2715 2.40217 9.72435 2.52231C9.35804 2.60275 9.00377 2.5134 8.72926 2.2713C8.31907 1.90955 7.68092 1.90958 7.27073 2.2713ZM7.99999 4.16983L8.79344 5.94673L10.8338 6.10804L9.28384 7.36754L9.75137 9.24413L7.99999 8.24563L6.24862 9.24413L6.71615 7.36754L5.16622 6.10804L7.20655 5.94673L7.99999 4.16983ZM6.71471 15L5.25201 13.6488L3.26764 14.1305L4.3268 10.4092C4.60286 11.2521 5.52641 11.7515 6.43905 11.551C6.57095 11.5221 6.68019 11.5496 6.77904 11.6368C7.0007 11.8323 7.26857 11.9694 7.55756 12.0388L6.71471 14.9999V15Z"
                                fill="#7E171B"
                            />
                        </svg>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-grey-900 text-base font-medium"
                            >Spend</span
                        >
                        <span class="text-primary-900 text-base font-semibold"
                            >RM {{ ranking.min_amount }}
                        </span>
                    </div>
                </div>
                <div
                    v-if="ranking.reward === 'active'"
                    class="flex flex-col gap-6"
                >
                    <div
                        class="rounded-[5px] bg-primary-50 text-primary-900 text-sm font-semibold py-2 px-3"
                    >
                        Entry Reward for this Tier
                    </div>
                    <div
                        v-for="(props, index) in rankingRewards"
                        :key="index"
                        class="flex flex-col gap-6"
                    >
                        <!--Discount Amount || Discount Percentage -->
                        <div
                            class="flex flex-col gap-6"
                            v-if="
                                props.reward_type === 'Discount (Amount)' ||
                                props.reward_type === 'Discount (Percentage)'
                            "
                        >
                            <div class="flex gap-3">
                                <div
                                    class="rounded-[1.5px] w-[60px] h-[60px] bg-primary-50 p-[13px]"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="34"
                                        height="35"
                                        viewBox="0 0 34 35"
                                        fill="none"
                                    >
                                        <path
                                            d="M27.0273 20.4883C26.4778 20.4883 26.0312 20.9348 26.0312 21.4844C26.0312 22.034 26.4778 22.4805 27.0273 22.4805C27.5769 22.4805 28.0234 22.034 28.0234 21.4844C28.0234 20.9348 27.5769 20.4883 27.0273 20.4883Z"
                                            fill="#7E171B"
                                        />
                                        <path
                                            d="M33.0039 6.54297H10.0436V9.53125C10.0436 10.0818 9.59809 10.5273 9.04752 10.5273C8.49695 10.5273 8.05143 10.0818 8.05143 9.53125V6.54297H0.996094C0.44552 6.54297 0 6.98849 0 7.53906V13.5156C0 14.0662 0.44552 14.5117 0.996094 14.5117C2.6439 14.5117 3.98438 15.8522 3.98438 17.5C3.98438 19.1478 2.6439 20.4883 0.996094 20.4883C0.44552 20.4883 0 20.9338 0 21.4844V27.4609C0 28.0115 0.44552 28.457 0.996094 28.457H7.96875V25.4688C7.96875 24.9182 8.41427 24.4727 8.96484 24.4727C9.51542 24.4727 9.96094 24.9182 9.96094 25.4688V28.457H33.0039C33.5545 28.457 34 28.0115 34 27.4609V7.53906C34 6.98849 33.5545 6.54297 33.0039 6.54297ZM9.96094 21.4844C9.96094 22.0349 9.51542 22.4805 8.96484 22.4805C8.41427 22.4805 7.96875 22.0349 7.96875 21.4844V19.4922C7.96875 18.9416 8.41427 18.4961 8.96484 18.4961C9.51542 18.4961 9.96094 18.9416 9.96094 19.4922V21.4844ZM9.96094 15.5078C9.96094 16.0584 9.51542 16.5039 8.96484 16.5039C8.41427 16.5039 7.96875 16.0584 7.96875 15.5078V13.5156C7.96875 12.9651 8.41427 12.5195 8.96484 12.5195C9.51542 12.5195 9.96094 12.9651 9.96094 13.5156V15.5078ZM16.0703 13.5156C16.0703 11.8678 17.4108 10.5273 19.0586 10.5273C20.7064 10.5273 22.0469 11.8678 22.0469 13.5156C22.0469 15.1634 20.7064 16.5039 19.0586 16.5039C17.4108 16.5039 16.0703 15.1634 16.0703 13.5156ZM18.5061 24.3053C18.048 23.9998 17.9244 23.3822 18.2298 22.924L26.1986 10.9709C26.5031 10.5127 27.1208 10.3901 27.5799 10.6946C28.038 11.0001 28.1616 11.6177 27.8562 12.0759L19.8874 24.0291C19.5869 24.4804 18.9715 24.6138 18.5061 24.3053ZM27.0273 24.4727C25.3795 24.4727 24.0391 23.1322 24.0391 21.4844C24.0391 19.8366 25.3795 18.4961 27.0273 18.4961C28.6751 18.4961 30.0156 19.8366 30.0156 21.4844C30.0156 23.1322 28.6751 24.4727 27.0273 24.4727Z"
                                            fill="#7E171B"
                                        />
                                        <path
                                            d="M19.0586 12.5195C18.509 12.5195 18.0625 12.966 18.0625 13.5156C18.0625 14.0652 18.509 14.5117 19.0586 14.5117C19.6082 14.5117 20.0547 14.0652 20.0547 13.5156C20.0547 12.966 19.6082 12.5195 19.0586 12.5195Z"
                                            fill="#7E171B"
                                        />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span
                                        class="text-grey-900 font-medium text-sm"
                                        >Entry Reward for
                                        {{ ranking.name }}</span
                                    >

                                    <span
                                        class="text-primary-950 font-medium text-base"
                                    >
                                        <template
                                            v-if="
                                                props.reward_type ===
                                                'Discount (Amount)'
                                            "
                                        >
                                            RM {{ props.discount }} Discount
                                        </template>
                                        <template v-else>
                                            {{ props.discount }} % Discount
                                        </template>
                                    </span>
                                    <span
                                        class="text-primary-900 font-normal text-[10px]"
                                    >
                                        <template
                                            v-if="
                                                props.min_purchase_amount !==
                                                null
                                            "
                                        >
                                            Min. spend: RM
                                            {{ props.min_purchase_amount }}
                                        </template>
                                        <template v-else>
                                            No min. spend
                                        </template>
                                    </span>
                                    <span
                                        class="text-grey-400 font-normal text-[10px]"
                                    >
                                        Valid Period:

                                        {{
                                            calculateValidPeriod(
                                                props.valid_period_from,
                                                props.valid_period_to // Ensure UTC format
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- Bonus Point -->
                        <div
                            class="flex flex-col"
                            v-if="props.reward_type === 'Bonus Point'"
                        >
                            <div class="flex gap-3">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="60"
                                    height="60"
                                    viewBox="0 0 60 60"
                                    fill="none"
                                >
                                    <rect
                                        x="0.5"
                                        y="0.5"
                                        width="59"
                                        height="59"
                                        rx="1"
                                        fill="#FFF1F2"
                                    />
                                    <rect
                                        x="0.5"
                                        y="0.5"
                                        width="59"
                                        height="59"
                                        rx="1"
                                        stroke="#FFE1E2"
                                    />
                                    <path
                                        d="M29.9652 28.2074H28.8564C28.8612 29.8025 28.857 29.1851 28.8631 30.4912C29.2714 30.4892 29.7082 30.4873 29.9652 30.4873C30.6059 30.4873 31.1469 29.9654 31.1469 29.3473C31.1469 28.7299 30.6059 28.2074 29.9652 28.2074Z"
                                        fill="#7E171B"
                                    />
                                    <path
                                        d="M33.8822 24.5672L30.003 20.6169L26.1543 24.5645C26.0799 24.6409 25.9944 24.7046 25.9001 24.7537L20.7939 27.4034L23.4828 32.218C23.5459 32.3309 23.5857 32.4544 23.6017 32.5819L24.3181 38.4037L29.802 37.1907C29.9441 37.1595 30.0908 37.1595 30.2328 37.1914L35.6881 38.4037L36.433 32.5772C36.449 32.4524 36.4881 32.3323 36.5492 32.222L39.2122 27.4034L34.132 24.7524C34.0395 24.7039 33.9552 24.6416 33.8822 24.5672ZM29.9651 32.479C29.7075 32.479 29.2647 32.4809 28.8537 32.4829V34.7801C28.8537 35.3298 28.4076 35.776 27.8578 35.776C27.3081 35.776 26.8619 35.3298 26.8619 34.7801V27.2149C26.8619 26.6699 27.3057 26.2157 27.8578 26.2157H29.9651C31.7152 26.2157 33.1386 27.6205 33.1386 29.3473C33.1386 31.0741 31.7152 32.479 29.9651 32.479Z"
                                        fill="#7E171B"
                                    />
                                    <path
                                        d="M42.0181 17.9819C35.3757 11.3395 24.6246 11.3393 17.9819 17.9819C11.3395 24.6244 11.3393 35.3754 17.9819 42.0181C24.6244 48.6606 35.3755 48.6608 42.0181 42.0181C48.6606 35.3756 48.6608 24.6246 42.0181 17.9819ZM41.4484 27.4738L38.3839 33.0188L37.5248 39.739C37.4499 40.3185 36.8935 40.7121 36.3204 40.5848L30.0159 39.1839L23.6783 40.5848C23.1037 40.7113 22.5468 40.3159 22.4746 39.7343L21.648 33.0188L18.5536 27.4778C18.2798 26.986 18.4669 26.3667 18.9646 26.1082L24.8395 23.0596L29.287 18.4978C29.6724 18.1029 30.3149 18.0913 30.7104 18.4952L35.1924 23.0596L41.0375 26.1095C41.5326 26.3682 41.719 26.9841 41.4484 27.4738Z"
                                        fill="#7E171B"
                                    />
                                </svg>
                                <div class="flex flex-col gap-1">
                                    <span
                                        class="text-grey-900 font-medium text-sm"
                                        >Entry Reward for
                                        {{ ranking.name }}</span
                                    >
                                    <span
                                        class="text-primary-950 font-medium text-base"
                                    >
                                        {{ props.bonus_point }} Bonus Point
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Free Item -->
                        <div
                            class="flex flex-col"
                            v-if="props.reward_type === 'Free Item'"
                        >
                            <div class="flex gap-3">
                                <div
                                    class="rounded-[1.5px] w-[60px] h-[60px] bg-primary-50 p-[13px]"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="34"
                                        height="35"
                                        viewBox="0 0 34 35"
                                        fill="none"
                                    >
                                        <g clip-path="url(#clip0_526_6961)">
                                            <path
                                                d="M23.9481 7.29953L20.8589 9.53304L22.0413 13.1552C22.2143 13.6848 22.0371 14.2397 21.59 14.5688C21.1429 14.8983 20.5645 14.9007 20.115 14.575L17 12.323L13.885 14.5753C13.4355 14.9002 12.8572 14.8982 12.41 14.5688C11.9629 14.2397 11.7857 13.6848 11.9586 13.1552L13.1411 9.53304L10.0519 7.29954C9.60049 6.97289 9.41957 6.41829 9.59007 5.8859C9.76056 5.35381 10.2297 5.0102 10.7854 5.0102H14.6179L15.8065 1.36922C15.9795 0.84115 16.4474 0.5 17 0.5C17.5526 0.5 18.0205 0.841149 18.1935 1.36891L19.3821 5.0102H23.2146C23.7703 5.0102 24.2394 5.35382 24.4099 5.8859C24.5804 6.41829 24.3995 6.97288 23.9481 7.29953ZM11.1129 15.0275C10.6376 14.3674 10.5106 13.5414 10.7652 12.7616L11.229 11.3406L4.71922 12.7641L11.4681 15.4205C11.3381 15.3025 11.2175 15.1727 11.1129 15.0275ZM12.472 23.6161C12.759 23.7281 13.0813 23.6179 13.2395 23.3527L16.0791 18.592L2.82104 13.3736L0.090311 17.951C-0.00628517 18.113 -0.0262186 18.3008 0.0344986 18.4797C0.0952158 18.6589 0.225235 18.7953 0.400334 18.8644L12.472 23.6161ZM22.531 15.4208L29.2808 12.7641L22.7711 11.3405L23.2348 12.761C23.4894 13.5411 23.3624 14.3671 22.8871 15.0269C22.7823 15.1724 22.6613 15.3025 22.531 15.4208ZM20.846 16.079C20.3204 16.079 19.8144 15.9137 19.3815 15.6009L17 13.8788L14.6179 15.6012C14.1856 15.9137 13.6796 16.079 13.154 16.079H13.1528C13.1482 16.079 13.1438 16.0783 13.1392 16.0782L17 17.5978L20.861 16.0781C20.856 16.0781 20.851 16.079 20.846 16.079ZM33.9097 17.9507L31.179 13.3736L17.9209 18.592L20.7605 23.3527C20.9184 23.6179 21.2416 23.7284 21.528 23.6161L33.5997 18.8644C33.7748 18.7953 33.9048 18.6589 33.9655 18.4797C34.0262 18.3008 34.0063 18.113 33.9097 17.9507ZM21.2965 24.9239C20.6513 24.9239 20.0334 24.5892 19.6835 24.0029L17.628 20.557V34.5L30.8754 29.2861C31.6028 28.9995 32.0725 28.3067 32.0725 27.5211V20.8221L21.9855 24.7925C21.7598 24.8813 21.5265 24.9239 21.2965 24.9239ZM12.7035 24.9239C12.4735 24.9239 12.2405 24.8814 12.0145 24.7925L1.92746 20.8222V27.5211C1.92746 28.3067 2.39726 28.9995 3.12494 29.2861L16.372 34.5V20.557L14.3165 24.0029C13.9666 24.5893 13.3484 24.9239 12.7035 24.9239Z"
                                                fill="#7E171B"
                                            />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_526_6961">
                                                <rect
                                                    width="34"
                                                    height="34"
                                                    fill="white"
                                                    transform="translate(0 0.5)"
                                                />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span
                                        class="text-grey-900 font-medium text-sm"
                                        >Entry Reward for
                                        {{ ranking.name }}</span
                                    >
                                    <span
                                        class="text-primary-950 font-medium text-base"
                                    >
                                        {{ props.item_qty }}x
                                        {{ props.free_item }}
                                    </span>
                                    <span
                                        class="text-grey-400 font-normal text-[10px]"
                                    >
                                        Valid Period:
                                        {{
                                            calculateValidPeriod(
                                                props.valid_period_from,
                                                props.valid_period_to
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
