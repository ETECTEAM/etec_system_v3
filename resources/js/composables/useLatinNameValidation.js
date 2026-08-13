import { computed } from "vue";

const latinNamePattern = /^[A-Za-z][A-Za-z .'-]*$/;
const latinNameMessage = "Name must use English letters only.";

export function latinNameError(value) {
  const name = String(value ?? "");

  if (!name) {
    return "";
  }

  return latinNamePattern.test(name) ? "" : latinNameMessage;
}

export function useLatinNameValidation(nameRef) {
  return computed(() => latinNameError(nameRef.value));
}
