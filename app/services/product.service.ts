import { supabase } from "../lib/supabase";
import { Product } from "../types/product";
import { ProductFormData } from "../lib/validations/product";

export class ProductService {
  static async getAll(): Promise<Product[]> {
    const { data, error } = await supabase
      .from("products")
      .select("*")
      .order("product_name");

    if (error) throw error;

    return data ?? [];
  }

  static async create(product: ProductFormData) {
    const { error } = await supabase
      .from("products")
      .insert([product]);

    if (error) throw error;
  }
}