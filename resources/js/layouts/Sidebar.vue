<script setup>
import { computed, ref, watch } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { Pen } from "@lucide/vue";
import { menuDomains } from "./menu";
import BugAnnotationOverlay from "../components/ui/bug-annotation/BugAnnotationOverlay.vue";
import { useI18n } from "@/i18n";

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["close"]);

const page = usePage();
const { t } = useI18n();

const currentPath = computed(() => page.url ?? "/");
const roles = computed(() => page.props.auth?.roles ?? []);
const permissions = computed(() => page.props.auth?.permissions ?? []);
const isSuperAdmin = computed(() => roles.value.includes("super_admin"));
const isAdmin = computed(() => roles.value.includes("admin"));

const canAccessNotifications = computed(() => isSuperAdmin.value || isAdmin.value);

const canAccessFloors = computed(
  () => isSuperAdmin.value || isAdmin.value || roles.value.includes("instructor"),
);

const menuContext = computed(() => ({
  isSuperAdmin: isSuperAdmin.value,
  isAdmin: isAdmin.value,
  roles: roles.value,
  permissions: permissions.value,
  canAccessNotifications: canAccessNotifications.value,
  canAccessFloors: canAccessFloors.value,
}));

const openMenus = ref(
  Object.fromEntries(
    menuDomains
      .filter((domain) => domain.key)
      .map((domain) => [domain.key, domain.isRoute?.(currentPath.value) ?? false]),
  ),
);

const menuItems = computed(() => {
  const base = [
    {
      label: "Dashboard",
      labelKey: "navigation.dashboard",
      href: "/dashboard",
      match: ["/dashboard"],
      exact: true,
      icon: "home",
    },
  ];

  for (const domain of menuDomains) {
    const item = domain.build(menuContext.value);
    if (item) base.push(item);
  }

  const singleItems = base.filter((item) => !item.children);
  const dropdownItems = base.filter((item) => item.children);

  return [...singleItems, ...dropdownItems];
});

function menuLabel(item) {
  return item.labelKey ? t(item.labelKey) : item.label;
}

function isActive(item) {
  const pathOnly = currentPath.value.split("?")[0].replace(/\/+$/, "") || "/";

  if (typeof item.isActive === "function") {
    return item.isActive(pathOnly);
  }

  if (!Array.isArray(item.match)) {
    return false;
  }

  return item.match.some((targetPath) => {
    const normalizedTarget = targetPath.replace(/\/+$/, "") || "/";

    if (item.exact) {
      return pathOnly === normalizedTarget;
    }

    return pathOnly === normalizedTarget || pathOnly.startsWith(`${normalizedTarget}/`);
  });
}

function isChildActive(children = []) {
  return children.some((child) => {
    if (isActive(child)) return true;
    if (child.children) return isChildActive(child.children);
    return false;
  });
}

function toggleMenu(key) {
  if (props.collapsed) return;
  openMenus.value[key] = !openMenus.value[key];
}

watch(currentPath, (path) => {
  for (const domain of menuDomains) {
    if (domain.key && domain.isRoute?.(path)) {
      openMenus.value[domain.key] = true;
    }
  }
});

const isDrawing = ref(false);

function toggleDrawing(event) {
  event?.stopPropagation();
  isDrawing.value = !isDrawing.value;
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

    <aside
      :class="[
        'relative h-screen border-r border-slate-200 bg-white transition-all duration-200 lg:sticky lg:top-0 dark:border-gray-800 dark:bg-gray-900',
        props.collapsed ? 'w-20' : 'w-80',
      ]"
    >
      <div
        :class="[
          'flex h-full flex-col py-6',
          props.collapsed ? 'px-3' : 'px-4',
        ]"
      >
        <div
          :class="[
            'flex items-start justify-between',
            props.collapsed ? 'justify-center' : '',
          ]"
        >
          <div v-if="!props.collapsed">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-gray-500">
              {{ t("app.brand") }}
            </p>
            <p class="text-base font-semibold text-slate-900 dark:text-gray-100">{{ t("app.controlCenter") }}</p>
          </div>
          <button
            type="button"
            class="rounded-lg border border-slate-200 p-1 text-slate-500 transition hover:bg-slate-50 lg:hidden dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
            @click="emit('close')"
          >
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>

        <nav class="mt-6 flex-1 overflow-y-auto">
          <p v-if="!props.collapsed" class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-gray-500">
            {{ t("navigation.navigation") }}
          </p>
          <ul class="space-y-1.5">
            <li v-for="item in menuItems" :key="item.href ?? item.key">
              <template v-if="item.children">
                <button
                  type="button"
                  :title="props.collapsed ? menuLabel(item) : ''"
                  :class="[
                    'flex w-full items-center rounded-xl text-sm font-semibold transition',
                    props.collapsed ? 'justify-center px-2 py-3' : 'justify-between px-3 py-2',
                    isChildActive(item.children) ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100',
                  ]"
                  @click="toggleMenu(item.key)"
                >
                  <span class="flex flex-1 items-center gap-2 text-left">
                    <svg v-if="item.icon === 'course'" class="h-4 w-4 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                      <path d="M8 7h8" />
                      <path d="M8 11h6" />
                      <path d="M8 15h4" />
                    </svg>

                    <svg v-else-if="item.icon === 'classes'" class="h-4 w-4 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                      <path d="M8 7h8" />
                      <path d="M8 11h6" />
                      <path d="M8 15h4" />
                      <path d="M6 6h10M6 10h10" />
                    </svg>

                    <svg v-else-if="item.icon === 'user'" class="h-4 w-4 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M16 19a4 4 0 0 0-8 0" />
                      <circle cx="12" cy="7" r="3" />
                      <path d="M20 8v6" />
                      <path d="M23 11h-6" />
                    </svg>

                    <svg v-else-if="item.icon === 'building_management'" class="h-4 w-4 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="4" y="2" width="16" height="20" rx="2" ry="2" />
                      <line x1="9" y1="22" x2="9" y2="16" />
                      <line x1="15" y1="22" x2="15" y2="16" />
                    </svg>

                    <svg v-else class="h-4 w-4 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                      <line x1="9" y1="3" x2="9" y2="21" />
                    </svg>

                    <span v-if="!props.collapsed">{{ menuLabel(item) }}</span>
                  </span>

                  <svg v-if="!props.collapsed" class="h-4 w-4 text-slate-400 transition dark:text-gray-500" :class="openMenus[item.key] ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 0 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                  </svg>
                </button>

                <ul
                  v-if="openMenus[item.key] && !props.collapsed"
                  class="ml-3 mt-2 space-y-1 border-l border-slate-200 pl-3 dark:border-gray-700"
                >
                  <li v-for="child in item.children" :key="child.href ?? child.key">
                    <Link
                      :href="child.href"
                      class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition"
                      :class="isActive(child) ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100'"
                      @click="emit('close')"
                    >
                      <span>{{ menuLabel(child) }}</span>
                      <span class="text-xs text-slate-400 dark:text-gray-500">›</span>
                    </Link>
                  </li>
                </ul>
              </template>

              <Link
                v-else
                :href="item.href"
                :title="props.collapsed ? menuLabel(item) : ''"
                :class="[
                  'flex items-center rounded-xl text-sm font-semibold transition',
                  props.collapsed ? 'justify-center px-2 py-3' : 'justify-between px-3 py-2',
                  isActive(item) ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100',
                ]"
                @click="emit('close')"
              >
                <span class="flex items-center gap-2">
                  <svg v-if="item.icon === 'notification'" class="h-4 w-4 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                    <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                  </svg>
                  <svg v-else-if="item.icon === 'home'" class="h-4 w-4 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                  </svg>
                  <svg v-else class="h-4 w-4 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                  </svg>
                  <span v-if="!props.collapsed">{{ menuLabel(item) }}</span>
                </span>
                <span v-if="!props.collapsed" class="text-xs text-slate-400 dark:text-gray-500">›</span>
              </Link>
            </li>
          </ul>
        </nav>

        <div class="mt-4 border-t border-slate-200 pt-4 dark:border-gray-700">
          <button
            type="button"
            :title="props.collapsed ? t('common.drawOnPage') : ''"
            :class="[
              'flex w-full items-center gap-2 rounded-xl border text-sm font-semibold transition',
              props.collapsed ? 'justify-center px-2 py-3' : 'px-3 py-2',
              isDrawing
                ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400'
                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800',
            ]"
            @click="toggleDrawing"
          >
            <Pen class="h-4 w-4" />
            <span v-if="!props.collapsed">{{ isDrawing ? t("common.stopDrawing") : t("common.drawOnPage") }}</span>
          </button>
        </div>
      </div>
    </aside>

    <BugAnnotationOverlay v-if="isDrawing" @close="isDrawing = false" />
  </div>
</template>
