<div id="calc-icon" title="Calculator" onclick="toggleCalc()">🧮</div>

<div id="calc-overlay" class="calc-hidden">
  <div id="calc-header">
    <span>🧮 Calculator</span>
    <button onclick="toggleCalc()" title="Band karo">✕</button>
  </div>
  <div id="calc-display">0</div>
  <div id="calc-buttons">
    <button class="calc-btn calc-clear" onclick="calcClear()">C</button>
    <button class="calc-btn calc-op" onclick="calcInput('backspace')">⌫</button>
    <button class="calc-btn calc-op" onclick="calcInput('%')">%</button>
    <button class="calc-btn calc-op" onclick="calcInput('/')">÷</button>

    <button class="calc-btn" onclick="calcInput('7')">7</button>
    <button class="calc-btn" onclick="calcInput('8')">8</button>
    <button class="calc-btn" onclick="calcInput('9')">9</button>
    <button class="calc-btn calc-op" onclick="calcInput('*')">×</button>

    <button class="calc-btn" onclick="calcInput('4')">4</button>
    <button class="calc-btn" onclick="calcInput('5')">5</button>
    <button class="calc-btn" onclick="calcInput('6')">6</button>
    <button class="calc-btn calc-op" onclick="calcInput('-')">−</button>

    <button class="calc-btn" onclick="calcInput('1')">1</button>
    <button class="calc-btn" onclick="calcInput('2')">2</button>
    <button class="calc-btn" onclick="calcInput('3')">3</button>
    <button class="calc-btn calc-op" onclick="calcInput('+')">+</button>

    <button class="calc-btn calc-zero" onclick="calcInput('0')">0</button>
    <button class="calc-btn" onclick="calcInput('.')">.</button>
    <button class="calc-btn calc-eq" onclick="calcEquals()">=</button>
  </div>
</div>

<style>
  #calc-icon {
    position: fixed; bottom: 20px; right: 20px; z-index: 9999;
    width: 52px; height: 52px; border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; cursor: pointer;
    box-shadow: 0 4px 15px rgba(37,99,235,0.4);
    transition: transform 0.2s, box-shadow 0.2s;
    user-select: none;
  }
  #calc-icon:hover { transform: scale(1.1); box-shadow: 0 6px 20px rgba(37,99,235,0.6); }

  #calc-overlay {
    position: fixed; z-index: 10000;
    width: 280px; border-radius: 16px;
    background: #1e293b; border: 1px solid rgba(255,255,255,0.15);
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    font-family: 'Segoe UI', sans-serif;
    overflow: hidden;
  }
  #calc-overlay.calc-hidden { display: none; }

  #calc-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; background: #0f172a; cursor: move;
    font-size: 14px; font-weight: 600; color: #e2e8f0;
  }
  #calc-header button {
    background: none; border: none; color: #94a3b8; font-size: 18px;
    cursor: pointer; padding: 0 4px; line-height: 1;
  }
  #calc-header button:hover { color: #fff; }

  #calc-display {
    padding: 16px 14px; text-align: right; font-size: 28px;
    font-weight: bold; color: #f1f5f9; background: #0f172a;
    min-height: 60px; word-break: break-all; line-height: 1.2;
  }

  #calc-buttons {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;
    padding: 10px;
  }
  .calc-btn {
    padding: 14px 0; font-size: 18px; font-weight: 600;
    border: none; border-radius: 10px; cursor: pointer;
    background: #334155; color: #e2e8f0;
    transition: background 0.15s;
  }
  .calc-btn:hover { background: #475569; }
  .calc-btn:active { background: #64748b; }
  .calc-op { background: #1e40af; color: #93c5fd; }
  .calc-op:hover { background: #1d4ed8; }
  .calc-clear { background: #991b1b; color: #fca5a5; }
  .calc-clear:hover { background: #b91c1c; }
  .calc-eq { background: #16a34a; color: #fff; }
  .calc-eq:hover { background: #15803d; }
  .calc-zero { grid-column: span 2; }
</style>

<script>
(function() {
  let calcExpr = '';
  let calcDisplay = '0';
  let calcJustEval = false;
  const displayEl = document.getElementById('calc-display');

  function updateDisplay() {
    let show = calcDisplay;
    if (show.length > 14) show = show.slice(0, 14) + '…';
    displayEl.textContent = show;
  }

  window.toggleCalc = function() {
    const ov = document.getElementById('calc-overlay');
    ov.classList.toggle('calc-hidden');
    if (!ov.classList.contains('calc-hidden')) {
      const defX = window.innerWidth - 310;
      const defY = window.innerHeight - 440;
      ov.style.left = Math.max(20, defX) + 'px';
      ov.style.top = Math.max(60, defY) + 'px';
    }
  };

  window.calcClear = function() {
    calcExpr = '';
    calcDisplay = '0';
    calcJustEval = false;
    updateDisplay();
  };

  window.calcInput = function(val) {
    if (val === 'backspace') {
      if (calcDisplay.length > 1) {
        calcDisplay = calcDisplay.slice(0, -1);
      } else {
        calcDisplay = '0';
      }
      updateDisplay();
      return;
    }
    if ('+-*/%'.includes(val)) {
      if (calcJustEval) {
        calcExpr = calcDisplay;
        calcJustEval = false;
      } else if (calcDisplay !== '0' && calcExpr === '') {
        calcExpr = calcDisplay;
      }
      if (calcExpr && !'+-*/%'.includes(calcExpr.slice(-1))) {
        calcExpr += val;
      } else if (calcExpr) {
        calcExpr = calcExpr.slice(0, -1) + val;
      }
      calcDisplay = '0';
      return;
    }
    if (calcJustEval) {
      calcDisplay = '0';
      calcExpr = '';
      calcJustEval = false;
    }
    if (calcDisplay === '0' && val !== '.') {
      calcDisplay = val;
    } else {
      if (val === '.' && calcDisplay.includes('.')) return;
      calcDisplay += val;
    }
    updateDisplay();
  };

  window.calcEquals = function() {
    let full = (calcExpr + calcDisplay).trim();
    if (!full) return;
    try {
      let result = Function('"use strict"; return (' + full + ')')();
      if (!isFinite(result)) {
        calcDisplay = 'Error';
      } else {
        calcDisplay = String(parseFloat(result.toFixed(10)));
      }
    } catch(e) {
      calcDisplay = 'Error';
    }
    calcExpr = '';
    calcJustEval = true;
    updateDisplay();
  };

  // Dragging
  const overlay = document.getElementById('calc-overlay');
  const header = document.getElementById('calc-header');
  let dragging = false, dragX = 0, dragY = 0;

  header.addEventListener('mousedown', function(e) {
    dragging = true;
    dragX = e.clientX - overlay.offsetLeft;
    dragY = e.clientY - overlay.offsetTop;
    e.preventDefault();
  });
  document.addEventListener('mousemove', function(e) {
    if (!dragging) return;
    let nx = e.clientX - dragX;
    let ny = e.clientY - dragY;
    nx = Math.max(0, Math.min(window.innerWidth - 50, nx));
    ny = Math.max(0, Math.min(window.innerHeight - 50, ny));
    overlay.style.left = nx + 'px';
    overlay.style.top = ny + 'px';
  });
  document.addEventListener('mouseup', function() { dragging = false; });

  // Keyboard support
  document.addEventListener('keydown', function(e) {
    if (document.getElementById('calc-overlay').classList.contains('calc-hidden')) return;
    const k = e.key;
    if (k >= '0' && k <= '9') { window.calcInput(k); e.preventDefault(); }
    else if (k === '.') { window.calcInput('.'); e.preventDefault(); }
    else if (k === '+') { window.calcInput('+'); e.preventDefault(); }
    else if (k === '-') { window.calcInput('-'); e.preventDefault(); }
    else if (k === '*') { window.calcInput('*'); e.preventDefault(); }
    else if (k === '/') { window.calcInput('/'); e.preventDefault(); }
    else if (k === '%') { window.calcInput('%'); e.preventDefault(); }
    else if (k === 'Enter' || k === '=') { window.calcEquals(); e.preventDefault(); }
    else if (k === 'Backspace') { window.calcInput('backspace'); e.preventDefault(); }
    else if (k === 'Escape' || k === 'c' || k === 'C') { window.calcClear(); e.preventDefault(); }
  });
})();
</script>
