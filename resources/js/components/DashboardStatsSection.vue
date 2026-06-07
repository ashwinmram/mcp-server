<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import type { DashboardStatItem } from '@/types';
import { ArrowDown, ArrowUp } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        title: string;
        stats: DashboardStatItem[];
        description?: string;
    }>(),
    {
        description: undefined,
    },
);

const { t } = useTranslations();

function comparisonLabel(item: DashboardStatItem): string {
    const key =
        item.comparisonType === 'snapshot'
            ? 'dashboard.vs_snapshot'
            : 'dashboard.vs_prior_period';

    return t(key).replace(':stat', item.previousStat);
}

function changeTooltip(item: DashboardStatItem): string {
    return item.changeFormat === 'points'
        ? t('dashboard.change_points')
        : t('dashboard.change_relative');
}

function changeSrOnly(item: DashboardStatItem): string {
    const direction =
        item.changeType === 'increase' ? 'Increased' : 'Decreased';

    if (item.changeFormat === 'points') {
        return `${direction} by ${item.change} (percentage points)`;
    }

    return `${direction} by ${item.change}`;
}
</script>

<template>
    <div>
        <h3
            class="text-base font-semibold text-gray-900 dark:text-white"
        >
            {{ title }}
        </h3>
        <p
            v-if="description"
            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
        >
            {{ description }}
        </p>
        <dl
            class="mt-5 grid grid-cols-1 divide-gray-200 overflow-hidden rounded-lg bg-white shadow md:grid-cols-3 md:divide-x md:divide-y-0 dark:divide-white/10 dark:bg-gray-800/75 dark:shadow-none dark:ring-1 dark:ring-inset dark:ring-white/10"
        >
            <div
                v-for="item in stats"
                :key="item.name"
                class="px-4 py-5 sm:p-6"
            >
                <dt
                    class="text-base font-normal text-gray-900 dark:text-gray-100"
                >
                    {{ item.name }}
                </dt>
                <dd
                    class="mt-1 flex items-baseline justify-between md:block lg:flex"
                >
                    <div
                        class="flex flex-wrap items-baseline gap-x-2 text-2xl font-semibold text-indigo-600 dark:text-indigo-400"
                    >
                        {{ item.stat }}
                        <span
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            {{ comparisonLabel(item) }}
                        </span>
                    </div>

                    <div
                        v-if="item.change !== '—'"
                        :title="changeTooltip(item)"
                        :class="[
                            item.changeType === 'increase'
                                ? 'bg-green-100 text-green-800 dark:bg-green-400/10 dark:text-green-400'
                                : 'bg-red-100 text-red-800 dark:bg-red-400/10 dark:text-red-400',
                            'inline-flex items-baseline rounded-full px-2.5 py-0.5 text-sm font-medium md:mt-2 lg:mt-0',
                        ]"
                    >
                        <ArrowUp
                            v-if="item.changeType === 'increase'"
                            class="-ml-1 mr-0.5 size-5 shrink-0 self-center text-green-500 dark:text-green-400"
                            aria-hidden="true"
                        />
                        <ArrowDown
                            v-else
                            class="-ml-1 mr-0.5 size-5 shrink-0 self-center text-red-500 dark:text-red-400"
                            aria-hidden="true"
                        />
                        <span class="sr-only">
                            {{ changeSrOnly(item) }}
                        </span>
                        {{ item.change }}
                    </div>
                </dd>
            </div>
        </dl>
    </div>
</template>
