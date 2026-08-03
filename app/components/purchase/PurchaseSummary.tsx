type Props = {
  grandTotal: number;
  onAddRow: () => void;
  onSave: () => void;
};

export default function PurchaseSummary({
  grandTotal,
  onAddRow,
  onSave,
}: Props) {
  return (
    <div className="mt-8 rounded-2xl border border-white/20 bg-white/10 p-6 backdrop-blur-xl">

      <div className="mb-6 flex items-center justify-between">

        <h2 className="text-2xl font-bold text-white">
          💰 Purchase Summary
        </h2>

        <div className="text-3xl font-bold text-green-400">
          ₹ {grandTotal}
        </div>

      </div>

      <div className="flex gap-4">

        <button
          onClick={onAddRow}
          className="flex-1 rounded-xl bg-blue-600 py-3 text-lg font-semibold text-white transition hover:bg-blue-500"
        >
          ➕ Add Product
        </button>

        <button
          onClick={onSave}
          className="flex-1 rounded-xl bg-green-600 py-3 text-lg font-semibold text-white transition hover:bg-green-500"
        >
          💾 Save Purchase
        </button>

      </div>

    </div>
  );
}