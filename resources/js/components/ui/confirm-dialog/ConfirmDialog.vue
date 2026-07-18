<script setup>
import { useConfirmDialogState } from '../../../composables/useConfirm'

const { state, handleConfirm, handleCancel } = useConfirmDialogState()
</script>

<template>
  <Teleport to="body">
    <Transition name="confirm-fade">
      <div
        v-if="state.visible"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirm-dialog-title"
        tabindex="-1"
        @keydown.esc.prevent="handleCancel"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
          @click="handleCancel"
        />

        <!-- Panel -->
        <div class="confirm-panel relative z-10 w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900">
          <h2 id="confirm-dialog-title" class="text-base font-bold text-slate-900 dark:text-gray-100">
            {{ state.title }}
          </h2>
          <p v-if="state.message" class="mt-2 text-sm text-slate-600 dark:text-gray-400">
            {{ state.message }}
          </p>

          <div class="mt-6 flex justify-end gap-3">
            <button
              type="button"
              class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
              @click="handleCancel"
            >
              {{ state.cancelText }}
            </button>
            <button
              type="button"
              :class="[
                'rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition',
                state.danger
                  ? 'bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-500'
                  : 'bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500',
              ]"
              @click="handleConfirm"
            >
              {{ state.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.confirm-fade-enter-active,
.confirm-fade-leave-active {
  transition: opacity 0.18s ease;
}
.confirm-fade-enter-from,
.confirm-fade-leave-to {
  opacity: 0;
}
.confirm-fade-enter-active .confirm-panel,
.confirm-fade-leave-active .confirm-panel {
  transition: transform 0.18s ease, opacity 0.18s ease;
}
.confirm-fade-enter-from .confirm-panel,
.confirm-fade-leave-to .confirm-panel {
  transform: scale(0.96) translateY(-8px);
  opacity: 0;
}
</style>
