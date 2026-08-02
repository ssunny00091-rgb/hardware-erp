"use client";

import { useState } from "react";
import { supabase } from "../../lib/supabase";

type Props = {
  open: boolean;
  onClose: () => void;
  onSaved: () => void;
};

export default function AddProductModal({
  open,
  onClose,
  onSaved,
}: Props) {
  const [productName, setProductName] = useState("");
  const [brand, setBrand] = useState("");
  const [category, setCategory] = useState("");
  const [unit, setUnit] = useState("Piece");

  const [purchasePrice, setPurchasePrice] = useState("");
  const [sellingPrice, setSellingPrice] = useState("");

  const [stock, setStock] = useState("");
  const [gst, setGst] = useState("18");
  const [hsn, setHsn] = useState("");

  const [saving, setSaving] = useState(false);

  const saveProduct = async () => {
    if (!productName.trim()) {
      alert("Please enter Product Name");
      return;
    }

    setSaving(true);

    const { error } = await supabase
      .from("products")
      .insert([
        {
          product_name: productName,
          brand,
          category,
          unit,
          purchase_price: Number(purchasePrice),
          selling_price: Number(sellingPrice),
          stock: Number(stock),
          gst_percent: Number(gst),
          hsn_code: hsn,
        },
      ]);

    setSaving(false);

    if (error) {
      alert(error.message);
      return;
    }

    alert("✅ Product Added Successfully");

    onSaved();
    onClose();

    setProductName("");
    setBrand("");
    setCategory("");
    setUnit("Piece");
    setPurchasePrice("");
    setSellingPrice("");
    setStock("");
    setGst("18");
    setHsn("");
  };

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
      <div className="w-full max-w-2xl rounded-2xl bg-slate-900 p-8 shadow-2xl">

        <div className="mb-6 flex items-center justify-between">
          <h2 className="text-3xl font-bold text-white">
            📦 Add Product
          </h2>

          <button
            onClick={onClose}
            className="rounded-lg bg-red-500 px-4 py-2 text-white"
          >
            ✕
          </button>
        </div>

        <div className="grid grid-cols-2 gap-4">

  <input
    value={productName}
    onChange={(e) => setProductName(e.target.value)}
    placeholder="Product Name"
    className="rounded-lg border p-3"
  />

  <input
    value={brand}
    onChange={(e) => setBrand(e.target.value)}
    placeholder="Brand"
    className="rounded-lg border p-3"
  />

  <input
    value={category}
    onChange={(e) => setCategory(e.target.value)}
    placeholder="Category"
    className="rounded-lg border p-3"
  />

  <select
    value={unit}
    onChange={(e) => setUnit(e.target.value)}
    className="rounded-lg border p-3"
  >
    <option value="Piece">Piece</option>
    <option value="Kg">Kg</option>
    <option value="Gram">Gram</option>
    <option value="Ltr">Ltr</option>
    <option value="ml">ml</option>
    <option value="Bag">Bag</option>
    <option value="Box">Box</option>
    <option value="Packet">Packet</option>
    <option value="Roll">Roll</option>
    <option value="Meter">Meter</option>
    <option value="Feet">Feet</option>
  </select>

  <input
    type="number"
    value={purchasePrice}
    onChange={(e) => setPurchasePrice(e.target.value)}
    placeholder="Purchase Price"
    className="rounded-lg border p-3"
  />

  <input
    type="number"
    value={sellingPrice}
    onChange={(e) => setSellingPrice(e.target.value)}
    placeholder="Selling Price"
    className="rounded-lg border p-3"
  />

  <input
    type="number"
    value={stock}
    onChange={(e) => setStock(e.target.value)}
    placeholder="Opening Stock"
    className="rounded-lg border p-3"
  />

  <input
    type="number"
    value={gst}
    onChange={(e) => setGst(e.target.value)}
    placeholder="GST %"
    className="rounded-lg border p-3"
  />

  <input
    value={hsn}
    onChange={(e) => setHsn(e.target.value)}
    placeholder="HSN Code"
    className="col-span-2 rounded-lg border p-3"
  />

</div>

<div className="mt-6 flex gap-3">

  <button
    onClick={onClose}
    className="flex-1 rounded-xl bg-gray-600 py-3 font-semibold text-white hover:bg-gray-500"
  >
    Cancel
  </button>

  <button
    onClick={saveProduct}
    disabled={saving}
    className="flex-1 rounded-xl bg-green-600 py-3 font-semibold text-white hover:bg-green-500 disabled:opacity-50"
  >
    {saving ? "Saving..." : "💾 Save Product"}
  </button>

</div>

      </div>
    </div>
  );
}