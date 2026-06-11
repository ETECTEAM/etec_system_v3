<script setup>
import { useForm } from '@inertiajs/vue3'

const emit = defineEmits(['close'])

const form = useForm({
  term_name: '',
})

function submit() {
  form.post('/dashboard/terms', {
    onSuccess: () => {
      form.reset()
      emit('close')
    }
  })
}
</script>

<template>
  <div>
   
    <div class="text-center mb-4">
      <h2 class="text-lg font-bold text-gray-900">Create Term</h2>
      <p class="text-sm text-gray-500">Add a new term</p>
    </div>

      <!-- Card -->
      <form @submit.prevent="submit" class="rounded-2xl border bg-white p-6 shadow-sm space-y-5">

        <div>
          <label class="text-sm font-medium text-gray-700">Term Name</label>

          <input
            v-model="form.term_name"
            type="text"
            class="mt-2 w-full rounded-xl border px-4 py-3 text-sm
                   focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none"
            placeholder="Enter term name"
          />

          <p v-if="form.errors.term_name" class="text-red-500 text-sm mt-1">
            {{ form.errors.term_name }}
          </p>
        </div>

        <div class="flex justify-end gap-3">

          <Link
            href="/dashboard/terms"
            class="rounded-xl border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </Link>

          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          >
            Create Term
          </button>

        </div>

      </form>

    </div>
  </div>
</template>
