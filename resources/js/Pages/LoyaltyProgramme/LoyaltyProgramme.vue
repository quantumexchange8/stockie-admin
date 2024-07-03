<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TabView from "@/Components/TabView.vue";
import Tier from "@/Pages/LoyaltyProgramme/Partial/Tier.vue";
import { ref } from "vue";
const tabs = ref(["Points", "Tier"]);
</script>

<template>
    <AuthenticatedLayout>
        <template #header>Loyalty Programme </template>

        <TabView :tabs="tabs">
            <template #tab-0>
                <!--Points-->
            </template>
            <template #tab-1>
                <Tier></Tier>
            </template>
        </TabView>
    </AuthenticatedLayout>
</template>
