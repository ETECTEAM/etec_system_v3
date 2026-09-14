import { reactive } from "vue";

// App-wide toast stack. Single module-scoped source of truth, rendered by the
// one <ToastHost /> mounted in app.js. Drop-in for the old vue-toastification
// useToast(): same success/error/warning/info(message, { timeout }) shape.
const MAX_VISIBLE = 4;

const toasts = reactive([]);
const timers = new Map();

let seq = 0;

function remove(id) {
  if (timers.has(id)) {
    clearTimeout(timers.get(id));
    timers.delete(id);
  }

  const index = toasts.findIndex((toast) => toast.id === id);
  if (index !== -1) {
    toasts.splice(index, 1);
  }
}

function schedule(toast, wait) {
  if (wait <= 0) {
    return;
  }

  toast.expiresAt = Date.now() + wait;
  timers.set(toast.id, setTimeout(() => remove(toast.id), wait));
}

// Freezes a toast's auto-dismiss timer (on hover) and remembers what's left.
function pause(id) {
  if (!timers.has(id)) {
    return;
  }

  clearTimeout(timers.get(id));
  timers.delete(id);

  const toast = toasts.find((item) => item.id === id);
  if (toast) {
    toast.remaining = Math.max(0, toast.expiresAt - Date.now());
  }
}

function resume(id) {
  const toast = toasts.find((item) => item.id === id);
  if (!toast || timers.has(id)) {
    return;
  }

  schedule(toast, toast.remaining ?? toast.timeout);
}

function normalizeTimeout(value, type) {
  if (value === false || value === 0) {
    return 0;
  }

  return Number.isFinite(value) ? value : (type === "error" || type === "warning" ? 6000 : 4000);
}

function add(type, message, options = {}) {
  const text = typeof message === "string" ? message : String(message ?? "");
  if (text.trim() === "") {
    return null;
  }

  // Re-fire even for an identical message (e.g. saving the same form twice):
  // drop the current copy so the new one visibly re-enters instead of the
  // action looking like it did nothing.
  const duplicate = toasts.find((toast) => toast.type === type && toast.message === text);
  if (duplicate) {
    remove(duplicate.id);
  }

  const toast = {
    id: ++seq,
    type,
    message: text,
    timeout: normalizeTimeout(options.timeout, type),
    remaining: null,
    expiresAt: 0,
  };

  toasts.push(toast);
  schedule(toast, toast.timeout);

  // Cap the stack - oldest falls off the top.
  while (toasts.length > MAX_VISIBLE) {
    remove(toasts[0].id);
  }

  return toast.id;
}

export function useToast() {
  return {
    success: (message, options) => add("success", message, options),
    error: (message, options) => add("error", message, options),
    warning: (message, options) => add("warning", message, options),
    info: (message, options) => add("info", message, options),
    clear: () => {
      timers.forEach((timer) => clearTimeout(timer));
      timers.clear();
      toasts.splice(0);
    },
  };
}

// Consumed only by <ToastHost />.
export function useToastStore() {
  return { toasts, remove, pause, resume };
}
