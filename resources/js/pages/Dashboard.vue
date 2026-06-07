<script setup lang="ts">
import DashboardStatsSection from '@/components/DashboardStatsSection.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type DashboardStats } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    stats: DashboardStats;
}>();

const { t } = useTranslations();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: dashboard().url,
    },
]);
</script>

<template>
    <Head :title="t('nav.dashboard')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl p-4"
        >
            <DashboardStatsSection
                :title="t('dashboard.knowledge_base')"
                :stats="stats.knowledgeBase"
            />

            <DashboardStatsSection
                :title="t('dashboard.project_details')"
                :stats="stats.projectDetails"
            />

            <div v-if="stats.bySourceProject.length > 0">
                <DashboardStatsSection
                    :title="t('dashboard.by_source_project')"
                    :stats="stats.bySourceProject"
                />
            </div>
            <p
                v-else
                class="text-sm text-gray-500 dark:text-gray-400"
            >
                {{ t('dashboard.no_projects') }}
            </p>
        </div>
    </AppLayout>
</template>
