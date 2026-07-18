<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
})

const normalizedItems = computed(() => props.items.map((item, index) => ({
  ...item,
  current: item.current ?? index === props.items.length - 1,
})))
</script>

<template>
  <nav aria-label="Breadcrumb" class="flex flex-wrap items-center text-sm font-medium">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-slate-500 dark:text-gray-400">
      <li v-for="(item, index) in normalizedItems" :key="item.label + index" class="flex items-center">
        <template v-if="index > 0">
          <svg class="mx-2 h-3.5 w-3.5 text-slate-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L10.94 10 7.23 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
          </svg>
        </template>

        <span v-if="item.current" class="text-blue-600 dark:text-blue-400">
          {{ item.label }}
        </span>
        <Link
          v-else-if="item.href"
          :href="item.href"
          class="text-slate-500 transition hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
          {{ item.label }}
        </Link>
        <span v-else class="text-slate-500 dark:text-gray-400">
          {{ item.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>
