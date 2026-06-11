<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Head, useForm, Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const term = page.props.term

const form = useForm({
  term_name: term.term_name,
})

function submit() {
  form.put(`/dashboard/terms/${term.id}`, {
    onSuccess: () => {
      // optional redirect handled by backend
    },
  })
}
</script>

<template>
  <Head title="Edit Term" />

  <DashboardLayout>
    <div class="max-w-xl mx-auto space-y-6">

      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Term</h1>
        <p class="text-sm text-gray-500">Update term information</p>
      </div>

      <!-- Card -->
      <form @submit.prevent="submit" class="rounded-md border bg-white p-6 shadow-sm space-y-6">

        <!-- Input -->
        <div>
          <label class="text-sm font-medium text-gray-700">Term Name</label>

          <input
            v-model="form.term_name"
            type="text"
            class="mt-2 w-full rounded-md border border-gray-300 px-4 py-3 text-sm
                   focus:border-yellow-500 focus:ring-2 focus:ring-yellow-100 outline-none transition"
          />

          <p v-if="form.errors.term_name" class="text-red-500 text-sm mt-1">
            {{ form.errors.term_name }}
          </p>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end gap-3">

          <Link
            href="/dashboard/terms"
            class="rounded-md border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </Link>

          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-md bg-yellow-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-yellow-600 disabled:opacity-50"
          >
            {{ form.processing ? 'Updating...' : 'Update Term' }}
          </button>

        </div>

      </form>

    </div>
  </DashboardLayout>
</template>
