<?php
namespace Juzdy\Debug;

class HtmlStyle
{
    public static function block(): string
    {
        return '<style>
.jz-debug{font:13px/1.45 Menlo,Consolas,Monaco,monospace;color:#111827;background:#f5f7fb;border:1px solid #e5e7eb;padding:12px 14px;border-radius:10px}
.jz-debug .jz-node{margin:4px 0}
.jz-debug summary{cursor:pointer;list-style:none;outline:none}
.jz-debug summary::-webkit-details-marker{display:none}
.jz-debug .jz-summary{display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap}
.jz-debug .jz-arrow{color:#6b7280}
.jz-debug .jz-brace{color:#9ca3af}
.jz-debug .jz-node > summary::before{content:"\25B6";color:#6b7280;font-size:11px;margin-right:6px}
.jz-debug .jz-node[open] > summary::before{content:"\25BC"}
.jz-debug ul{margin:6px 0 0 18px;padding:0;border-left:1px dashed #e5e7eb}
.jz-debug li{list-style:none;margin:2px 0;padding-left:8px}
.jz-debug .jz-type{color:#374151;font-weight:600}
.jz-debug .jz-meta{color:#6b7280}
.jz-debug .jz-key{color:#0f172a;margin-right:4px}
.jz-debug .jz-key-str{color:#2563eb}
.jz-debug .jz-key-num{color:#7c3aed}
.jz-debug .jz-string{color:#15803d;word-break:break-word}
.jz-debug .jz-number{color:#d97706}
.jz-debug .jz-bool{color:#dc2626}
.jz-debug .jz-null{color:#6b7280}
.jz-debug .jz-recursion{color:#dc2626;font-weight:600}
</style>';
    }
}
