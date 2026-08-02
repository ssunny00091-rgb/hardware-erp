"use client";

import { useEffect, useState } from "react";
import { supabase } from "../../lib/supabase";

type Product = {
  id: number;
  product_name: string;
  brand: string;
  category: string;
  unit: string;
  purchase_price: number;
  selling_price: number;
  stock: number;
  gst_percent: number;
  hsn_code: string;
};

type Props = {
  open: boolean;
  product: Product | null;
  onClose: () => void;
  onSaved: () => void;
};

export default function EditProductModal({
  open,
  product,
  onClose,
  onSaved,
}: Props) {
  const [form, setForm] = useState<Product | null>(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (product) {
      setForm(product);
    }
  }, [product]);

  if (!open || !form) return null;

  const updateField = (field: keyof Product, value: any) => {
    setForm((prev) =>
      prev
        ? {
            ...prev,
            [field]: value,
          }
        : null
    );
  };

  const updateProduct = async () => {
    setSaving(true);

    const { error } = await supabase
      .from("products")
      .update({
        product_name: form.product_name,
        brand: form.brand,
        category: form.category,
        unit: form.unit,
        purchase_price: Number(form.purchase_price),
        selling_price: Number(form.selling_price),
        stock: Number(form.stock),
        gst_percent: Number(form.gst_percent),
        hsn_code: form.hsn_code,
      })
      .eq("id", form.id);

    setSaving(false);

    if (error) {
      alert(error.message);
      return;
    }

    alert("✅ Product Updated");

    onSaved();
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60">

      <div className="w-full max-w-2xl rounded-2xl bg-slate-900 p-8">

        <div className="mb-6 flex justify-between">

          <h2 className="text-3xl font-bold text-white">
            ✏️ Edit Product
          </h2>

          <button
            onClick={onClose}
            className="rounded bg-red-500 px-4 py-2 text-white"
          >
            ✕
          </button>

        </div>

        <div className="grid grid-cols-2 gap-4">

          <input
            value={form.product_name}
            onChange={(e) =>
              updateField("product_name", e.target.value)
            }
            className="rounded border p-3"
          />

          <input
            value={form.brand}
            onChange={(e) =>
              updateField("brand", e.target.value)
            }
            className="rounded border p-3"
          />

          <input
            value={form.category}
            onChange={(e) =>
              updateField("category", e.target.value)
            }
            className="rounded border p-3"
          />

          <input
            value={form.unit}
            onChange={(e) =>
              updateField("unit", e.target.value)
            }
            className="rounded border p-3"
          />

          <input
            type="number"
            value={form.purchase_price}
            onChange={(e) =>
              updateField("purchase_price", e.target.value)
            }
            className="rounded border p-3"
          />

          <input
            type="number"
            value={form.selling_price}
            onChange={(e) =>
              updateField("selling_price", e.target.value)
            }
            className="rounded border p-3"
          />

          <input
            type="number"
            value={form.stock}
            onChange={(e) =>
              updateField("stock", e.target.value)
            }
            className="rounded border p-3"
          />

          <input
            type="number"
            value={form.gst_percent}
            onChange={(e) =>
              updateField("gst_percent", e.target.value)
            }
            className="rounded border p-3"
          />

          <input
            value={form.hsn_code}
            onChange={(e) =>
              updateField("hsn_code", e.target.value)
            }
            className="col-span-2 rounded border p-3"
          />

        </div>

        <button
          onClick={updateProduct}
          disabled={saving}
          className="mt-6 w-full rounded-xl bg-green-600 py-3 text-white"
        >
          {saving ? "Updating..." : "💾 Update Product"}
        </button>

      </div>

    </div>
  );
}