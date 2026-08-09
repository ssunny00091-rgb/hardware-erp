export interface Supplier {
  id: number;

  supplier_name: string;

  mobile: string | null;

  address: string | null;

  gst_no: string | null;

  opening_balance: number;

  created_at: string;
}