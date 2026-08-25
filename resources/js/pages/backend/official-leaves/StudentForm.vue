<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  student: Object,
  token: String,
  expiresAt: String,
})

const countdown = ref(0)
let interval = null

const form = useForm({
  student_id: props.student.id,
  start_date: '',
  end_date: '',
  reason: '',
})

function submit() {
  form.post(`/dashboard/official-leaves/request?token=${props.token}`, {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  })
}

onMounted(() => {
  interval = setInterval(() => {
    countdown.value = Math.max(0, Math.floor((new Date(props.expiresAt) - new Date()) / 1000))
    if (countdown.value <= 0) clearInterval(interval)
  }, 1000)
})
onUnmounted(() => { if (interval) clearInterval(interval) })

function fmt(s) { return `${Math.floor(s/60)}:${(s%60).toString().padStart(2,'0')}` }
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
      <div class="bg-white rounded-2xl shadow-lg border border-slate-200 dark:bg-gray-900 dark:border-gray-800 p-6">
        <div class="text-center mb-6">
          <h1 class="text-xl font-bold text-slate-900 dark:text-gray-100">Official Leave Request</h1>
          <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">Complete the form below to submit your leave request.</p>
          <p v-if="countdown > 0" class="text-xs text-slate-400 mt-2">Token expires in <span class="font-mono font-bold text-blue-600">{{ fmt(countdown) }}</span></p>
          <p v-else class="text-xs text-red-500 font-semibold mt-2">This link has expired.</p>
        </div>

        <div class="bg-slate-50 rounded-xl p-4 mb-6 dark:bg-gray-800">
          <p class="text-sm"><span class="font-medium text-slate-700 dark:text-gray-300">Name:</span> {{ student.full_name }}</p>
          <p class="text-sm"><span class="font-medium text-slate-700 dark:text-gray-300">ID:</span> {{ student.id }}</p>
        </div>

        <form v-if="countdown > 0" @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Start Date</label>
            <input v-model="form.start_date" type="date" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
            <p v-if="form.errors.start_date" class="text-red-500 text-xs mt-1">{{ form.errors.start_date }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">End Date</label>
            <input v-model="form.end_date" type="date" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
            <p v-if="form.errors.end_date" class="text-red-500 text-xs mt-1">{{ form.errors.end_date }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Reason</label>
            <textarea v-model="form.reason" rows="3" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
            <p v-if="form.errors.reason" class="text-red-500 text-xs mt-1">{{ form.errors.reason }}</p>
          </div>
          <button type="submit" :disabled="form.processing" class="w-full px-4 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition">
            {{ form.processing ? 'Submitting...' : 'Submit Leave Request' }}
          </button>
        </form>
        <div v-else class="text-center py-6">
          <p class="text-sm text-red-500 font-medium">This QR code has expired. Please ask the office for a new one.</p>
        </div>
      </div>
    </div>
  </div>
</template>
