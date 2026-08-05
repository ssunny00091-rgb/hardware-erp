type Props = {
  customerName: string;
  mobile: string;
  invoiceNo: string;
  billDate: string;
  onCustomerChange: (value: string) => void;
  onMobileChange: (value: string) => void;
  onInvoiceChange: (value: string) => void;
  onDateChange: (value: string) => void;
};

export default function BillingForm({ customerName, mobile, invoiceNo, billDate, onCustomerChange, onMobileChange, onInvoiceChange, onDateChange }: Props) {
  const fieldClass = "mt-2 w-full rounded-xl border border-sky-300/25 bg-slate-950/50 p-3 font-semibold text-white shadow-inner outline-none placeholder:text-slate-400 focus:border-sky-300 focus:ring-2 focus:ring-sky-300/20";
  return <section className="rounded-3xl border border-sky-300/20 bg-gradient-to-br from-sky-500/15 via-white/10 to-violet-500/15 p-5 shadow-2xl backdrop-blur-xl sm:p-6"><div className="mb-5 flex items-center gap-3"><span className="grid h-10 w-10 place-items-center rounded-xl bg-sky-400/20 text-xl text-sky-200">★</span><div><h2 className="text-xl font-bold text-white">Customer & invoice</h2><p className="text-sm text-sky-100">Highlighted customer details</p></div></div><div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><label className="rounded-2xl bg-sky-400/10 p-3 text-sm font-bold text-sky-100">Customer name<input value={customerName} onChange={(event) => onCustomerChange(event.target.value)} placeholder="Enter customer name" className={fieldClass} /></label><label className="rounded-2xl bg-violet-400/10 p-3 text-sm font-bold text-violet-100">Mobile number<input type="tel" value={mobile} onChange={(event) => onMobileChange(event.target.value)} placeholder="Enter mobile number" className={fieldClass} /></label><label className="rounded-2xl bg-amber-400/10 p-3 text-sm font-bold text-amber-100">Invoice number<input value={invoiceNo} onChange={(event) => onInvoiceChange(event.target.value)} placeholder="Invoice number" className={fieldClass} /></label><label className="rounded-2xl bg-emerald-400/10 p-3 text-sm font-bold text-emerald-100">Bill date<input type="date" value={billDate} onChange={(event) => onDateChange(event.target.value)} className={fieldClass} /></label></div></section>;
}
