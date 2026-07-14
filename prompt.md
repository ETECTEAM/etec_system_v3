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
```

# Prompt For Change code multiple-line to one-line style

```
In <file>, first collapse any multi-line plain HTML element attributes/tags back to a single line each (input, button, Link, p, span, td, th, etc.) — no line breaks inside those tags, matching the style already used elsewhere in this file/project. Custom Vue components (PascalCase, e.g. SelectSearch, Pagination, PageHero) are exempt and may stay multi-line when they have several props. Then add short explanatory comments: in the <script setup> block, comment any non-obvious logic (computed properties, watchers, form defaults, submit behavior); in the <template>, add one short comment above each form field/section explaining anything non-obvious (disabled/locked state, validation, why a value is blank, etc.). Keep comments to one line each — no multi-line comment blocks, and don't comment things that are already obvious from the code.
```

# Prompt For Laravel Route File Comments (style used in routes/web/backend/user.php)

```
TASK: Add short, one-line descriptive comments above route definitions and route groups in <routes file>, matching the existing style in routes/web/backend/user.php.

REFERENCE STYLE FILE: routes/web/backend/user.php

STYLE TO MATCH:
- Each individual route gets exactly ONE line directly above it, starting with "// " and phrased as "Route to <verb> <what>." (e.g. "// Route to display the list of users in the dashboard.", "// Route to assign permissions to a specific role.") — state what the route does, not how it's implemented.
- When routes are grouped by middleware/purpose (e.g. read vs. write, throttle tiers), add ONE short comment above the group's Route::middleware(...)->group(...) line explaining the reasoning for that grouping (e.g. "// Reads: generous limit, just enough to stop scripted scraping/polling abuse.", "// Mutations: tighter limit - these create/change/delete accounts, roles, and permissions."). This is a WHY comment, not a repeat of the middleware name.
- Keep every comment to a single line — no multi-line comment blocks, no docblocks per route.
- Don't comment routes whose purpose is already fully obvious from the URI + controller method name alone — only add a comment where a fresh reader would otherwise need to open the controller to know what the route is for.
- Preserve the file's existing top-of-file docblock (the /* ... */ block explaining what the whole route file covers) — don't remove or rewrite it unless asked.

RULES:
- Do not change any route definitions, middleware, URIs, or controller references — comments only.
- Match capitalization/punctuation exactly: comment starts with a capital letter, ends with a period.
- If a route already has a comment, only rewrite it if it's inaccurate or missing information; don't reformat comments that already fit the style.

OUTPUT:
- The full updated routes file content (or a diff) with the new comments added.
- A one-line note of how many routes/groups received new comments.
```