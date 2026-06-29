<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  show: Boolean,
  x: Number,
  y: Number,
  actions: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['select', 'close'])

const menuRef = ref(null)
const adjustedX = ref(0)
const adjustedY = ref(0)

function adjustPosition() {
  if (!menuRef.value) return

  const rect = menuRef.value.getBoundingClientRect()

  let ax = props.x
  let ay = props.y

  if (ax + rect.width > window.innerWidth) {
    ax = window.innerWidth - rect.width - 12
  }
  if (ay + rect.height > window.innerHeight) {
    ay = window.innerHeight - rect.height - 12
  }

  adjustedX.value = Math.max(4, ax)
  adjustedY.value = Math.max(4, ay)
}

function selectItem(action) {
  if (action.disabled) return
  emit('select', action.key)
  emit('close')
}

function handleOutsideClick(event) {
  if (!props.show) return
  if (menuRef.value && !menuRef.value.contains(event.target)) {
    emit('close')
  }
}

function handleEscape(event) {
  if (event.key === 'Escape' && props.show) {
    emit('close')
  }
}

function handleCloseEvents() {
  if (props.show) emit('close')
}

watch(() => props.show, (val) => {
  if (val) {
    nextTick(() => adjustPosition())
  }
})

onMounted(() => {
  document.addEventListener('mousedown', handleOutsideClick, true)
  document.addEventListener('keydown', handleEscape)
  window.addEventListener('scroll', handleCloseEvents, true)
  window.addEventListener('resize', handleCloseEvents)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleOutsideClick, true)
  document.removeEventListener('keydown', handleEscape)
  window.removeEventListener('scroll', handleCloseEvents, true)
  window.removeEventListener('resize', handleCloseEvents)
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      ref="menuRef"
      :style="{
        position: 'fixed',
        left: adjustedX + 'px',
        top: adjustedY + 'px',
        zIndex: 9999,
        minWidth: '10rem',
      }"
      class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg pt-2 pb-2"
      @mousedown.stop
      @click.stop
    >
      <div class="relative px-4 pb-1">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Actions</span>
        <button
          type="button"
          class="absolute right-3 top-0 flex h-5 w-5 items-center justify-center rounded text-sm leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-600"
          @click="emit('close')"
        >
          ×
        </button>
      </div>
      <button
        v-for="action in actions"
        :key="action.key"
        type="button"
        class="flex w-full items-center px-4 py-2 text-left text-sm transition disabled:cursor-not-allowed"
        :class="action.danger
          ? 'text-red-600 hover:bg-red-50 disabled:text-red-300'
          : 'text-slate-700 hover:bg-slate-50 disabled:text-slate-400'"
        :disabled="action.disabled"
        @click="selectItem(action)"
      >
        {{ action.label }}
      </button>
    </div>
  </Teleport>
</template>
