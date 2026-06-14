<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { watch } from 'vue'

const page = usePage()

const props = defineProps({
  time: Object
})

const terms = page.props.terms

const emit = defineEmits(['close'])

const form = useForm({
  time_name: '',
  term_id: '',
})

// fill form when data comes
watch(
  () => props.time,
  (val) => {
    if (val) {
      form.time_name = val.time_name
      form.term_id = val.term_id
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
      <h2 class="text-lg font-bold text-gray-900">
        Edit Time
      </h2>
      <p class="text-sm text-gray-500">
        Update time information
      </p>
    </div>

    <!-- FORM -->
    <form
      @submit.prevent="submit"
      class="rounded-2xl border bg-white p-6 shadow-sm space-y-5"
    >

      <!-- TIME NAME -->
      <div>
        <label class="text-sm font-medium text-gray-700">
          Time Name
        </label>

        <input
          v-model="form.time_name"
          type="text"
          class="mt-2 w-full rounded-xl border px-4 py-3 text-sm
                 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none"
          placeholder="Enter time name"
        />

        <p v-if="form.errors.time_name" class="text-red-500 text-sm mt-1">
          {{ form.errors.time_name }}
        </p>
      </div>

      <!-- TERM SELECT -->
      <div>
        <label class="text-sm font-medium text-gray-700">
          Term
        </label>

        <select
          v-model="form.term_id"
          class="mt-2 w-full rounded-xl border px-4 py-3 text-sm bg-gray-50
                 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none transition"
        >
          <option value="">Select Term</option>

          <option
            v-for="term in terms"
            :key="term.id"
            :value="term.id"
          >
            {{ term.term_name }}
          </option>
        </select>

        <p v-if="form.errors.term_id" class="text-red-500 text-sm mt-1">
          {{ form.errors.term_id }}
        </p>
      </div>

      <!-- ACTIONS -->
      <div class="flex justify-end gap-3">

        <button
          type="button"
          @click="$emit('close')"
          class="rounded-xl border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 active:bg-orange-300"
        >
          Cancel
        </button>

        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-xl bg-yellow-500 px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-600 disabled:opacity-50"
        >
          {{ form.processing ? 'Updating...' : 'Update Time' }}
        </button>

      </div>

    </form>

  </div>
</template>
