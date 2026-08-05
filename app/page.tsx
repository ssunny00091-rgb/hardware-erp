"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { supabase } from "./lib/supabase";
import MotionPage from "./components/layout/MotionPage";
import { motion } from "framer-motion";

type Totals = { sales: number; purchases: number };
const currency = new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR", maximumFractionDigits: 0 });

export default function Home() {
  const [totals, setTotals] = useState<Totals>({ sales: 0, purchases: 0 });
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;
    const loadDashboard = async () => {
      const today = new Date().toISOString().slice(0, 10);
      const [sales, purchases] = await Promise.all([
        supabase.from("sales").select("total").eq("bill_date", today),
        supabase.from("purchases").select("products").eq("purchase_date", today),
      ]);
      if (!active) return;
      if (sales.error || purchases.error) { setError(sales.error?.message ?? purchases.error?.message ?? "Could not load dashboard."); return; }
      const purchaseTotal = (purchases.data ?? []).reduce((sum, purchase) => sum + ((purchase.products as { qty: string; price: string }[]).reduce((itemTotal, item) => itemTotal + Number(item.qty) * Number(item.price), 0)), 0);
      setTotals({ sales: (sales.data ?? []).reduce((sum, sale) => sum + Number(sale.total), 0), purchases: purchaseTotal });
    };
    void loadDashboard();
    return () => { active = false; };
  }, []);

  const actions = [["/billing", "Create sale", "emerald"], ["/sales", "Customer bills", "amber"], ["/purchase", "Record purchase", "sky"], ["/products", "Manage products", "violet"]] as const;
  return <MotionPage><div className="mx-auto max-w-6xl p-5 pt-20 sm:p-10"><header className="mb-10"><motion.p initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ delay: 0.1 }} className="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Business overview</motion.p><h1 className="mt-2 bg-gradient-to-r from-white via-sky-100 to-violet-200 bg-clip-text text-3xl font-bold text-transparent sm:text-5xl">SATYANARAYAN HARDWARE</h1><p className="mt-2 text-xl font-semibold text-sky-200 sm:text-2xl">Dashboard</p><p className="mt-3 max-w-xl text-slate-300">Billing, purchase entry and product stock—all in one place.</p></header>{error && <p role="status" className="mb-5 rounded-xl bg-red-500/15 p-4 text-red-100">{error}</p>}<section className="grid gap-4 sm:grid-cols-2">{[["Today’s sales", currency.format(totals.sales), "emerald"], ["Today’s purchases", currency.format(totals.purchases), "sky"]].map(([label, value, tone], index) => <motion.div key={label} initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.12 + index * 0.08 }} whileHover={{ y: -6, scale: 1.01 }} className={`rounded-3xl border p-6 shadow-2xl backdrop-blur-xl ${tone === "emerald" ? "border-emerald-300/20 bg-emerald-400/10" : "border-sky-300/20 bg-sky-400/10"}`}><p className={tone === "emerald" ? "text-emerald-200" : "text-sky-200"}>{label}</p><p className="mt-3 text-4xl font-bold">{value}</p></motion.div>)}</section><section className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{actions.map(([href, label, tone], index) => <motion.div key={href} initial={{ opacity: 0, y: 18 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.28 + index * 0.07 }} whileHover={{ y: -7, scale: 1.025 }} whileTap={{ scale: 0.98 }}><Link href={href} className={`block rounded-2xl p-6 font-semibold shadow-lg transition ${tone === "emerald" ? "bg-emerald-600 hover:bg-emerald-500" : tone === "amber" ? "bg-amber-600 hover:bg-amber-500" : tone === "sky" ? "bg-sky-600 hover:bg-sky-500" : "bg-violet-600 hover:bg-violet-500"}`}>{label} <span className="float-right">→</span></Link></motion.div>)}</section></div></MotionPage>;
}
