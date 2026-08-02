type InvoiceActionsProps = {
  onEdit: () => void;
  onDownload: () => void;
  onPrint: () => void;
  onSave: () => void;
};

export default function InvoiceActions({
  onEdit,
  onDownload,
  onPrint,
  onSave,
}: InvoiceActionsProps) {
  return (
    <div className="sticky bottom-0 flex flex-wrap justify-center gap-4 border-t bg-white p-4">

      <button
        onClick={onEdit}
        className="rounded-lg bg-gray-600 px-5 py-2 text-white hover:bg-gray-700"
      >
        ✏️ Edit
      </button>

      <button
        onClick={onDownload}
        className="rounded-lg bg-green-600 px-5 py-2 text-white hover:bg-green-700"
      >
        📄 Download PDF
      </button>

      <button
  onClick={() => alert("Print setup is under development")}
  className="rounded-lg bg-blue-600 px-5 py-2 text-white hover:bg-blue-700"
>
  🖨️ Print
</button>

      <button
        onClick={onSave}
        className="rounded-lg bg-purple-600 px-5 py-2 text-white hover:bg-purple-700"
      >
        💾 Save Sale
      </button>

    </div>
  );
}