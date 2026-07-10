import { useForm } from '@inertiajs/vue3'

export function useSaveForm(initialData = {}) {
  const form = useForm(initialData)

  function save(url, options = {}) {
    return form.put(url, { preserveScroll: true, ...options })
  }

  return { form, save }
}
