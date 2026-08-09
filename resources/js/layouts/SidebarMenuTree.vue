<script setup>
import { Link } from "@inertiajs/vue3";
import { useI18n } from "@/i18n";
import { menuIcons, defaultMenuIcon } from "./menu/icons";

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
            <component :is="menuIcons[item.icon] || defaultMenuIcon" v-if="depth === 0" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" />

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
          <component :is="menuIcons[item.icon] || defaultMenuIcon" v-if="depth === 0" class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500" />
          <span v-if="!collapsed" class="truncate">{{ menuLabel(item) }}</span>
        </span>
        <span v-if="!collapsed" class="shrink-0 text-xs text-slate-400 dark:text-gray-500">›</span>
      </Link>
    </li>
  </ul>
</template>
