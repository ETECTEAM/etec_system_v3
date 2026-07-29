<script setup>
import { useForm } from '@inertiajs/vue3'
import { nextTick, onMounted, ref } from 'vue'

const props = defineProps({
  terms: Array
})

const emit = defineEmits(['close'])

const form = useForm({
  time_name: '',
  term_id: '',
})

const inputRef = ref(null)

onMounted(() => {
  nextTick(() => {
    inputRef.value?.focus()
  })
})

function submit() {
  form.post('/dashboard/times', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      emit('close')
    }
  })
}
</script>

<template>
  <div>

    <!-- HEADER -->
    <div class="text-center mb-4">
      <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
        {{ $t('Create Time') }}
      </h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ $t('Add a new time schedule') }}
      </p>
    </div>

    <!-- FORM -->
    <form
      @submit.prevent="submit"
      class="rounded-2xl border bg-white p-6 shadow-sm space-y-5 dark:border-gray-800 dark:bg-gray-900"
    >

      <!-- TIME NAME -->
      <div>
        <label class="text-sm  font-medium text-gray-700 dark:text-gray-200">
          {{ $t('Time Name') }}
        </label>

        <input
          ref="inputRef"
          v-model="form.time_name"
          type="text"
          :placeholder="$t('Enter time name')"
          class="mt-2 w-full rounded-xl border px-4 py-3 text-sm
                 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
        />

        <p v-if="form.errors.time_name" class="text-red-500 text-sm mt-1 dark:text-red-400">
          {{ form.errors.time_name }}
        </p>
      </div>

      <!-- TERM SELECT -->
      <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
          {{ $t('Term') }}
        </label>

        <select
          v-model="form.term_id"
          class="mt-2 w-full rounded-xl border px-4 py-3 text-sm bg-gray-50
                 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none transition dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:bg-gray-800 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
        >
          <option value="">{{ $t('Select Term') }}</option>

          <option
            v-for="term in terms"
            :key="term.id"
            :value="term.id"
          >
            {{ term.term_name }}
          </option>
        </select>

        <p v-if="form.errors.term_id" class="text-red-500 text-sm mt-1 dark:text-red-400">
          {{ form.errors.term_id }}
        </p>
      </div>

      <!-- ACTIONS -->
      <div class="flex justify-end gap-3">

        <button
          type="button"
          @click="$emit('close')"
          class="rounded-xl border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50  cursor-pointer dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
        >
          {{ $t('Cancel') }}
        </button>

        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-xl cursor-pointer bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-500"
        >
          {{ form.processing ? $t('Creating...') : $t('Create Time') }}
        </button>

      </div>

    </form>

  </div>
</template>
