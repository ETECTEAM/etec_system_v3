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
  <main class="min-h-screen bg-slate-100 text-slate-900">
    <div class="grid min-h-screen w-full grid-cols-1 lg:grid-cols-[1.1fr_0.9fr]">
      <section class="relative hidden min-h-screen overflow-hidden bg-blue-900 p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-16">
        <div class="absolute -right-10 -top-10 h-56 w-56 rounded-full bg-blue-300/30 blur-3xl"></div>
        <div class="absolute -bottom-16 left-10 h-64 w-64 rounded-full bg-sky-300/20 blur-3xl"></div>

        <div class="relative">
          <p class="text-xs uppercase tracking-[0.35em] text-blue-100/90">ETEC System</p>
          <h1 class="mt-6 text-4xl font-black leading-tight">Welcome back to your learning operations hub.</h1>
          <p class="mt-4 max-w-md text-sm text-slate-200/90">
            Manage classes, students, schedules, and reporting with a fast admin flow.
          </p>
        </div>

        <div class="relative rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
          <p class="text-sm text-slate-200">"Fast setup, smooth workflow, fewer blockers."</p>
          <p class="mt-3 text-xs uppercase tracking-[0.22em] text-blue-100">Team Workflow</p>
        </div>
      </section>

      <section class="flex min-h-screen items-center justify-center bg-linear-to-br from-slate-100 via-white to-blue-50 p-6 sm:p-10 xl:p-16">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-2xl shadow-slate-300/30 sm:p-10">
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
      </section>
    </div>
  </main>
</template>
