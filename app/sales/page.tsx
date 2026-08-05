"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { supabase } from "../lib/supabase";

type SaleItem = { name: string; qty: string; unit?: string; price: string };
type Sale = { id: string; invoice_no: string | null; bill_date: string | null; customer_name: string; mobile: string | null; total: number; products: SaleItem[]; created_at: string };
const money = new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR" });

export default function SalesPage() {
  const [sales, setSales] = useState<Sale[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [deletingId, setDeletingId] = useState<string | null>(null);

  useEffect(() => {
    let active = true;
    const loadSales = async () => {
      const { data, error: queryError } = await supabase.from("sales").select("id, invoice_no, bill_date, customer_name, mobile, total, products, created_at").order("created_at", { ascending: false });
      if (!active) return;
      if (queryError) setError(queryError.message);
      else setSales((data ?? []) as Sale[]);
      setLoading(false);
    };
    void loadSales();
    return () => { active = false; };
  }, []);

  const deleteSale = async (id: string) => {
    if (!window.confirm("Delete this invoice? Its product quantities will be restored to stock.")) return;
    setDeletingId(id); setError(null);
    const { error: deleteError } = await supabase.rpc("delete_sale_and_restore_stock", { p_sale_id: id });
    setDeletingId(null);
    if (deleteError) return setError(deleteError.message);
    setSales((current) => current.filter((sale) => sale.id !== id));
  };

  return <main className="min-h-screen bg-slate-950 p-4 pt-20 text-white sm:p-8"><div className="mx-auto max-w-6xl"><header className="mb-8 flex flex-wrap items-center justify-between gap-4"><div><p className="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Sales</p><h1 className="text-3xl font-bold sm:text-4xl">Customer bills</h1></div><Link href="/billing" className="rounded-xl bg-emerald-600 px-5 py-3 font-semibold hover:bg-emerald-500">Create new bill</Link></header>{error && <p className="mb-4 rounded-xl bg-red-500/15 p-4 text-red-100">{error}</p>}{loading ? <p className="text-slate-300">Loading records...</p> : sales.length === 0 ? <div className="rounded-2xl border border-dashed border-white/20 p-8 text-slate-300">No sale record found yet. Create and save a bill to see it here.</div> : <div className="space-y-3">{sales.map((sale) => <details key={sale.id} className="rounded-2xl border border-white/15 bg-white/5 p-5 open:bg-white/10"><summary className="cursor-pointer list-none"><div className="flex flex-wrap items-center justify-between gap-3"><div><p className="font-bold text-lg">{sale.customer_name}</p><p className="text-sm text-slate-300">{sale.invoice_no ?? "No invoice number"} · {sale.mobile ?? "No mobile"}</p></div><div className="text-right"><p className="font-bold text-emerald-300">{money.format(Number(sale.total))}</p><p className="text-sm text-slate-300">{sale.bill_date ? new Date(`${sale.bill_date}T00:00:00`).toLocaleDateString("en-IN") : new Date(sale.created_at).toLocaleDateString("en-IN")}</p></div></div></summary><div className="mt-5 border-t border-white/10 pt-4"><div className="mb-4 flex gap-3"><Link href={`/billing?edit=${sale.id}`} className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold hover:bg-sky-500">Edit invoice</Link><button type="button" onClick={() => deleteSale(sale.id)} disabled={deletingId === sale.id} className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold hover:bg-red-500 disabled:opacity-60">{deletingId === sale.id ? "Deleting..." : "Delete invoice"}</button></div><div className="overflow-x-auto"><table className="min-w-full text-left text-sm"><thead className="text-slate-300"><tr><th className="pb-2 pr-5">Product</th><th className="pb-2 pr-5">Qty</th><th className="pb-2 pr-5">Rate</th><th className="pb-2 text-right">Amount</th></tr></thead><tbody>{(sale.products ?? []).map((item, index) => <tr key={`${item.name}-${index}`} className="border-t border-white/10"><td className="py-2 pr-5">{item.name}</td><td className="py-2 pr-5">{item.qty} {item.unit ?? ""}</td><td className="py-2 pr-5">{money.format(Number(item.price))}</td><td className="py-2 text-right">{money.format(Number(item.qty) * Number(item.price))}</td></tr>)}</tbody></table></div></div></details>)}</div>}</div></main>;
}
