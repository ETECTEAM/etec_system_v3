export function build() {
  return {
    label: "Account Security",
    href: "/dashboard/account-security",
    match: ["/dashboard/account-security"],
    exact: false,
    icon: "account_security",
    isActive: (path) => path.startsWith("/dashboard/account-security"),
  };
}
