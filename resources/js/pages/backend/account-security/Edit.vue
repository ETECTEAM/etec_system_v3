<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import { useToast } from 'vue-toastification'
import { CheckCircle2, Mail, RotateCcw, Save, ShieldAlert, ShieldCheck } from '@lucide/vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'

const props = defineProps({
  loginEmail: String,
  recoveryEmail: String,
  recoveryVerified: Boolean,
})

const toast = useToast()
const page = usePage()

watch(() => page.props.flash, (flash) => {
  if (flash?.success) {
    toast.success(flash.success)
  } else if (flash?.error) {
    toast.error(flash.error)
  }
}, { deep: true })

const form = useForm({
  recovery_email: '',
  current_password: '',
})

function submit() {
  form.transform((data) => ({
    recovery_email: data.recovery_email.trim().toLowerCase(),
    current_password: data.current_password,
  })).post('/dashboard/account-security/recovery-email', {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  })
}

function resend() {
  const resendForm = useForm({})
  resendForm.post('/dashboard/account-security/recovery-email/resend', { preserveScroll: true })
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Account Security', current: true },
]
</script>

<template>
  <Head title="Account Security" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Security"
        title="Account Security"
        description="Add a personal recovery email so password-reset links go somewhere only you can reach, separate from your login email."
      />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-start gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400">
            <Mail class="h-5 w-5" />
          </span>
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">Recovery email</h3>
            <p class="mt-1 max-w-md text-sm text-slate-500 italic dark:text-gray-400">
              Login email: <span class="font-semibold not-italic">{{ props.loginEmail }}</span>
            </p>
          </div>
        </div>

        <div class="my-6 border-t border-slate-200 dark:border-gray-800"></div>

        <div v-if="props.recoveryEmail" class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 p-4 dark:border-gray-800">
          <div class="flex items-center gap-3">
            <ShieldCheck v-if="props.recoveryVerified" class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
            <ShieldAlert v-else class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
            <div>
              <p class="text-sm font-semibold text-slate-800 dark:text-gray-200">{{ props.recoveryEmail }}</p>
              <p class="text-xs text-slate-500 dark:text-gray-400">
                <span v-if="props.recoveryVerified" class="inline-flex items-center gap-1 text-emerald-700 dark:text-emerald-400">
                  <CheckCircle2 class="h-3.5 w-3.5" /> Verified
                </span>
                <span v-else>Pending verification - check that inbox for a link.</span>
              </p>
            </div>
          </div>
          <button
            v-if="!props.recoveryVerified"
            type="button"
            class="flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-400 hover:text-blue-700 dark:border-gray-700 dark:text-gray-300 dark:hover:border-blue-500/50 dark:hover:text-blue-400"
            @click="resend"
          >
            <RotateCcw class="h-3.5 w-3.5" />
            Resend link
          </button>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-200">
              {{ props.recoveryEmail ? 'Change recovery email' : 'Add recovery email' }}
            </label>
            <input
              v-model="form.recovery_email"
              type="email"
              placeholder="you@personal-email.com"
              class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >
            <p v-if="form.errors.recovery_email" class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ form.errors.recovery_email }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-200">Current password</label>
            <input
              v-model="form.current_password"
              type="password"
              autocomplete="current-password"
              class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >
            <p v-if="form.errors.current_password" class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ form.errors.current_password }}</p>
          </div>

          <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-6 dark:border-gray-800">
            <button
              type="submit"
              :disabled="form.processing"
              class="flex items-center gap-2 rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              <Save class="h-4 w-4" />
              {{ form.processing ? 'Saving...' : 'Save recovery email' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
