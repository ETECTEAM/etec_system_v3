export function build(ctx) {
  if (!ctx.canAccessNotifications) return null;

  return {
    label: "Notifications",
    href: "/dashboard/notifications",
    match: ["/dashboard/notifications"],
    exact: false,
    icon: "notification",
    isActive: (path) => path.startsWith("/dashboard/notifications"),
  };
}
