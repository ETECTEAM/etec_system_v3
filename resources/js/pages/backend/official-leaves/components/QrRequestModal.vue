<script setup>
import { computed, ref } from 'vue'
import { QrcodeCanvas } from 'qrcode.vue'
import { CheckCircle2, RefreshCw, ScanLine, X } from '@lucide/vue'
import { useI18n } from '@/i18n'
import StatusBadge from './StatusBadge.vue'

const props = defineProps({
  student: {
    type: Object,
    required: true,
  },
  sessionState: {
    type: String,
    default: 'idle',
  },
  qrUrl: {
    type: String,
    default: '',
  },
  remainingSeconds: {
    type: Number,
    default: 0,
  },
  leave: {
    type: Object,
    default: null,
  },
  deciding: {
    type: Boolean,
    default: false,
  },
  rejectNote: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'regenerate', 'approve', 'reject', 'update:rejectNote'])

const { t } = useI18n()

const showRejectInput = ref(false)

const countdown = computed(() => {
  const total = Math.max(0, Math.floor(props.remainingSeconds))
  const minutes = String(Math.floor(total / 60)).padStart(2, '0')
  const seconds = String(total % 60).padStart(2, '0')

  return `${minutes}:${seconds}`
})

function formatDateTime(value) {
  if (!value) return '-'

  return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="emit('close')" />

      <div class="relative z-10 w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-600 dark:text-blue-400">{{ $t('Official Leave') }}</p>
            <h3 class="mt-1 text-lg font-black text-slate-950 dark:text-gray-100">{{ student.full_name }}</h3>
            <p class="mt-0.5 text-xs font-semibold text-slate-500 dark:text-gray-400">
              {{ student.classes?.length ? student.classes.join(', ') : (student.course ?? '-') }}
            </p>
          </div>

          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-400 dark:hover:bg-gray-800"
            @click="emit('close')"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Waiting for the phone scan -->
        <div v-if="sessionState === 'waiting'" class="mt-5 space-y-4 text-center">
          <div class="mx-auto w-fit rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200 dark:bg-gray-50">
            <QrcodeCanvas :value="qrUrl" :size="200" level="H" />
          </div>

          <p class="flex items-center justify-center gap-2 text-sm font-semibold text-slate-600 dark:text-gray-300">
            <ScanLine class="h-4 w-4 animate-pulse" />
            {{ $t('Ask the student to scan this code and fill in the form.') }}
          </p>

          <p class="font-mono text-2xl font-black" :class="remainingSeconds > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-rose-600'">
            {{ countdown }}
          </p>

          <button
            type="button"
            class="inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg bg-slate-100 px-3 text-sm font-bold text-slate-700 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            @click="emit('regenerate')"
          >
            <RefreshCw class="h-4 w-4" />
            {{ $t('Regenerate QR') }}
          </button>
        </div>

        <!-- Expired before submission -->
        <div v-else-if="sessionState === 'expired'" class="mt-5 space-y-4 text-center">
          <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-6 text-sm font-semibold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
            {{ $t('The QR code expired. Generate a new one and let the student scan again.') }}
          </p>

          <button
            type="button"
            class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-bold text-white transition hover:bg-blue-700"
            @click="emit('regenerate')"
          >
            <RefreshCw class="h-4 w-4" />
            {{ $t('Regenerate QR') }}
          </button>
        </div>

        <!-- Review card after the phone submitted -->
        <div v-else-if="sessionState === 'submitted' && leave" class="mt-5 space-y-4">
          <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            <CheckCircle2 class="h-4 w-4" />
            {{ $t('Request received — review below.') }}
          </div>

          <dl class="space-y-2 rounded-xl border border-slate-200 p-4 text-sm dark:border-gray-700">
            <div class="flex items-center justify-between gap-4">
              <dt class="font-semibold text-slate-500 dark:text-gray-400">{{ $t('Student') }}</dt>
              <dd class="text-right font-bold text-slate-900 dark:text-gray-100">{{ leave.student.full_name }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
              <dt class="font-semibold text-slate-500 dark:text-gray-400">{{ $t('Class / Course') }}</dt>
              <dd class="max-w-[60%] text-right font-bold text-slate-900 dark:text-gray-100">
                {{ leave.classes?.length ? leave.classes.join(', ') : (leave.course ?? '-') }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-4">
              <dt class="font-semibold text-slate-500 dark:text-gray-400">{{ $t('Date range') }}</dt>
              <dd class="text-right font-bold text-slate-900 dark:text-gray-100">{{ leave.start_date }} → {{ leave.end_date }} ({{ leave.days }}d)</dd>
            </div>
            <div class="flex items-start justify-between gap-4">
              <dt class="shrink-0 font-semibold text-slate-500 dark:text-gray-400">{{ $t('Reason') }}</dt>
              <dd class="text-right font-semibold italic text-slate-700 dark:text-gray-200">“{{ leave.reason }}”</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
              <dt class="font-semibold text-slate-500 dark:text-gray-400">{{ $t('Requested at') }}</dt>
              <dd class="text-right font-bold text-slate-900 dark:text-gray-100">{{ formatDateTime(leave.requested_at) }}</dd>
            </div>
            <div class="flex items-center justify-end pt-1">
              <StatusBadge :status="leave.status" />
            </div>
          </dl>

          <template v-if="leave.status === 'pending'">
            <div v-if="showRejectInput">
              <textarea
                :value="rejectNote"
                rows="2"
                :placeholder="$t('Short rejection note (required)')"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                @input="emit('update:rejectNote', $event.target.value)"
              />

              <div class="mt-3 grid grid-cols-2 gap-2">
                <button
                  type="button"
                  class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-100 px-3 text-sm font-bold text-slate-700 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-gray-800 dark:text-gray-200"
                  @click="showRejectInput = false"
                >
                  {{ $t('Back') }}
                </button>
                <button
                  type="button"
                  :disabled="deciding"
                  class="inline-flex h-10 items-center justify-center rounded-lg bg-rose-600 px-3 text-sm font-bold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70"
                  @click="emit('reject')"
                >
                  {{ deciding ? $t('Working...') : $t('Confirm Reject') }}
                </button>
              </div>
            </div>

            <div v-else class="grid grid-cols-2 gap-2">
              <button
                type="button"
                :disabled="deciding"
                class="inline-flex h-11 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 text-sm font-black text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-70 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300"
                @click="showRejectInput = true"
              >
                {{ $t('Reject') }}
              </button>
              <button
                type="button"
                :disabled="deciding"
                class="inline-flex h-11 items-center justify-center rounded-lg bg-emerald-600 px-3 text-sm font-black text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-70"
                @click="emit('approve')"
              >
                {{ deciding ? $t('Working...') : $t('Approve') }}
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </Teleport>
</template>
