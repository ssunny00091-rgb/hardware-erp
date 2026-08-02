"use client";

import { motion } from "framer-motion";

export default function AnimatedBackground() {
  return (
    <div className="fixed inset-0 -z-10 overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-black">

      {/* Blue Blob */}
      <motion.div
        animate={{
          x: [0, 120, 0],
          y: [0, -80, 0],
          rotate: [0, 25, 0],
        }}
        transition={{
          duration: 16,
          repeat: Infinity,
          ease: "easeInOut",
        }}
        className="absolute -left-32 -top-32 h-[420px] w-[420px] rounded-full bg-blue-500/10 blur-3xl"
      />

      {/* Orange Blob */}
      <motion.div
        animate={{
          x: [0, -100, 0],
          y: [0, 70, 0],
          rotate: [0, -20, 0],
        }}
        transition={{
          duration: 18,
          repeat: Infinity,
          ease: "easeInOut",
        }}
        className="absolute -right-32 top-32 h-[380px] w-[380px] rounded-full bg-orange-500/10 blur-3xl"
      />

      {/* Green Blob */}
      <motion.div
        animate={{
          x: [0, 80, 0],
          y: [0, 120, 0],
          scale: [1, 1.15, 1],
        }}
        transition={{
          duration: 20,
          repeat: Infinity,
          ease: "easeInOut",
        }}
        className="absolute bottom-[-150px] left-[20%] h-[450px] w-[450px] rounded-full bg-emerald-500/10 blur-3xl"
      />

      {/* Purple Blob */}
      <motion.div
        animate={{
          x: [0, -70, 0],
          y: [0, -90, 0],
          rotate: [0, 15, 0],
        }}
        transition={{
          duration: 22,
          repeat: Infinity,
          ease: "easeInOut",
        }}
        className="absolute bottom-[-120px] right-[15%] h-[320px] w-[320px] rounded-full bg-purple-500/10 blur-3xl"
      />
    </div>
  );
}