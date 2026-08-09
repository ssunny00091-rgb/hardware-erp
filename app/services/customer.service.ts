import { supabase } from "../lib/supabase";
import { Customer } from "../types/customer";

export class CustomerService {
  static async getAll(): Promise<Customer[]> {
    const { data, error } = await supabase
      .from("customers")
      .select("*")
      .order("customer_name");

    if (error) throw error;

    return data ?? [];
  }

  static async create(
    customer: Omit<Customer, "id" | "created_at" | "updated_at">
  ) {
    const { error } = await supabase
      .from("customers")
      .insert([customer]);

    if (error) throw error;
  }
}