<script setup>
import { computed, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Pen } from "@lucide/vue";
import { menuDomains } from "./menu";
import SidebarMenuTree from "./SidebarMenuTree.vue";
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

function itemKey(item, parentKey = "root") {
  return `${parentKey}:${item.key ?? item.href ?? item.labelKey ?? item.label}`;
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

function openActiveMenus(items = menuItems.value, parentKey = "root") {
  for (const item of items) {
    if (!item.children) continue;

    const key = itemKey(item, parentKey);

    if (isChildActive(item.children)) {
      openMenus.value[key] = true;
      openActiveMenus(item.children, key);
    }
  }
}

watch(currentPath, () => {
  openActiveMenus();
});

watch(menuItems, () => {
  openActiveMenus();
}, { immediate: true });

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
          <SidebarMenuTree
            :items="menuItems"
            :open-menus="openMenus"
            :collapsed="props.collapsed"
            :is-active="isActive"
            :is-child-active="isChildActive"
            :item-key="itemKey"
            @close="emit('close')"
            @toggle="toggleMenu"
          />
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
