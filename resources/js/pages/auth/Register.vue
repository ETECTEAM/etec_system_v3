<script setup>
import { ref } from 'vue'
import AuthCard from '../../components/auth/AuthCard.vue'
import AuthLayout from '../../layouts/AuthLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

defineOptions({
  layout: AuthLayout,
})

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

function submit() {
  form.post('/register')
}
</script>

<template>
  <AuthCard>
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
          placeholder="you@etec.com"
        >
        <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
      </label>

      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700">Password</span>
        <div class="relative">
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="new-password"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-12 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            placeholder="Minimum 8 characters"
          >

          <button
            type="button"
            class="absolute top-1/2 right-3 -translate-y-1/2 text-slate-500 transition hover:text-slate-700"
            @click="showPassword = !showPassword"
          >
            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58A2 2 0 0012 16a2 2 0 001.42-.58" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 4.24A10.94 10.94 0 0112 4c5 0 9.27 3.11 11 8-1.01 2.85-2.94 5.11-5.41 6.44" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.61 6.61C4.62 8 3.1 9.86 2 12c.69 1.94 1.79 3.62 3.17 4.94" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-8 10-8 10 8 10 8-3.5 8-10 8S2 12 2 12z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
          </button>
        </div>
        <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600">{{ form.errors.password }}</span>
      </label>

      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700">Confirm Password</span>
        <div class="relative">
          <input
            v-model="form.password_confirmation"
            :type="showPasswordConfirmation ? 'text' : 'password'"
            autocomplete="new-password"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-12 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            placeholder="Repeat password"
          >

          <button
            type="button"
            class="absolute top-1/2 right-3 -translate-y-1/2 text-slate-500 transition hover:text-slate-700"
            @click="showPasswordConfirmation = !showPasswordConfirmation"
          >
            <svg v-if="showPasswordConfirmation" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58A2 2 0 0012 16a2 2 0 001.42-.58" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 4.24A10.94 10.94 0 0112 4c5 0 9.27 3.11 11 8-1.01 2.85-2.94 5.11-5.41 6.44" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.61 6.61C4.62 8 3.1 9.86 2 12c.69 1.94 1.79 3.62 3.17 4.94" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-8 10-8 10 8 10 8-3.5 8-10 8S2 12 2 12z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
          </button>
        </div>
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
  </AuthCard>
</template>
