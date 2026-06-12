<<<<<<< HEAD
<!-- <script setup>
import { computed, ref, watch } from 'vue'
=======
<script setup>
import { computed } from 'vue'
>>>>>>> 8c762159b54856bc87fbd12c230f63929af3c175
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
const user = computed(() => page.props.auth?.user ?? null)

const isAdmin = computed(() => {
    return roles.value.includes('admin') || user.value?.role === 'admin'
})

const isInstructor = computed(() => {
    return roles.value.includes('instructor') || user.value?.role === 'instructor'
})

const menuItems = computed(() => {
    const base = []

    // Admin can see Dashboard
    if (isAdmin.value) {
        base.push({
            label: 'Dashboard',
            href: '/dashboard',
            icon: 'home',
            match: ['/dashboard'],
            exact: true,
        })
    }

    // Admin + Instructor can see Instructor page
    if (isAdmin.value || isInstructor.value) {
        base.push({
            label: 'Instructor',
            href: '/dashboard/users',
            icon: 'users',
            match: ['/dashboard/users'],
<<<<<<< HEAD
            exact: false,
            isActive: (path) => (
                path === '/dashboard/users'
                || /^\/dashboard\/users\/\d+$/.test(path)
                || path.startsWith('/dashboard/users/edit')
            ),
=======
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
>>>>>>> origin/dev
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
<<<<<<< HEAD
=======

function isChildActive(children = []) {
    return children.some((child) => isActive(child))
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
})
>>>>>>> origin/dev
</script>

<template>
    <div
        :class="[
            'fixed inset-0 z-40 lg:static lg:inset-auto lg:z-auto',
            props.open ? 'block' : 'hidden lg:block',
        ]"
    >
        <div
            class="absolute inset-0 bg-slate-900/30 lg:hidden"
            @click="emit('close')"
        />

<<<<<<< HEAD
        <aside class="relative h-screen w-64 border-r border-slate-200 bg-white lg:sticky lg:top-0">
            <div class="flex h-full flex-col px-4 py-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">
                            ETEC
                        </p>
                        <p class="text-base font-semibold text-slate-900">
                            Control Center
                        </p>
=======
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
>>>>>>> origin/dev
                    </div>

                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 p-1 text-slate-500 transition hover:bg-slate-50 lg:hidden"
                        @click="emit('close')"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>

                <nav class="mt-6 flex-1">
<<<<<<< HEAD
                    <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                        Navigation
                    </p>

=======
                    <p v-if="!props.collapsed" class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">Navigation</p>
>>>>>>> origin/dev
                    <ul class="space-y-1.5">
<<<<<<< HEAD
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
                                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M16 19a4 4 0 0 0-8 0" />
                                            <circle cx="12" cy="7" r="3" />
                                            <path d="M20 8v6" />
                                            <path d="M23 11h-6" />
                                        </svg>
                                        <span v-if="!props.collapsed">{{ item.label }}</span>
                                    </span>
                                    <svg v-if="!props.collapsed" class="h-4 w-4 text-slate-400 transition" :class="openMenus[item.key] ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 0 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <ul v-if="openMenus[item.key] && !props.collapsed" class="ml-3 mt-2 space-y-1 border-l border-slate-200 pl-3">
                                    <li v-for="child in item.children" :key="child.href">
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
                            
=======
                        <li
                            v-for="item in menuItems"
                            :key="item.href"
                        >
>>>>>>> 8c762159b54856bc87fbd12c230f63929af3c175
                            <Link
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
                                    <!-- People / users icon for Instructor -->
                                    <svg
                                        v-if="item.icon === 'users'"
                                        class="h-4 w-4 text-slate-400"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                                        <circle cx="10" cy="7" r="4" />
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>

                                    <!-- Home icon for Dashboard -->
                                    <svg
                                        v-else
                                        class="h-4 w-4 text-slate-400"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path d="M3 10.5 12 4l9 6.5" />
                                        <path d="M5 9.5V20h14V9.5" />
                                    </svg>
<<<<<<< HEAD

                                    {{ item.label }}
                                </span>

                                <span class="text-xs text-slate-400">›</span>
=======
                                    <span v-if="!props.collapsed">{{ item.label }}</span>
                                </span>
                                <span v-if="!props.collapsed" class="text-xs text-slate-400">›</span>
>>>>>>> origin/dev
                            </Link>
                        </li>
                    </ul>
                    <ul>
                       <router-link to="/courses">Course</router-link>
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
<<<<<<< HEAD
</template> -->
<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close'])
const page = usePage()
const currentPath = computed(() => page.url ?? '/')

const menuItems = [
    {
        label: 'Dashboard',
        href: '/dashboard',
    },
    {
        label: 'Courses',
        href: '/admin/courses',
    },
]

function isActive(item) {
    if (item.href) {
        return currentPath.value === item.href || currentPath.value.startsWith(item.href + '/')
    }
    return false
}
</script>

<template>
    <div
        :class="[
            'fixed inset-0 z-40 lg:static lg:inset-auto lg:z-auto',
            props.open ? 'block' : 'hidden lg:block',
        ]"
    >
        <div class="absolute inset-0 bg-slate-900/30 lg:hidden" @click="emit('close')" />

        <aside class="relative h-screen w-64 border-r border-slate-200 bg-white">
            <div class="flex h-full flex-col px-4 py-6">
                <div class="mb-6">
                    <p class="text-xs font-semibold uppercase text-slate-400">ETEC</p>
                    <p class="text-base font-semibold text-slate-900">Control Center</p>
                </div>

                <nav class="flex-1">
                    <ul class="space-y-1">
                        <li v-for="item in menuItems" :key="item.href">
                            <Link
                                :href="item.href"
                                class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition"
                                :class="isActive(item) ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50'"
                                @click="emit('close')"
                            >
                                <span class="flex items-center gap-3">
                                    <!-- Dashboard Icon -->
                                    <svg v-if="item.label === 'Dashboard'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                    </svg>
                                    <!-- Courses Icon -->
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    {{ item.label }}
                                </span>
                                <span class="text-xs text-slate-400">›</span>
                            </Link>
                        </li>
                    </ul>
                </nav>

                <div class="mt-4 border-t border-slate-200 pt-4">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Logout
                    </Link>
                </div>
            </div>
        </aside>
    </div>
=======
>>>>>>> 8c762159b54856bc87fbd12c230f63929af3c175
</template>