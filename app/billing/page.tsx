"use client";

import { Suspense, useEffect, useRef, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import BillingForm from "../../components/billing/BillingForm";
import BillingRow from "../../components/billing/BillingRow";
import BillingSummary from "../../components/billing/BillingSummary";
import InvoicePreview from "../components/billing/InvoicePreview";
import type { InventoryProduct } from "../components/billing/ProductSearch";
import { supabase } from "../lib/supabase";
import { generateInvoiceNumber } from "../utils/invoiceNumber";
import MotionPage from "../components/layout/MotionPage";
import { motion } from "framer-motion";

type BillingProduct = { name: string; qty: string; unit: string; price: string };
const emptyRow = (): BillingProduct => ({ name: "", qty: "", unit: "Piece", price: "" });
const productFields = "id, product_name, selling_price, purchase_price, unit, stock";

function BillingEditor() {
  const [customerName, setCustomerName] = useState("");
  const [mobile, setMobile] = useState("");
  const [invoiceNo, setInvoiceNo] = useState(generateInvoiceNumber);
  const [billDate, setBillDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [products, setProducts] = useState<BillingProduct[]>([emptyRow()]);
  const [productMaster, setProductMaster] = useState<InventoryProduct[]>([]);
  const [saving, setSaving] = useState(false);
  const [showPreview, setShowPreview] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const productRefs = useRef<(HTMLInputElement | null)[]>([]);
  const searchParams = useSearchParams();
  const router = useRouter();
  const editingId = searchParams.get("edit");

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
    if (!editingId) return;
    let active = true;
    const loadSale = async () => {
      const { data, error } = await supabase.from("sales").select("customer_name, mobile, invoice_no, bill_date, products").eq("id", editingId).single();
      if (!active) return;
      if (error) return setMessage(error.message);
      setCustomerName(data.customer_name ?? ""); setMobile(data.mobile ?? ""); setInvoiceNo(data.invoice_no ?? generateInvoiceNumber()); setBillDate(data.bill_date ?? new Date().toISOString().slice(0, 10)); setProducts((data.products as BillingProduct[]) ?? [emptyRow()]);
    };
    void loadSale();
    return () => { active = false; };
  }, [editingId]);

  const handleProductChange = (index: number, field: keyof BillingProduct, value: string) => {
    setProducts((current) => current.map((product, productIndex) => productIndex === index ? { ...product, [field]: value } : product));
  };

  const addRow = () => {
    setProducts((current) => [...current, emptyRow()]);
    window.setTimeout(() => productRefs.current[products.length]?.focus(), 0);
  };
  const deleteRow = (index: number) => setProducts((current) => current.length === 1 ? [emptyRow()] : current.filter((_, rowIndex) => rowIndex !== index));
  const validProducts = products.filter((item) => item.name.trim() && Number(item.qty) > 0 && Number(item.price) >= 0);
  const enteredRows = products.filter((item) => item.name || item.qty || item.price);
  const grandTotal = validProducts.reduce((total, item) => total + Number(item.qty) * Number(item.price), 0);

  const saveBill = async () => {
    setMessage(null);
    if (!customerName.trim()) return setMessage("Customer name is required.");
    if (validProducts.length === 0) return setMessage("Add at least one complete product row.");
    if (validProducts.length !== enteredRows.length) return setMessage("Complete or remove incomplete product rows.");

    setSaving(true);
    const { error } = await supabase.rpc(editingId ? "update_sale_and_adjust_stock" : "create_sale_and_reduce_stock", editingId ? { p_sale_id: editingId, p_customer_name: customerName, p_mobile: mobile, p_invoice_no: invoiceNo, p_bill_date: billDate, p_products: validProducts, p_total: grandTotal } : { p_customer_name: customerName, p_mobile: mobile, p_invoice_no: invoiceNo, p_bill_date: billDate, p_products: validProducts, p_total: grandTotal });
    setSaving(false);
    if (error) return setMessage(error.message);

    setMessage(`Invoice ${invoiceNo} ${editingId ? "updated" : "saved"} and stock updated.`);
    setShowPreview(false);
    setCustomerName(""); setMobile(""); setInvoiceNo(generateInvoiceNumber()); setProducts([emptyRow()]); if (editingId) router.replace("/billing");
    try { setProductMaster(await loadProducts()); } catch (error) { setMessage(error instanceof Error ? error.message : "Bill saved, but stock could not be refreshed."); }
  };

  const openPreview = () => {
    setMessage(null);
    if (!customerName.trim()) return setMessage("Customer name is required before previewing the invoice.");
    if (validProducts.length === 0 || validProducts.length !== enteredRows.length) return setMessage("Add at least one complete product row before previewing.");
    setShowPreview(true);
  };

  return (
    <MotionPage>
    <main className="p-4 pt-20 sm:p-8">
      <div className="mx-auto max-w-7xl">
        <motion.header initial={{ opacity: 0, x: -16 }} animate={{ opacity: 1, x: 0 }} className="mb-8"><p className="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">Sales</p><h1 className="bg-gradient-to-r from-white to-sky-200 bg-clip-text text-3xl font-bold text-transparent sm:text-4xl">{editingId ? "Edit invoice" : "Create invoice"}</h1></motion.header>
        {message && <p role="status" className="mb-5 rounded-xl border border-sky-300/30 bg-sky-300/10 p-4 text-sky-100">{message}</p>}
        <BillingForm customerName={customerName} mobile={mobile} invoiceNo={invoiceNo} billDate={billDate} onCustomerChange={setCustomerName} onMobileChange={setMobile} onInvoiceChange={setInvoiceNo} onDateChange={setBillDate} />
        <motion.section initial={{ opacity: 0, y: 14 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }} className="relative z-20 mt-8 rounded-3xl border border-white/10 bg-white/5 p-3 shadow-xl backdrop-blur-xl sm:p-5"><div className="mb-3 hidden grid-cols-[minmax(0,2fr)_minmax(90px,0.6fr)_minmax(100px,0.8fr)_minmax(100px,0.8fr)_48px] gap-3 px-3 text-sm font-semibold text-slate-300 md:grid"><span>Product</span><span>Qty</span><span>Rate</span><span>Total</span><span /></div>{products.map((product, index) => <BillingRow key={index} index={index} product={product} products={productMaster} productInputRef={(element) => { productRefs.current[index] = element; }} total={Number(product.qty) * Number(product.price)} onChange={handleProductChange} onDelete={deleteRow} onAddRow={addRow} />)}</motion.section>
        <BillingSummary grandTotal={grandTotal} onAddRow={addRow} onPreview={openPreview} onSave={saveBill} saving={saving} />
        <InvoicePreview open={showPreview} onClose={() => setShowPreview(false)} customerName={customerName} mobile={mobile} products={validProducts} grandTotal={grandTotal} invoiceNo={invoiceNo} billDate={billDate} onDownload={() => window.print()} onSave={saveBill} />
      </div>
    </main></MotionPage>
  );
}

export default function BillingPage() {
  return <Suspense fallback={<main className="min-h-screen bg-slate-950 p-8 text-white">Loading invoice...</main>}><BillingEditor /></Suspense>;
}
