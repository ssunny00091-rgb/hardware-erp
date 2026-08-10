import { NextResponse } from "next/server";
import { supabase } from "@/app/lib/supabase";

export async function GET() {
  const { data, error } = await supabase
    .from("suppliers")
    .select("id, supplier_name")
    .order("supplier_name");

  if (error) {
    return NextResponse.json(
      { error: error.message },
      { status: 500 }
    );
  }

  return NextResponse.json(
    (data ?? []).map((supplier) => ({
      id: supplier.id,
      name: supplier.supplier_name,
    }))
  );
}