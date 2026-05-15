<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Select...',
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...',
  },
  emptyText: {
    type: String,
    default: 'No results found',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const search = ref('')
const root = ref(null)
const searchInput = ref(null)

const filteredOptions = computed(() => {
  const keyword = search.value.trim().toLowerCase()

  if (keyword === '') {
    return props.options
  }

  return props.options.filter((option) => option.label.toLowerCase().includes(keyword))
})

const selectedLabel = computed(() => {
  const found = props.options.find((option) => option.value === props.modelValue)

  return found ? found.label : null
})

async function toggleDropdown() {
  if (props.disabled) {
    return
  }

  open.value = !open.value

  if (open.value) {
    await nextTick()
    searchInput.value?.focus()
  }
}

function closeDropdown() {
  open.value = false
  search.value = ''
}

function selectOption(option) {
  emit('update:modelValue', option.value)
  closeDropdown()
}

function clearSelection() {
  emit('update:modelValue', '')
  closeDropdown()
}

function handleDocumentClick(event) {
  if (!root.value?.contains(event.target)) {
    closeDropdown()
  }
}

function handleEscape(event) {
  if (event.key === 'Escape') {
    closeDropdown()
  }
}

watch(open, async (isOpen) => {
  if (isOpen) {
    await nextTick()
    searchInput.value?.focus()
  }
})

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
  <div ref="root" class="relative w-full">
    <button
      type="button"
      class="flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-left text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
      :disabled="disabled"
      @click="toggleDropdown"
    >
      <span :class="selectedLabel ? 'text-slate-700' : 'text-slate-400'">
        {{ selectedLabel || placeholder }}
      </span>
      <span class="text-slate-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''">▾</span>
    </button>

    <div
      v-if="open"
      class="absolute z-50 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
    >
      <div class="border-b border-slate-200 p-2">
        <input
          ref="searchInput"
          v-model="search"
          type="text"
          :placeholder="searchPlaceholder"
          class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
        >
      </div>

      <div class="max-h-48 overflow-y-auto py-1">
        <button
          v-if="modelValue !== ''"
          type="button"
          class="block w-full px-4 py-2 text-left text-sm text-slate-500 transition hover:bg-slate-50"
          @click="clearSelection"
        >
          Clear selection
        </button>

        <button
          v-for="option in filteredOptions"
          :key="option.value"
          type="button"
          class="flex w-full items-center justify-between px-4 py-2 text-left text-sm text-slate-700 transition hover:bg-blue-50"
          @click="selectOption(option)"
        >
          <span>{{ option.label }}</span>
          <span v-if="option.value === modelValue" class="text-blue-600">✓</span>
        </button>

        <div v-if="filteredOptions.length === 0" class="px-4 py-3 text-sm text-slate-400">
          {{ emptyText }}
        </div>
      </div>
    </div>
  </div>
</template>
