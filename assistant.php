<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/assistant.php';

$keySaveError = '';
$keySavedFlash = isset($_GET['key']) && $_GET['key'] === 'saved';
$keySaveFailFlash = isset($_GET['key']) && $_GET['key'] === 'fail';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['openrouter_api_key'])) {
    $provided = trim((string) $_POST['openrouter_api_key']);
    $model = trim((string) ($_POST['openrouter_model'] ?? ''));
    if ($provided === '') {
        $keySaveError = 'Pehle OpenRouter key paste karo (sk-or-v1-...).';
    } else {
        try {
            persist_openrouter_key($provided, $model);
            header('Location: ' . app_url('assistant.php?key=saved'));
            exit;
        } catch (Throwable $e) {
            $keySaveError = $e->getMessage();
        }
    }
}

$orCfg = openrouter_config();
$pageTitle = 'Assistant';
$activeNav = 'assistant';
require __DIR__ . '/includes/header.php';
$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$reminderBannerRows = reminder_banner_rows(db());
require __DIR__ . '/includes/reminder-banner.php';
?>

<div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
  <div>
    <h1 class="text-2xl font-bold sm:text-4xl">🤖 Shop Assistant</h1>
    <p class="mt-2 text-sm text-gray-300">Bolo ya type karo — sale, purchase, product, ledger, due reminder. Supplier bill ki photo/PDF daalo (kisi bhi format), supplier + bill automatic save.</p>
  </div>
  <p id="assistant-key-status" class="text-sm <?= $orCfg['api_key'] !== '' ? 'text-emerald-300' : 'text-amber-300' ?>">
    <?= $orCfg['api_key'] !== '' ? $h('OpenRouter key save hai · ' . ($orCfg['model'] ?? '')) : 'Pehle OpenRouter API key save karo' ?>
  </p>
</div>

<?php if ($keySavedFlash): ?>
  <div class="mb-4 rounded-xl bg-emerald-600 px-4 py-3 font-semibold text-white">
    OpenRouter key save ho gayi. Ab neeche se sale / bill / ledger pooch sakte ho.
  </div>
<?php endif; ?>
<?php if ($keySaveFailFlash && $keySaveError === ''): ?>
  <div class="mb-4 rounded-xl bg-rose-600 px-4 py-3 font-semibold text-white">
    Key save nahi hui. Phir se paste karke try karo.
  </div>
<?php endif; ?>

<section class="mb-4 rounded-2xl border border-white/20 bg-white/10 p-4">
  <h2 class="font-semibold">OpenRouter API key</h2>
  <p class="mt-2 text-sm text-gray-300">
    Key <a class="underline" href="https://openrouter.ai/keys" target="_blank" rel="noreferrer">openrouter.ai/keys</a> se copy karo
    (<strong>sk-or-v1-</strong>). ChatGPT key nahi chalegi.
    Pehle <strong>Key save karo</strong> — internet test baad mein.
  </p>
  <form id="or-key-form" class="mt-3" action="<?= $h(app_url('assistant.php')) ?>" method="post">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
      <input type="password" id="or-key" name="openrouter_api_key" placeholder="sk-or-v1-..." class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900 md:col-span-2" autocomplete="off">
      <input type="text" id="or-model" name="openrouter_model" placeholder="google/gemini-2.5-flash" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
    </div>
    <input type="hidden" name="skip_test" value="1">
    <div class="mt-3 flex flex-wrap gap-2">
      <button type="submit" id="btn-save-key" class="rounded-xl bg-emerald-600 px-4 py-2 font-semibold">Key save karo</button>
      <button type="button" id="btn-test-key" class="rounded-xl bg-sky-700 px-4 py-2 font-semibold">Internet se test</button>
    </div>
  </form>
  <p id="or-test-msg" class="mt-2 text-sm text-gray-300"></p>
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
    <button type="button" id="btn-sound" class="rounded-lg bg-white/15 px-3 py-2 font-semibold">🔇 Sound band</button>
  </div>
  <p id="mic-status" class="mb-2 text-sm text-amber-200"></p>
  <p id="https-mic-hint" class="mb-2 hidden rounded-lg bg-rose-800/80 px-3 py-2 text-xs text-rose-50"></p>
  <div id="chat-files" class="mb-2 hidden"></div>
  <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
    <textarea id="chat-input" rows="2" placeholder="Bolo ya likho: 'Ram ko 2 Asian paint 10 litre @ 450, 2000 due'  ·  ya bill photo attach karo" class="min-h-[52px] flex-1 rounded-xl border border-gray-300 bg-white p-3 text-gray-900"></textarea>
    <div class="flex flex-wrap gap-2">
      <label class="cursor-pointer rounded-xl bg-white/20 px-4 py-3 text-center font-semibold">
        📷 Photo
        <input type="file" id="chat-file" class="hidden" accept="image/*,.pdf,application/pdf" multiple>
      </label>
      <label class="cursor-pointer rounded-xl bg-violet-700 px-4 py-3 text-center font-semibold">
        🎙️ Voice note
        <input type="file" id="chat-voice-file" class="hidden" accept="audio/*,video/webm,video/mp4,.webm,.m4a,.mp3,.wav,.ogg,.aac" capture>
      </label>
      <button type="button" id="btn-add-more" class="hidden rounded-xl bg-sky-600 px-4 py-3 font-semibold">➕ Add more photo</button>
      <button type="button" id="btn-mic" class="rounded-xl bg-rose-600 px-4 py-3 font-semibold">🎤 Bolke</button>
      <button type="button" id="btn-send" class="rounded-xl bg-green-600 px-5 py-3 font-semibold">Send</button>
    </div>
  </div>
  <p class="mt-2 text-xs text-gray-400">
    Phone par <strong>Bolke</strong> dabao, bolo, phir <strong>Stop</strong>. Mic allow karna zaroori hai.
    Agar live mic na chale to <strong>Voice note</strong> se phone ka recorder khulega.
    <strong>Sound</strong> on karo to jawab awaaz mein aayega.
  </p>
</div>

<script src="<?= htmlspecialchars(app_url('assets/js/assistant.js'), ENT_QUOTES, 'UTF-8') ?>?v=voice3"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
