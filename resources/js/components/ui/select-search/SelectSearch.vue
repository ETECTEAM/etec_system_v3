<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

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
  emptyText: {
    type: String,
    default: 'No results found',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  buttonClass: {
    type: String,
    default: '',
  },
  // Off for required fields (e.g. a 2-option toggle) where emitting '' would
  // leave nothing valid selected.
  clearable: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const root = ref(null)
const panel = ref(null)
const triggerButton = ref(null)
const panelStyle = ref({})

const selectedLabel = computed(() => {
  const found = props.options.find((option) => option.value === props.modelValue)

  return found ? t(found.label) : null
})

// The panel is teleported to <body> (see template) rather than absolutely
// positioned inside this component's own DOM position, because any ancestor
// that scrolls horizontally (e.g. a table wrapped in overflow-x-auto for
// mobile) forces its overflow-y to compute as "auto" too - that's a CSS
// spec rule, not a bug: if one axis is non-visible, a "visible" other axis
// still computes to auto. There is no CSS-only way to keep one axis
// scrollable and the other free; explicitly setting overflow-y: visible
// alongside overflow-x: auto does not override it. So instead of fighting
// that, the dropdown escapes via Teleport and is positioned from the
// trigger button's actual screen coordinates.
function updatePanelPosition() {
  const rect = triggerButton.value?.getBoundingClientRect()

  if (!rect) {
    return
  }

  const offset = 8
  const panelHeight = panel.value?.offsetHeight ?? 240
  const spaceBelow = window.innerHeight - rect.bottom - offset
  const spaceAbove = rect.top - offset
  const opensUpward = spaceBelow < panelHeight && spaceAbove > spaceBelow

  panelStyle.value = {
    position: 'fixed',
    left: `${rect.left}px`,
    width: `${rect.width}px`,
    ...(opensUpward
      ? { bottom: `${window.innerHeight - rect.top + offset}px` }
      : { top: `${rect.bottom + offset}px` }),
  }
}

async function toggleDropdown() {
  if (props.disabled) {
    return
  }

  open.value = !open.value

  if (open.value) {
    await nextTick()
    updatePanelPosition()
  }
}

function closeDropdown() {
  open.value = false
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
  if (!root.value?.contains(event.target) && !panel.value?.contains(event.target)) {
    closeDropdown()
  }
}

function handleEscape(event) {
  if (event.key === 'Escape') {
    closeDropdown()
  }
}

// Teleported, so it's no longer inside whatever container scrolls - keep it
// anchored to the trigger by recomputing its position on scroll/resize
// rather than closing it. `capture: true` catches scrolling on any ancestor
// container, not only window-level scroll - except the panel's own option
// list, which scrolls internally (max-h-48 overflow-y-auto below) and must
// not reposition the whole panel while the user is scrolling through it.
// (Closing instead of repositioning was tried first, but browsers can fire
// a scroll event as a side effect of the teleported panel simply appearing
// - e.g. a focus-driven scroll-into-view - which closed the dropdown the
// instant it opened.)
function handleScroll(event) {
  if (open.value && !panel.value?.contains(event.target)) {
    updatePanelPosition()
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
  document.addEventListener('keydown', handleEscape)
  window.addEventListener('scroll', handleScroll, true)
  window.addEventListener('resize', handleScroll)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
  document.removeEventListener('keydown', handleEscape)
  window.removeEventListener('scroll', handleScroll, true)
  window.removeEventListener('resize', handleScroll)
})
</script>

<template>
  <div ref="root" class="relative w-full">
    <button
      ref="triggerButton"
      type="button"
      :class="buttonClass || 'flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-left text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:focus:border-blue-500 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-700 dark:disabled:text-gray-500'"
      :disabled="disabled"
      @click="toggleDropdown"
    >
      <span :class="selectedLabel ? 'text-slate-700 dark:text-gray-200' : 'text-slate-400 dark:text-gray-500'">
        {{ selectedLabel || t(placeholder) }}
      </span>
      <span class="text-slate-500 transition-transform duration-200 dark:text-gray-400" :class="open ? 'rotate-180' : ''">▾</span>
    </button>

    <Teleport to="body">
      <div
        v-if="open"
        ref="panel"
        :style="panelStyle"
        class="z-[130] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
      >
        <div class="max-h-48 overflow-y-auto py-1">
          <button
            v-if="clearable && modelValue !== ''"
            type="button"
            class="block w-full px-4 py-2 text-left text-sm text-slate-500 transition hover:bg-slate-50 dark:text-gray-400 dark:hover:bg-gray-700"
            @click="clearSelection"
          >
            {{ t('Clear selection') }}
          </button>

          <button
            v-for="option in options"
            :key="option.value"
            type="button"
            class="flex w-full items-center justify-between px-4 py-2 text-left text-sm text-slate-700 transition hover:bg-blue-50 dark:text-gray-300 dark:hover:bg-gray-700"
            @click="selectOption(option)"
          >
            <span>{{ t(option.label) }}</span>
            <span v-if="option.value === modelValue" class="text-blue-600 dark:text-blue-400">✓</span>
          </button>

          <div v-if="options.length === 0" class="px-4 py-3 text-sm text-slate-400 dark:text-gray-500">
            {{ t(emptyText) }}
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
