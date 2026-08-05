"use client";

import { motion } from "framer-motion";
import type { ReactNode } from "react";

type Props = { children: ReactNode; className?: string };

export default function MotionPage({ children, className = "" }: Props) {
  return (
    <main className={`relative min-h-screen overflow-hidden bg-slate-950 text-white ${className}`}>
      <motion.div aria-hidden className="pointer-events-none absolute -left-32 -top-36 h-96 w-96 rounded-full bg-sky-500/20 blur-3xl" animate={{ x: [0, 55, 0], y: [0, 35, 0], scale: [1, 1.12, 1] }} transition={{ duration: 13, repeat: Infinity, ease: "easeInOut" }} />
      <motion.div aria-hidden className="pointer-events-none absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-violet-500/20 blur-3xl" animate={{ x: [0, -45, 0], y: [0, -35, 0], scale: [1, 0.9, 1] }} transition={{ duration: 16, repeat: Infinity, ease: "easeInOut" }} />
      <motion.div initial={{ opacity: 0, y: 18 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.45, ease: "easeOut" }} className="relative z-10">{children}</motion.div>
    </main>
  );
}
