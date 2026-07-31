<script setup>
import { useForm } from '@inertiajs/vue3'
import { watch } from 'vue'

const props = defineProps({
  time: Object
})

const emit = defineEmits(['close'])

const form = useForm({
  time_name: '',
})

// fill form when data comes
watch(
  () => props.time,
  (val) => {
    if (val) {
      form.time_name = val.time_name
    }
  },
  { immediate: true }
)

function submit() {
  form.put(`/dashboard/times/${props.time.id}`, {
    preserveScroll: true,
    onSuccess: () => emit('close')
  })
}
</script>
<template>
  <div>

    <!-- HEADER (same as Terms) -->
    <div class="text-center mb-4">
      <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
        {{ $t('Edit Time') }}
      </h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ $t('Update time information') }}
      </p>
    </div>

    <!-- FORM -->
    <form
      @submit.prevent="submit"
      class="rounded-2xl border bg-white p-6 shadow-sm space-y-5 dark:border-gray-800 dark:bg-gray-900"
    >

      <!-- TIME NAME -->
      <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
          {{ $t('Time Name') }}
        </label>

        <input
          v-model="form.time_name"
          type="text"
          class="mt-2 w-full rounded-xl border px-4 py-3 text-sm
                 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
          :placeholder="$t('Enter time name')"
        />

        <p v-if="form.errors.time_name" class="text-red-500 text-sm mt-1 dark:text-red-400">
          {{ form.errors.time_name }}
        </p>
      </div>

      <!-- ACTIONS -->
      <div class="flex justify-end gap-3">

        <button
          type="button"
          @click="$emit('close')"
          class="rounded-xl border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 active:bg-orange-300 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800 dark:active:bg-orange-500/30"
        >
          {{ $t('Cancel') }}
        </button>

        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-xl bg-yellow-500 px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-600 disabled:opacity-50 dark:bg-yellow-600 dark:hover:bg-yellow-500"
        >
          {{ form.processing ? $t('Updating...') : $t('Update Time') }}
        </button>

      </div>

    </form>

  </div>
</template>
