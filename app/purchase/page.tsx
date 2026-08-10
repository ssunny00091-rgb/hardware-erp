"use client";

import { useEffect, useRef, useState } from "react";
import PurchaseForm from "../components/purchase/PurchaseForm";
import PurchaseRow from "../components/purchase/PurchaseRow";
import PurchaseSummary from "../components/purchase/PurchaseSummary";
import type { InventoryProduct } from "../components/billing/ProductSearch";
import { supabase } from "../lib/supabase";
import MotionPage from "../components/layout/MotionPage";
import { motion } from "framer-motion";
import { SupplierService } from "../services/supplier.service";
import type { Supplier } from "../types/supplier";

type PurchaseProduct = { name: string; qty: string; unit: string; price: string };
const emptyRow = (): PurchaseProduct => ({ name: "", qty: "", unit: "Piece", price: "" });
const productFields = "id, product_name, selling_price, purchase_price, unit, stock";

export default function PurchasePage() {
  const [supplierName, setSupplierName] = useState("");
  const [invoiceNo, setInvoiceNo] = useState("");
  const [purchaseDate, setPurchaseDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [products, setProducts] = useState<PurchaseProduct[]>([emptyRow()]);
  const [productMaster, setProductMaster] = useState<InventoryProduct[]>([]);
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const productRefs = useRef<(HTMLInputElement | null)[]>([]);

  const loadProducts = async () => {
    const { data, error } = await supabase.from("products").select(productFields).order("product_name");
    if (error) throw error;
    return data ?? [];
  };
  useEffect(() => {
    let active = true;
    void loadProducts().then((data) => { if (active) setProductMaster(data); }).catch((error: Error) => { if (active) setMessage(error.message); });
    return () => { active = false; };
  }, []);
  useEffect(() => {
  let active = true;

  void SupplierService.getAll()
    .then((data) => {
      if (active) {
        setSuppliers(data);
      }
    })
    .catch((error) => {
      if (active) {
        setMessage(
          error instanceof Error
            ? error.message
            : "Failed to load suppliers."
        );
      }
    });

  return () => {
    active = false;
  };
}, []);

  const handleProductChange = (index: number, field: keyof PurchaseProduct, value: string) => setProducts((current) => current.map((item, rowIndex) => rowIndex === index ? { ...item, [field]: value } : item));
  const addRow = () => { setProducts((current) => [...current, emptyRow()]); window.setTimeout(() => productRefs.current[products.length]?.focus(), 0); };
  const deleteRow = (index: number) => setProducts((current) => current.length === 1 ? [emptyRow()] : current.filter((_, rowIndex) => rowIndex !== index));
  const validProducts = products.filter((item) => item.name.trim() && Number(item.qty) > 0 && Number(item.price) >= 0);
  const enteredRows = products.filter((item) => item.name || item.qty || item.price);
  const grandTotal = validProducts.reduce((total, item) => total + Number(item.qty) * Number(item.price), 0);

  const savePurchase = async () => {
    setMessage(null);
    if (!supplierName.trim()) return setMessage("Supplier name is required.");
    if (validProducts.length === 0) return setMessage("Add at least one complete product row.");
    if (validProducts.length !== enteredRows.length) return setMessage("Complete or remove incomplete product rows.");
    setSaving(true);
    const { error } = await supabase.rpc("create_purchase_and_increase_stock", { p_supplier_name: supplierName, p_invoice_no: invoiceNo, p_purchase_date: purchaseDate, p_products: validProducts });
    setSaving(false);
    if (error) return setMessage(error.message);
    setMessage("Purchase saved and stock updated.");
    setSupplierName(""); setInvoiceNo(""); setProducts([emptyRow()]);
    try { setProductMaster(await loadProducts()); } catch (error) { setMessage(error instanceof Error ? error.message : "Purchase saved, but stock could not be refreshed."); }
  };

  return (
    <MotionPage><main className="p-4 sm:p-8"><div className="mx-auto max-w-7xl"><motion.header initial={{ opacity: 0, x: -16 }} animate={{ opacity: 1, x: 0 }} className="mb-8"><p className="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Inventory</p><h1 className="bg-gradient-to-r from-white to-sky-200 bg-clip-text text-3xl font-bold text-transparent sm:text-4xl">Purchase entry</h1></motion.header>{message && <p role="status" className="mb-5 rounded-xl border border-sky-300/30 bg-sky-300/10 p-4 text-sky-100">{message}</p>}
    <PurchaseForm
  supplierName={supplierName}
  invoiceNo={invoiceNo}
  purchaseDate={purchaseDate}
  suppliers={suppliers}
  onSupplierChange={setSupplierName}
  onInvoiceChange={setInvoiceNo}
  onDateChange={setPurchaseDate}
/>
        <motion.section initial={{ opacity: 0, y: 14 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }} className="mt-8 rounded-3xl border border-white/10 bg-white/5 p-3 shadow-xl backdrop-blur-xl sm:p-5"><div className="mb-3 hidden grid-cols-[minmax(0,2fr)_minmax(90px,0.6fr)_minmax(100px,0.8fr)_minmax(100px,0.8fr)_48px] gap-3 px-3 text-sm font-semibold text-slate-300 md:grid"><span>Product</span><span>Qty</span><span>Rate</span><span>Total</span><span /></div>{products.map((product, index) => <PurchaseRow key={index} index={index} product={product} products={productMaster} productInputRef={(element) => { productRefs.current[index] = element; }} total={Number(product.qty) * Number(product.price)} onChange={handleProductChange} onDelete={deleteRow} onAddRow={addRow} />)}</motion.section><PurchaseSummary grandTotal={grandTotal} onAddRow={addRow} onSave={savePurchase} saving={saving} /></div></main></MotionPage>
  );
}
