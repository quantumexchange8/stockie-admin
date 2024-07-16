<script setup>
import { ref, computed } from 'vue';
import FileUpload from 'primevue/fileupload';
import Button from './Button.vue';
import { CircledTimesIcon } from './Icons/solid';

const props = defineProps({
    inputName: String,
    errorMessage: String,
	modelValue: [String, File],
    imageClass: {
        type: String,
        default: 'h-60',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const hasFile = ref(false);

const emit = defineEmits(['update:modelValue']);

const selectFile = (files) => {
    hasFile.value = files.length > 0;
    emit('update:modelValue', files.length > 0 ? files[0] : '');
}

const removeFile = (removeFileCallback) => {
    removeFileCallback();
    emit('update:modelValue', '');
}

const imageClasses = computed(() => [
    'w-full h-full object-cover rounded-[5px]',
    props.imageClass
])
</script>

<template>
    <div 
        class="w-full flex items-center justify-center rounded-[5px] bg-grey-50 outline-dashed outline-2 outline-grey-200"
    >
        <FileUpload 
            :name="props.inputName" 
            @select="selectFile($event.files)" 
            accept="image/*" 
            :maxFileSize="6000000"
            :fileLimit="1"
            :disabled="hasFile"
            :class="[
                {
                    'hover:cursor-no-drop': hasFile
                }
            ]"
            :pt="{
                root: {
                    class: 'relative w-full h-full'
                },
                message: {
                    class: 'absolute flex flex-row justify-between self-stretch w-full border border-primary-800 bg-blue'
                },
                input: {
                    class: 'hidden'
                },
                content: {
                    class: 'w-full h-full'
                },
                empty: {
                    class: 'w-full h-full flex flex-col items-center justify-center'
                },
                buttonbar: ({ files }) => {
                    return {
                        class: [
                            {
                                'hidden': hasFile
                            }
                        ]
                    }
                },
            }"
        >
            <template #header="{ chooseCallback, uploadCallback, clearCallback, files }">
                <div class="flex justify-center items-center w-full">
                    <Button 
                        @click="chooseCallback()" 
                        :type="'button'"
                        :size="'md'"
                        class="!w-fit absolute bottom-28"
                    >
                        Select an image
                    </Button>
                </div>
            </template>
            <template #content="{ files, uploadedFiles, removeUploadedFileCallback, removeFileCallback }">
                <div 
                    v-if="files.length > 0"
                    class="relative w-full h-full flex self-stretch" 
                    :class="[
                        {
                            'hover:cursor-no-drop': hasFile
                        }
                    ]">
                    <img 
                        role="presentation" 
                        :alt="files[0].name" 
                        :src="files[0].objectURL" 
                        :class="imageClasses"
                    />
                    <CircledTimesIcon 
                        class="absolute top-2 right-2 w-6 h-6 fill-white text-primary-900 hover:text-primary-500 cursor-pointer"
                        @click="removeFile(removeFileCallback); hasFile = false;"  
                    />
                </div>
            </template>
            <template #empty>
                <div class="flex flex-col justify-center gap-3 p-3 w-full h-full self-stretch items-center">
                    <div class="flex flex-col justify-center items-center pt-2">
                        <svg 
                            xmlns="http://www.w3.org/2000/svg" 
                            width="60" 
                            height="61" 
                            viewBox="0 0 60 61" 
                            fill="none"
                        >
                            <path 
                                d="M10 41.1056C6.98504 39.0874 5 35.6505 5 31.75C5 25.8911 9.47877 21.0782 15.1994 20.5484C16.3695 13.4303 22.5506 8 30 
                                8C37.4494 8 43.6305 13.4303 44.8006 20.5484C50.5212 21.0782 55 25.8911 55 31.75C55 35.6505 53.015 39.0874 50 41.1056M20 
                                40.5L30 30.5M30 30.5L40 40.5M30 30.5V53" 
                                stroke="#B2BEC7" 
                                stroke-width="4" 
                                stroke-linecap="round" 
                                stroke-linejoin="round"
                            />
                        </svg>
                        <div class="flex flex-col justify-center items-center gap-1">
                            <p class="text-lg font-semibold text-grey-300">Drag & drop</p>
                            <p class="text-base font-normal text-grey-300">or</p>
                            <div class="h-11"></div>
                        </div>
                    </div>
                    <p class="text-grey-300 text-xs font-medium">Suggested image size: 1920 x 1080 pixel</p>
                </div>
            </template>
        </FileUpload>
    </div>
</template>
