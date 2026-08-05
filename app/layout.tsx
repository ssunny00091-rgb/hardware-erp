import type { Metadata } from "next";
import "./globals.css";
import AppSidebar from "./components/layout/AppSidebar";

export const metadata: Metadata = {
  title: "Satyanarayan Hardware Stores",
  description: "Billing, purchases and inventory management.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className="h-full antialiased">
      <body className="min-h-full bg-slate-950"><div className="flex min-h-screen"><AppSidebar /><div className="min-w-0 flex-1">{children}</div></div></body>
    </html>
  );
}
