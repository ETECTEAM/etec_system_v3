export function build(ctx) {
  // Same audience as the rest of the absence-block feature: admin + super_admin.
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Blocklist",
    labelKey: "navigation.absenceBlocklist",
    href: "/dashboard/absence-blocks",
    match: ["/dashboard/absence-blocks"],
    exact: true,
    icon: "absence_block",
    isActive: (path) => path === "/dashboard/absence-blocks",
  };
}
