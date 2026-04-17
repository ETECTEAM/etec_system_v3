<script>
import AuthLayout from '../../layouts/AuthLayout.vue'

export default {
  layout: AuthLayout,
}
</script>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  login: '',
  password: '',
})

function submit() {
  form.post('/login')
}
</script>

<template>
  <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl sm:p-10">
    <div class="mb-8">
      <h2 class="text-3xl font-black text-slate-900">Sign In</h2>
      <p class="mt-2 text-sm text-slate-600">Use your email or username to continue.</p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700">Email or Username</span>
        <input
          v-model="form.login"
          type="text"
          autocomplete="username"
          class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
          placeholder="you@example.com or username"
        >
        <span v-if="form.errors.login" class="mt-1 block text-xs text-red-600">{{ form.errors.login }}</span>
      </label>

      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700">Password</span>
        <input
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
          placeholder="Enter your password"
        >
        <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600">{{ form.errors.password }}</span>
      </label>

      <p v-if="form.hasErrors && !form.errors.login && !form.errors.password" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        Invalid login credentials.
      </p>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full rounded-xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70"
      >
        {{ form.processing ? 'Signing in...' : 'Sign In' }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
      New here?
      <Link href="/register" class="font-semibold text-blue-900 hover:text-blue-950">Create account</Link>
    </p>
  </div>
</template>
