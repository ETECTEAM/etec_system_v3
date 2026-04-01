<script setup>
import { reactive, ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const form = reactive({
  login: '',
  password: '',
})

const loading = ref(false)
const generalError = ref('')
const errors = reactive({
  login: '',
  password: '',
})

function resetErrors() {
  generalError.value = ''
  errors.login = ''
  errors.password = ''
}

async function submit() {
  resetErrors()
  loading.value = true

  try {
    const { data } = await window.axios.post('/api/v1/auth/login', {
      login: form.login,
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
      errors.login = fieldErrors.login?.[0] || fieldErrors.email?.[0] || ''
      errors.password = fieldErrors.password?.[0] || ''
      if (!errors.login && !errors.password) {
        generalError.value = 'Invalid login credentials.'
      }
    } else {
      generalError.value = 'Unable to login right now. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="min-h-screen bg-slate-100 text-slate-900">
    <div class="mx-auto grid min-h-screen max-w-6xl grid-cols-1 overflow-hidden lg:grid-cols-2">
      <section class="relative hidden bg-slate-900 p-10 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-10 -top-10 h-56 w-56 rounded-full bg-cyan-400/30 blur-3xl"></div>
        <div class="absolute -bottom-16 left-10 h-64 w-64 rounded-full bg-emerald-300/20 blur-3xl"></div>

        <div class="relative">
          <p class="text-xs uppercase tracking-[0.35em] text-cyan-200/90">ETEC System</p>
          <h1 class="mt-6 text-4xl font-black leading-tight">Welcome back to your learning operations hub.</h1>
          <p class="mt-4 max-w-md text-sm text-slate-200/90">
            Manage classes, students, schedules, and reporting with a fast admin flow.
          </p>
        </div>

        <div class="relative rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
          <p class="text-sm text-slate-200">"Fast setup, smooth workflow, fewer blockers."</p>
          <p class="mt-3 text-xs uppercase tracking-[0.22em] text-cyan-200">Team Workflow</p>
        </div>
      </section>

      <section class="flex items-center justify-center p-6 sm:p-10">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-300/30">
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
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100"
                placeholder="you@example.com or username"
              >
              <span v-if="errors.login" class="mt-1 block text-xs text-red-600">{{ errors.login }}</span>
            </label>

            <label class="block">
              <span class="mb-2 block text-sm font-semibold text-slate-700">Password</span>
              <input
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100"
                placeholder="Enter your password"
              >
              <span v-if="errors.password" class="mt-1 block text-xs text-red-600">{{ errors.password }}</span>
            </label>

            <p v-if="generalError" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
              {{ generalError }}
            </p>

            <button
              type="submit"
              :disabled="loading"
              class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {{ loading ? 'Signing in...' : 'Sign In' }}
            </button>
          </form>

          <p class="mt-6 text-center text-sm text-slate-600">
            New here?
            <Link href="/register" class="font-semibold text-cyan-700 hover:text-cyan-900">Create account</Link>
          </p>
        </div>
      </section>
    </div>
  </main>
</template>
