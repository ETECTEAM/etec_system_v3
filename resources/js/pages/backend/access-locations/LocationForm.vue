<script setup>
import { computed, ref } from 'vue'
import { Crosshair, LoaderCircle, MapPin, Save, Trash2, Upload } from '@lucide/vue'

const props = defineProps({
  // An Inertia useForm() instance owned by the parent page.
  form: { type: Object, required: true },
  lockableRoutes: { type: Array, default: () => [] },
  submitLabel: { type: String, default: 'Save' },
  canDelete: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'delete'])

const locating = ref(false)
const locateError = ref('')
const locateAccuracy = ref(null)

const latNum = computed(() => Number(props.form.latitude))
const lngNum = computed(() => Number(props.form.longitude))

const hasCoords = computed(
  () => props.form.latitude !== '' && props.form.longitude !== '' && !Number.isNaN(latNum.value) && !Number.isNaN(lngNum.value),
)

// A small bounding box around the marker for the OpenStreetMap embed - widened a
// little as the radius grows so the whole circle stays in frame.
const mapSrc = computed(() => {
  if (!hasCoords.value) return ''

  const radius = Number(props.form.radius_meters) || 150
  const d = Math.max(0.0025, (radius / 111320) * 3)
  const minLng = (lngNum.value - d).toFixed(6)
  const minLat = (latNum.value - d).toFixed(6)
  const maxLng = (lngNum.value + d).toFixed(6)
  const maxLat = (latNum.value + d).toFixed(6)

  return `https://www.openstreetmap.org/export/embed.html?bbox=${minLng}%2C${minLat}%2C${maxLng}%2C${maxLat}&layer=mapnik&marker=${latNum.value}%2C${lngNum.value}`
})

const gmapsLink = computed(() =>
  hasCoords.value ? `https://www.google.com/maps/search/?api=1&query=${latNum.value},${lngNum.value}` : null,
)

function useCurrentLocation() {
  if (!('geolocation' in navigator)) {
    locateError.value = 'This browser has no location support.'
    return
  }

  locating.value = true
  locateError.value = ''

  navigator.geolocation.getCurrentPosition(
    ({ coords }) => {
      props.form.latitude = Number(coords.latitude.toFixed(7))
      props.form.longitude = Number(coords.longitude.toFixed(7))
      locateAccuracy.value = Math.round(coords.accuracy)
      locating.value = false
    },
    (err) => {
      locateError.value =
        err.code === err.PERMISSION_DENIED
          ? 'Location permission denied. Allow it in the browser, or type the coordinates below.'
          : 'Could not read your location. Try again or enter the coordinates manually.'
      locating.value = false
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
  )
}

// ── Import from a GeoJSON file (e.g. an area drawn in geojson.io) ───────────
// This model stores a circle (point + radius), so an uploaded shape is reduced
// to the smallest circle that encloses it: the centre, and the farthest vertex
// as the radius. A geojson.io "circle" round-trips back to almost the same
// centre + radius it was drawn with.
const geojsonInput = ref(null)
const importInfo = ref('')
const importError = ref('')

function metersBetween(lat1, lon1, lat2, lon2) {
  const R = 6371000
  const dLat = ((lat2 - lat1) * Math.PI) / 180
  const dLon = ((lon2 - lon1) * Math.PI) / 180
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.sin(dLon / 2) ** 2
  return 2 * R * Math.asin(Math.min(1, Math.sqrt(a)))
}

function featuresFrom(data) {
  if (!data || typeof data !== 'object') return []
  if (data.type === 'FeatureCollection') return data.features ?? []
  if (data.type === 'Feature') return [data]
  if (data.type && data.coordinates) return [{ type: 'Feature', properties: {}, geometry: data }]
  return []
}

function outerRing(geometry) {
  if (geometry?.type === 'Polygon') return geometry.coordinates?.[0] ?? null
  if (geometry?.type === 'MultiPolygon') return geometry.coordinates?.[0]?.[0] ?? null
  return null
}

function averagePoint(ring) {
  // GeoJSON rings repeat the first point at the end - drop it before averaging.
  const closed =
    ring.length > 1 && ring[0][0] === ring[ring.length - 1][0] && ring[0][1] === ring[ring.length - 1][1]
  const pts = closed ? ring.slice(0, -1) : ring
  const [sx, sy] = pts.reduce(([x, y], [lng, lat]) => [x + lng, y + lat], [0, 0])
  return [sx / pts.length, sy / pts.length]
}

function applyImport(lat, lng, radius, fileName) {
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    importError.value = 'Could not read coordinates from that file.'
    return
  }
  props.form.latitude = Number(lat.toFixed(7))
  props.form.longitude = Number(lng.toFixed(7))
  if (Number.isFinite(radius) && radius > 0) {
    props.form.radius_meters = Math.min(20000, Math.max(10, Math.round(radius)))
  }
  importInfo.value = Number.isFinite(radius) && radius > 0
    ? `Imported centre + ${Math.round(radius)} m radius from ${fileName}.`
    : `Imported point from ${fileName} - set the radius below.`
}

function importGeojson(text, fileName) {
  importInfo.value = ''
  importError.value = ''

  let data
  try {
    data = JSON.parse(text)
  } catch {
    importError.value = 'That file is not valid JSON.'
    return
  }

  for (const feature of featuresFrom(data)) {
    const geometry = feature.geometry ?? feature
    const circleCentre = feature.properties?.['@circle']?.center

    if (geometry?.type === 'Point' && Array.isArray(geometry.coordinates)) {
      const [lng, lat] = geometry.coordinates
      applyImport(lat, lng, null, fileName)
      return
    }

    const ring = outerRing(geometry)
    if (ring?.length) {
      const [cLng, cLat] = Array.isArray(circleCentre) ? circleCentre : averagePoint(ring)
      const radius = Math.max(...ring.map(([lng, lat]) => metersBetween(cLat, cLng, lat, lng)))
      applyImport(cLat, cLng, radius, fileName)
      return
    }
  }

  importError.value = 'No point or area found in that GeoJSON.'
}

function onGeojsonFile(event) {
  const file = event.target.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = () => importGeojson(String(reader.result), file.name)
  reader.onerror = () => { importError.value = 'Could not read that file.' }
  reader.readAsText(file)

  event.target.value = '' // allow re-picking the same file
}

function toggleRoute(key) {
  const next = new Set(props.form.route_keys ?? [])
  next.has(key) ? next.delete(key) : next.add(key)
  props.form.route_keys = [...next]
}

const allRoutesSelected = computed(
  () => props.lockableRoutes.length > 0 && props.lockableRoutes.every((o) => (props.form.route_keys ?? []).includes(o.key)),
)

function toggleAllRoutes() {
  props.form.route_keys = allRoutesSelected.value ? [] : props.lockableRoutes.map((o) => o.key)
}

const inputClass =
  'w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20'
</script>

<template>
  <form class="space-y-6" @submit.prevent="emit('submit')">
    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Left: fields -->
      <div class="space-y-5 lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <h3 class="mb-4 text-sm font-bold text-slate-900 dark:text-gray-100">{{ $t('Location') }}</h3>

          <div class="space-y-4">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">
                {{ $t('Name') }} <span class="text-red-500">*</span>
              </label>
              <input v-model="form.name" type="text" :class="inputClass" :placeholder="$t('e.g. Main Office')" required />
              <p v-if="form.errors.name" class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Description') }}</label>
              <textarea v-model="form.description" rows="2" :class="inputClass" :placeholder="$t('Optional note')" />
              <p v-if="form.errors.description" class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ form.errors.description }}</p>
            </div>

            <div>
              <div class="flex flex-wrap gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-800 transition hover:bg-blue-100 disabled:opacity-60 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300"
                  :disabled="locating"
                  @click="useCurrentLocation"
                >
                  <component :is="locating ? LoaderCircle : Crosshair" class="h-4 w-4" :class="{ 'animate-spin': locating }" />
                  {{ locating ? $t('Reading location...') : $t('Use my current location') }}
                </button>

                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                  @click="geojsonInput?.click()"
                >
                  <Upload class="h-4 w-4" />
                  {{ $t('Import GeoJSON') }}
                </button>
                <input
                  ref="geojsonInput"
                  type="file"
                  accept=".geojson,.json,application/geo+json,application/json"
                  class="hidden"
                  @change="onGeojsonFile"
                />
              </div>
              <p v-if="locateAccuracy !== null && !locateError" class="mt-1 text-xs text-slate-400 dark:text-gray-500">
                {{ $t('Accuracy') }} ~{{ locateAccuracy }}m
              </p>
              <p v-if="locateError" class="mt-1 text-xs font-semibold text-amber-600 dark:text-amber-400">{{ locateError }}</p>
              <p v-if="importInfo" class="mt-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ importInfo }}</p>
              <p v-if="importError" class="mt-1 text-xs font-semibold text-amber-600 dark:text-amber-400">{{ importError }}</p>
              <p class="mt-1.5 text-xs text-slate-400 dark:text-gray-500">
                {{ $t('Draw an area in geojson.io, export as GeoJSON, and import it here - any shape becomes the smallest circle that covers it.') }}
              </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">
                  {{ $t('Latitude') }} <span class="text-red-500">*</span>
                </label>
                <input v-model="form.latitude" type="number" step="any" :class="inputClass" placeholder="11.5564" required />
                <p v-if="form.errors.latitude" class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ form.errors.latitude }}</p>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">
                  {{ $t('Longitude') }} <span class="text-red-500">*</span>
                </label>
                <input v-model="form.longitude" type="number" step="any" :class="inputClass" placeholder="104.9282" required />
                <p v-if="form.errors.longitude" class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ form.errors.longitude }}</p>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-300">
                  {{ $t('Radius (m)') }} <span class="text-red-500">*</span>
                </label>
                <input v-model.number="form.radius_meters" type="number" min="10" max="20000" :class="inputClass" required />
                <p v-if="form.errors.radius_meters" class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ form.errors.radius_meters }}</p>
              </div>
            </div>

            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40">
              <span>
                <span class="block text-sm font-semibold text-slate-800 dark:text-gray-100">{{ $t('Active') }}</span>
                <span class="block text-xs text-slate-500 dark:text-gray-400">{{ $t('Inactive locations are ignored by the location lock.') }}</span>
              </span>
              <span class="relative inline-flex items-center">
                <input v-model="form.is_active" type="checkbox" class="peer sr-only" />
                <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-blue-900 dark:bg-gray-600 dark:peer-checked:bg-blue-600"></span>
                <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
              </span>
            </label>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-gray-100">{{ $t('Routes unlocked here') }}</h3>
              <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                {{ $t('Ticked areas are only reachable while a user is physically inside this location. Untouched areas are never affected.') }}
              </p>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
              @click="toggleAllRoutes"
            >
              {{ allRoutesSelected ? $t('Clear all') : $t('Select all') }}
            </button>
          </div>

          <div class="mt-4 space-y-2.5">
            <label
              v-for="option in lockableRoutes"
              :key="option.key"
              class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 px-4 py-3 transition hover:border-slate-300 dark:border-gray-800 dark:hover:border-gray-700"
              :class="{ 'border-blue-300 bg-blue-50/50 dark:border-blue-500/40 dark:bg-blue-500/10': (form.route_keys ?? []).includes(option.key) }"
            >
              <input
                type="checkbox"
                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-900 focus:ring-blue-200 dark:border-gray-600"
                :checked="(form.route_keys ?? []).includes(option.key)"
                @change="toggleRoute(option.key)"
              />
              <span>
                <span class="block text-sm font-semibold text-slate-800 dark:text-gray-100">{{ option.label }}</span>
                <span class="block text-xs text-slate-500 dark:text-gray-400">{{ option.description }}</span>
              </span>
            </label>
          </div>
          <p v-if="form.errors.route_keys" class="mt-2 text-xs font-semibold text-red-600 dark:text-red-400">{{ form.errors.route_keys }}</p>
        </div>
      </div>

      <!-- Right: map preview -->
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100">
          <MapPin class="h-4 w-4 text-blue-900 dark:text-blue-400" />
          {{ $t('Preview') }}
        </div>
        <div v-if="hasCoords" class="overflow-hidden rounded-xl border border-slate-200 dark:border-gray-800">
          <iframe
            :src="mapSrc"
            class="h-64 w-full"
            style="border: 0"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Location preview"
          />
        </div>
        <div v-else class="flex h-64 items-center justify-center rounded-xl border border-dashed border-slate-300 text-center text-xs text-slate-400 dark:border-gray-700 dark:text-gray-500">
          {{ $t('Enter coordinates to see the map') }}
        </div>
        <a
          v-if="gmapsLink"
          :href="gmapsLink"
          target="_blank"
          rel="noopener"
          class="mt-3 block text-center text-xs font-semibold text-blue-700 hover:underline dark:text-blue-400"
        >
          {{ $t('Open in Google Maps') }}
        </a>
      </div>
    </div>

    <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-6 dark:border-gray-800">
      <button
        v-if="canDelete"
        type="button"
        class="inline-flex items-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10"
        @click="emit('delete')"
      >
        <Trash2 class="h-4 w-4" />
        {{ $t('Remove location') }}
      </button>
      <span v-else></span>

      <button
        type="submit"
        :disabled="form.processing"
        class="flex items-center gap-2 rounded-xl bg-blue-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
      >
        <Save class="h-4 w-4" />
        {{ form.processing ? $t('Saving...') : submitLabel }}
      </button>
    </div>
  </form>
</template>
