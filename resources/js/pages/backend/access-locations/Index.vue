<script setup>
import { computed, ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ShieldCheck } from '@lucide/vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '@/components/ui/breadcrumbs/Breadcrumbs.vue'
import { PageHero } from '@/components/ui/page-hero'
import { useConfirm } from '@/composables/useConfirm'
import { useI18n } from '@/i18n'
import LocationForm from './LocationForm.vue'

const props = defineProps({
  location: { type: Object, default: null },
  lockableRoutes: { type: Array, default: () => [] },
  featureEnabled: { type: Boolean, default: false },
  sessionTtlSeconds: { type: Number, default: 900 },
})

const { confirm } = useConfirm()
const { t } = useI18n()

const enabled = ref(props.featureEnabled)
const savingToggle = ref(false)

const ttlMinutes = computed(() => Math.round(props.sessionTtlSeconds / 60))
const lockedCount = computed(() => (props.location?.is_active ? props.location.route_keys?.length ?? 0 : 0))

const form = useForm({
  name: props.location?.name ?? '',
  description: props.location?.description ?? '',
  latitude: props.location?.latitude ?? '',
  longitude: props.location?.longitude ?? '',
  radius_meters: props.location?.radius_meters ?? 150,
  is_active: props.location?.is_active ?? true,
  route_keys: [...(props.location?.route_keys ?? [])],
})

function save() {
  form
    .transform((data) => ({
      ...data,
      latitude: data.latitude === '' ? null : Number(data.latitude),
      longitude: data.longitude === '' ? null : Number(data.longitude),
      radius_meters: Number(data.radius_meters),
    }))
    .put('/dashboard/access-locations', { preserveScroll: true })
}

async function removeLocation() {
  if (!props.location) return

  const ok = await confirm({
    title: t('Remove this location?'),
    message: t('The approved location and its route rules are deleted. Nothing stays locked.'),
    confirmText: t('Remove'),
    danger: true,
  })
  if (!ok) return

  router.delete(`/dashboard/access-locations/${props.location.id}`, { preserveScroll: true })
}

async function onToggle(event) {
  const next = event.target.checked

  if (!next) {
    event.target.checked = true
    const ok = await confirm({
      title: t('Turn off the location lock?'),
      message: t('Every locked route becomes reachable from anywhere again until you turn this back on.'),
      confirmText: t('Turn Off'),
      danger: true,
    })
    if (!ok) return
  }

  savingToggle.value = true
  router.put(
    '/dashboard/access-locations/settings',
    { enabled: next },
    {
      preserveScroll: true,
      onSuccess: () => { enabled.value = next },
      onFinish: () => { savingToggle.value = false },
    },
  )
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Location Lock', current: true },
]
</script>

<template>
  <Head :title="$t('Location Lock')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Access Control"
        :title="$t('Location Lock')"
        :description="$t('Restrict chosen dashboard routes to users physically inside your approved location.')"
      />

      <!-- Master switch -->
      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-wrap items-start justify-between gap-5">
          <div class="flex items-start gap-4">
            <span
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
              :class="enabled
                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'"
            >
              <ShieldCheck class="h-5 w-5" />
            </span>
            <div>
              <div class="flex flex-wrap items-center gap-2.5">
                <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">{{ $t('Location lock') }}</h3>
                <span
                  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold tracking-wide uppercase"
                  :class="enabled
                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                    : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'"
                >
                  <span class="h-1.5 w-1.5 rounded-full" :class="enabled ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                  {{ enabled ? $t('on') : $t('off') }}
                </span>
              </div>
              <p class="mt-1.5 max-w-lg text-sm text-slate-500 dark:text-gray-400">
                {{ $t('When on, a route ticked below opens only while the user\'s device is inside the location. A check stays valid for about :min minutes.', { min: ttlMinutes }) }}
              </p>
              <p v-if="enabled && lockedCount === 0" class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
                {{ $t('No active location locks any route yet, so nothing is restricted.') }}
              </p>
            </div>
          </div>

          <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 py-2 pr-3.5 pl-3 dark:border-gray-800" :class="{ 'opacity-60': savingToggle }">
            <span class="text-sm font-semibold whitespace-nowrap text-slate-600 dark:text-gray-300">
              {{ enabled ? $t('on') : $t('off') }}
            </span>
            <span class="relative inline-flex items-center">
              <input :checked="enabled" :disabled="savingToggle" type="checkbox" class="peer sr-only" @change="onToggle" />
              <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-blue-900 dark:bg-gray-600 dark:peer-checked:bg-blue-600"></span>
              <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
            </span>
          </label>
        </div>
      </div>

      <!-- Single approved location + its locked routes, edited in place -->
      <div>
        <div class="mb-3 flex items-center justify-between gap-3">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-gray-500">{{ $t('Approved Location') }}</p>
            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
              {{ location ? $t('One point, one radius, and the routes it unlocks.') : $t('Set the point and tick the routes to lock. No need to add them one by one.') }}
            </p>
          </div>
          <span
            v-if="location"
            class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase"
            :class="form.recentlySuccessful
              ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
              : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'"
          >
            {{ form.recentlySuccessful ? $t('Saved') : $t('Saved location') }}
          </span>
        </div>

        <LocationForm
          :form="form"
          :lockable-routes="lockableRoutes"
          :submit-label="location ? $t('Save Location') : $t('Create Location')"
          :can-delete="!!location"
          @submit="save"
          @delete="removeLocation"
        />
      </div>
    </section>
  </DashboardLayout>
</template>
