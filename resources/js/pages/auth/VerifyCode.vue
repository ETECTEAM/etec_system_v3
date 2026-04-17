<script setup>
import axios from 'axios'
import { onMounted, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthCard from '../../components/auth/AuthCard.vue'
import AuthLayout from '../../layouts/AuthLayout.vue'

defineOptions({
  layout: AuthLayout,
})

const page = usePage()
const pendingEmail = page.props.pendingEmail ?? ''
const pendingUserId = page.props.pendingUserId ?? null

const code = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')

onMounted(() => {
  if (pendingUserId) {
    localStorage.setItem('verify_user_id', String(pendingUserId))
  }
})

async function submit() {
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    const userId = localStorage.getItem('verify_user_id')

    const response = await axios.post('/api/verify-code', {
      code: code.value,
      user_id: userId ? Number(userId) : null,
    })

    localStorage.removeItem('verify_user_id')
    router.visit(response.data.redirect ?? '/login')
  } catch (error) {
    errorMessage.value = error.response?.data?.errors?.code?.[0] ?? 'Unable to verify the code right now.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <AuthCard>
    <div class="mb-8">
      <h2 class="text-3xl font-black text-slate-900">Verify Code</h2>
      <p class="mt-2 text-sm text-slate-600">
        Enter the 6-digit verification code for {{ pendingEmail }} to activate your account.
      </p>
    </div>

    <form class="space-y-4" @submit.prevent="submit">
      <label class="block">
        <span class="mb-2 block text-sm font-semibold text-slate-700">Verification Code</span>
        <input
          v-model="code"
          type="text"
          inputmode="numeric"
          maxlength="6"
          class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-center text-lg tracking-[0.4em] outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
          placeholder="000000"
        >
      </label>

      <p v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        {{ errorMessage }}
      </p>

      <button
        type="submit"
        :disabled="isSubmitting || code.length !== 6"
        class="w-full rounded-xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70"
      >
        {{ isSubmitting ? 'Verifying...' : 'Verify Code' }}
      </button>
    </form>
  </AuthCard>
</template>
