<script setup>
import TextInput from '@/Components/TextInput.vue'
</script>

<template>
    <div class="flex flex-col gap-16 items-center">
        <!--  -------------------------Labelled Text Input----------------------------- -->
        <h2>Labelled Text Input</h2>
        <div class="flex flex-col gap-2 w-max-[100%]">
            <!-- ---------------State: Default & Hover------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No --> 
                <TextInput
                    :inputId="'label'"
                    class="col-span-full xl:col-span-4"
                    :labelText="'Label'"
                />
                <!-- Hint: Yes, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                    :hintText="'Hint text to help user'"
                    :labelText="'Label'"
                />
                <!-- Hint: No, Error: Yes -->
                <TextInput
                    class="xl:col-span-4"
                    :errorMessage="'This is an error message'"
                    :labelText="'Label'"
                />
            </div>
            <!-- ---------------State: Active-------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                    :disabled="true"
                    :labelText="'Label'"
                />
            </div>
        </div>
        
        <!--  -------------------------Text Input--------------------------------------- -->
        <h2>Text Input</h2>
        <div class="flex flex-col gap-4">
            <!-- ---------------State: Default & Hover------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No --> 
                <TextInput
                    :inputId="'label'"
                    class="col-span-full xl:col-span-4"
                />
                <!-- Hint: Yes, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                    :hintText="'Hint text to help user'"
                />
                <!-- Hint: No, Error: Yes -->
                <TextInput
                    class="xl:col-span-4"
                    :errorMessage="'This is an error message'"
                />
            </div>
            <!-- ---------------State: Active-------------- -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-20 mb-12 self-center">
                <!-- Hint: No, Error: No -->
                <TextInput
                    class="xl:col-span-4"
                    :disabled="true"
                />
            </div>
        </div>
    </div>
</template>
