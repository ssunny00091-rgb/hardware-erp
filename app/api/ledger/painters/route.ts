import { NextResponse } from "next/server";
import { supabase } from "@/app/lib/supabase";

export async function GET() {
  const { data, error } = await supabase
    .from("painters")
    .select("id, painter_name")
    .order("painter_name");

  if (error) {
    return NextResponse.json(
      { error: error.message },
      { status: 500 }
    );
  }

  return NextResponse.json(
    (data ?? []).map((painter) => ({
      id: painter.id,
      name: painter.painter_name,
    }))
  );
}