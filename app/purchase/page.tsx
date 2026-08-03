"use client";

import { useEffect, useRef, useState } from "react";

import { supabase } from "../lib/supabase";

import PurchaseForm from "../components/purchase/PurchaseForm";
import PurchaseRow from "../components/purchase/PurchaseRow";
import PurchaseSummary from "../components/purchase/PurchaseSummary";

type PurchaseProduct = {
  name: string;
  qty: string;
  unit: string;
  price: string;
};

type Product = {
  id: number;
  product_name: string;
  stock: number;
};

export default function PurchasePage() {
    

  const [supplierName, setSupplierName] = useState("");

  const [invoiceNo, setInvoiceNo] = useState("");

  const [purchaseDate, setPurchaseDate] = useState(
    new Date().toISOString().split("T")[0]
  );


  const [products, setProducts] = useState<PurchaseProduct[]>([
    {
      name: "",
      qty: "",
      unit: "Piece",
      price: "",
    },
  ]);

  const [productMaster, setProductMaster] =
    useState<Product[]>([]);
    const productRefs = useRef<(HTMLInputElement | null)[]>([]);

  useEffect(() => {
    fetchProducts();
  }, []);
  const handleProductChange = (
  index: number,
  field: "name" | "qty" | "unit" | "price",
  value: string
) => {
  setProducts((prev) => {
    const updated = [...prev];

    updated[index] = {
      ...updated[index],
      [field]: value,
    };

    return updated;
  });
};

const addRow = () => {
  setProducts((prev) => {
    const updated = [
      ...prev,
      {
        name: "",
        qty: "",
        unit: "Piece",
        price: "",
      },
    ];

    setTimeout(() => {
      productRefs.current[updated.length - 1]?.focus();
    }, 0);

    return updated;
  });
};

const deleteRow = (index: number) => {
  setProducts(products.filter((_, i) => i !== index));
};

  const fetchProducts = async () => {
    const { data } = await supabase
      .from("products")
      .select("*")
      .order("product_name");

    setProductMaster(data || []);
  };
    const grandTotal = products.reduce((total, item) => {
  return total + Number(item.qty) * Number(item.price);
}, 0);
return (
  <main className="min-h-screen bg-slate-900 p-8">

    <div className="mx-auto max-w-7xl">

      <h1 className="mb-8 text-4xl font-bold text-white">
        🛒 Purchase Entry
      </h1>

      <PurchaseForm
        supplierName={supplierName}
        invoiceNo={invoiceNo}
        purchaseDate={purchaseDate}
        onSupplierChange={setSupplierName}
        onInvoiceChange={setInvoiceNo}
        onDateChange={setPurchaseDate}
      />

      <div className="mt-8">

        <div className="mb-4 grid grid-cols-5 gap-3 rounded-xl bg-white/10 p-4 text-white">

          <div>Product</div>

          <div>Qty</div>

          <div>Purchase Price</div>

          <div>Total</div>

          <div>Action</div>

        </div>

        {products.map((product, index) => (

   <PurchaseRow
  key={index}
  index={index}
  product={product}
  productInputRef={(el) => {
    productRefs.current[index] = el;
  }}
  total={Number(product.qty) * Number(product.price)}
  onChange={handleProductChange}
  onDelete={deleteRow}
  onAddRow={addRow}
/>

        ))}

      </div>

      <PurchaseSummary
        grandTotal={grandTotal}
        onAddRow={addRow}
        onSave={() => {}}
      />

    </div>

  </main>
);}