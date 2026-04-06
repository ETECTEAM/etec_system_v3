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
  <main class="min-h-screen bg-slate-100 text-slate-900">
    <div class="grid min-h-screen w-full grid-cols-1 lg:grid-cols-[1.1fr_0.9fr]">
      <section class="relative hidden min-h-screen overflow-hidden bg-blue-900 p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-16">
        <div class="absolute -right-10 -top-10 h-56 w-56 rounded-full bg-blue-300/30 blur-3xl"></div>
        <div class="absolute -bottom-16 left-10 h-64 w-64 rounded-full bg-sky-300/20 blur-3xl"></div>

        <div class="relative">
          <p class="text-xs uppercase tracking-[0.35em] text-blue-100/90">ETEC System</p>
          <h1 class="mt-6 text-4xl font-black leading-tight">Create your account and start managing smarter.</h1>
          <p class="mt-4 max-w-md text-sm text-blue-100/90">
            Join the dashboard to organize courses, operations, and team workflows in one place.
          </p>
        </div>

        <div class="relative rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
          <p class="text-sm text-blue-100">"Set up once, move faster every day."</p>
          <p class="mt-3 text-xs uppercase tracking-[0.22em] text-blue-100">Onboarding</p>
        </div>
      </section>

      <section class="flex min-h-screen items-center justify-center bg-linear-to-br from-slate-100 via-white to-blue-50 p-6 sm:p-10 xl:p-16">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-2xl shadow-slate-300/30 sm:p-10">
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
      </section>
    </div>
  </main>
</template>
