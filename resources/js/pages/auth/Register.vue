<script setup>
import { reactive, ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const loading = ref(false)
const generalError = ref('')
const errors = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

function resetErrors() {
  generalError.value = ''
  errors.name = ''
  errors.email = ''
  errors.password = ''
  errors.password_confirmation = ''
}

function validatePasswordMatch() {
  if (form.password_confirmation && form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Passwords do not match.'
    return false
  }
  return true
}

async function submit() {
  resetErrors()

  if (!validatePasswordMatch()) {
    return
  }

  loading.value = true

  try {
    const { data } = await window.axios.post('/api/v1/auth/register', {
      name: form.name,
      email: form.email,
      password: form.password,
    })

    if (data?.token) {
      localStorage.setItem('auth_token', data.token)
      window.axios.defaults.headers.common.Authorization = `Bearer ${data.token}`
    }

    window.location.href = '/'
  } catch (error) {
    if (error?.response?.status === 422) {
      const fieldErrors = error.response.data?.errors || {}
      errors.name = fieldErrors.name?.[0] || ''
      errors.email = fieldErrors.email?.[0] || ''
      errors.password = fieldErrors.password?.[0] || ''
      if (!errors.name && !errors.email && !errors.password) {
        generalError.value = 'Unable to register with provided details.'
      }
    } else {
      generalError.value = 'Unable to create account right now. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="min-h-screen bg-slate-100 text-slate-900">
    <div class="mx-auto grid min-h-screen max-w-6xl grid-cols-1 overflow-hidden lg:grid-cols-2">
      <section class="relative hidden bg-emerald-900 p-10 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-10 -top-10 h-56 w-56 rounded-full bg-lime-300/20 blur-3xl"></div>
        <div class="absolute -bottom-16 left-10 h-64 w-64 rounded-full bg-cyan-300/25 blur-3xl"></div>

        <div class="relative">
          <p class="text-xs uppercase tracking-[0.35em] text-emerald-100/90">ETEC System</p>
          <h1 class="mt-6 text-4xl font-black leading-tight">Create your account and start managing smarter.</h1>
          <p class="mt-4 max-w-md text-sm text-emerald-100/90">
            Join the dashboard to organize courses, operations, and team workflows in one place.
          </p>
        </div>

        <div class="relative rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
          <p class="text-sm text-emerald-100">"Set up once, move faster every day."</p>
          <p class="mt-3 text-xs uppercase tracking-[0.22em] text-lime-100">Onboarding</p>
        </div>
      </section>

      <section class="flex items-center justify-center p-6 sm:p-10">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-300/30">
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
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                placeholder="Your name"
              >
              <span v-if="errors.name" class="mt-1 block text-xs text-red-600">{{ errors.name }}</span>
            </label>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700">Email</span>
              <input
                v-model="form.email"
                type="email"
                autocomplete="email"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                placeholder="you@example.com"
              >
              <span v-if="errors.email" class="mt-1 block text-xs text-red-600">{{ errors.email }}</span>
            </label>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700">Password</span>
              <input
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                placeholder="Minimum 8 characters"
              >
              <span v-if="errors.password" class="mt-1 block text-xs text-red-600">{{ errors.password }}</span>
            </label>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700">Confirm Password</span>
              <input
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                placeholder="Repeat password"
              >
              <span v-if="errors.password_confirmation" class="mt-1 block text-xs text-red-600">{{ errors.password_confirmation }}</span>
            </label>

            <p v-if="generalError" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
              {{ generalError }}
            </p>

            <button
              type="submit"
              :disabled="loading"
              class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {{ loading ? 'Creating account...' : 'Create Account' }}
            </button>
          </form>

          <p class="mt-6 text-center text-sm text-slate-600">
            Already have an account?
            <Link href="/login" class="font-semibold text-emerald-700 hover:text-emerald-900">Sign in</Link>
          </p>
        </div>
      </section>
    </div>
  </main>
</template>
