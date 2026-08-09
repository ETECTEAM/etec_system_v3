<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  align: {
    type: String,
    default: 'right',
  },
})

const emit = defineEmits(['select'])

const open = ref(false)
const root = ref(null)

function toggle() {
  open.value = !open.value
}

function close() {
  open.value = false
}

function selectItem(item) {
  if (item.disabled) {
    return
  }

  emit('select', item)
  close()
}

function handleDocumentClick(event) {
  if (!root.value?.contains(event.target)) {
    close()
  }
}

function handleEscape(event) {
  if (event.key === 'Escape') {
    close()
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
  document.addEventListener('keydown', handleEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
  document.removeEventListener('keydown', handleEscape)
})
</script>

<template>
  <div ref="root" class="relative inline-flex">
    <button
      type="button"
      class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
      :aria-label="t('More actions')"
      @click="toggle"
    >
      <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path d="M6 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm6 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm6 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0z" />
      </svg>
    </button>

    <div
      v-if="open"
      :class="[
        'absolute z-40 mt-2 min-w-[10rem] overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800',
        props.align === 'left' ? 'left-0' : 'right-0',
      ]"
    >
      <button
        v-for="item in items"
        :key="item.key"
        type="button"
        class="flex w-full items-center justify-between px-4 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-400 dark:text-gray-300 dark:hover:bg-gray-700 dark:disabled:text-gray-600"
        :disabled="item.disabled"
        @click="selectItem(item)"
      >
        <span>{{ t(item.label) }}</span>
        <span v-if="item.hint" class="text-xs text-slate-400 dark:text-gray-500">{{ item.hint }}</span>
      </button>
    </div>
  </div>
</template>
