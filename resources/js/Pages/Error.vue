<script setup>
import Button from '@/Components/Button.vue';
import { Error404Illust, Error408Illust, Error503Illust } from '@/Components/Icons/illus';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue'

const props = defineProps({ status: Number })

const header = computed(() => {
    return {
        503: '503: Service Unavailable',
        408: '408: Request Timeout',
        404: '404: Page Not Found',
    }[props.status]
})

const title = computed(() => {
    return {
        503: 'Oops!',
        408: 'Whoops!',
        404: 'Uh-oh!',
    }[props.status]
})

const subtitle = computed(() => {
    return {
        503: 'Caught us tidying up🔧',
        408: 'Connection lost.',
        404: 'Page does not exist',
    }[props.status]
})

const description = computed(() => {
    return {
        503: 'Our developers are working hard to improve your experience with us. Sit back and have a sip of coffee ☕️, your journey will resume shortly.',
        408: 'It seems like your internet is on a vacation⛱️. Please try to reconnect your Wi-Fi or refresh the page to see if it helps.',
        404: `We can’t seem to find the page you’re looking for. <br> Meanwhile, please try again or go back to the previous page.`,
    }[props.status]
})

const previousPage = () => {
    window.history.go(-1);
}

const refresh = () => {
    window.location.reload();
}
</script>

<template>
    <Head :title=header />

    <div class="w-full h-screen inline-flex justify-center pr-[87.36px] items-center gap-[110px]">
        
        <template v-if="props.status === 503"><Error503Illust /></template>
        <template v-if="props.status === 408"><Error408Illust /></template>
        <template v-if="props.status === 404"><Error404Illust /></template>

        <div class="flex flex-col items-start gap-[61px]">
            <div class="flex flex-col items-start gap-[33px]">
                <div class="flex flex-col items-start self-stretch">
                    <span class="text-primary-900 text-center max-h-[120px] text-[96px] not-italic font-extrabold tracking-[-1.92px] ">
                        {{ title }}
                    </span>
                    <span class="self-stretch text-primary-900 text-[36px] not-italic font-medium">
                        {{ subtitle }}
                    </span>
                </div>
                <span v-html="description" class="text-grey-900 text-base font-normal max-w-[440px]"></span>
            </div>

            <Button
                :type="'submit'"
                :size="'lg'"
                @click="previousPage"
                v-if="props.status === 404"
                class="!w-fit"
            >
                Take me back
            </Button>

            <Button
                :type="'button'"
                :size="'lg'"
                @click="refresh"
                v-if="props.status === 408"
                class="!w-fit"

            >
                Refresh
            </Button>
        </div>
    </div>
</template>