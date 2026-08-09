export interface Product {
  id: number;

  product_name: string;

  category: string | null;

  brand: string | null;

  unit: string;

  purchase_price: number;

  selling_price: number;

  stock: number;

  minimum_stock: number;

  hsn_code: string | null;

  gst_percent: number;

  created_at: string;
}