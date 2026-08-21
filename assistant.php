<?php

declare(strict_types=1);

$pageTitle = 'Assistant';
$activeNav = 'assistant';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
  <div>
    <h1 class="text-2xl font-bold sm:text-4xl">🤖 Shop Assistant</h1>
    <p class="mt-2 text-sm text-gray-300">Bolo ya type karo — sale, purchase, product, ledger. Supplier bill ki photo/PDF daalo (kisi bhi format), supplier + bill automatic save.</p>
  </div>
  <p id="assistant-key-status" class="text-sm text-amber-300">OpenRouter key check ho rahi hai…</p>
</div>

<section class="mb-4 rounded-2xl border border-white/20 bg-white/10 p-4">
  <details>
    <summary class="cursor-pointer font-semibold">OpenRouter API key</summary>
    <p class="mt-2 text-sm text-gray-300">Key <a class="underline" href="https://openrouter.ai/keys" target="_blank" rel="noreferrer">openrouter.ai/keys</a> se lo. Sirf shop computer pe <code>.env</code> mein save hoti hai.</p>
    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
      <input type="password" id="or-key" placeholder="sk-or-v1-..." class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900 md:col-span-2">
      <input type="text" id="or-model" placeholder="google/gemini-2.5-flash" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
    </div>
    <button type="button" id="btn-save-key" class="mt-3 rounded-xl bg-emerald-600 px-4 py-2 font-semibold">Save key</button>
  </details>
</section>

<div id="chat-log" class="chat-log mb-4 flex flex-col gap-3 overflow-y-auto rounded-2xl border border-white/20 bg-black/30 p-3 sm:p-4"></div>

<div class="rounded-2xl border border-white/20 bg-white/10 p-3">
  <div class="mb-3 flex flex-wrap items-center gap-3 text-sm">
    <span class="text-gray-300">Bolne ki bhasha:</span>
    <label class="flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2">
      <input type="radio" name="voice-lang" value="hi-IN"> हिंदी
    </label>
    <label class="flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2">
      <input type="radio" name="voice-lang" value="en-IN"> Hinglish
    </label>
  </div>
  <div id="chat-files" class="mb-2 hidden"></div>
  <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
    <textarea id="chat-input" rows="2" placeholder="Bolo ya likho: 'Ram ko 2 Asian paint 10 litre @ 450, 2000 due'  ·  ya bill photo attach karo" class="min-h-[52px] flex-1 rounded-xl border border-gray-300 bg-white p-3 text-gray-900"></textarea>
    <div class="flex flex-wrap gap-2">
      <label class="cursor-pointer rounded-xl bg-white/20 px-4 py-3 text-center font-semibold">
        📷 Photo
        <input type="file" id="chat-file" class="hidden" accept="image/*,.pdf,application/pdf" multiple>
      </label>
      <button type="button" id="btn-add-more" class="hidden rounded-xl bg-sky-600 px-4 py-3 font-semibold">➕ Add more photo</button>
      <button type="button" id="btn-mic" class="rounded-xl bg-rose-600 px-4 py-3 font-semibold">🎤 Bolke</button>
      <button type="button" id="btn-send" class="rounded-xl bg-green-600 px-5 py-3 font-semibold">Send</button>
    </div>
  </div>
  <p class="mt-2 text-xs text-gray-400">2+ page ka bill ho to pehli photo ke baad <strong>Add more photo</strong> dabao — saari pages ek hi bill mein save hongi. Mic ~3 second wait. Jawab bina awaaz ke text mein aata hai.</p>
</div>

<script src="<?= htmlspecialchars(app_url('assets/js/assistant.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
