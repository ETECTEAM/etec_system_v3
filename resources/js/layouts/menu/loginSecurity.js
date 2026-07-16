export const key = "login_security";

export function isRoute(path) {
  const p = path.split("?")[0];
  return p.startsWith("/dashboard/login-security");
}

export function build(ctx) {
  const canManage = ctx.isSuperAdmin;
  const canUnblock = ctx.isSuperAdmin || ctx.isAdmin;

  if (!canManage && !canUnblock) return null;

  const children = [];

  if (canManage) {
    children.push({
      label: "Lockout Settings",
      href: "/dashboard/login-security",
      match: ["/dashboard/login-security"],
      exact: true,
    });
  }

  if (canUnblock) {
    children.push({
      label: "Blocked Accounts",
      href: "/dashboard/login-security/blocked-accounts",
      match: ["/dashboard/login-security/blocked-accounts"],
      exact: false,
    });
  }

  // A single destination doesn't need a submenu - link straight to it.
  if (children.length === 1) {
    return {
      label: "Login Security",
      key,
      href: children[0].href,
      match: children[0].match,
      icon: "login_security",
      isActive: (path) => path.startsWith(children[0].href),
    };
  }

  return {
    label: "Login Security",
    key,
    match: ["/dashboard/login-security"],
    icon: "login_security",
    children,
  };
}
