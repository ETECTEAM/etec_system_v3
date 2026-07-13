<script setup>
import { computed } from 'vue'

const props = defineProps({
  currentPage: {
    type: Number,
    default: 1,
  },
  lastPage: {
    type: Number,
    default: 1,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['page-change'])

const visiblePages = computed(() => {
  const pages = []
  const total = props.lastPage
  const current = props.currentPage

  if (total <= 7) {
    for (let page = 1; page <= total; page += 1) {
      pages.push(page)
    }

    return pages
  }

  pages.push(1)

  if (current > 3) {
    pages.push('...')
  }

  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)

  for (let page = start; page <= end; page += 1) {
    pages.push(page)
  }

  if (current < total - 2) {
    pages.push('...')
  }

  pages.push(total)

  return pages
})

function changePage(page) {
  if (props.disabled || page < 1 || page > props.lastPage || page === props.currentPage) {
    return
  }

  emit('page-change', page)
}
</script>

<template>
  <div class="flex flex-wrap items-center justify-center gap-2">
    <button
      type="button"
      class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-700"
      :disabled="props.disabled || props.currentPage === 1"
      @click="changePage(props.currentPage - 1)"
    >
      Prev
    </button>

    <button
      v-for="(page, index) in visiblePages"
      :key="`${page}-${index}`"
      type="button"
      class="rounded-xl px-3 py-1.5 text-sm font-medium transition-all duration-200"
      :class="[
        page === props.currentPage
          ? 'border border-blue-900 bg-blue-900 text-white shadow-sm dark:border-blue-500 dark:bg-blue-500'
          : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-700',
        page === '...' ? 'cursor-default border-transparent bg-transparent hover:border-transparent hover:bg-transparent dark:bg-transparent dark:hover:bg-transparent' : '',
      ]"
      :disabled="props.disabled || page === '...'"
      @click="page !== '...' && changePage(page)"
    >
      {{ page }}
    </button>

    <button
      type="button"
      class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-700"
      :disabled="props.disabled || props.currentPage === props.lastPage"
      @click="changePage(props.currentPage + 1)"
    >
      Next
    </button>
  </div>
</template>
