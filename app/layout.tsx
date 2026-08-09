import type { Metadata } from "next";
import "./globals.css";
import AppSidebar from "./components/layout/AppSidebar";
import { Geist } from "next/font/google";
import { cn } from "@/lib/utils";

const geist = Geist({subsets:['latin'],variable:'--font-sans'});

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
    <html lang="en" className={cn("h-full antialiased", "font-sans", geist.variable)}>
      <body className="min-h-full bg-slate-950"><div className="flex min-h-screen"><AppSidebar /><div className="min-w-0 flex-1">{children}</div></div></body>
    </html>
  );
}
