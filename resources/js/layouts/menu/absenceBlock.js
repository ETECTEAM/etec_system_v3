export const key = "absenceBlock";

export function isRoute(path) {
  return path.split("?")[0].startsWith("/dashboard/absence-blocks");
}

export function build(ctx) {
  // Whole feature is admin + super_admin. The only super_admin-exclusive action
  // (hard-lock unlock) lives inside the Blocklist page, not the menu.
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  const children = [
    {
      label: "Blocklist",
      labelKey: "navigation.absenceBlocklist",
      href: "/dashboard/absence-blocks",
      match: ["/dashboard/absence-blocks"],
      exact: true,
      isActive: (path) => path === "/dashboard/absence-blocks",
    },
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

  return {
    label: "Absence Blocks",
    labelKey: "navigation.absenceBlocks",
    key,
    match: ["/dashboard/absence-blocks"],
    icon: "absence_block",
    children,
  };
}
