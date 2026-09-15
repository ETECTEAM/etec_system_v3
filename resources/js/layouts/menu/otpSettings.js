export const key = "otp_settings";

export function isRoute(path) {
  const p = path.split("?")[0];
  return p.startsWith("/dashboard/otp-settings");
}

export function build(ctx) {
  if (!ctx.isSuperAdmin) return null;

  return {
    label: "OTP Verification",
    labelKey: "navigation.otpVerification",
    key,
    href: "/dashboard/otp-settings",
    match: ["/dashboard/otp-settings"],
    icon: "otp_settings",
    isActive: (path) => path.startsWith("/dashboard/otp-settings"),
  };
}
