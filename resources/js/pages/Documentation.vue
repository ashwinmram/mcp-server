<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import MarketingSiteHeader from '@/components/MarketingSiteHeader.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, documentation, home, login, register } from '@/routes';
import { type AppPageProps, type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const { t } = useTranslations();
const page = usePage<AppPageProps>();

const user = computed(() => page.props.auth?.user);
const canRegister = computed(
    () => (page.props as { canRegister?: boolean }).canRegister ?? true,
);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: dashboard().url,
    },
    {
        title: t('documentation.page_title'),
        href: documentation().url,
    },
]);

const docSections = computed(() => [
    {
        title: 'documentation.section_overview_title',
        body: 'documentation.section_overview_body',
    },
    {
        title: 'documentation.section_dashboard_title',
        body: 'documentation.section_dashboard_body',
    },
    {
        title: 'documentation.section_setup_title',
        body: 'documentation.section_setup_body',
    },
    {
        title: 'documentation.section_ai_clients_title',
        body: 'documentation.section_ai_clients_body',
    },
    {
        title: 'documentation.section_mcp_tools_title',
        body: 'documentation.section_mcp_tools_body',
    },
    {
        title: 'documentation.section_pushing_title',
        body: 'documentation.section_pushing_body',
    },
    {
        title: 'documentation.section_project_details_title',
        body: 'documentation.section_project_details_body',
    },
    {
        title: 'documentation.section_troubleshooting_title',
        body: 'documentation.section_troubleshooting_body',
    },
]);

const proseClasses =
    'mx-auto max-w-2xl space-y-6 text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]';

const sectionHeadingClass =
    'text-base font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]';

const sectionBodyClass = 'space-y-3';
</script>

<template>
    <Head :title="t('documentation.page_title')">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <AppLayout v-if="user" :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div
                class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            >
                <h1
                    class="mb-6 text-xl font-semibold tracking-tight text-foreground"
                >
                    {{ t('documentation.headline') }}
                </h1>
                <div :class="proseClasses">
                    <section
                        v-for="(section, index) in docSections"
                        :key="index"
                        class="border-t border-border pt-6 first:border-t-0 first:pt-0"
                    >
                        <h2 :class="sectionHeadingClass">
                            {{ t(section.title) }}
                        </h2>
                        <p :class="sectionBodyClass">
                            {{ t(section.body) }}
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>

    <div
        v-else
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <MarketingSiteHeader />

        <section class="mx-auto w-full max-w-7xl px-6 py-16 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-2xl">
                <div
                    class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <h1
                        class="text-3xl font-bold tracking-tight text-[#1b1b18] sm:text-4xl dark:text-[#EDEDEC]"
                    >
                        {{ t('documentation.headline') }}
                    </h1>
                    <div class="flex shrink-0 items-center gap-3">
                        <Link
                            :href="home()"
                            class="text-sm font-medium text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                        >
                            {{ t('documentation.home') }}
                        </Link>
                        <Link
                            :href="login()"
                            class="text-sm font-medium text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                        >
                            {{ t('auth.log_in') }}
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="text-sm font-medium hover:underline"
                            style="color: #ce9e47"
                        >
                            {{ t('auth.register') }}
                        </Link>
                    </div>
                </div>
                <div :class="proseClasses">
                    <section
                        v-for="(section, index) in docSections"
                        :key="index"
                        class="border-t border-[#e3e3e0] pt-6 first:border-t-0 first:pt-0 dark:border-[#3E3E3A]"
                    >
                        <h2 :class="sectionHeadingClass">
                            {{ t(section.title) }}
                        </h2>
                        <p :class="sectionBodyClass">
                            {{ t(section.body) }}
                        </p>
                    </section>
                </div>
            </div>
        </section>
    </div>
</template>
