"use client";

import { useState } from "react";
import { supabase } from "../../lib/supabase";

type Product = { id: number; product_name: string; brand: string; category: string; unit: string; purchase_price: number; selling_price: number; stock: number; gst_percent: number; hsn_code: string };
type Props = { open: boolean; product: Product | null; onClose: () => void; onSaved: () => void };
type EditableField = Exclude<keyof Product, "id">;

export default function EditProductModal({ open, product, onClose, onSaved }: Props) {
  const [form, setForm] = useState<Product | null>(product);
  const [saving, setSaving] = useState(false);
  if (!open || !form) return null;
  const updateField = (field: EditableField, value: string) => setForm((current) => current ? { ...current, [field]: value } : null);
  const updateProduct = async () => {
    if (!form.product_name.trim()) return;
    setSaving(true);
    const { error } = await supabase.from("products").update({ product_name: form.product_name, brand: form.brand, category: form.category, unit: form.unit, purchase_price: Number(form.purchase_price), selling_price: Number(form.selling_price), stock: Number(form.stock), gst_percent: Number(form.gst_percent), hsn_code: form.hsn_code }).eq("id", form.id);
    setSaving(false);
    if (error) return alert(error.message);
    onSaved(); onClose();
  };
  return <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"><div className="w-full max-w-2xl rounded-2xl bg-slate-900 p-6 shadow-2xl sm:p-8"><div className="mb-6 flex justify-between"><h2 className="text-2xl font-bold text-white">Edit product</h2><button type="button" onClick={onClose} className="rounded bg-red-500 px-4 py-2 text-white">Close</button></div><div className="grid grid-cols-1 gap-4 sm:grid-cols-2"><input value={form.product_name} onChange={(event) => updateField("product_name", event.target.value)} placeholder="Product name" className="rounded border p-3" /><input value={form.brand} onChange={(event) => updateField("brand", event.target.value)} placeholder="Brand" className="rounded border p-3" /><input value={form.category} onChange={(event) => updateField("category", event.target.value)} placeholder="Category" className="rounded border p-3" /><input value={form.unit} onChange={(event) => updateField("unit", event.target.value)} placeholder="Unit" className="rounded border p-3" /><input type="number" value={form.purchase_price} onChange={(event) => updateField("purchase_price", event.target.value)} placeholder="Purchase price" className="rounded border p-3" /><input type="number" value={form.selling_price} onChange={(event) => updateField("selling_price", event.target.value)} placeholder="Selling price" className="rounded border p-3" /><input type="number" value={form.stock} onChange={(event) => updateField("stock", event.target.value)} placeholder="Stock" className="rounded border p-3" /><input type="number" value={form.gst_percent} onChange={(event) => updateField("gst_percent", event.target.value)} placeholder="GST %" className="rounded border p-3" /><input value={form.hsn_code} onChange={(event) => updateField("hsn_code", event.target.value)} placeholder="HSN code" className="rounded border p-3 sm:col-span-2" /></div><button type="button" onClick={updateProduct} disabled={saving} className="mt-6 w-full rounded-xl bg-green-600 py-3 text-white disabled:opacity-50">{saving ? "Updating..." : "Update product"}</button></div></div>;
}
