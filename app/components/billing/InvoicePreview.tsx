import InvoiceLayout from "./InvoiceLayout";
import InvoiceActions from "./InvoiceActions";
type Product = {
  name: string;
  qty: string;
  unit: string;
  price: string;
};

type InvoicePreviewProps = {
  open: boolean;
  onClose: () => void;
  customerName: string;
  mobile: string;
  products: Product[];
  grandTotal: number;
  onDownload: () => void;
  onSave: () => void;
  invoiceNo?: string;
  billDate?: string;
  saving?: boolean;
};

export default function InvoicePreview({
  open,
  onClose,
  customerName,
  mobile,
  products,
  grandTotal,
  onDownload,
  onSave,
  invoiceNo,
  billDate,
}: InvoicePreviewProps) {
  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <div className="flex max-h-[95vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
        {/* Header */}
        <div className="flex items-center justify-between border-b bg-white px-6 py-4">
          <h2 className="text-2xl font-bold text-blue-700">
            Invoice Preview
          </h2>

          <button
            onClick={onClose}
            className="rounded-lg bg-red-500 px-4 py-2 text-white hover:bg-red-600"
          >
            ✕ Close
          </button>
        </div>

        {/* Invoice */}
        <div className="flex-1 overflow-y-auto bg-gray-100 p-6">
          <InvoiceLayout
            customerName={customerName}
            mobile={mobile}
            products={products}
            grandTotal={grandTotal}
            invoiceNo={invoiceNo}
            billDate={billDate}
          />
        </div>

        {/* Bottom Buttons */}
       <InvoiceActions
  onEdit={onClose}
  onDownload={onDownload}
  onPrint={() => window.print()}
  onSave={onSave}
/>
      </div>
    </div>
  );
}
