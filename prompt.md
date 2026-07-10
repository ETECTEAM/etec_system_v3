# prompt for clean code frontend code

```
TASK: Refactor the save logic of a Vue component into reusable composables.

CONTEXT:
- Framework: Vue 3 + Inertia.js (Laravel backend)
- Feature folder: resources/js/pages/backend/[FEATURE_FOLDER]/
- Component to refactor: [COMPONENT_NAME].vue
- Save endpoint: [HTTP_METHOD] [ENDPOINT_URL]
- Initial form data shape: [FORM_DATA_SHAPE]   (e.g. { permissions: [] })

IF YOU HAVE FILE/PROJECT ACCESS (e.g. terminal, IDE, repo tools):
- Locate and read the component file yourself at the feature folder path above.
- Check if resources/js/composables/useSaveForm.js already exists before creating one.
- Apply all changes directly to the files.

IF YOU DO NOT HAVE FILE ACCESS (chat-only):
- Ask me to paste the component's <script setup> content before proceeding.
- Output full new file contents for me to copy manually, instead of editing in place.

GOAL STRUCTURE:

1. GLOBAL composable — resources/js/composables/useSaveForm.js
   Reuse it if it already exists; otherwise create it.
   It wraps Inertia's useForm and exposes a generic `save(url, options)` 
   method calling form.put() (or the relevant HTTP method) merged with 
   `preserveScroll: true`. It must contain NOTHING specific to this 
   feature — it should work for any page in the project, now or in future.

   Example shape:
   js
   import { useForm } from '@inertiajs/vue3'

   export function useSaveForm(initialData = {}) {
     const form = useForm(initialData)
     function save(url, options = {}) {
       return form.put(url, { preserveScroll: true, ...options })
     }
     return { form, save }
   }
   

2. FEATURE-LOCAL composable — resources/js/pages/backend/[FEATURE_FOLDER]/composables/use[FEATURE_NAME].js
   Move logic here that ONLY this feature needs — derived/computed data, 
   toggle/select functions, or formatting rules tied to this feature's 
   specific data shape. This file may import and use useSaveForm() 
   internally.

3. UPDATE [COMPONENT_NAME].vue
   - Import both composables.
   - Remove the logic that was moved out.
   - Keep ONLY UI-only state (search inputs, pagination, local selection, 
     debounced refs) and template bindings inside the component.

RULES:
- Structural refactor only — do not change behavior, UI, or the save 
  request logic.
- Reuse the global composable if one already fits; don't duplicate it.
- Don't put feature-specific logic inside the global composables folder.
- If something is ambiguous (could be global or feature-local), ask me 
  instead of guessing.

OUTPUT:
- Full contents of any new/updated composable files
- Updated <script setup> of [COMPONENT_NAME].vue
- A short note: was useSaveForm.js reused or newly created?

