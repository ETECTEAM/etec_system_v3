import { useForm } from '@inertiajs/vue3'

export function useSaveForm(initialData = {}) {
  const form = useForm(initialData)

  // Defaults to PUT (the common "save an edit" case); pass { method: 'post' } for creates.
  function save(url, options = {}) {
    const { method = 'put', ...rest } = options

    return form[method](url, { preserveScroll: true, ...rest })
  }

  return { form, save }
}
