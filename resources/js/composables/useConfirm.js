import { reactive } from 'vue'

// Module-scoped state (outside the composable function) so every component
// shares one confirm dialog instead of each needing to mount its own modal.
const state = reactive({
  visible: false,
  title: '',
  message: '',
  confirmText: 'Confirm',
  cancelText: 'Cancel',
  danger: false,
})

let resolvePromise = null

/**
 * Opens the shared confirm dialog and resolves once the user answers.
 * @param {{ title?: string, message?: string, confirmText?: string, cancelText?: string, danger?: boolean }} options
 * @returns {Promise<boolean>} true if confirmed, false if cancelled/dismissed
 */
function confirm(options = {}) {
  // A previous confirm() left dangling (e.g. component unmounted before the
  // user answered) shouldn't hang forever — resolve it as cancelled.
  resolvePromise?.(false)

  state.title = options.title ?? 'Are you sure?'
  state.message = options.message ?? ''
  state.confirmText = options.confirmText ?? 'Confirm'
  state.cancelText = options.cancelText ?? 'Cancel'
  state.danger = options.danger ?? false
  state.visible = true

  return new Promise((resolve) => {
    resolvePromise = resolve
  })
}

function handleConfirm() {
  state.visible = false
  resolvePromise?.(true)
  resolvePromise = null
}

function handleCancel() {
  state.visible = false
  resolvePromise?.(false)
  resolvePromise = null
}

// Call from anywhere to trigger the dialog: const { confirm } = useConfirm()
export function useConfirm() {
  return { confirm }
}

// Used internally by <ConfirmDialog /> to render the shared state.
export function useConfirmDialogState() {
  return { state, handleConfirm, handleCancel }
}
