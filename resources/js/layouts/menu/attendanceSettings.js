export const key = "attendance_settings";

export function isRoute(path) {
  return path.split("?")[0].startsWith("/dashboard/attendance-settings");
}

export function build(ctx) {
  if (!ctx.isSuperAdmin) return null;

  return {
    label: "Attendance Settings",
    labelKey: "navigation.attendanceSettings",
    key,
    href: "/dashboard/attendance-settings",
    match: ["/dashboard/attendance-settings"],
    icon: "attendance_settings",
    isActive: (path) => path.startsWith("/dashboard/attendance-settings"),
  };
}
