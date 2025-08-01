<script setup>
import Button from '@/Components/Button.vue';
import { Error404Illust, Error408Illust, Error503Illust, WarningReIllust, NatureFunIllust, ServerDownIllust } from '@/Components/Icons/illus';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue'
import { wTrans } from 'laravel-vue-i18n';

const props = defineProps({ status: Number })

const form = useForm({

});

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
        404: wTrans('public.error.error_404'),
        408: wTrans('public.error.error_408'),
        400: wTrans('public.error.error_400'),
        401: wTrans('public.error.error_401'),
        403: wTrans('public.error.error_403'),
        429: wTrans('public.error.error_429'),
        503: wTrans('public.error.error_503'),
        500: wTrans('public.error.error_500'),
        502: wTrans('public.error.error_502'),
    }[props.status]
})

const description = computed(() => {
    return {
        404: wTrans('public.error.error_404_message'),
        408: wTrans('public.error.error_408_message'),
        400: wTrans('public.error.error_400_message'),
        401: wTrans('public.error.error_401_message'),
        403: wTrans('public.error.error_403_message'),
        429: wTrans('public.error.error_429_message'),
        503: wTrans('public.error.error_503_message'),
        500: wTrans('public.error.error_500_message'),
        502: wTrans('public.error.error_502_message'),
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
    if (props.status !== 403) {
        window.location.href = '/dashboard';
    }

    form.post(route('logout'), {
        preserveScroll: true,
        preserveState: 'errors',
        onError: (error) => {
            console.error(error);
        }
    })
}

const login = () => {
    window.location.href = 'login';
}
</script>

<template>
    <Head :title=header />

    <div class="w-full h-screen inline-flex justify-center pr-16 lg:pr-[87.36px] items-center gap-20 lg:gap-28">
        
        <component :is="errorIllust" />

        <div class="flex flex-col items-start gap-[61px]">
            <div class="flex flex-col items-start gap-[33px]">
                <div class="flex flex-col items-start self-stretch">
                    <span class="text-primary-900 text-center max-h-[120px] text-[48px] sm:text-[60px] md:text-[72px] lg:text-[96px] not-italic font-extrabold tracking-[-1.92px] ">{{ title }}</span>
                    <span class="self-stretch text-primary-900 text-md sm:text-xl md:text-[36px] not-italic font-medium">{{ subtitle }}</span>
                </div>
                <span class="text-grey-900 text-base font-normal max-w-[440px]">{{ description }}</span>
            </div>

            <div class="flex items-start gap-4">
                <Button
                    :type="'button'"
                    :size="'lg'"
                    @click="previousPage"
                    v-if="props.status === 404"
                    class="!w-fit"
                >
                    {{ $t('public.action.take_me_back') }}
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
                    {{ $t('public.action.refresh') }}
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
                    {{ props.status === 403 ? $t('public.action.logout') : $t('public.action.go_to_homepage') }}
                </Button>

                <Button
                    :type="'button'"
                    :size="'lg'"
                    @click="login"
                    v-if="props.status === 401"
                    class="!w-fit"
                >
                    {{ $t('public.action.login') }}
                </Button>
            </div>
        </div>
    </div>
</template>