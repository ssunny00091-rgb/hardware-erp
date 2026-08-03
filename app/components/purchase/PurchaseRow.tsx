import { useRef } from "react";

type PurchaseRowProps = {
  index: number;
  product: {
    name: string;
    qty: string;
    price: string;
  };
  total: number;
  onChange: (
    index: number,
    field: "name" | "qty" | "price",
    value: string
  ) => void;
  onDelete: (index: number) => void;
  onAddRow: () => void;
};

export default function PurchaseRow({
  index,
  product,
  total,
  onChange,
  onDelete,
  onAddRow,
}: PurchaseRowProps) {
  const qtyRef = useRef<HTMLInputElement>(null);
  const priceRef = useRef<HTMLInputElement>(null);

  return (
    <div className="mb-3 grid grid-cols-5 gap-3">

      {/* Product */}
      <input
        type="text"
        placeholder="Product Name"
        value={product.name}
        onChange={(e) =>
          onChange(index, "name", e.target.value)
        }
        onKeyDown={(e) => {
          if (e.key === "Enter") {
            e.preventDefault();
            qtyRef.current?.focus();
          }
        }}
        className="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder-gray-300"
      />

      {/* Qty */}
      <input
        ref={qtyRef}
        type="number"
        placeholder="Qty"
        value={product.qty}
        onChange={(e) =>
          onChange(index, "qty", e.target.value)
        }
        onKeyDown={(e) => {
          if (e.key === "Enter") {
            e.preventDefault();
            priceRef.current?.focus();
          }
        }}
        className="rounded-xl border border-white/20 bg-white/10 p-3 text-white"
      />

      {/* Purchase Price */}
      <input
        ref={priceRef}
        type="number"
        placeholder="Purchase Price"
        value={product.price}
        onChange={(e) =>
          onChange(index, "price", e.target.value)
        }
        onKeyDown={(e) => {
          if (e.key === "Enter") {
            e.preventDefault();
            onAddRow();
          }
        }}
        className="rounded-xl border border-white/20 bg-white/10 p-3 text-white"
      />

      {/* Total */}
      <div className="flex items-center justify-center rounded-xl border border-white/20 bg-white/10 font-bold text-green-400">
        ₹{total}
      </div>

      {/* Delete */}
      <button
        onClick={() => onDelete(index)}
        className="rounded-xl bg-red-500 p-3 text-white hover:bg-red-600"
      >
        🗑
      </button>

    </div>
  );
}