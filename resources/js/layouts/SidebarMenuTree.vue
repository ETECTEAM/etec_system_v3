<script setup>
import { Link } from "@inertiajs/vue3";
import { useI18n } from "@/i18n";

defineOptions({
  name: "SidebarMenuTree",
});

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  openMenus: {
    type: Object,
    required: true,
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
  depth: {
    type: Number,
    default: 0,
  },
  parentKey: {
    type: String,
    default: "root",
  },
  isActive: {
    type: Function,
    required: true,
  },
  isChildActive: {
    type: Function,
    required: true,
  },
  itemKey: {
    type: Function,
    required: true,
  },
});

const emit = defineEmits(["close", "toggle"]);

const { t } = useI18n();

function menuLabel(item) {
  return item.labelKey ? t(item.labelKey) : item.label;
}

function nestedKey(item) {
  return props.itemKey(item, props.parentKey);
}

function childParentKey(item) {
  return nestedKey(item);
}

function firstChildHref(item) {
  const child = item.children?.find((entry) => entry.href || entry.children?.length);

  if (!child) return null;
  if (child.href) return child.href;

  return firstChildHref(child);
}
</script>

<template>
  <ul :class="depth === 0 ? 'space-y-1.5' : 'ml-3 mt-2 space-y-1 border-l border-slate-200 pl-3 dark:border-gray-700'">
    <li v-for="item in items" :key="nestedKey(item)">
      <template v-if="item.children">
        <component
          :is="collapsed && firstChildHref(item) ? Link : 'button'"
          :href="collapsed ? firstChildHref(item) : undefined"
          :type="collapsed ? undefined : 'button'"
          :title="collapsed ? menuLabel(item) : ''"
          :class="[
            'flex w-full items-center rounded-xl text-sm font-semibold transition',
            collapsed ? 'justify-center px-2 py-3' : 'justify-between px-3 py-2',
            isChildActive(item.children) ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100',
            depth > 0 && !collapsed ? 'rounded-lg font-medium' : '',
          ]"
          @click="collapsed ? emit('close') : emit('toggle', nestedKey(item))"
        >
          <span
            :class="[
              'flex min-w-0 items-center gap-2 text-left',
              collapsed ? '' : 'flex-1',
            ]"
          >
            <svg v-if="item.icon === 'course'" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
              <path d="M8 7h8" />
              <path d="M8 11h6" />
              <path d="M8 15h4" />
            </svg>

            <svg v-else-if="item.icon === 'classes'" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
              <path d="M8 7h8" />
              <path d="M8 11h6" />
              <path d="M8 15h4" />
              <path d="M6 6h10M6 10h10" />
            </svg>

            <svg v-else-if="item.icon === 'user'" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 19a4 4 0 0 0-8 0" />
              <circle cx="12" cy="7" r="3" />
              <path d="M20 8v6" />
              <path d="M23 11h-6" />
            </svg>

            <svg v-else-if="item.icon === 'building_management'" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="4" y="2" width="16" height="20" rx="2" ry="2" />
              <line x1="9" y1="22" x2="9" y2="16" />
              <line x1="15" y1="22" x2="15" y2="16" />
            </svg>

            <svg v-else-if="item.icon === 'notification'" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
              <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
            </svg>

            <svg v-else-if="item.icon === 'home'" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
              <polyline points="9 22 9 12 15 12 15 22" />
            </svg>

            <svg v-else-if="depth === 0" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
              <line x1="9" y1="3" x2="9" y2="21" />
            </svg>

            <span v-if="!collapsed" class="truncate">{{ menuLabel(item) }}</span>
          </span>

          <svg v-if="!collapsed" class="h-4 w-4 shrink-0 text-slate-400 transition dark:text-gray-500" :class="openMenus[nestedKey(item)] ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 0 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
          </svg>
        </component>

        <SidebarMenuTree
          v-if="openMenus[nestedKey(item)] && !collapsed"
          :items="item.children"
          :open-menus="openMenus"
          :collapsed="collapsed"
          :depth="depth + 1"
          :parent-key="childParentKey(item)"
          :is-active="isActive"
          :is-child-active="isChildActive"
          :item-key="itemKey"
          @close="emit('close')"
          @toggle="emit('toggle', $event)"
        />
      </template>

      <Link
        v-else
        :href="item.href"
        :title="collapsed ? menuLabel(item) : ''"
        :class="[
          'flex items-center rounded-xl text-sm transition',
          depth === 0 ? 'font-semibold' : 'font-medium',
          collapsed ? 'justify-center px-2 py-3' : 'justify-between px-3 py-2',
          isActive(item) ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100',
          depth > 0 && !collapsed ? 'rounded-lg' : '',
        ]"
        @click="emit('close')"
      >
        <span class="flex min-w-0 items-center gap-2">
          <svg v-if="item.icon === 'notification'" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
          </svg>
          <svg v-else-if="item.icon === 'home'" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
            <polyline points="9 22 9 12 15 12 15 22" />
          </svg>
          <svg v-else-if="depth === 0" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
          </svg>
          <span v-if="!collapsed" class="truncate">{{ menuLabel(item) }}</span>
        </span>
        <span v-if="!collapsed" class="shrink-0 text-xs text-slate-400 dark:text-gray-500">›</span>
      </Link>
    </li>
  </ul>
</template>
