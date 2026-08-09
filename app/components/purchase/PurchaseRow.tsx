"use client";

import { useRef } from "react";
import ProductSearch, { type InventoryProduct } from "../billing/ProductSearch";

type PurchaseRowProps = {
  index: number;
  product: { name: string; qty: string; unit: string; price: string };
  products: InventoryProduct[];
  productInputRef?: React.Ref<HTMLInputElement>;
  total: number;
  onChange: (index: number, field: "name" | "qty" | "unit" | "price", value: string) => void;
  onDelete: (index: number) => void;
  onAddRow: () => void;
};

export default function PurchaseRow({ index, product, products, productInputRef, total, onChange, onDelete, onAddRow }: PurchaseRowProps) {
  const qtyRef = useRef<HTMLInputElement>(null);
  const priceRef = useRef<HTMLInputElement>(null);
  return (
    <div className="mb-3 grid gap-3 md:grid-cols-[minmax(0,2fr)_minmax(90px,0.6fr)_minmax(100px,0.8fr)_minmax(100px,0.8fr)_48px]">
      <ProductSearch ref={productInputRef} products={products} priceType="purchase" value={product.name} onChange={(value) => onChange(index, "name", value)} onSelect={(name, price, unit) => { onChange(index, "name", name); onChange(index, "price", String(price)); onChange(index, "unit", unit); window.setTimeout(() => qtyRef.current?.focus(), 0); }} />
      <input ref={qtyRef} min="1" type="number" placeholder="Qty" value={product.qty} onChange={(event) => onChange(index, "qty", event.target.value)} onKeyDown={(event) => { if (event.key === "Enter") { event.preventDefault(); priceRef.current?.focus(); } }} className="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300" />
      <input ref={priceRef} min="0" type="number" placeholder="Purchase price" value={product.price} onChange={(event) => onChange(index, "price", event.target.value)} onKeyDown={(event) => { if (event.key === "Enter") { event.preventDefault(); onAddRow(); } }} className="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300" />
      <div className="flex items-center justify-center rounded-xl border border-white/20 bg-white/10 font-bold text-emerald-300">₹{total.toFixed(2)}</div>
      <button type="button" aria-label="Remove product" onClick={() => onDelete(index)} className="rounded-xl bg-red-500 p-3 text-white hover:bg-red-600">×</button>
    </div>
  );
}
