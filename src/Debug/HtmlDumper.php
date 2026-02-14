<?php
namespace Juzdy\Debug;

class HtmlDumper implements DumperInterface
{
    private const MAX_DEPTH = 6;

    private static bool $stylePrinted = false;

    public function dump(mixed $data): string
    {
        if (!self::$stylePrinted) {
            self::$stylePrinted = true;
            $style = HtmlStyle::block();
        } else {
            $style = '';
        }

        $seen = [];
        $body = '<div class="jz-debug">' . $this->renderValue($data, $seen, 0, null) . '</div>';

        return $style . $body;
    }

    private function renderValue(mixed $value, array &$seen, int $depth, ?string $keyHtml): string
    {
        if ($depth > self::MAX_DEPTH) {
            return $this->renderInline($keyHtml, '<span class="jz-type">max-depth</span>');
        }

        if (is_array($value)) {
            return $this->renderArray($value, $seen, $depth, $keyHtml);
        }

        if (is_object($value)) {
            return $this->renderObject($value, $seen, $depth, $keyHtml);
        }

        return $this->renderInline($keyHtml, $this->renderScalar($value));
    }

    private function renderArray(array $value, array &$seen, int $depth, ?string $keyHtml): string
    {
        $count = count($value);
        $summary = $this->renderCompositeSummary('array', '(' . $count . ')', '[', ']');

        if ($count === 0) {
            return $this->renderInline($keyHtml, $summary);
        }

        $details = '<details class="jz-node"><summary>' . $this->summaryLine($keyHtml, $summary) . '</summary><ul>';
        foreach ($value as $key => $item) {
            $details .= '<li>' . $this->renderValue($item, $seen, $depth + 1, $this->renderKey($key)) . '</li>';
        }
        $details .= '</ul></details>';

        return $details;
    }

    private function renderObject(object $value, array &$seen, int $depth, ?string $keyHtml): string
    {
        $id = spl_object_id($value);
        $class = get_class($value);
        $classSafe = HtmlEscaper::escape($class);

        if (isset($seen[$id])) {
            $summary = '<span class="jz-type">object</span><span class="jz-meta">(' . $classSafe . ')</span><span class="jz-recursion">*recursion*</span>';
            return $this->renderInline($keyHtml, $summary);
        }

        $seen[$id] = true;
        $vars = ObjectInspector::toArray($value);
        $count = count($vars);
        $summary = $this->renderCompositeSummary('object', '(' . $classSafe . ')', '{', '}', $count);

        if ($count === 0) {
            return $this->renderInline($keyHtml, $summary);
        }

        $details = '<details class="jz-node"><summary>' . $this->summaryLine($keyHtml, $summary) . '</summary><ul>';
        foreach ($vars as $key => $item) {
            $details .= '<li>' . $this->renderValue($item, $seen, $depth + 1, $this->renderKey($key)) . '</li>';
        }
        $details .= '</ul></details>';

        return $details;
    }

    private function summaryLine(?string $keyHtml, string $summary): string
    {
        if ($keyHtml === null) {
            return '<span class="jz-summary">' . $summary . '</span>';
        }

        return '<span class="jz-summary">' . $keyHtml . '<span class="jz-arrow">=&gt;</span>' . $summary . '</span>';
    }

    private function renderInline(?string $keyHtml, string $valueHtml): string
    {
        if ($keyHtml === null) {
            return '<span class="jz-summary">' . $valueHtml . '</span>';
        }

        return '<span class="jz-summary">' . $keyHtml . '<span class="jz-arrow">=&gt;</span>' . $valueHtml . '</span>';
    }

    private function renderCompositeSummary(string $type, string $meta, string $open, string $close, ?int $count = null): string
    {
        $countHtml = $count === null ? '' : '<span class="jz-meta">{' . $count . '}</span>';

        return '<span class="jz-type">' . $type . '</span><span class="jz-meta">' . $meta . '</span>' . $countHtml .
            '<span class="jz-brace">' . $open . '</span><span class="jz-brace">' . $close . '</span>';
    }

    private function renderScalar(mixed $value): string
    {
        if ($value instanceof UninitializedValue) {
            return '<span class="jz-type">uninitialized</span><span class="jz-null">*uninitialized*</span>';
        }

        if (is_string($value)) {
            $safe = HtmlEscaper::escape($value);
            $len = strlen($value);
            return '<span class="jz-type">string</span><span class="jz-meta">(' . $len . ')</span><span class="jz-string">"' . $safe . '"</span>';
        }

        if (is_int($value)) {
            return '<span class="jz-type">int</span><span class="jz-number">' . $value . '</span>';
        }

        if (is_float($value)) {
            return '<span class="jz-type">float</span><span class="jz-number">' . $value . '</span>';
        }

        if (is_bool($value)) {
            return '<span class="jz-type">bool</span><span class="jz-bool">' . ($value ? 'true' : 'false') . '</span>';
        }

        if ($value === null) {
            return '<span class="jz-type">null</span><span class="jz-null">null</span>';
        }

        if (is_resource($value)) {
            $type = HtmlEscaper::escape(get_resource_type($value));
            return '<span class="jz-type">resource</span><span class="jz-meta">(' . $type . ')</span>';
        }

        return '<span class="jz-type">unknown</span>';
    }

    private function renderKey(int|string $key): string
    {
        if (is_int($key)) {
            return '<span class="jz-key jz-key-num">' . $key . '</span>';
        }

        return '<span class="jz-key jz-key-str">' . HtmlEscaper::escape($key) . '</span>';
    }
}
