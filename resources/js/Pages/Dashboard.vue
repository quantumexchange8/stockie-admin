<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue'
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 ">
                    <div class="text-gray-900 mb-4">Buttons</div>
                    <div class="grid grid-cols-12 gap-8">
                        <!--  -------------------------Left Col----------------------------- -->
                        <div class="flex flex-col col-span-6">
                            <!-- ---------------Variant: Primary, Size: M------------- -->
                            <div class="flex flex-col gap-4 mb-12">
                                <!-- State: Default & Hover -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :size="'md'"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Left -->
                                    <Button
                                        :type="'button'"
                                        :size="'md'"
                                        :iconPosition="'left'"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Right -->
                                    <Button
                                        :type="'button'"
                                        :size="'md'"
                                        :iconPosition="'right'"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                                <!-- State: Disabled -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :size="'md'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Left -->
                                    <Button
                                        :type="'button'"
                                        :size="'md'"
                                        :iconPosition="'left'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Right -->
                                    <Button
                                        :type="'button'"
                                        :size="'md'"
                                        :iconPosition="'right'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                            </div>
                            <!-- ---------------Variant: Secondary, Size: M------------ -->
                            <div class="flex flex-col gap-4 mb-12">
                                <!-- State: Default & Hover -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :variant="'secondary'"
                                        :size="'md'"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Left -->
                                    <Button
                                        :type="'button'"
                                        :variant="'secondary'"
                                        :size="'md'"
                                        :iconPosition="'left'"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Right -->
                                    <Button
                                        :type="'button'"
                                        :variant="'secondary'"
                                        :size="'md'"
                                        :iconPosition="'right'"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                                <!-- State: Disabled -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :variant="'secondary'"
                                        :size="'md'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Left -->
                                    <Button
                                        :type="'button'"
                                        :variant="'secondary'"
                                        :size="'md'"
                                        :iconPosition="'left'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Right -->
                                    <Button
                                        :type="'button'"
                                        :variant="'secondary'"
                                        :size="'md'"
                                        :iconPosition="'right'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                            </div>
                            <!-- ---------------Variant: Tertiary, Size: M-------------- -->
                            <div class="flex flex-col gap-4 mb-12">
                                <!-- State: Default & Hover -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :variant="'tertiary'"
                                        :size="'md'"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Left -->
                                    <Button
                                        :type="'button'"
                                        :variant="'tertiary'"
                                        :size="'md'"
                                        :iconPosition="'left'"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Right -->
                                    <Button
                                        :type="'button'"
                                        :variant="'tertiary'"
                                        :size="'md'"
                                        :iconPosition="'right'"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                                <!-- State: Disabled -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :variant="'tertiary'"
                                        :size="'md'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Left -->
                                    <Button
                                        :type="'button'"
                                        :variant="'tertiary'"
                                        :size="'md'"
                                        :iconPosition="'left'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Right -->
                                    <Button
                                        :type="'button'"
                                        :variant="'tertiary'"
                                        :size="'md'"
                                        :iconPosition="'right'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                            </div>
                            <!-- ---------------Variant: Red, Size: M------------------- -->
                            <div class="flex flex-col gap-4 mb-12">
                                <!-- State: Default & Hover -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :variant="'red'"
                                        :size="'md'"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Left -->
                                    <Button
                                        :type="'button'"
                                        :variant="'red'"
                                        :size="'md'"
                                        :iconPosition="'left'"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Right -->
                                    <Button
                                        :type="'button'"
                                        :variant="'red'"
                                        :size="'md'"
                                        :iconPosition="'right'"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                                <!-- State: Disabled -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :variant="'red'"
                                        :size="'md'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Left -->
                                    <Button
                                        :type="'button'"
                                        :variant="'red'"
                                        :size="'md'"
                                        :iconPosition="'left'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                    <!-- Icon: Right -->
                                    <Button
                                        :type="'button'"
                                        :variant="'red'"
                                        :size="'md'"
                                        :iconPosition="'right'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                            </div>
                        </div>
                        
                        <!--  -------------------------Right Col----------------------------- -->
                        <!-- Variant: Primary, Size: L -->
                        <div class="flex flex-col col-span-6">
                            <!-- ---------------Variant: Primary, Size: M------------- -->
                            <div class="flex flex-col gap-4 mb-12">
                                <!-- State: Default & Hover -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :size="'lg'"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                                <!-- State: Disabled -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :size="'lg'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                            </div>
                            <!-- ---------------Variant: Secondary, Size: M------------ -->
                            <div class="flex flex-col gap-4 mb-12">
                                <!-- State: Default & Hover -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :size="'lg'"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                                <!-- State: Disabled -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :size="'lg'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                            </div>
                            <!-- ---------------Variant: Tertiary, Size: M-------------- -->
                            <div class="flex flex-col gap-4 mb-12">
                                <!-- State: Default & Hover -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :size="'lg'"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                                <!-- State: Disabled -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :size="'lg'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                            </div>
                            <!-- ---------------Variant: Red, Size: M------------------- -->
                            <div class="flex flex-col gap-4 mb-12">
                                <!-- State: Default & Hover -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :size="'lg'"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                                <!-- State: Disabled -->
                                <div class="flex gap-6">
                                    <!-- Icon: No -->
                                    <Button
                                        :type="'button'"
                                        :size="'lg'"
                                        :disabled="true"
                                    >
                                        Button CTA
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
