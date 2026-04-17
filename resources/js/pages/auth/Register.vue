<script>
import AuthLayout from '../../layouts/AuthLayout.vue'

export default {
  layout: AuthLayout,
}
</script>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

function validatePasswordMatch() {
  if (form.password_confirmation && form.password !== form.password_confirmation) {
    form.setError('password_confirmation', 'Passwords do not match.')
    return false
  }

  form.clearErrors('password_confirmation')
  return true
}

function submit() {
  if (!validatePasswordMatch()) {
    return
  }

  form.post('/register')
}
</script>

<template>
  <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl sm:p-10">
    <div class="mb-8">
      <h2 class="text-3xl font-black text-slate-900">Create Account</h2>
      <p class="mt-2 text-sm text-slate-600">Register to access the dashboard.</p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700">Full Name</span>
        <input
          v-model="form.name"
          type="text"
          autocomplete="name"
          class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
          placeholder="Your name"
        >
        <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600">{{ form.errors.name }}</span>
      </label>

      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700">Email</span>
        <input
          v-model="form.email"
          type="email"
          autocomplete="email"
          class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
          placeholder="you@example.com"
        >
        <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
      </label>

      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700">Password</span>
        <input
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
          placeholder="Minimum 8 characters"
        >
        <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600">{{ form.errors.password }}</span>
      </label>

      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700">Confirm Password</span>
        <input
          v-model="form.password_confirmation"
          type="password"
          autocomplete="new-password"
          class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
          placeholder="Repeat password"
        >
        <span v-if="form.errors.password_confirmation" class="mt-1 block text-xs text-red-600">{{ form.errors.password_confirmation }}</span>
      </label>

      <p v-if="form.hasErrors && !form.errors.name && !form.errors.email && !form.errors.password && !form.errors.password_confirmation" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        Unable to create account right now. Please try again.
      </p>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full rounded-xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70"
      >
        {{ form.processing ? 'Creating account...' : 'Create Account' }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
      Already have an account?
      <Link href="/login" class="font-semibold text-blue-900 hover:text-blue-950">Sign in</Link>
    </p>
  </div>
</template>
