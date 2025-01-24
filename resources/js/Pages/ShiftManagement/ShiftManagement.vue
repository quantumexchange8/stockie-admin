<script setup>
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Button from '@/Components/Button.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const home = ref({
    label: 'Shift Control',
})

const spoiler = ref(false);
const showSpoiler = () => {
    spoiler.value = !spoiler.value;
}
</script>

<template>
    <Head title="Shift Control" />

    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :home="home" />
        </template>

        <!-- <Button
            :variant="'primary'"
            :type="'button'"
            :size="'md'"
            @click="showSpoiler"
            class="!w-fit"
        >
            Spoiler
        </Button>
        <img src="https://i.imgur.com/rO77IUN.jpeg" class="w-[500px]" v-if="spoiler"> -->
    </AuthenticatedLayout>        
</template>

