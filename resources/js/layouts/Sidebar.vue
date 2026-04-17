<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const currentPath = computed(() => page.url ?? '/')
const roles = computed(() => page.props.auth?.roles ?? [])
const isSuperAdmin = computed(() => roles.value.includes('super_admin'))
const canAccessNotifications = computed(() => roles.value.includes('super_admin') || roles.value.includes('admin'))
const openMenus = ref({
    user: true,
})

const menuItems = computed(() => {
    const base = [
        {
            label: 'Dashboard',
            href: '/dashboard',
            match: ['/dashboard'],
            exact: true,
        },
    ]

    if (canAccessNotifications.value) {
        base.push({
            label: 'Notifications',
            href: '/dashboard/notifications',
            match: ['/dashboard/notifications'],
            exact: false,
            isActive: (path) => path.startsWith('/dashboard/notifications'),
        })
    }

    if (isSuperAdmin.value) {
        base.push({
            label: 'User Management',
            key: 'user',
            match: ['/dashboard/users'],
            children: [
                {
                    label: 'User',
                    href: '/dashboard/users',
                    match: ['/dashboard/users'],
                    exact: false,
                    isActive: (path) => (
                        path === '/dashboard/users'
                        || path.startsWith('/dashboard/users/create')
                        || path.startsWith('/dashboard/users/edit')
                    ),
                },
                {
                    label: 'User Role',
                    href: '/dashboard/users/roles',
                    match: ['/dashboard/users/roles'],
                    exact: false,
                    isActive: (path) => path.startsWith('/dashboard/users/roles'),
                },
                {
                    label: 'Permission',
                    href: '/dashboard/users/permissions',
                    match: ['/dashboard/users/permissions'],
                    exact: false,
                    isActive: (path) => path.startsWith('/dashboard/users/permissions'),
                },
            ],
        })
    }

    return base
})

function isActive(item) {
    const pathOnly = currentPath.value.split('?')[0].replace(/\/+$/, '') || '/'

    if (typeof item.isActive === 'function') {
        return item.isActive(pathOnly)
    }

    return item.match.some((targetPath) => {
        const normalizedTarget = targetPath.replace(/\/+$/, '') || '/'

        if (item.exact) {
            return pathOnly === normalizedTarget
        }

        return pathOnly === normalizedTarget || pathOnly.startsWith(`${normalizedTarget}/`)
    })
}

function isChildActive(children = []) {
    return children.some((child) => isActive(child))
}

function toggleMenu(key) {
    openMenus.value[key] = !openMenus.value[key]
}

watch(
    currentPath,
    () => {
        if (currentPath.value.startsWith('/dashboard/users')) {
            openMenus.value.user = true
        }
    },
    { immediate: true },
)

onMounted(() => {
    // Keep sidebar section expanded when navigating inside user management.
    if (currentPath.value.startsWith('/dashboard/users')) {
        openMenus.value.user = true
    }
})
</script>

<template>
    <aside class="sticky top-0 h-screen w-full max-w-72 border-r border-blue-900/70 bg-blue-950 text-blue-50">
        <div class="flex h-full flex-col px-4 py-6">
            <nav class="mt-2 flex-1">
                <p class="mb-4 px-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-blue-300/70">Navigation</p>
                <ul class="space-y-1.5">
                    <li v-for="item in menuItems" :key="item.href ?? item.key">
                        <template v-if="item.children">
                            <button
                                type="button"
                                class="group flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold transition"
                                :class="isChildActive(item.children)
                                    ? 'bg-blue-900/80 text-white'
                                    : 'text-blue-100 hover:bg-blue-900/70 hover:text-white'"
                                @click="toggleMenu(item.key)"
                            >
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-blue-300/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M16 19a4 4 0 0 0-8 0" />
                                        <circle cx="12" cy="7" r="3" />
                                        <path d="M20 8v6" />
                                        <path d="M23 11h-6" />
                                    </svg>
                                    {{ item.label }}
                                </span>
                                <svg class="h-4 w-4 text-blue-300/70 transition" :class="openMenus[item.key] ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 0 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <ul v-if="openMenus[item.key]" class="ml-4 mt-2 space-y-1.5">
                                <li v-for="child in item.children" :key="child.href">
                                    <Link
                                        :href="child.href"
                                        class="group relative flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition"
                                        :class="isActive(child)
                                            ? 'bg-blue-900/80 text-white'
                                            : 'text-blue-100 hover:bg-blue-900/70 hover:text-white'"
                                    >
                                        <span>{{ child.label }}</span>
                                        <span class="text-xs opacity-70">›</span>
                                    </Link>
                                </li>
                            </ul>
                        </template>

                        <Link
                            v-else
                            :href="item.href"
                            class="group flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold transition"
                            :class="isActive(item)
                                ? 'bg-blue-900/80 text-white'
                                : 'text-blue-100 hover:bg-blue-900/70 hover:text-white'"
                        >
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-blue-300/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 10.5 12 4l9 6.5" />
                                    <path d="M5 9.5V20h14V9.5" />
                                </svg>
                                {{ item.label }}
                            </span>
                            <span class="text-xs opacity-70">›</span>
                        </Link>
                    </li>
                </ul>
            </nav>

            <div class="mt-4 border-t border-blue-900/70 pt-4">
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="w-full rounded-lg bg-blue-900 px-3 py-2.5 text-sm font-semibold text-blue-50 transition hover:bg-blue-800"
                >
                    Logout
                </Link>
            </div>
        </div>
    </aside>
</template>
