// Turns "2026-09-20 13:20[:05]" or "13:20" into "2026-09-20 1:20 pm" / "1:20 pm"; anything else passes through.
export function formatTime12(value) {
  if (!value) {
    return value;
  }

  const match = String(value).match(/^(?:(\d{4}-\d{2}-\d{2})[ T])?(\d{1,2}):(\d{2})(?::\d{2})?$/);

  if (!match) {
    return value;
  }

  const [, date, hours, minutes] = match;
  const hour = Number(hours);
  const time = `${hour % 12 || 12}:${minutes} ${hour >= 12 ? "pm" : "am"}`;

  return date ? `${date} ${time}` : time;
}
