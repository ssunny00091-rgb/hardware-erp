type Props = {
  supplierName: string;
  invoiceNo: string;
  purchaseDate: string;

  onSupplierChange: (value: string) => void;
  onInvoiceChange: (value: string) => void;
  onDateChange: (value: string) => void;
};

export default function PurchaseForm({
  supplierName,
  invoiceNo,
  purchaseDate,
  onSupplierChange,
  onInvoiceChange,
  onDateChange,
}: Props) {
  return (
    <div className="mb-8 rounded-2xl border border-white/20 bg-white/10 p-6 backdrop-blur-xl">

      <h2 className="mb-6 text-2xl font-bold text-white">
        🛒 Purchase Details
      </h2>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">

        <input
          type="text"
          placeholder="Supplier Name"
          value={supplierName}
          onChange={(e) => onSupplierChange(e.target.value)}
          className="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder-gray-300"
        />

        <input
          type="text"
          placeholder="Invoice Number"
          value={invoiceNo}
          onChange={(e) => onInvoiceChange(e.target.value)}
          className="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder-gray-300"
        />

        <input
          type="date"
          value={purchaseDate}
          onChange={(e) => onDateChange(e.target.value)}
          className="rounded-xl border border-white/20 bg-white/10 p-3 text-white"
        />

      </div>

    </div>
  );
}