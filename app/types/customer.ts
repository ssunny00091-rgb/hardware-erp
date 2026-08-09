export interface Customer {
  id: number;

  customer_code: string;

  customer_name: string;

  mobile: string | null;

  email: string | null;

  gst_no: string | null;

  address: string | null;

  city: string | null;

  state: string | null;

  pincode: string | null;

  credit_limit: number;

  payment_term: number;

  opening_balance: number;

  status: boolean;

  created_at: string;

  updated_at: string;
}