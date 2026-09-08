<script setup>
import { X, Pencil } from "@lucide/vue";
import { useEnrollmentRegistrations } from "@/composables/useEnrollmentRegistrations";

const {
  editModalOpen,
  editingRow,
  editDraft,
  editErrors,
  editSaving,
  editNameLiveError,
  cancelEdit,
  saveEdit,
} = useEnrollmentRegistrations();
</script>

<template>
  <div
    v-if="editModalOpen && editingRow"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4"
    @click.self="cancelEdit"
  >
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-500/10">
            <Pencil class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
          </span>
          <div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Edit Registration') }}</h3>
            <p class="text-xs text-slate-500 dark:text-gray-400">{{ editingRow.class_title }}</p>
          </div>
        </div>
        <button
          type="button"
          :aria-label="$t('Close')"
          class="-mr-2 -mt-1 shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-gray-200"
          @click="cancelEdit"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <form class="mt-5 space-y-4" @submit.prevent="saveEdit">
        <div>
          <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Student Name') }}</label>
          <input
            v-model="editDraft.name"
            type="text"
            :class="[
              'w-full rounded-xl border px-4 py-2.5 text-sm outline-none transition focus:ring-2 dark:bg-gray-800 dark:text-gray-200',
              editNameLiveError || editErrors.name
                ? 'border-red-300 focus:border-red-500 focus:ring-red-100 dark:border-red-500/60'
                : 'border-slate-300 focus:border-blue-600 focus:ring-blue-100 dark:border-gray-600',
            ]"
            :placeholder="$t('Enter student name')"
          />
          <p v-if="editNameLiveError || editErrors.name" class="mt-1 text-xs text-red-600">
            {{ editNameLiveError || editErrors.name[0] }}
          </p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Gender') }}</label>
          <div class="inline-flex overflow-hidden rounded-xl border border-slate-300 dark:border-gray-600">
            <button
              type="button"
              class="px-4 py-2 text-sm font-semibold transition"
              :class="editDraft.gender === 'male' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-gray-800 dark:text-gray-300'"
              @click="editDraft.gender = 'male'"
            >{{ $t('Male') }}</button>
            <button
              type="button"
              class="border-l border-slate-300 px-4 py-2 text-sm font-semibold transition dark:border-gray-600"
              :class="editDraft.gender === 'female' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-gray-800 dark:text-gray-300'"
              @click="editDraft.gender = 'female'"
            >{{ $t('Female') }}</button>
          </div>
          <p v-if="editErrors.gender" class="mt-1 text-xs text-red-600">{{ editErrors.gender[0] }}</p>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Phone') }}</label>
          <input
            v-model="editDraft.phone"
            type="text"
            inputmode="numeric"
            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            :placeholder="$t('Enter phone number')"
          />
          <p v-if="editErrors.phone" class="mt-1 text-xs text-red-600">{{ editErrors.phone[0] }}</p>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button
            type="button"
            class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            @click="cancelEdit"
          >
            {{ $t('Cancel') }}
          </button>
          <button
            type="submit"
            :disabled="editSaving || !!editNameLiveError"
            class="inline-flex items-center gap-2 rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            {{ editSaving ? $t('Saving...') : $t('Save Changes') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
