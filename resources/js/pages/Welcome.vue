<script setup lang="ts">
import MarketingSiteHeader from '@/components/MarketingSiteHeader.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, documentation, login, register } from '@/routes';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';

const props = withDefaults(
    defineProps<{
        canRegister: boolean;
        demoVideoId?: string;
    }>(),
    {
        canRegister: true,
        demoVideoId: '',
    },
);

const { t } = useTranslations();
const page = usePage();

const features = computed(() =>
    Array.from({ length: 9 }, (_, index) => {
        const n = index + 1;

        return {
            title: t(`welcome.feature_${n}_title`),
            description: t(`welcome.feature_${n}_description`),
        };
    }),
);

const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    name: t('welcome.app_name'),
    applicationCategory: 'DeveloperApplication',
    operatingSystem: 'Web',
    offers: {
        '@type': 'Offer',
        price: '0',
        priceCurrency: 'USD',
    },
    description: t('welcome.meta_description'),
    featureList: features.value.map((feature) => feature.title),
    url: typeof window !== 'undefined' ? window.location.origin : '',
    author: {
        '@type': 'Person',
        name: 'Ashwin Mohan Ram',
        email: 'ashwinmram@gmail.com',
    },
}));

onMounted(() => {
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.textContent = JSON.stringify(structuredData.value);
    script.id = 'structured-data';
    document.head.appendChild(script);
});

onUnmounted(() => {
    const script = document.getElementById('structured-data');
    if (script) {
        script.remove();
    }
});
</script>

<template>
    <Head>
        <title>{{ t('welcome.meta_title') }}</title>
        <meta name="description" :content="t('welcome.meta_description')" />
        <meta name="keywords" :content="t('welcome.meta_keywords')" />
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <!-- Header Navigation -->
        <MarketingSiteHeader />

        <!-- Hero Section -->
        <section class="mx-auto w-full max-w-7xl px-6 py-16 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-4xl text-center">
                <div class="mb-6 flex justify-center">
                    <img
                        src="/favicon.png"
                        :alt="t('welcome.app_name')"
                        class="h-16 w-auto object-contain sm:h-20"
                    />
                </div>
                <h2
                    class="mb-6 text-4xl font-bold tracking-tight text-[#1b1b18] sm:text-5xl lg:text-6xl dark:text-[#EDEDEC]"
                >
                    {{ t('welcome.hero_title') }}
                    <span class="block" style="color: #ce9e47">
                        {{ t('welcome.hero_accent') }}
                    </span>
                </h2>
                <p
                    class="mx-auto mb-8 max-w-2xl text-lg leading-8 text-[#706f6c] dark:text-[#A1A09A]"
                >
                    {{ t('welcome.hero_description') }}
                </p>
                <div
                    class="mb-12 flex flex-col items-center justify-center gap-4 sm:flex-row"
                >
                    <Link
                        v-if="!page.props.auth.user && canRegister"
                        :href="register.url()"
                        class="inline-block rounded-sm border border-[#19140035] bg-[#1b1b18] px-8 py-3 text-base font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white"
                    >
                        {{ t('welcome.get_started') }}
                    </Link>
                    <Link
                        v-if="!page.props.auth.user"
                        :href="login.url()"
                        class="inline-block rounded-sm border border-[#19140035] px-8 py-3 text-base font-medium text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        {{ t('welcome.sign_in') }}
                    </Link>
                    <Link
                        v-if="page.props.auth.user"
                        :href="dashboard()"
                        class="inline-block rounded-sm border border-[#19140035] bg-[#1b1b18] px-8 py-3 text-base font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white"
                    >
                        {{ t('welcome.go_to_dashboard') }}
                    </Link>
                </div>

                <!-- Video Embed -->
                <div
                    class="relative mx-auto mb-16 aspect-video w-full max-w-4xl overflow-hidden rounded-lg shadow-xl"
                >
                    <iframe
                        v-if="demoVideoId"
                        class="h-full w-full"
                        :src="`https://www.youtube.com/embed/${demoVideoId}`"
                        :title="t('welcome.app_name')"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center bg-gradient-to-br from-[#f4e4c1] to-[#FDFDFC] dark:from-[#1a1508] dark:to-[#0a0a0a]"
                    >
                        <p
                            class="text-lg font-medium text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            {{ t('welcome.video_coming_soon') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section
            class="border-t border-[#19140035] bg-white py-16 lg:py-24 dark:border-[#3E3E3A] dark:bg-[#161615]"
        >
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2
                        class="mb-4 text-3xl font-bold tracking-tight text-[#1b1b18] sm:text-4xl dark:text-[#EDEDEC]"
                    >
                        {{ t('welcome.features_title') }}
                    </h2>
                    <p
                        class="text-lg leading-8 text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        {{ t('welcome.features_description') }}
                    </p>
                </div>
                <div
                    class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-3"
                >
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="flex flex-col rounded-lg border border-[#e3e3e0] bg-[#FDFDFC] p-8 shadow-sm dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    >
                        <h3
                            class="mb-4 truncate text-xl font-semibold text-[#1b1b18] sm:whitespace-normal dark:text-[#EDEDEC]"
                        >
                            {{ feature.title }}
                        </h3>
                        <p class="flex-1 text-[#706f6c] dark:text-[#A1A09A]">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section
            class="border-t border-[#19140035] bg-[#FDFDFC] py-16 lg:py-24 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
        >
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div
                    class="mx-auto max-w-2xl rounded-lg border border-[#e3e3e0] bg-white p-8 text-center shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                >
                    <h2
                        class="mb-4 text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        {{ t('welcome.cta_title') }}
                    </h2>
                    <p
                        class="mb-8 text-lg leading-8 text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        {{ t('welcome.cta_description') }}
                    </p>
                    <div
                        class="flex flex-col items-center justify-center gap-4 sm:flex-row"
                    >
                        <Link
                            v-if="!page.props.auth.user && canRegister"
                            :href="register.url()"
                            class="inline-block rounded-sm border border-[#19140035] bg-[#1b1b18] px-8 py-3 text-base font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white"
                        >
                            {{ t('welcome.create_account') }}
                        </Link>
                        <Link
                            v-if="!page.props.auth.user"
                            :href="login.url()"
                            class="inline-block rounded-sm border border-[#19140035] px-8 py-3 text-base font-medium text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                        >
                            {{ t('welcome.sign_in') }}
                        </Link>
                        <Link
                            v-if="page.props.auth.user"
                            :href="dashboard()"
                            class="inline-block rounded-sm border border-[#19140035] bg-[#1b1b18] px-8 py-3 text-base font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white"
                        >
                            {{ t('welcome.go_to_dashboard') }}
                        </Link>
                        <Link
                            :href="documentation()"
                            class="inline-block rounded-sm border border-[#19140035] px-8 py-3 text-base font-medium text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                        >
                            {{ t('welcome.view_documentation') }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer
            class="border-t border-[#19140035] bg-white py-12 dark:border-[#3E3E3A] dark:bg-[#161615]"
        >
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div
                    class="flex flex-col items-center justify-between gap-4 sm:flex-row"
                >
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        © {{ new Date().getFullYear() }}
                        {{ t('welcome.copyright') }}
                    </p>
                    <div class="flex gap-6">
                        <Link
                            :href="documentation()"
                            class="text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                        >
                            {{ t('nav.documentation') }}
                        </Link>
                        <a
                            href="https://github.com/ashwinmram/mcp-server"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                        >
                            {{ t('nav.github_repo') }}
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
