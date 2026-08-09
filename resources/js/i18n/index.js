import { computed, inject, reactive } from 'vue'
import en from '../locales/en.json'
import km from '../locales/km.json'
import { backendTranslations } from './backendText'
import { backendExtraTranslations } from './backendExtraText'
import { backendFinalTranslations } from './backendFinalText'
import { backendPlaceholderTranslations } from './backendPlaceholderText'
import { literalTranslations } from './commonText'

const I18N_KEY = Symbol('i18n')

const messages = { en, km }
const supportedLocales = [
  { code: 'en', labelKey: 'locale.en' },
  { code: 'km', labelKey: 'locale.km' },
]

function resolveMessage(locale, key) {
  if (typeof key !== 'string') return undefined

  return key.split('.').reduce((value, part) => value?.[part], messages[locale])
}

function applyDocumentLocale(locale) {
  if (typeof document === 'undefined') return

  document.documentElement.lang = locale
  document.documentElement.dataset.locale = locale
}

export function createI18n(initialLocale = 'en') {
  const state = reactive({
    locale: messages[initialLocale] ? initialLocale : 'en',
  })

  applyDocumentLocale(state.locale)

  function setLocale(locale) {
    if (messages[locale]) {
      state.locale = locale
      applyDocumentLocale(locale)
    }
  }

  function t(key, replacements = {}) {
    if (key === null || key === undefined) return ''

    const template = resolveMessage(state.locale, key)
      ?? resolveMessage('en', key)
      ?? literalTranslations[state.locale]?.[key]
      ?? backendTranslations[state.locale]?.[key]
      ?? backendExtraTranslations[state.locale]?.[key]
      ?? backendFinalTranslations[state.locale]?.[key]
      ?? backendPlaceholderTranslations[state.locale]?.[key]
      ?? key

    return Object.entries(replacements)
      .sort(([a], [b]) => b.length - a.length)
      .reduce(
        (message, [name, value]) => message.replaceAll(`:${name}`, value),
        template,
      )
  }

  return {
    install(app) {
      const api = {
        locale: computed(() => state.locale),
        supportedLocales,
        setLocale,
        t,
      }

      app.provide(I18N_KEY, api)
      app.config.globalProperties.$t = t
    },
  }
}

export function useI18n() {
  const i18n = inject(I18N_KEY)

  if (!i18n) {
    throw new Error('i18n plugin is not installed')
  }

  return i18n
}
