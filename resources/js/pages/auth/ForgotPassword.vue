<script setup>
import AuthCard from './components/AuthCard.vue'
import AuthLayout from '../../layouts/AuthLayout.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'

defineOptions({
  layout: AuthLayout,
})

const page = usePage()

const form = useForm({
  email: '',
})

function submit() {
  form
    .transform((data) => ({ ...data, email: data.email.trim().toLowerCase() }))
    .post('/forgot-password')
}
</script>

<template>
  <AuthCard>
    <div class="mb-8">
      <h2 class="text-3xl font-black text-slate-900 dark:text-gray-100">Forgot Password</h2>
      <p class="mt-2 text-sm text-slate-600 dark:text-gray-400">Enter your email and we'll send you a link to reset your password.</p>
    </div>

    <div
      v-if="page.props.flash?.success"
      class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400"
    >
      {{ page.props.flash.success }}
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Email</span>
        <input
          v-model="form.email"
          type="email"
          autocomplete="email"
          class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
          placeholder="you@etec.com"
        >
        <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ form.errors.email }}</span>
      </label>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full rounded-xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
      >
        {{ form.processing ? 'Sending...' : 'Send Reset Link' }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600 dark:text-gray-400">
      Remembered your password?
      <Link href="/login" class="font-semibold text-blue-900 hover:text-blue-950 dark:text-blue-400 dark:hover:text-blue-300">Sign in</Link>
    </p>
  </AuthCard>
</template>
