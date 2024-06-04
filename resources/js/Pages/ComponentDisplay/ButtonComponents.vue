<script setup>
import Button from '@/Components/Button.vue'
</script>

<template>
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 justify-items-center">
        <!--  -------------------------Left Col----------------------------- -->
        <div class="flex flex-col col-span-1 xl:col-span-6">
            <h2>Medium Sized Buttons</h2>
            <h2>Primary</h2>
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
            <h2>Secondary</h2>
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
            <h2>Tertiary</h2>
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
            <h2>Red</h2>
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
        <div class="flex flex-col col-span-1 xl:col-span-6">
            <h2>Large Sized Buttons</h2>
            <h2>Primary</h2>
            <!-- ---------------Variant: Primary, Size: L------------- -->
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
                    <!-- Icon: Left -->
                    <Button
                        :type="'button'"
                        :size="'lg'"
                        :iconPosition="'left'"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Right -->
                    <Button
                        :type="'button'"
                        :size="'lg'"
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
                        :size="'lg'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Left -->
                    <Button
                        :type="'button'"
                        :size="'lg'"
                        :iconPosition="'left'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Right -->
                    <Button
                        :type="'button'"
                        :size="'lg'"
                        :iconPosition="'right'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                </div>
            </div>
            <h2>Secondary</h2>
            <!-- ---------------Variant: Secondary, Size: L------------ -->
            <div class="flex flex-col gap-4 mb-12">
                <!-- State: Default & Hover -->
                <div class="flex gap-6">
                    <!-- Icon: No -->
                    <Button
                        :type="'button'"
                        :variant="'secondary'"
                        :size="'lg'"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Left -->
                    <Button
                        :type="'button'"
                        :variant="'secondary'"
                        :size="'lg'"
                        :iconPosition="'left'"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Right -->
                    <Button
                        :type="'button'"
                        :variant="'secondary'"
                        :size="'lg'"
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
                        :size="'lg'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Left -->
                    <Button
                        :type="'button'"
                        :variant="'secondary'"
                        :size="'lg'"
                        :iconPosition="'left'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Right -->
                    <Button
                        :type="'button'"
                        :variant="'secondary'"
                        :size="'lg'"
                        :iconPosition="'right'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                </div>
            </div>
            <h2>Tertiary</h2>
            <!-- ---------------Variant: Tertiary, Size: L-------------- -->
            <div class="flex flex-col gap-4 mb-12">
                <!-- State: Default & Hover -->
                <div class="flex gap-6">
                    <!-- Icon: No -->
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Left -->
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        :iconPosition="'left'"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Right -->
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
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
                        :size="'lg'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Left -->
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        :iconPosition="'left'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Right -->
                    <Button
                        :type="'button'"
                        :variant="'tertiary'"
                        :size="'lg'"
                        :iconPosition="'right'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                </div>
            </div>
            <h2>Red</h2>
            <!-- ---------------Variant: Red, Size: L------------------- -->
            <div class="flex flex-col gap-4 mb-12">
                <!-- State: Default & Hover -->
                <div class="flex gap-6">
                    <!-- Icon: No -->
                    <Button
                        :type="'button'"
                        :variant="'red'"
                        :size="'lg'"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Left -->
                    <Button
                        :type="'button'"
                        :variant="'red'"
                        :size="'lg'"
                        :iconPosition="'left'"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Right -->
                    <Button
                        :type="'button'"
                        :variant="'red'"
                        :size="'lg'"
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
                        :size="'lg'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Left -->
                    <Button
                        :type="'button'"
                        :variant="'red'"
                        :size="'lg'"
                        :iconPosition="'left'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                    <!-- Icon: Right -->
                    <Button
                        :type="'button'"
                        :variant="'red'"
                        :size="'lg'"
                        :iconPosition="'right'"
                        :disabled="true"
                    >
                        Button CTA
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
