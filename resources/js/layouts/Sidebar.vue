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
            exact: false,
            isActive: (path) => (
                path === '/dashboard/users'
                || /^\/dashboard\/users\/\d+$/.test(path)
                || path.startsWith('/dashboard/users/edit')
            ),
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
                    <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                        Navigation
                    </p>

                    <ul class="space-y-1.5">
                        <li
                            v-for="item in menuItems"
                            :key="item.href"
                        >
                            <Link
                                :href="item.href"
                                class="flex items-center justify-between rounded-xl px-3 py-2 text-sm font-semibold transition"
                                :class="isActive(item)
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
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
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Logout
                    </Link>
                </div>
            </div>
        </aside>
    </div>
</template>