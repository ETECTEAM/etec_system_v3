<script setup>
import { computed, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close'])

const page = usePage()

const currentPath = computed(() => page.url ?? '/')
const roles = computed(() => page.props.auth?.roles ?? [])
const isSuperAdmin = computed(() => roles.value.includes('super_admin'))
const canAccessNotifications = computed(() => roles.value.includes('super_admin') || roles.value.includes('admin'))
const canAccessFloors = computed(() => roles.value.includes('super_admin') || roles.value.includes('admin') || roles.value.includes('instructor'))

function isClassManagementRoute(path) {
    return path.split('?')[0].startsWith('/class-types') || path.split('?')[0].startsWith('/class-categories')
}

function isUserManagementRoute(path) {
    return path.split('?')[0].startsWith('/dashboard/users')
}

function isFloorRoute(path) {
    return path.split('?')[0].startsWith('/dashboard/floors')
}

const openMenus = ref({
    floor: isFloorRoute(currentPath.value),
    user: isUserManagementRoute(currentPath.value),
    classes: isClassManagementRoute(currentPath.value),
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

    if (canAccessFloors.value) {
        base.push({
            label: 'Floor',
            key: 'floor',
            match: ['/dashboard/floors'],
            children: [
                {
                    label: 'Index',
                    href: '/dashboard/floors',
                    match: ['/dashboard/floors'],
                    exact: true,
                },
                {
                    label: 'Create',
                    href: '/dashboard/floors/create',
                    match: ['/dashboard/floors/create'],
                    exact: false,
                },
            ],
        })
    }

    if (canAccessNotifications.value) {
        base.push({
            label: 'Notifications',
            href: '/dashboard/notifications',
            match: ['/dashboard/notifications'],
            exact: false,
            icon: 'notification',
            isActive: (path) => path.startsWith('/dashboard/notifications'),
        })
    }

    // Class Management (Admin / Workers)
    base.push({
        label: 'Class Management',
        key: 'classes',
        match: ['/class-types', '/class-categories'],
        icon: 'classes',
        children: [
            {
                label: 'Class Type',
                href: '/class-types',
                match: ['/class-types'],
                exact: false,
                isActive: (path) => path.startsWith('/class-types'),
            },
            {
                label: 'Class Category',
                href: '/class-categories',
                match: ['/class-categories'],
                exact: false,
                isActive: (path) => path.startsWith('/class-categories'),
            },
        ],
    })

    if (isSuperAdmin.value) {
        base.push({
            label: 'User Management',
            key: 'user',
            match: ['/dashboard/users'],
            icon: 'user',
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
                        || /^\/dashboard\/users\/\d+$/.test(path)
                    ),
                },
                {
                    label: 'Role & Permission',
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
        },
        {
            label: 'Term Management',
            key: 'term',
            match: ['/dashboard/terms'],
            icon: 'term',
            children: [
                {
                    label: 'Terms',
                    href: '/dashboard/terms',
                    match: ['/dashboard/terms'],
                    exact: false,
                    isActive: (path) => (
                        path === '/dashboard/terms'
                        || path.startsWith('/dashboard/terms/create')
                        || path.startsWith('/dashboard/terms/edit')
                    ),
                },
            ],
        },
        {
            label: 'Time Management',
            key: 'time',
            match: ['/dashboard/times'],
            icon: 'time',
            children: [
                {
                    label: 'Times',
                    href: '/dashboard/times',
                    match: ['/dashboard/times'],
                    exact: false,
                    isActive: (path) => (
                        path === '/dashboard/times'
                        || path.startsWith('/dashboard/times/create')
                        || path.startsWith('/dashboard/times/edit')
                        || /^\/dashboard\/times\/\d+$/.test(path)
                    ),
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
    return children.some((child) => {
        if (isActive(child)) {
            return true
        }
        if (child.children) {
            return isChildActive(child.children)
        }
        return false
    })
}

function toggleMenu(key) {
    if (props.collapsed) {
        return
    }
    openMenus.value[key] = !openMenus.value[key]
}

watch(currentPath, (path) => {
    if (isUserManagementRoute(path)) {
        openMenus.value.user = true
    }
    if (isFloorRoute(path)) {
        openMenus.value.floor = true
    }
    if (isClassManagementRoute(path)) {
        openMenus.value.classes = true
    }
})
</script>

<template>
    <div
        :class="[
            'fixed inset-0 z-40 lg:static lg:inset-auto lg:z-auto',
            props.open ? 'block' : 'hidden lg:block',
        ]"
    >
        <div class="absolute inset-0 bg-slate-900/30 lg:hidden" @click="emit('close')" />

        <aside
            :class="[
                'relative h-screen border-r border-slate-200 bg-white transition-all duration-200 lg:sticky lg:top-0',
                props.collapsed ? 'w-20' : 'w-64',
            ]"
        >
            <div :class="['flex h-full flex-col py-6', props.collapsed ? 'px-3' : 'px-4']">
                <div :class="['flex items-start justify-between', props.collapsed ? 'justify-center' : '']">
                    <div v-if="!props.collapsed">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">ETEC</p>
                        <p class="text-base font-semibold text-slate-900">Control Center</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 p-1 text-slate-500 transition hover:bg-slate-50 lg:hidden"
                        @click="emit('close')"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <nav class="mt-6 flex-1 overflow-y-auto">
                    <p v-if="!props.collapsed" class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Navigation</p>
                    <ul class="space-y-1.5">
                        <li v-for="item in menuItems" :key="item.href ?? item.key">
                            <template v-if="item.children">
                                <button
                                    type="button"
                                    :title="props.collapsed ? item.label : ''"
                                    :class="[
                                        'flex w-full items-center rounded-xl text-sm font-semibold transition',
                                        props.collapsed ? 'justify-center px-2 py-3' : 'justify-between px-3 py-2',
                                        isChildActive(item.children)
                                            ? 'bg-blue-50 text-blue-700'
                                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900',
                                    ]"
                                    @click="toggleMenu(item.key)"
                                >
                                    <span class="flex items-center gap-2">
                                        <svg v-if="item.icon === 'classes'" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                                            <path d="M6 6h10M6 10h10" />
                                        </svg>

                                        <svg v-else-if="item.icon === 'user'" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M16 19a4 4 0 0 0-8 0" />
                                            <circle cx="12" cy="7" r="3" />
                                            <path d="M20 8v6" />
                                            <path d="M23 11h-6" />
                                        </svg>

                                        <svg v-else class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z" />
                                        </svg>

                                        <span v-if="!props.collapsed">{{ item.label }}</span>
                                    </span>
                                    
                                    <svg v-if="!props.collapsed" class="h-4 w-4 text-slate-400 transition" :class="openMenus[item.key] ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 0 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <ul v-if="openMenus[item.key] && !props.collapsed" class="ml-3 mt-2 space-y-1 border-l border-slate-200 pl-3">
                                    <li v-for="child in item.children" :key="child.href ?? child.key">
                                        <Link
                                            :href="child.href"
                                            class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition"
                                            :class="isActive(child)
                                                ? 'bg-blue-50 text-blue-700'
                                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                                            @click="emit('close')"
                                        >
                                            <span>{{ child.label }}</span>
                                            <span class="text-xs text-slate-400">›</span>
                                        </Link>
                                    </li>
                                </ul>
                            </template>

                            <Link
                                v-else
                                :href="item.href"
                                :title="props.collapsed ? item.label : ''"
                                :class="[
                                    'flex items-center rounded-xl text-sm font-semibold transition',
                                    props.collapsed ? 'justify-center px-2 py-3' : 'justify-between px-3 py-2',
                                    isActive(item)
                                        ? 'bg-blue-50 text-blue-700'
                                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900',
                                ]"
                                @click="emit('close')"
                            >
                                <span class="flex items-center gap-2">
                                    <svg v-if="item.icon === 'notification'" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                                    </svg>
                                    <svg v-else class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                        <polyline points="9 22 9 12 15 12 15 22" />
                                    </svg>
                                    <span v-if="!props.collapsed">{{ item.label }}</span>
                                </span>
                                <span v-if="!props.collapsed" class="text-xs text-slate-400">›</span>
                            </Link>
                        </li>
                    </ul>
                </nav>

                <div class="mt-4 border-t border-slate-200 pt-4">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        :title="props.collapsed ? 'Logout' : ''"
                        :class="[
                            'w-full rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 transition hover:bg-slate-50',
                            props.collapsed ? 'px-2 py-3' : 'px-3 py-2',
                        ]"
                    >
                        <span v-if="props.collapsed">↩</span>
                        <span v-else>Logout</span>
                    </Link>
                </div>
            </div>
        </aside>
    </div>
</template>