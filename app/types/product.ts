export interface Product {
  id: number;

  product_name: string;

  category: string | null;

  brand: string | null;

  unit: string;

  purchase_price: number;

  selling_price: number;

  gst: number;

  stock: number;

  minimum_stock: number;

  created_at: string;
}