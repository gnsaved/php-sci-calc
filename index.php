<?php
session_start();

require_once __DIR__ . '/src/Calculator.php';
require_once __DIR__ . '/src/History.php';

use App\Core\Calculator;
use App\Storage\History;

$calc = new Calculator();
$history = new History(__DIR__ . '/database/calculator.db');

$display = '';
$err = '';
$mode = $_POST['mode'] ?? 'scientific';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['clear_history'])) {
        $history->clear();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $expr = trim($_POST['expression'] ?? '');
    
    if ($expr === '') {
        $display = '0';
    } elseif (strlen($expr) > 500) {
        $err = 'Expression too long';
        $display = substr($expr, 0, 50) . '...';
    } else {
        $result = $calc->compute($expr);
        
        if ($result['ok']) {
            $display = $result['value'];
            $history->save($expr, $result['value'], $mode);
        } else {
            $err = $result['error'];
            $display = $expr;
        }
    }
}

$historyList = $history->recent(12);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scientific Calculator</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/main.css">
</head>
<body>
    <main class="app">
        <section class="calc-section">
            <h1 id="calcTitle">Scientific Calculator</h1>
            
            <div class="calculator">
                <div class="screen">
                    <input type="text" id="display" value="<?= htmlspecialchars($display ?: '0') ?>" readonly>
                    <?php if ($err): ?>
                        <p class="err"><?= htmlspecialchars($err) ?></p>
                    <?php endif; ?>
                </div>

                <div class="mode-switch">
                    <button type="button" id="btnBasic" class="<?= $mode === 'basic' ? 'on' : '' ?>">Basic</button>
                    <button type="button" id="btnSci" class="<?= $mode === 'scientific' ? 'on' : '' ?>">Scientific</button>
                </div>

                <form method="post" id="calcForm">
                    <input type="hidden" name="expression" id="exprField">
                    <input type="hidden" name="mode" id="modeField" value="<?= $mode ?>">

                    <div id="sciKeys" class="<?= $mode === 'basic' ? 'hide' : '' ?>">
                        <div class="keyrow">
                            <button type="button" class="k fn" data-v="sin(">sin</button>
                            <button type="button" class="k fn" data-v="cos(">cos</button>
                            <button type="button" class="k fn" data-v="tan(">tan</button>
                            <button type="button" class="k fn" data-v="log(">log</button>
                            <button type="button" class="k fn" data-v="ln(">ln</button>
                        </div>
                        <div class="keyrow">
                            <button type="button" class="k fn" data-v="asin(">sin⁻¹</button>
                            <button type="button" class="k fn" data-v="acos(">cos⁻¹</button>
                            <button type="button" class="k fn" data-v="atan(">tan⁻¹</button>
                            <button type="button" class="k fn" data-v="sqrt(">√</button>
                            <button type="button" class="k fn" data-v="^">xʸ</button>
                        </div>
                        <div class="keyrow">
                            <button type="button" class="k fn" data-v="pi">π</button>
                            <button type="button" class="k fn" data-v="e">e</button>
                            <button type="button" class="k fn" data-v="(">(</button>
                            <button type="button" class="k fn" data-v=")">)</button>
                            <button type="button" class="k fn" data-v="!">n!</button>
                        </div>
                    </div>

                    <div class="mainkeys">
                        <div class="keyrow">
                            <button type="button" class="k dark" data-a="clear">C</button>
                            <button type="button" class="k dark" data-a="back">⌫</button>
                            <button type="button" class="k op" data-v="%">%</button>
                            <button type="button" class="k op" data-v="/">÷</button>
                        </div>
                        <div class="keyrow">
                            <button type="button" class="k" data-v="7">7</button>
                            <button type="button" class="k" data-v="8">8</button>
                            <button type="button" class="k" data-v="9">9</button>
                            <button type="button" class="k op" data-v="*">×</button>
                        </div>
                        <div class="keyrow">
                            <button type="button" class="k" data-v="4">4</button>
                            <button type="button" class="k" data-v="5">5</button>
                            <button type="button" class="k" data-v="6">6</button>
                            <button type="button" class="k op" data-v="-">−</button>
                        </div>
                        <div class="keyrow">
                            <button type="button" class="k" data-v="1">1</button>
                            <button type="button" class="k" data-v="2">2</button>
                            <button type="button" class="k" data-v="3">3</button>
                            <button type="button" class="k op" data-v="+">+</button>
                        </div>
                        <div class="keyrow">
                            <button type="button" class="k" data-v="00">00</button>
                            <button type="button" class="k" data-v="0">0</button>
                            <button type="button" class="k" data-v=".">.</button>
                            <button type="button" class="k eq" data-a="submit">=</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <aside class="history-section">
            <header>
                <h2>History</h2>
                <form method="post">
                    <button type="submit" name="clear_history" value="1">Clear</button>
                </form>
            </header>
            
            <?php if (empty($historyList)): ?>
                <p class="no-history">Nothing here yet</p>
            <?php else: ?>
                <ul>
                <?php foreach ($historyList as $row): ?>
                    <li data-expr="<?= htmlspecialchars($row['expression']) ?>">
                        <code><?= htmlspecialchars($row['expression']) ?></code>
                        <span>= <?= htmlspecialchars($row['result']) ?></span>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </aside>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
