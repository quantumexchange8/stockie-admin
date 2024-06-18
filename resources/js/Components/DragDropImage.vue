<script setup>
import { ref } from 'vue';
import FileUpload from 'primevue/fileupload';
import Button from './Button.vue';
import { CircledTimesIcon } from './Icons/solid';

const props = defineProps({
    inputName: String,
    errorMessage: String,
	modelValue: [String, Date, Array],
    disabled: {
        type: Boolean,
        default: false,
    },
});

const hasFile = ref(false);

const emit = defineEmits(['update:modelValue']);

const selectFile = (files) => {
    hasFile.value = true;
    emit('update:modelValue', files[0].name);
}
</script>

<template>
    <div 
        class="w-full h-[244px] rounded-[5px] bg-grey-50 outline-dashed outline-2 outline-grey-200"
    >
        <FileUpload 
            :name="props.inputName" 
            @select="selectFile($event.files)" 
            accept="image/*" 
            :maxFileSize="6000000"
            :fileLimit="1"
            :pt="{
                input: {
                    class: 'hidden'
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
                <div class="flex flex-col justify-center gap-3 p-3 w-full h-full self-stretch items-center" v-if="files.length === 0">
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
                        </div>
                    </div>
                    <Button 
                        @click="chooseCallback()" 
                        :type="'button'"
                        :size="'md'"
                        class="!w-fit"
                    >
                        Select an image
                    </Button>
                    <p class="text-grey-300 text-xs font-medium">Suggested image size: 1920 x 1080 pixel</p>
                </div>
            </template>
            <template #content="{ files, uploadedFiles, removeUploadedFileCallback, removeFileCallback }">
                <div v-if="files.length > 0">
                    <div class="w-full h-full flex self-stretch items-center">
                        <img 
                            role="presentation" 
                            :alt="files[0].name" 
                            :src="files[0].objectURL" 
                            class="h-[244px] w-full object-contain"
                        />
                        <CircledTimesIcon 
                            class="absolute top-72 left-1/2 w-6 h-6 fill-white text-primary-900 hover:text-primary-500 cursor-pointer"
                            @click="removeFileCallback(); hasFile = false"  
                        />
                    </div>
                </div>
            </template>
        </FileUpload>
    </div>
</template>
