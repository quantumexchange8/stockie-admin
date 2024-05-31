<script setup>
import TextInput from '@/Components/TextInput.vue'
</script>

<template>
    <div class="flex flex-col gap-16 items-center">
        <!--  -------------------------Labelled Text Input----------------------------- -->
        <div class="flex flex-col gap-2 w-max-[100%]">
            <!-- ---------------State: Default------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <TextInput
                    :inputId="'label'"
                    class="col-span-full xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: Yes, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: No, Error: Yes -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
            </div>
            <!-- ---------------State: Hover------------ -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: Yes, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: No, Error: Yes -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
            </div>
            <!-- ---------------State: Active-------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: Yes, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: No, Error: Yes -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
            </div>
            <!-- ---------------State: Filled------------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: Yes, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: No, Error: Yes -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
            </div>
            <!-- ---------------State: Disabled------------------- -->
            <div class="flex gap-20 mb-12">
                <!-- Hint: No, Error: No -->
                <TextInput
                >
                </TextInput>
            </div>
        </div>
        
        <!--  -------------------------Text Input--------------------------------------- -->
        <!-- Variant: Primary, Size: L -->
        <div class="flex flex-col gap-4">
            <!-- ---------------Variant: Primary, Size: M------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: Yes, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: No, Error: Yes -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
            </div>
            <!-- ---------------Variant: Secondary, Size: M------------ -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: Yes, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: No, Error: Yes -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
            </div>
            <!-- ---------------Variant: Tertiary, Size: M-------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: Yes, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: No, Error: Yes -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
            </div>
            <!-- ---------------Variant: Red, Size: M------------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: Yes, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
                <!-- Hint: No, Error: Yes -->
                <TextInput
                    class="xl:col-span-4"
                >
                </TextInput>
            </div>
            <!-- ---------------Variant: Red, Size: M------------------- -->
            <div class="flex gap-20 mb-12">
                <!-- Hint: No, Error: No -->
                <TextInput
                >
                </TextInput>
            </div>
        </div>
    </div>
</template>
