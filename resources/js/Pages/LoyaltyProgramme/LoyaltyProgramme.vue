<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TabView from "@/Components/TabView.vue";
import Tier from "@/Pages/LoyaltyProgramme/Partial/Tier.vue";
import { ref } from "vue";
import Breadcrumb from '@/Components/Breadcrumb.vue';

const home = ref({
    label: 'Loyalty Programme',
});

const tabs = ref(["Points", "Tier"]);
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb 
                :home="home" 
            />
        </template>

        <TabView :tabs="tabs">
            <template #tab-0>
                <!--Points-->
            </template>
            <template #tab-1>
                <Tier />
            </template>
        </TabView>
    </AuthenticatedLayout>
</template>
