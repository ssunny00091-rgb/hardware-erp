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
};

export default function PurchaseRow({
  index,
  product,
  total,
  onChange,
  onDelete,
}: PurchaseRowProps) {
  return (
    <div className="mb-3 grid grid-cols-5 gap-3">

      <input
        type="text"
        placeholder="Product Name"
        value={product.name}
        onChange={(e) =>
          onChange(index, "name", e.target.value)
        }
        className="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder-gray-300"
      />

      <input
        type="number"
        placeholder="Qty"
        value={product.qty}
        onChange={(e) =>
          onChange(index, "qty", e.target.value)
        }
        className="rounded-xl border border-white/20 bg-white/10 p-3 text-white"
      />

      <input
        type="number"
        placeholder="Purchase Price"
        value={product.price}
        onChange={(e) =>
          onChange(index, "price", e.target.value)
        }
        className="rounded-xl border border-white/20 bg-white/10 p-3 text-white"
      />

      <div className="flex items-center justify-center rounded-xl border border-white/20 bg-white/10 font-bold text-green-400">
        ₹{total}
      </div>

      <button
        onClick={() => onDelete(index)}
        className="rounded-xl bg-red-500 p-3 text-white hover:bg-red-600"
      >
        🗑
      </button>

    </div>
  );
}