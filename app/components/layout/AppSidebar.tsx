"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { AnimatePresence, motion } from "framer-motion";
import { useState } from "react";

const links = [
  { href: "/", label: "Dashboard", icon: "▦" },
  { href: "/billing", label: "Create bill", icon: "+" },
  { href: "/sales", label: "Customer bills", icon: "≡" },
  { href: "/purchase", label: "Purchases", icon: "↙" },
  { href: "/products", label: "Products", icon: "□" },
];

export default function AppSidebar() {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const navigation = <nav className="space-y-2">{links.map((link) => { const active = pathname === link.href; return <Link key={link.href} href={link.href} onClick={() => setOpen(false)} className={`flex items-center gap-3 rounded-xl px-4 py-3 font-medium transition ${active ? "bg-white text-slate-950 shadow-lg" : "text-slate-300 hover:bg-white/10 hover:text-white"}`}><span className="grid h-7 w-7 place-items-center rounded-lg bg-slate-900/15 text-lg">{link.icon}</span>{link.label}</Link>; })}</nav>;
  const brand = <div className="px-3"><p className="text-xs font-bold uppercase tracking-[0.2em] text-sky-200">Welcome to</p><p className="mt-2 text-2xl font-black leading-none tracking-tight text-white">SATYANARAYAN</p><p className="text-lg font-bold tracking-[0.18em] text-sky-200">HARDWARE</p></div>;

  return <>
    <button type="button" aria-label="Open navigation menu" onClick={() => setOpen(true)} className="fixed left-4 top-4 z-40 rounded-xl bg-slate-900/90 px-4 py-3 text-white shadow-xl backdrop-blur lg:hidden">Menu</button>
    <aside className="sticky top-0 hidden h-screen w-72 shrink-0 border-r border-white/10 bg-slate-950/95 p-5 shadow-2xl backdrop-blur-xl lg:block"><div className="mb-10 pt-5">{brand}</div>{navigation}<div className="absolute bottom-6 left-5 right-5 rounded-xl border border-sky-300/15 bg-sky-400/10 p-4 text-xs text-sky-100">Fast billing. Clear stock.</div></aside>
    <AnimatePresence>{open && <><motion.button aria-label="Close navigation menu" className="fixed inset-0 z-40 bg-black/60 lg:hidden" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => setOpen(false)} /><motion.aside initial={{ x: -300 }} animate={{ x: 0 }} exit={{ x: -300 }} transition={{ type: "spring", stiffness: 280, damping: 28 }} className="fixed inset-y-0 left-0 z-50 w-72 border-r border-white/10 bg-slate-950 p-5 shadow-2xl lg:hidden"><button type="button" onClick={() => setOpen(false)} className="mb-7 rounded-lg px-3 py-2 text-slate-300 hover:bg-white/10">Close ×</button><div className="mb-10">{brand}</div>{navigation}</motion.aside></>}</AnimatePresence>
  </>;
}
