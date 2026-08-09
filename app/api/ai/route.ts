import { NextResponse } from "next/server";

export async function POST() {
  return NextResponse.json({
    reply: "Hello Sunny 👋 ERP AI is Ready."
  });
}