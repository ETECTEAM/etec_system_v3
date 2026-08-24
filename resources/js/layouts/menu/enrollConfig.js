export function build(ctx) {
  if (!ctx.isSuperAdmin && !ctx.isAdmin) return null;

  return {
    label: "Enroll Config",
    labelKey: "navigation.enrollConfig",
    key: "enroll-config",
    icon: "enroll-config",
    href: "/dashboard/enroll/config",
    match: ["/dashboard/enroll/config"],
    exact: false,
    isActive: (path) => path.startsWith("/dashboard/enroll/config"),
  };
}
