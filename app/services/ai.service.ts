import { AIMessage } from "@/app/types/ai";

export class AIService {
  static async sendMessage(
    messages: AIMessage[]
  ) {
    const response = await fetch("/api/ai", {
      method: "POST",

      headers: {
        "Content-Type": "application/json",
      },

      body: JSON.stringify({
        messages,
      }),
    });

    if (!response.ok) {
      throw new Error("AI Request Failed");
    }

    return response.json();
  }
}