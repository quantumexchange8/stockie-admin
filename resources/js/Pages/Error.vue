<script setup>
import Button from '@/Components/Button.vue';
import { Error404Illust, Error408Illust, Error503Illust, WarningReIllust, NatureFunIllust, ServerDownIllust } from '@/Components/Icons/illus';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue'

const props = defineProps({ status: Number })

const header = computed(() => {
    return {
        404: '404: Page Not Found',
        408: '408: Request Timeout',
        400: '400: Bad Request',
        401: '401: Unauthorized',
        403: '403: Forbidden',
        429: '429: Too many request',
        503: '503: Service Unavailable',
        500: '500: Internal Server Error',
        502: '502: Bad Gateway'
    }[props.status]
})

const title = computed(() => {
    return {
        404: 'Uh-oh!',
        408: 'Whoops!',
        400: 'Oops!',
        401: 'Uh-oh!',
        403: 'Uh-oh!',
        429: 'Whoa',
        503: 'Oops!',
        500: 'Oops!',
        502: 'Uh-oh!'
    }[props.status]
})

const subtitle = computed(() => {
    return {
        404: 'Page does not exist',
        408: 'Connection lost.',
        400: 'That’s not quite right',
        401: 'Access denied',
        403: 'No peeking allowed',
        429: 'slow down there!',
        503: 'Caught us tidying up🔧',
        500: 'Something went wrong',
        502: 'Trouble on the bridge',
    }[props.status]
})

const description = computed(() => {
    return {
        404: `We can’t seem to find the page you’re looking for. <br> Meanwhile, please try again or go back to the previous page.`,
        408: 'It seems like your internet is on a vacation⛱️. Please try to reconnect your Wi-Fi or refresh the page to see if it helps.',
        400: 'It seems like your request got tangled up on its way here. Double-check the URL or try again.',
        401: 'Looks like you’re trying to sneak in without a pass. Log in or provide the right credentials, and we’ll gladly open the gates for you.',
        403: 'Seems like this area is off-limits. Check with your administrator if you think you should have access.',
        429: 'You’ve been sending requests faster than we can keep up! We’ve temporarily paused to catch our breath. Please give it a moment, and try again soon. ',
        503: 'Our developers are working hard to improve your experience with us. Sit back and have a sip of coffee ☕️, your journey will resume shortly.',
        500: 'Our server ran into a hiccup while trying to process your request. We’re on it! In the meantime, feel free to refresh or come back later.',
        502: 'We’re having a bit of trouble communicating with another server. It’s not you—it’s us. Hang tight, and we’ll get it fixed ASAP.',
    }[props.status]
})

const errorMap = {
    404: Error404Illust,
    408: Error408Illust,
    400: WarningReIllust,
    401: WarningReIllust,
    403: WarningReIllust,
    429: NatureFunIllust,
    503: Error503Illust,
    500: ServerDownIllust,
    502: ServerDownIllust,
};

const errorIllust = computed(() => errorMap[props.status] || null);

const previousPage = () => {
    window.history.go(-1);
}

const refresh = () => {
    window.location.reload();
}

const homepage = () => {
    window.location.href = '/dashboard';
}

const login = () => {
    window.location.href = 'login';
}
</script>

<template>
    <Head :title=header />

    <div class="w-full h-screen inline-flex justify-center pr-[87.36px] items-center gap-[110px]">
        
        <component :is="errorIllust" />

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

            <div class="flex items-start gap-4">
                <Button
                    :type="'button'"
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
                    v-if="  props.status === 408 || 
                            props.status === 400 || 
                            props.status === 429 || 
                            props.status === 500 || 
                            props.status === 502"
                    class="!w-fit"
                >
                    Refresh
                </Button>

                <Button
                    :type="'button'"
                    :size="'lg'"
                    :variant="props.status === 403 ? 'primary' : 'secondary'"
                    @click="homepage"
                    v-if="  props.status === 400 ||
                            props.status === 403 ||
                            props.status === 429 ||
                            props.status === 500 || 
                            props.status === 502"
                    class="!w-fit"
                >
                    Go to home page
                </Button>

                <Button
                    :type="'button'"
                    :size="'lg'"
                    @click="login"
                    v-if="props.status === 401"
                    class="!w-fit"
                >
                    Log in now
                </Button>
            </div>
        </div>
    </div>
</template>