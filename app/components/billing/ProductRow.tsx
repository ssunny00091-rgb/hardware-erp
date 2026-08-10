"use client";

import { useRef } from "react";
import ProductSearch from "./ProductSearch";

type ProductRowProps = {
  index: number;

  product: {
    name: string;
    qty: string;
    unit: string;
    price: string;
  };

  products: {
    id: number;
    product_name: string;
    selling_price: number;
    purchase_price: number;
    unit: string;
    stock: number;
  }[];

  productInputRef?: React.Ref<HTMLInputElement>;

  onChange: (
    index: number,
    field: "name" | "qty" | "unit" | "price",
    value: string
  ) => void;

  total: number;

  onDelete: (index: number) => void;

  onAddNewRow: () => void;
  onAddNewProduct: (productName: string) => void;
};

export default function ProductRow({
  index,
  product,
  products,
  productInputRef,
  onChange,
  total,
  onDelete,
  onAddNewRow,
  onAddNewProduct,
}: ProductRowProps) {
  const qtyRef = useRef<HTMLInputElement>(null);
  const priceRef = useRef<HTMLInputElement>(null);
  

  return (
    <div className="mb-3 grid grid-cols-1 gap-3 rounded-xl border border-white/10 bg-white/5 p-3 md:grid-cols-[minmax(0,2fr)_90px_110px_120px_50px]">

      {/* Product */}
      <ProductSearch
        ref={productInputRef}
        value={product.name}
        products={products}
        priceType="selling"
        onChange={(value) =>
          onChange(index, "name", value)
        }
        onSelect={(name, price, unit) => {
          onChange(index, "name", name);
          onChange(index, "price", String(price));
          onChange(index, "unit", unit);

          window.setTimeout(() => {
            qtyRef.current?.focus();
          }, 0);
        }}
        onAddNewProduct={() => {
  onAddNewProduct(product.name);
}}
      />

      {/* Quantity */}
      <input
        ref={qtyRef}
        type="number"
        min="1"
        value={product.qty}
        placeholder="Qty"
        className="w-full rounded-lg border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300"
        onChange={(e) =>
          onChange(index, "qty", e.target.value)
        }
        onKeyDown={(e) => {
          if (e.key === "Enter") {
            e.preventDefault();
            priceRef.current?.focus();
          }
        }}
      />

      {/* Price */}
      <input
        ref={priceRef}
        type="number"
        min="0"
        value={product.price}
        placeholder="Price"
        className="w-full rounded-lg border border-white/20 bg-white/10 p-3 text-white placeholder:text-slate-300"
        onChange={(e) =>
          onChange(index, "price", e.target.value)
        }
        onKeyDown={(e) => {
          if (e.key === "Enter") {
            e.preventDefault();
            onAddNewRow();
          }
        }}
      />

      {/* Total */}
      <div className="flex items-center font-semibold text-green-400">
        ₹{total.toLocaleString("en-IN")}
      </div>

      {/* Delete */}
      <button
        type="button"
        onClick={() => onDelete(index)}
        className="rounded-lg bg-red-500 px-3 py-2 text-white hover:bg-red-600"
      >
        🗑️
      </button>
    </div>
  );
}