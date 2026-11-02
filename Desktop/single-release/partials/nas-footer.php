<?php

declare(strict_types=1);

$nasBrand = $nasBrand ?? 'sw';
?>
<footer class="nas-footer">
    <div class="nas-footer__brand">
        <?php if ($nasBrand === 'qdi'): ?>
            <?php
            $qdiSize = 'footer';
            $qdiInverse = true;
            require __DIR__ . '/qdi-lockup.php';
            ?>
        <?php else: ?>
            <?php
            $brandSize = 'footer';
            $brandInverse = true;
            require __DIR__ . '/brand-lockup.php';
            $brandInverse = false;
            ?>
        <?php endif; ?>
    </div>
    <ul class="nas-social" aria-label="وسائل التواصل">
        <li>
            <a href="https://www.linkedin.com" rel="noopener noreferrer" target="_blank" aria-label="LinkedIn">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.5 9.5H4V20h2.5V9.5ZM5.25 4A1.75 1.75 0 1 0 5.26 7.5 1.75 1.75 0 0 0 5.25 4ZM20 20h-2.5v-5.6c0-1.9-.7-3.2-2.4-3.2-1.3 0-2 0.9-2.3 1.7-.1.3-.1.7-.1 1.1V20H10.2s.04-8.9 0-9.8H12.7v1.4c.4-.6 1.4-1.7 3.5-1.7 2.6 0 4.5 1.7 4.5 5.3V20Z"/></svg>
            </a>
        </li>
        <li>
            <a href="https://www.youtube.com" rel="noopener noreferrer" target="_blank" aria-label="YouTube">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M23 12.2s0-3.2-.4-4.6c-.2-.9-.9-1.6-1.8-1.8C18.9 5.4 12 5.4 12 5.4s-6.9 0-8.8.4c-.9.2-1.6.9-1.8 1.8C1 9 1 12.2 1 12.2s0 3.2.4 4.6c.2.9.9 1.6 1.8 1.8 1.9.4 8.8.4 8.8.4s6.9 0 8.8-.4c.9-.2 1.6-.9 1.8-1.8.4-1.4.4-4.6.4-4.6ZM9.8 15.5V8.9l6.4 3.3-6.4 3.3Z"/></svg>
            </a>
        </li>
        <li>
            <a href="https://x.com" rel="noopener noreferrer" target="_blank" aria-label="X">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M14.7 10.4 22 2h-2.2l-6.3 7.2L8.4 2H2l7.7 11.1L2 22h2.2l6.8-7.7L15.6 22H22l-7.3-11.6Zm-2.4 2.7-.8-1.1L5 3.5h2.6l5.1 7.3.8 1.1 6.7 9.6h-2.6l-5.3-7.4Z"/></svg>
            </a>
        </li>
        <li>
            <a href="https://www.instagram.com" rel="noopener noreferrer" target="_blank" aria-label="Instagram">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 7.2A4.8 4.8 0 1 0 16.8 12 4.8 4.8 0 0 0 12 7.2Zm0 7.9A3.1 3.1 0 1 1 15.1 12 3.1 3.1 0 0 1 12 15.1Zm6.1-8.9a1.1 1.1 0 1 1-1.1-1.1 1.1 1.1 0 0 1 1.1 1.1ZM12 4.6c1.6 0 1.8 0 2.5.1a4 4 0 0 1 1.4.3 2.6 2.6 0 0 1 1.5 1.5 4 4 0 0 1 .3 1.4c0 .7.1.9.1 2.5s0 1.8-.1 2.5a4 4 0 0 1-.3 1.4 2.6 2.6 0 0 1-1.5 1.5 4 4 0 0 1-1.4.3c-.7 0-.9.1-2.5.1s-1.8 0-2.5-.1a4 4 0 0 1-1.4-.3 2.6 2.6 0 0 1-1.5-1.5 4 4 0 0 1-.3-1.4c0-.7-.1-.9-.1-2.5s0-1.8.1-2.5a4 4 0 0 1 .3-1.4 2.6 2.6 0 0 1 1.5-1.5 4 4 0 0 1 1.4-.3c.7 0 .9-.1 2.5-.1Zm0-1.8c-1.6 0-1.8 0-2.5.1a5.8 5.8 0 0 0-2 .5 4.4 4.4 0 0 0-2.5 2.5 5.8 5.8 0 0 0-.5 2c-.1.7-.1.9-.1 2.5s0 1.8.1 2.5a5.8 5.8 0 0 0 .5 2 4.4 4.4 0 0 0 2.5 2.5 5.8 5.8 0 0 0 2 .5c.7.1.9.1 2.5.1s1.8 0 2.5-.1a5.8 5.8 0 0 0 2-.5 4.4 4.4 0 0 0 2.5-2.5 5.8 5.8 0 0 0 .5-2c.1-.7.1-.9.1-2.5s0-1.8-.1-2.5a5.8 5.8 0 0 0-.5-2 4.4 4.4 0 0 0-2.5-2.5 5.8 5.8 0 0 0-2-.5c-.7-.1-.9-.1-2.5-.1Z"/></svg>
            </a>
        </li>
        <li>
            <a href="https://www.facebook.com" rel="noopener noreferrer" target="_blank" aria-label="Facebook">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M14.2 8.4V6.7c0-.6.4-.8 1-.8h1.6V3h-2.8C10.8 3 10 5 10 6.9v1.5H8v3h2V21h4.2v-9.6h2.8l.4-3h-3.2Z"/></svg>
            </a>
        </li>
    </ul>
    <p class="nas-copy">© جميع الحقوق محفوظة <?= date('Y') ?></p>
</footer>
