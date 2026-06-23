<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import Diamond from '@/components/Diamond.vue';
import NavUser from '@/components/NavUser.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { index as properties } from '@/routes/properties';
import type { NavItem } from '@/types';

const page = usePage();
const isLandlord = computed(() => page.props.auth.roles.includes('landlord'));

const { isCurrentUrl } = useCurrentUrl();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    ...(isLandlord.value
        ? [
              {
                  title: 'Properties',
                  href: properties(),
              },
          ]
        : []),
    {
        title: 'Settings',
        href: editProfile(),
    },
]);
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-border bg-sidebar text-sidebar-foreground"
    >
        <div class="flex h-16 shrink-0 items-center px-4">
            <Link
                :href="dashboard()"
                class="flex items-center gap-2"
                aria-label="Dwellow home"
            >
                <AppLogo />
            </Link>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-2">
            <ul class="flex flex-col gap-0.5">
                <li v-for="item in mainNavItems" :key="item.title">
                    <Link
                        :href="item.href"
                        :class="
                            cn(
                                'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors',
                                isCurrentUrl(item.href)
                                    ? 'bg-accent font-semibold text-foreground'
                                    : 'text-muted-foreground hover:bg-secondary hover:text-foreground',
                            )
                        "
                    >
                        <Diamond
                            :size="8"
                            :class="
                                isCurrentUrl(item.href)
                                    ? 'text-foreground'
                                    : 'text-muted-foreground/60'
                            "
                        />
                        <span class="flex-1 truncate">{{ item.title }}</span>
                        <span
                            v-if="item.count !== undefined"
                            class="rounded-full bg-secondary px-1.5 py-0.5 text-2xs font-medium text-muted-foreground tabular-nums"
                        >
                            {{ item.count }}
                        </span>
                    </Link>
                </li>
            </ul>
        </nav>

        <div class="shrink-0 border-t border-border p-3">
            <NavUser />
        </div>
    </aside>
</template>
