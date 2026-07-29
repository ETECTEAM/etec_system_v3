export function build(ctx) {
  if (!ctx.canAccessNotifications) return null;

  return {
    label: "Notifications",
    labelKey: "navigation.notifications",
    href: "/dashboard/notifications",
    match: ["/dashboard/notifications"],
    exact: false,
    icon: "notification",
    isActive: (path) => path.startsWith("/dashboard/notifications"),
  };
}
