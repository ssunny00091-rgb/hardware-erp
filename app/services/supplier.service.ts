import { supabase } from "../lib/supabase";
import { Supplier } from "../types/supplier";

export class SupplierService {
  static async getAll(): Promise<Supplier[]> {
    const { data, error } = await supabase
      .from("suppliers")
      .select("*")
      .order("supplier_name");

    if (error) {
      throw error;
    }

    return data ?? [];
  }

  static async create(
    supplier: {
      supplier_name: string;
      mobile?: string;
      address?: string;
      gst_no?: string;
      opening_balance: number;
    }
  ) {
    const { error } = await supabase
      .from("suppliers")
      .insert([
        {
          supplier_name: supplier.supplier_name,
          mobile: supplier.mobile || null,
          address: supplier.address || null,
          gst_no: supplier.gst_no || null,
          opening_balance: supplier.opening_balance,
        },
      ]);

    if (error) {
      throw error;
    }
  }

  static async update(
    id: number,
    supplier: Partial<{
      supplier_name: string;
      mobile: string | null;
      address: string | null;
      gst_no: string | null;
      opening_balance: number;
    }>
  ) {
    const { error } = await supabase
      .from("suppliers")
      .update(supplier)
      .eq("id", id);

    if (error) {
      throw error;
    }
  }

  static async delete(id: number) {
    const { error } = await supabase
      .from("suppliers")
      .delete()
      .eq("id", id);

    if (error) {
      throw error;
    }
  }
}