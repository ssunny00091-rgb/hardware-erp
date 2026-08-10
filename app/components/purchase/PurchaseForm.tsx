"use client";

type Supplier = {
  id: number;
  supplier_name: string;
};

type Props = {
  supplierName: string;
  invoiceNo: string;
  purchaseDate: string;

  suppliers: Supplier[];

  onSupplierChange: (value: string) => void;
  onInvoiceChange: (value: string) => void;
  onDateChange: (value: string) => void;
};

export default function PurchaseForm({
  supplierName,
  invoiceNo,
  purchaseDate,
  suppliers,
  onSupplierChange,
  onInvoiceChange,
  onDateChange,
}: Props) {
  return (
    <section className="rounded-3xl border border-white/10 bg-white/5 p-5 shadow-xl backdrop-blur-xl">
      <h2 className="mb-6 text-2xl font-bold text-white">
        🛒 Purchase Details
      </h2>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">

        {/* Supplier */}
        <select
          value={supplierName}
          onChange={(e) =>
            onSupplierChange(e.target.value)
          }
          className="rounded-xl border border-white/20 bg-slate-800 p-3 text-white"
        >
          <option value="">
            Select Supplier
          </option>

          {suppliers.map((supplier) => (
            <option
              key={supplier.id}
              value={supplier.supplier_name}
            >
              {supplier.supplier_name}
            </option>
          ))}
        </select>

        {/* Invoice */}
        <input
          type="text"
          placeholder="Invoice Number"
          value={invoiceNo}
          onChange={(e) =>
            onInvoiceChange(e.target.value)
          }
          className="rounded-xl border border-white/20 bg-white/10 p-3 text-white placeholder-gray-300"
        />

        {/* Date */}
        <input
          type="date"
          value={purchaseDate}
          onChange={(e) =>
            onDateChange(e.target.value)
          }
          className="rounded-xl border border-white/20 bg-white/10 p-3 text-white"
        />

      </div>
    </section>
  );
}