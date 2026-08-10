import { NextResponse } from "next/server";
import { supabase } from "@/app/lib/supabase";

export async function GET() {
  const { data, error } = await supabase
    .from("customers")
    .select("id, customer_name")
    .eq("status", true)
    .order("customer_name");

  if (error) {
    return NextResponse.json(
      { error: error.message },
      { status: 500 }
    );
  }

  return NextResponse.json(
    (data ?? []).map((customer) => ({
      id: customer.id,
      name: customer.customer_name,
    }))
  );
}