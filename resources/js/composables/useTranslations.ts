import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type TranslationsMap = Record<string, Record<string, string>>;

function getNested(obj: Record<string, unknown>, path: string): string | undefined {
    const keys = path.split('.');
    let current: unknown = obj;
    for (const key of keys) {
        if (current === null || current === undefined || typeof current !== 'object') {
            return undefined;
        }
        current = (current as Record<string, unknown>)[key];
    }
    return typeof current === 'string' ? current : undefined;
}

export function useTranslations() {
    const page = usePage();
    const locale = computed(() => (page.props.locale as string) ?? 'en');
    const fallbackLocale = 'en';
    const translations = computed(() => (page.props.translations as TranslationsMap) ?? {});

    const t = computed(() => {
        return (key: string): string => {
            const localeTranslations = translations.value[locale.value] as Record<string, unknown> | undefined;
            const fallbackTranslations = translations.value[fallbackLocale] as Record<string, unknown> | undefined;
            return (
                getNested((localeTranslations ?? {}) as Record<string, unknown>, key) ??
                getNested((fallbackTranslations ?? {}) as Record<string, unknown>, key) ??
                key
            );
        };
    });

    return { t: t.value, locale };
}
