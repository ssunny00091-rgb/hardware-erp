"use client";

import { Suspense, useEffect, useRef, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import ProductRow from "../components/billing/ProductRow";
import InvoicePreview from "../components/billing/InvoicePreview";
import type { InventoryProduct } from "../components/billing/ProductSearch";
import { supabase } from "../lib/supabase";
import { generateInvoiceNumber } from "../utils/invoiceNumber";
import MotionPage from "../components/layout/MotionPage";
import { motion } from "framer-motion";

type BillingProduct = {
  name: string;
  qty: string;
  unit: string;
  price: string;
};

const emptyRow = (): BillingProduct => ({
  name: "",
  qty: "",
  unit: "Piece",
  price: "",
});

const productFields =
  "id, product_name, selling_price, purchase_price, unit, stock";

function BillingEditor() {
  const [customerName, setCustomerName] = useState("");
  const [mobile, setMobile] = useState("");
  const [invoiceNo, setInvoiceNo] = useState(generateInvoiceNumber);
  const [billDate, setBillDate] = useState(
    () => new Date().toISOString().slice(0, 10)
  );

  const [products, setProducts] = useState<BillingProduct[]>([
    emptyRow(),
  ]);

  const [productMaster, setProductMaster] = useState<
    InventoryProduct[]
  >([]);

  const [saving, setSaving] = useState(false);
  const [showPreview, setShowPreview] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [showAddProduct, setShowAddProduct] = useState(false);
const [newProductName, setNewProductName] = useState("");
const [selectedProductRowIndex, setSelectedProductRowIndex] =
  useState<number | null>(null);
const [newProductBrand, setNewProductBrand] = useState("");
const [newProductUnit, setNewProductUnit] = useState("Piece");
const [newProductPurchasePrice, setNewProductPurchasePrice] = useState("");
const [newProductSellingPrice, setNewProductSellingPrice] = useState("");
const [newProductGst, setNewProductGst] = useState("18");
const [newProductStock, setNewProductStock] = useState("");
const [savingProduct, setSavingProduct] = useState(false);

  const productRefs = useRef<
    (HTMLInputElement | null)[]
  >([]);

  const searchParams = useSearchParams();
  const router = useRouter();

  const editingId = searchParams.get("edit");

  async function loadProducts() {
    const { data, error } = await supabase
      .from("products")
      .select(productFields)
      .order("product_name");

    if (error) {
      throw error;
    }

    return (data ?? []) as InventoryProduct[];
  }

  useEffect(() => {
    let active = true;

    void loadProducts()
      .then((data) => {
        if (active) {
          setProductMaster(data);
        }
      })
      .catch((error: Error) => {
        if (active) {
          setMessage(error.message);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  useEffect(() => {
    if (!editingId) return;

    let active = true;

    async function loadSale() {
      const { data, error } = await supabase
        .from("sales")
        .select(
          "customer_name, mobile, invoice_no, bill_date, products"
        )
        .eq("id", editingId)
        .single();

      if (!active) return;

      if (error) {
        setMessage(error.message);
        return;
      }

      setCustomerName(data.customer_name ?? "");
      setMobile(data.mobile ?? "");
      setInvoiceNo(
        data.invoice_no ?? generateInvoiceNumber()
      );
      setBillDate(
        data.bill_date ??
          new Date().toISOString().slice(0, 10)
      );

      setProducts(
        (data.products as BillingProduct[]) ?? [emptyRow()]
      );
    }

    void loadSale();

    return () => {
      active = false;
    };
  }, [editingId]);

  function handleProductChange(
    index: number,
    field: keyof BillingProduct,
    value: string
  ) {
    setProducts((current) =>
      current.map((product, productIndex) =>
        productIndex === index
          ? {
              ...product,
              [field]: value,
            }
          : product
      )
    );
  }

  function addRow() {
    setProducts((current) => [
      ...current,
      emptyRow(),
    ]);

    window.setTimeout(() => {
      productRefs.current[products.length]?.focus();
    }, 0);
  }

  function deleteRow(index: number) {
    setProducts((current) =>
      current.length === 1
        ? [emptyRow()]
        : current.filter(
            (_, rowIndex) => rowIndex !== index
          )
    );
  }

  const validProducts = products.filter(
    (item) =>
      item.name.trim() &&
      Number(item.qty) > 0 &&
      Number(item.price) >= 0
  );

  const enteredRows = products.filter(
    (item) =>
      item.name.trim() ||
      item.qty.trim() ||
      item.price.trim()
  );

  const grandTotal = validProducts.reduce(
    (total, item) =>
      total +
      Number(item.qty) * Number(item.price),
    0
  );

  async function saveBill() {
    setMessage(null);

    if (!customerName.trim()) {
      setMessage("Customer name is required.");
      return;
    }

    if (validProducts.length === 0) {
      setMessage(
        "Add at least one complete product row."
      );
      return;
    }

    if (validProducts.length !== enteredRows.length) {
      setMessage(
        "Complete or remove incomplete product rows."
      );
      return;
    }

    setSaving(true);

    const rpcName = editingId
      ? "update_sale_and_adjust_stock"
      : "create_sale_and_reduce_stock";

    const rpcParams = editingId
      ? {
          p_sale_id: editingId,
          p_customer_name: customerName,
          p_mobile: mobile,
          p_invoice_no: invoiceNo,
          p_bill_date: billDate,
          p_products: validProducts,
          p_total: grandTotal,
        }
      : {
          p_customer_name: customerName,
          p_mobile: mobile,
          p_invoice_no: invoiceNo,
          p_bill_date: billDate,
          p_products: validProducts,
          p_total: grandTotal,
        };

    const { error } = await supabase.rpc(
      rpcName,
      rpcParams
    );

    setSaving(false);

    if (error) {
      setMessage(error.message);
      return;
    }

    setMessage(
      `Invoice ${invoiceNo} ${
        editingId ? "updated" : "saved"
      } and stock updated.`
    );

    setShowPreview(false);

    setCustomerName("");
    setMobile("");
    setInvoiceNo(generateInvoiceNumber());
    setProducts([emptyRow()]);

    if (editingId) {
      router.replace("/billing");
    }

    try {
      setProductMaster(await loadProducts());
    } catch (error) {
      setMessage(
        error instanceof Error
          ? error.message
          : "Bill saved, but stock could not be refreshed."
      );
    }
  }
   async function saveNewProduct() {
  setMessage(null);

  const name = newProductName.trim();

  if (!name) {
    setMessage("Product name is required.");
    return;
  }

  const purchasePrice = Number(newProductPurchasePrice);
  const sellingPrice = Number(newProductSellingPrice);
  const gstPercent = Number(newProductGst);
  const stock = Number(newProductStock);

  if (purchasePrice < 0 || sellingPrice < 0) {
    setMessage("Price cannot be negative.");
    return;
  }

  if (gstPercent < 0) {
    setMessage("GST cannot be negative.");
    return;
  }

  if (stock < 0) {
    setMessage("Stock cannot be negative.");
    return;
  }

  setSavingProduct(true);

  const { data, error } = await supabase
    .from("products")
    .insert({
      product_name: name,
      brand: newProductBrand.trim() || null,
      unit: newProductUnit || "Piece",
      purchase_price: purchasePrice,
      selling_price: sellingPrice,
      gst_percent: gstPercent,
      stock,
    })
    .select(productFields)
    .single();

  setSavingProduct(false);

  if (error) {
    setMessage(error.message);
    return;
  }

  const newProduct = data as InventoryProduct;

  setProductMaster((current) =>
    [...current, newProduct].sort((a, b) =>
      a.product_name.localeCompare(b.product_name)
    )
  );

  if (selectedProductRowIndex !== null) {
  setProducts((current) =>
    current.map((item, index) =>
      index === selectedProductRowIndex
        ? {
            ...item,
            name: newProduct.product_name,
            unit: newProduct.unit,
            price: String(newProduct.selling_price),
          }
        : item
    )
  );
}
setSelectedProductRowIndex(null);

  setShowAddProduct(false);

  setNewProductName("");
  setNewProductBrand("");
  setNewProductUnit("Piece");
  setNewProductPurchasePrice("");
  setNewProductSellingPrice("");
  setNewProductGst("18");
  setNewProductStock("");

  setMessage(
    `Product "${newProduct.product_name}" added successfully.`
  );
}
{showAddProduct && (
  <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4">
    <div className="w-full max-w-lg rounded-2xl border border-white/10 bg-slate-900 p-6 shadow-2xl">

      <h2 className="mb-6 text-2xl font-bold text-white">
        ➕ Add New Product
      </h2>

      <div className="space-y-4">

        <input
          type="text"
          value={newProductName}
          onChange={(e) =>
            setNewProductName(e.target.value)
          }
          placeholder="Product Name"
          className="w-full rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300"
        />

        <input
          type="text"
          value={newProductBrand}
          onChange={(e) =>
            setNewProductBrand(e.target.value)
          }
          placeholder="Brand"
          className="w-full rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300"
        />

        <select
          value={newProductUnit}
          onChange={(e) =>
            setNewProductUnit(e.target.value)
          }
          className="w-full rounded-xl border border-white/20 bg-slate-800 p-3 text-white"
        >
          <option value="Piece">Piece</option>
          <option value="Box">Box</option>
          <option value="Packet">Packet</option>
          <option value="Kg">Kg</option>
          <option value="Liter">Liter</option>
          <option value="Bucket">Bucket</option>
          <option value="Bag">Bag</option>
        </select>

        <input
          type="number"
          min="0"
          value={newProductPurchasePrice}
          onChange={(e) =>
            setNewProductPurchasePrice(e.target.value)
          }
          placeholder="Purchase Price"
          className="w-full rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300"
        />

        <input
          type="number"
          min="0"
          value={newProductSellingPrice}
          onChange={(e) =>
            setNewProductSellingPrice(e.target.value)
          }
          placeholder="Selling Price"
          className="w-full rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300"
        />

        <input
          type="number"
          min="0"
          value={newProductGst}
          onChange={(e) =>
            setNewProductGst(e.target.value)
          }
          placeholder="GST %"
          className="w-full rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300"
        />

        <input
          type="number"
          min="0"
          value={newProductStock}
          onChange={(e) =>
            setNewProductStock(e.target.value)
          }
          placeholder="Current Stock"
          className="w-full rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300"
        />

      </div>

      <div className="mt-6 flex justify-end gap-3">

        <button
          type="button"
          onClick={() => setShowAddProduct(false)}
          disabled={savingProduct}
          className="rounded-xl bg-slate-600 px-5 py-3 font-semibold text-white hover:bg-slate-500 disabled:opacity-50"
        >
          Cancel
        </button>

        <button
          type="button"
          onClick={saveNewProduct}
          disabled={savingProduct}
          className="rounded-xl bg-green-600 px-5 py-3 font-semibold text-white hover:bg-green-500 disabled:opacity-50"
        >
          {savingProduct
            ? "Saving..."
            : "Save & Add to Sale"}
        </button>

      </div>

    </div>
  </div>
)}
  function openPreview() {
    setMessage(null);

    if (!customerName.trim()) {
      setMessage(
        "Customer name is required before previewing the invoice."
      );
      return;
    }

    if (
      validProducts.length === 0 ||
      validProducts.length !== enteredRows.length
    ) {
      setMessage(
        "Add at least one complete product row before previewing."
      );
      return;
    }

    setShowPreview(true);
  }

 

  return (
    <MotionPage>
      <main className="min-h-screen p-4 text-white sm:p-8">
        <div className="mx-auto max-w-7xl">

          <motion.header
            initial={{ opacity: 0, x: -16 }}
            animate={{ opacity: 1, x: 0 }}
            className="mb-8"
          >
            <p className="text-sm font-semibold uppercase tracking-[0.18em] text-sky-300">
              Sales
            </p>

            <h1 className="text-3xl font-bold sm:text-4xl">
              {editingId
                ? "Edit Invoice"
                : "Create Invoice"}
            </h1>
          </motion.header>

          {message && (
            <div className="mb-5 rounded-xl border border-sky-300/30 bg-sky-300/10 p-4 text-sky-100">
              {message}
            </div>
          )}

          {/* Customer Details */}
          <section className="rounded-3xl border border-white/10 bg-white/5 p-5 shadow-xl backdrop-blur-xl">
            <h2 className="mb-5 text-xl font-semibold">
              Customer Details
            </h2>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">

              <input
                type="text"
                value={customerName}
                onChange={(e) =>
                  setCustomerName(e.target.value)
                }
                placeholder="Customer Name"
                className="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300"
              />

              <input
                type="text"
                value={mobile}
                onChange={(e) =>
                  setMobile(e.target.value)
                }
                placeholder="Mobile Number"
                className="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300"
              />

              <input
                type="date"
                value={billDate}
                onChange={(e) =>
                  setBillDate(e.target.value)
                }
                className="rounded-xl border border-white/20 bg-white/10 p-3 text-white"
              />

              <input
                type="text"
                value={invoiceNo}
                onChange={(e) =>
                  setInvoiceNo(e.target.value)
                }
                placeholder="Invoice Number"
                className="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300"
              />

            </div>
          </section>

          {/* Products */}
          <motion.section
            initial={{ opacity: 0, y: 14 }}
            animate={{ opacity: 1, y: 0 }}
            className="relative z-20 mt-8 rounded-3xl border border-white/10 bg-white/5 p-3 shadow-xl backdrop-blur-xl sm:p-5"
          >
            <div className="mb-3 hidden grid-cols-[minmax(0,2fr)_90px_110px_120px_50px] gap-3 px-3 text-sm font-semibold text-slate-300 md:grid">
              <span>Product</span>
              <span>Qty</span>
              <span>Rate</span>
              <span>Total</span>
              <span />
            </div>

            {products.map((product, index) => (
              <ProductRow
  key={index}
  index={index}
  product={product}
  products={productMaster}
  productInputRef={(element) => {
    productRefs.current[index] = element;
  }}
  total={Number(product.qty) * Number(product.price)}
  onChange={handleProductChange}
  onDelete={deleteRow}
  onAddNewRow={addRow}
  onAddNewProduct={() => {
    setSelectedProductRowIndex(index);
    setNewProductName(product.name);
    setShowAddProduct(true);
  }}
/>
            ))}

            <div className="mt-5 flex flex-col items-end gap-4 border-t border-white/10 pt-5">
              <div className="text-2xl font-bold">
                Total: ₹
                {grandTotal.toLocaleString("en-IN")}
              </div>

              <div className="flex gap-3">
                <button
                  type="button"
                  onClick={addRow}
                  className="rounded-xl bg-blue-600 px-5 py-3 font-semibold hover:bg-blue-500"
                >
                  + Add Product
                </button>

                <button
                  type="button"
                  onClick={openPreview}
                  className="rounded-xl bg-slate-600 px-5 py-3 font-semibold hover:bg-slate-500"
                >
                  Preview Invoice
                </button>
              </div>
            </div>
          </motion.section>
         
          <InvoicePreview
            open={showPreview}
            onClose={() => setShowPreview(false)}
            customerName={customerName}
            mobile={mobile}
            products={validProducts}
            grandTotal={grandTotal}
            invoiceNo={invoiceNo}
            billDate={billDate}
            onDownload={() => window.print()}
            onSave={saveBill}
          />

          {saving && (
            <div className="fixed bottom-5 right-5 z-50 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white shadow-xl">
              Saving invoice...
            </div>
          )}

        </div>
      </main>
    </MotionPage>
  );
}

export default function BillingPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen bg-slate-900 p-10 text-center text-white">
          Loading invoice...
        </div>
      }
    >
      <BillingEditor />
    </Suspense>
  );
}