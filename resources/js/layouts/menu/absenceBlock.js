export const key = "absenceBlock";

// The Blocklist page itself (exactly /dashboard/absence-blocks) lives under Daily Work
// (see absenceBlocklist.js), so it must not open or highlight this group.
function isGroupPath(path) {
  const pathOnly = path.split("?")[0].replace(/\/+$/, "");

  return pathOnly.startsWith("/dashboard/absence-blocks/");
}

export function isRoute(path) {
  return isGroupPath(path);
}

export function build(ctx) {
  // Whole feature is admin + super_admin. The only super_admin-exclusive action
  // (hard-lock unlock) lives inside the Blocklist page, not the menu.
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  const children = [
    {
      label: "Attendance Rules",
      labelKey: "navigation.attendanceRules",
      href: "/dashboard/absence-blocks/rules",
      match: ["/dashboard/absence-blocks/rules"],
      isActive: (path) => path.startsWith("/dashboard/absence-blocks/rules"),
    },
    {
      label: "Rule Settings",
      labelKey: "navigation.attendanceRuleSettings",
      href: "/dashboard/absence-blocks/settings",
      match: ["/dashboard/absence-blocks/settings"],
      isActive: (path) => path.startsWith("/dashboard/absence-blocks/settings"),
    },
    {
      label: "Audit Log",
      labelKey: "navigation.absenceBlockAudit",
      href: "/dashboard/absence-blocks/audit",
      match: ["/dashboard/absence-blocks/audit"],
      isActive: (path) => path.startsWith("/dashboard/absence-blocks/audit"),
    },
  ];

  // Auto-record config sits with the other attendance rules; only super_admin can open it.
  if (ctx.isSuperAdmin) {
    children.push({
      label: "Auto-Record Settings",
      labelKey: "navigation.attendanceSettings",
      href: "/dashboard/attendance-settings",
      match: ["/dashboard/attendance-settings"],
      isActive: (path) => path.startsWith("/dashboard/attendance-settings"),
    });
  }

  return {
    label: "Absence Blocks",
    labelKey: "navigation.absenceBlocks",
    key,
    match: ["/dashboard/absence-blocks"],
    isActive: isGroupPath,
    icon: "absence_block",
    section: "system",
    children,
  };
}
